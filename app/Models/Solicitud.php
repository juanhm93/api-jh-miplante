<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    // A.2 — Campos fillable de la solicitud (el update usa solo validated()).

    protected $fillable = [
        'nombre',
        'telefono',
        'direccion',
        'cupo_asignado',
        'estado',
        'rol_usuario',
        'user_id',
    ];
}
