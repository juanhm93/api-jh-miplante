<?php

namespace App\Http\Controllers;


use App\Http\Requests\RecibirFirmaWebhookRequest;
use App\Models\Pagare;

class FirmaController extends Controller
{
    // A.4 — Recepción de un webhook de firma digital
    // TODO: hacer el webhook mas seguro.
    // El request valida el Bearer token del proveedor (secret compartido) y el pagare_id.
    // Sin ese token cualquiera podría marcar un pagaré como firmado.
    public function recibir(RecibirFirmaWebhookRequest $request)
    {
        $pagare = Pagare::find($request->validated('pagare_id'));
        if (! $pagare) {
            return response()->json(['message' => 'Pagare not found'], 404);
        }

        $pagare->estado = 'FIRMADO';
        $pagare->save();

        return response()->json(['ok' => true]);
    }
}
