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
