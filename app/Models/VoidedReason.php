<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoidedReason extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'ejemplo',
        'categoria',
        'requiere_justificacion',
        'activo',
        'orden',
    ];

    protected $casts = [
        'requiere_justificacion' => 'boolean',
        'activo' => 'boolean',
        'orden' => 'integer',
    ];

    /**
     * Scope para obtener solo motivos activos
     */
    public function scopeActive($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para obtener motivos por categoría
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('categoria', $category);
    }

    /**
     * Scope para ordenar por el campo orden
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('orden', 'asc')->orderBy('nombre', 'asc');
    }
}
