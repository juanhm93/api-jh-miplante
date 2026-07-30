<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Solicitud;

class SolicitudPolicy
{
    // A.2 — Autorización: solo el usuario que creó la solicitud puede actualizarla.

    /**
     * Create a new policy instance.
     */
    public function __construct() {}

    public function update(User $user, Solicitud $solicitud)
    {
        return $user->id === $solicitud->user_id;
    }
}
