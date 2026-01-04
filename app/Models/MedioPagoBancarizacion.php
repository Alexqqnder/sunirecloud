<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedioPagoBancarizacion extends Model
{
    use HasFactory;

    protected $table = 'medios_pago_bancarizacion';

    protected $fillable = [
        'codigo',
        'descripcion',
        'requiere_numero_operacion',
        'requiere_banco',
        'requiere_fecha',
        'activo',
        'observaciones',
    ];

    protected $casts = [
        'requiere_numero_operacion' => 'boolean',
        'requiere_banco' => 'boolean',
        'requiere_fecha' => 'boolean',
        'activo' => 'boolean',
    ];

    /**
     * Scope para obtener solo medios de pago activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para buscar por código
     */
    public function scopeByCodigo($query, string $codigo)
    {
        return $query->where('codigo', $codigo);
    }
}
