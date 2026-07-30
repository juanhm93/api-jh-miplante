<?php

namespace App\Services;

class CheckoutService
{
    // A.1 — Cálculo del total de una compra
    // TODO: Encontrar el error en el metodo calcularTotal y corregirlo.

    public function calcularTotal(array $items, float $tasaFianza): float
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['precio'] * $item['cantidad'];
            $fianza   = ($item['precio'] * $item['cantidad']) * $tasaFianza;
            $subtotal += $fianza;
        }
        $ivaFianza = $subtotal * 0.19;
        return $subtotal + $ivaFianza;
    }
}
