<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $fillable = [
        'nombre',
        'cedula',
        'telefono',
        'email',
        'score_credito',
    ];

    public function cedulaEnmascarada(): string
    {
        return str_repeat('*', max(0, strlen($this->cedula) - 4)) . substr($this->cedula, -4);
    }
}
