<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotaVenta extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'nota_ventas';

    protected $fillable = [
        'company_id',
        'branch_id',
        'client_id',
        'tipo_documento',
        'serie',
        'correlativo',
        'numero_completo',
        'fecha_emision',
        'ubl_version',
        'moneda',
        'tipo_operacion',
        'valor_venta',
        'mto_oper_gravadas',
        'mto_oper_exoneradas',
        'mto_oper_inafectas',
        'mto_igv',
        'mto_isc',
        'total_impuestos',
        'mto_imp_venta',
        'mto_descuentos',
        'mto_cargos',
        'detalles',
        'leyendas',
        'datos_adicionales',
        'pdf_path',
        'codigo_hash',
        'usuario_creacion',
        'observaciones',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'detalles' => 'array',
        'leyendas' => 'array',
        'datos_adicionales' => 'array',
        'valor_venta' => 'decimal:2',
        'mto_oper_gravadas' => 'decimal:2',
        'mto_oper_exoneradas' => 'decimal:2',
        'mto_oper_inafectas' => 'decimal:2',
        'mto_igv' => 'decimal:2',
        'mto_isc' => 'decimal:2',
        'total_impuestos' => 'decimal:2',
        'mto_imp_venta' => 'decimal:2',
        'mto_descuentos' => 'decimal:2',
        'mto_cargos' => 'decimal:2',
    ];

    // ============================================
    // RELACIONES
    // ============================================

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    // ============================================
    // ACCESSORS
    // ============================================

    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->mto_imp_venta, 2, '.', ',');
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->fecha_emision->format('d/m/Y');
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('fecha_emision', [$startDate, $endDate]);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('fecha_emision', 'desc')
                     ->orderBy('created_at', 'desc');
    }
}
