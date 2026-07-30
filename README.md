# API JH — MiPlante

Soluciones y análisis de las tareas del challenge.

## Configuración del proyecto

- **PHP:** 8.2+ (este proyecto se desarrolló con PHP **8.3**). Laravel 12 requiere `^8.2`.
- **Dependencias:**

```bash
composer install
```

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

**Corrección:** Revisando la [documentación de queries de Laravel](https://laravel.com/docs/12.x/queries#pessimistic-locking), la opción que mejor encajaba fue el *pessimistic locking* con `lockForUpdate()` dentro de una transacción: mientras una compra descuenta stock, la otra espera. Con `firstOrFail()` me aseguro de que el producto exista; si no hay stock, lanzo una excepción.

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
