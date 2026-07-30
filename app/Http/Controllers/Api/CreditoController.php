<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResumenCreditoRequest;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;

class CreditoController extends Controller
{
    public function __construct(
        private CheckoutService $checkoutService,
    ) {}

    /**
     * Resume las cuotas de un crédito: total a pagar, cantidad y fecha de la última cuota.
     */
    public function resumen(ResumenCreditoRequest $request): JsonResponse
    {
        $resumen = $this->checkoutService->resumenCuotas(
            $request->validated('cuotas')
        );

        return response()->json($resumen);
    }
}
