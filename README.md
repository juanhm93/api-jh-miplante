# API JH — MiPlante

Soluciones y análisis de las tareas del challenge.

## Configuración del proyecto

- **PHP:** 8.2+ (este proyecto se desarrolló con PHP **8.3**). Laravel 12 requiere `^8.2`.
- **Dependencias e instalación:**

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
```

Variables de entorno relevantes (ver [`.env.example`](.env.example)):

```env
# Secret compartido para autenticar el webhook de firma digital (A.4).
FIRMA_WEBHOOK_SECRET=

# Tasas contables (config del software; no vienen en el payload del API).
CREDITO_TASA_FIANZA=0.05
CREDITO_TASA_IVA=0.19

# Usuarios iniciales del seed (login desde el principio).
# Los correos se definen en .env / config/seed.php, no se documentan aquí.
SEED_USER_EMAIL_LAURA=
SEED_USER_EMAIL_GERENCIA=
SEED_USER_PASSWORD=
```

Tras el seed, los usuarios de [`config/seed.php`](config/seed.php) quedan listos para autenticarse vía `POST /api/login`.

## Encuentra y corrige

### A.1 — Cálculo del total de una compra

**Archivo:** [`app/Services/CheckoutService.php`](app/Services/CheckoutService.php)

**Problema:** El método `calcularTotal` calculaba mal el IVA de la fianza.

La línea incorrecta era:

```php
$ivaFianza = ($subtotal * $tasaFianza) * 0.19;
```

Eso volvía a multiplicar la tasa de fianza por el subtotal. Pero la fianza **ya se había sumado** al subtotal dentro del `foreach` (precio × cantidad × tasa de fianza). Aplicar otra vez `$tasaFianza` sobre ese subtotal cobraba de más.

**Corrección:** aplicar solo el IVA (19 %) sobre el subtotal, que ya incluye la fianza:

```php
$ivaFianza = $subtotal * 0.19;
```

### A.2 — Actualizar una solicitud

**Archivos:**

- [`app/Http/Controllers/SolicitudController.php`](app/Http/Controllers/SolicitudController.php)
- [`app/Http/Requests/UpdateSolicitudRequest.php`](app/Http/Requests/UpdateSolicitudRequest.php)
- [`app/Policies/SolicitudPolicy.php`](app/Policies/SolicitudPolicy.php)
- [`app/Models/Solicitud.php`](app/Models/Solicitud.php)

**Problema:** El método `update` no era seguro: actualizaba la solicitud sin validar los datos de entrada ni autorizar al usuario. En un escenario con riesgo, cualquiera podría alterar campos sensibles (`cupo_asignado`, `estado`, `rol_usuario`, etc.).

**Corrección:** se implementó un `FormRequest` personalizado (`UpdateSolicitudRequest`) para validar los datos de la solicitud antes de actualizarla. Dentro del request se usa una política de autorización (`SolicitudPolicy`) para verificar si el usuario tiene permisos para actualizar la solicitud. La única regla de autorización es que el usuario debe ser el mismo que creó la solicitud (`$user->id === $solicitud->user_id`). El controlador solo actualiza con `$request->validated()`.

```php
public function update(UpdateSolicitudRequest $request, Solicitud $solicitud)
{
    $solicitud->update($request->validated());
    return back()->with('ok', 'Datos actualizados');
}
```

### A.3 — Descuento de inventario al comprar

**Archivo:** [`app/Services/InventarioService.php`](app/Services/InventarioService.php)

**Problema:** Al comprar había que garantizar que el producto exista, que tenga stock suficiente y que el inventario se descuente bien. El riesgo principal era la concurrencia: dos compras a la vez podían leer el mismo stock y sobrevender.

**Corrección:** Revisando la [documentación de queries de Laravel](https://laravel.com/docs/12.x/queries#pessimistic-locking), la opción que mejor encajaba fue el _pessimistic locking_ con `lockForUpdate()` dentro de una transacción: mientras una compra descuenta stock, la otra espera. Con `firstOrFail()` me aseguro de que el producto exista; si no hay stock, lanzo una excepción.

```php
public function comprar(int $productoId, int $cantidad): void
{
    DB::transaction(function () use ($productoId, $cantidad) {
        $producto = Producto::where('id', $productoId)->lockForUpdate()->firstOrFail();

        if ($producto->stock >= $cantidad) {
            $producto->stock = $producto->stock - $cantidad;
            $producto->save();
        } else {
            throw new \Exception('Sin stock');
        }
    });
}
```

### A.4 — Recepción de un webhook de firma digital

**Archivos:**

- [`app/Http/Controllers/FirmaController.php`](app/Http/Controllers/FirmaController.php)
- [`app/Http/Requests/RecibirFirmaWebhookRequest.php`](app/Http/Requests/RecibirFirmaWebhookRequest.php)
- [`app/Models/Pagare.php`](app/Models/Pagare.php)

**Problema:** El endpoint que recibe el webhook del proveedor de firma digital marcaba un pagaré como `FIRMADO` sin autenticar la petición. Cualquiera podría llamar al endpoint y firmar pagarés ajenos.

**Corrección:** se implementó un `FormRequest` (`RecibirFirmaWebhookRequest`) que autoriza con un secret compartido (`FIRMA_WEBHOOK_SECRET`) enviado como Bearer token, y valida que `pagare_id` exista. La comparación usa `hash_equals` para evitar timing attacks. El controlador solo actúa si esa autorización y validación pasan.

También me parecieron atractivas otras prácticas (firma HMAC del payload, allowlist de IPs, protección ante replay con timestamp/nonce), pero para este caso hipotético preferí el token generado que se puede proveer al cliente/proveedor: es simple de integrar y deja claro el contrato de autenticación.

```php
public function authorize(): bool
{
    $token = $this->bearerToken();
    $secret = (string) env('FIRMA_WEBHOOK_SECRET');

    return $token !== null
        && $secret !== ''
        && hash_equals($secret, $token);
}
```

Variable de entorno (caso hipotético; ver [`.env.example`](.env.example)):

```env
FIRMA_WEBHOOK_SECRET=
```

### A.5 — Registro (logging) de una validación de identidad

**Archivos:**

- [`app/Services/IdentidadService.php`](app/Services/IdentidadService.php)
- [`app/Models/Cliente.php`](app/Models/Cliente.php)

**Problema:** El logger de validación de identidad registraba datos sensibles (cédula completa, OTP y score crediticio). En un log eso es un riesgo de privacidad innecesario: para trazar que la validación ocurrió basta con identificar al cliente, no exponer secretos ni datos de buró.

**Corrección:** se dejó de loguear el OTP y el score. La cédula se enmascara (solo últimos 4 dígitos) con un método en el modelo (`cedulaEnmascarada()`), para reutilizarlo donde haga falta. El OTP, aunque sea temporal / de un solo uso, sigue siendo un secreto: si alguien lee los logs mientras el código sigue vigente, podría usarlo. Por eso también se omitió.

```php
public function logger(Cliente $cliente, string $codigoOtp, string $scoreBureau): void
{
    Log::info('Validando identidad', [
        'cliente_id' => $cliente->id,
        'cedula'     => $cliente->cedulaEnmascarada(),
    ]);
}
```

```php
public function cedulaEnmascarada(): string
{
    return str_repeat('*', max(0, strlen($this->cedula) - 4)) . substr($this->cedula, -4);
}
```

## Construyendo algo pequeño (end-to-end)

Flujo mínimo de API: autenticación con Sanctum y un endpoint que resume las cuotas de un crédito.

**Archivos:**

- [`routes/api.php`](routes/api.php)
- [`app/Http/Controllers/Api/LoginController.php`](app/Http/Controllers/Api/LoginController.php)
- [`app/Http/Controllers/Api/CreditoController.php`](app/Http/Controllers/Api/CreditoController.php)
- [`app/Http/Requests/ResumenCreditoRequest.php`](app/Http/Requests/ResumenCreditoRequest.php)
- [`app/Services/CheckoutService.php`](app/Services/CheckoutService.php)
- [`config/credito.php`](config/credito.php)
- [`config/seed.php`](config/seed.php)
- [`database/seeders/DatabaseSeeder.php`](database/seeders/DatabaseSeeder.php)
- [`tests/Feature/CreditoResumenTest.php`](tests/Feature/CreditoResumenTest.php)

### Autenticación — `POST /api/login`

El `LoginController` valida `email`, `password` y `device_name`, verifica credenciales y responde con el usuario y un token de Sanctum (`createToken`). El modelo `User` usa el trait `HasApiTokens`.

```php
return response()->json([
    'data' => [
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ],
        'token' => $user->createToken($request->device_name)->plainTextToken,
    ],
], 200);
```

### Resumen de crédito — `POST /api/creditos/resumen`

Protegido con `auth:sanctum`. Recibe una lista de cuotas (`monto` + `fecha` en `Y-m-d`), validada por `ResumenCreditoRequest`, y delega el cálculo a `CheckoutService::resumenCuotas`: total a pagar, número de cuotas y fecha de la última.

```json
{
    "cuotas": [
        { "monto": 150000, "fecha": "2026-08-15" },
        { "monto": 150000, "fecha": "2026-09-15" },
        { "monto": 150000, "fecha": "2026-10-15" }
    ]
}
```

Respuesta:

```json
{
    "total_a_pagar": 450000,
    "numero_cuotas": 3,
    "fecha_ultima_cuota": "2026-10-15"
}
```

Las tasas de fianza e IVA viven en `config/credito.php` (variables `CREDITO_TASA_*`); no viajan en el payload. El resumen de cuotas solo suma montos: no aplica esas tasas hasta tener más contexto contable.

### Seed de usuarios

`DatabaseSeeder` crea los usuarios definidos en `config/seed.php` (correos y clave vía `SEED_USER_*` en `.env`). Así se puede hacer login desde el primer arranque sin crear cuentas a mano.

### Pruebas

[`tests/Feature/CreditoResumenTest.php`](tests/Feature/CreditoResumenTest.php) cubre el resumen correcto (total, cantidad y fecha última) y el rechazo de payload inválido (422).

## Preguntas cortas

### 4. Te asignan un módulo en producción con un bug de cálculo que cobra de más a algunos clientes. El código es antiguo y no tiene pruebas. ¿Cuáles son tus primeros pasos para diagnosticar y corregir sin romper nada más?

Primero acotaría el impacto: qué clientes, desde cuándo y por cuánto se está cobrando de más. Con eso tengo un universo claro y puedo armar casos de prueba reales.

Luego reproduciría el cálculo fuera de producción (local o un ambiente de prueba) con esos mismos datos, haciendo una “corrida en frío”: monto esperado vs monto que está cobrando el sistema. Si hace falta, agregaría logs temporales en los puntos donde se arma el total, solo para ver en qué paso se desvía.

Como el código es viejo y no tiene pruebas, antes de tocar la lógica escribiría pruebas mínimas con los casos que ya identifiqué (el incorrecto y al menos uno correcto). Así la corrección queda cubierta y bajo el riesgo de romper otros cobros.

Solo después corregiría el cálculo, validaría con esos casos y desplegaría con cuidado, dejando documentado el alcance del error por si hay que ajustar cobros a los clientes afectados.

---

### 5. En pocas líneas: ¿cuál es la diferencia entre autenticación y autorización, y por qué importa en un sistema que maneja cupos de crédito?

La autenticación responde _quién eres_ (por ejemplo, iniciar sesión y tener una sesión o token válido). La autorización responde _qué puedes hacer_ una vez autenticado.

En un sistema de cupos de crédito eso es crítico: un usuario autenticado no debería poder aprobar cupos, ver solicitudes ajenas o modificar límites solo por estar logueado. Hay acciones propias del solicitante y otras reservadas a roles como administrador. En Laravel eso se suele manejar con policies, gates o middleware de permisos.

---

### 6. Si tuvieras que integrarte con un servicio externo por SOAP/XML que a veces responde lento o falla, ¿qué precauciones tomarías para que eso no tumbe tu aplicación?

No haría que la petición del usuario dependa directamente de ese servicio. Lo movería a una cola con un job en Laravel: la app responde rápido y el proceso lento corre aparte.

Además pondría timeouts claros, reintentos controlados (sin saturar el servicio) y manejo de fallos: si SOAP no responde, la app no se cae; registra el error y puede reintentar o marcar el caso para revisión.

Un cron podría ayudar a reprocesar pendientes, pero no como única estrategia: si la llamada es lenta o falla, el job en cola da más control y menos riesgo de tumbar la experiencia del usuario.
