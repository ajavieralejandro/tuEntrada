<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entrada extends Model
{
    protected $fillable = [
        'evento',
        'codigo_qr',
        'nombre',
        'dni',
        'fecha',
        'valido',
        'usada',
        'qr_path'
    ];
}
