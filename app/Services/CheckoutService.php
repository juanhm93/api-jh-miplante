<?php

namespace App\Services;

class CheckoutService
{
    // A.1 — Cálculo del total de una compra
    // TODO: Encontrar el error en el metodo calcularTotal y corregirlo.
    // Las tasas (fianza / IVA) viven en config/credito.php, no en el request.

    public function calcularTotal(array $items, ?float $tasaFianza = null): float
    {
        $tasaFianza ??= (float) config('credito.tasa_fianza');
        $tasaIva = (float) config('credito.tasa_iva');

        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['precio'] * $item['cantidad'];
            $fianza   = ($item['precio'] * $item['cantidad']) * $tasaFianza;
            $subtotal += $fianza;
        }
        $ivaFianza = $subtotal * $tasaIva;
        return $subtotal + $ivaFianza;
    }

    /**
     * Resume una lista de cuotas de un crédito.
     *
     * @param  array<int, array{monto: float|int|string, fecha: string}>  $cuotas
     * @return array{total_a_pagar: float, numero_cuotas: int, fecha_ultima_cuota: string}
     */
    public function resumenCuotas(array $cuotas): array
    {
        $total = 0.0;
        $fechaUltima = null;

        foreach ($cuotas as $cuota) {
            $total += (float) $cuota['monto'];

            if ($fechaUltima === null || $cuota['fecha'] > $fechaUltima) {
                $fechaUltima = $cuota['fecha'];
            }
        }

        return [
            'total_a_pagar' => round($total, 2),
            'numero_cuotas' => count($cuotas),
            'fecha_ultima_cuota' => $fechaUltima,
        ];
    }
}
