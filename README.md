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
