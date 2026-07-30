<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Usuarios iniciales (seed)
    |--------------------------------------------------------------------------
    |
    | Correos y clave por defecto para poder iniciar sesión tras el seed.
    | Se pueden sobreescribir con variables de entorno.
    |
    */

    'password' => env('SEED_USER_PASSWORD', '12345678'),

    'users' => [
        [
            'name' => 'Laura Miplante',
            'email' => env('SEED_USER_EMAIL_LAURA', 'lauramiplante@gmail.com'),
        ],
        [
            'name' => 'Gerencia Miplante',
            'email' => env('SEED_USER_EMAIL_GERENCIA', 'gerencia@miplante.com'),
        ],
    ],

];
