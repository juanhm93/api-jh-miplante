<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSolicitudRequest extends FormRequest
{
    // A.2 — Validación y autorización antes de actualizar la solicitud.
    // authorize(): delega en SolicitudPolicy (solo el dueño puede actualizar).
    // rules(): valida los campos permitidos; el controlador usa solo validated().

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->solicitud);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255',
            'telefono' => 'required|string|max:255',
            'direccion' => 'required|string|max:255',
            'cupo_asignado' => 'required|numeric',
            'estado' => 'required|string|max:255',
            'rol_usuario' => 'required|string|max:255',
        ];
    }
}
