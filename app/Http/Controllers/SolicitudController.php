<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Http\Requests\UpdateSolicitudRequest;

class SolicitudController extends Controller
{
    // A.2 — Actualizar una solicitud
    // TODO: Generar un metodo de actualizar mas seguro, en el caso de tener riesgo.

    public function update(UpdateSolicitudRequest $request, Solicitud $solicitud)
    {
        $solicitud->update($request->validated());
        return back()->with('ok', 'Datos actualizados');
    }
}
