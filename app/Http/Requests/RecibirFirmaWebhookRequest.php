<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RecibirFirmaWebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $token = $this->bearerToken();
        $secret = (string) env('FIRMA_WEBHOOK_SECRET');

        return $token !== null
            && $secret !== ''
            && hash_equals($secret, $token);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pagare_id' => 'required|exists:pagares,id',
        ];
    }
}
