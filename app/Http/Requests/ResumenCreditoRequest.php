<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ResumenCreditoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cuotas' => ['required', 'array', 'min:1'],
            'cuotas.*.monto' => ['required', 'numeric', 'min:0'],
            'cuotas.*.fecha' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
