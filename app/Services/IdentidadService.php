<?php

namespace App\Services;

use App\Models\Cliente;
use Illuminate\Support\Facades\Log;

class IdentidadService
{
    // A.5 — Registro (logging) de una validación de identidad
    public function logger(Cliente $cliente, string $codigoOtp, string $scoreBureau): void
    {
        // No se registran OTP ni score: son datos sensibles e innecesarios
        // para trazar que la validación ocurrió. El OTP, aunque temporal,
        // sigue siendo un secreto de un solo uso y no conviene dejarlo en logs.
        Log::info('Validando identidad', [
            'cliente_id' => $cliente->id,
            'cedula'     => $cliente->cedulaEnmascarada(),
        ]);
    }
}
