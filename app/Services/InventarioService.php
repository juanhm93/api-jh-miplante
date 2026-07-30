<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Producto;

class InventarioService
{
    // A.3 — Descuento de inventario al comprar

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
}
