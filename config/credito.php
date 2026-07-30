<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tasas contables del crédito / checkout
    |--------------------------------------------------------------------------
    |
    | Estas tasas pertenecen a la configuración del software, no al payload
    | del cliente. Se usan en cálculos de compra (fianza / IVA). El resumen
    | de cuotas solo suma montos; no aplica tasas hasta tener más contexto
    | contable.
    |
    */

    'tasa_fianza' => (float) env('CREDITO_TASA_FIANZA', 0.05),

    'tasa_iva' => (float) env('CREDITO_TASA_IVA', 0.19),

];
