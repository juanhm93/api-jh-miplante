<?php

namespace Tests\Feature;

use Tests\TestCase;

class CreditoResumenTest extends TestCase
{
    public function test_resumen_de_cuotas_devuelve_total_cantidad_y_fecha_ultima(): void
    {
        $payload = [
            'cuotas' => [
                ['monto' => 150000, 'fecha' => '2026-08-15'],
                ['monto' => 150000, 'fecha' => '2026-10-15'],
                ['monto' => 150000, 'fecha' => '2026-09-15'],
            ],
        ];

        $response = $this->postJson('/api/creditos/resumen', $payload);

        $response->assertOk()
            ->assertExactJson([
                'total_a_pagar' => 450000,
                'numero_cuotas' => 3,
                'fecha_ultima_cuota' => '2026-10-15',
            ]);
    }

    public function test_resumen_rechaza_payload_invalido(): void
    {
        $response = $this->postJson('/api/creditos/resumen', [
            'cuotas' => [
                ['monto' => -1, 'fecha' => 'no-es-fecha'],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['cuotas.0.monto', 'cuotas.0.fecha']);
    }
}
