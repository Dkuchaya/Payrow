<?php
 
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'type',
        'calculation_type',
        'default_amount',

        // Existing tax configuration
        'is_taxable',

        // Statutory classifications
        'is_paye_taxable',
        'is_napsa_pensionable',
        'is_nhima_liable',
        'is_sdl_liable',

        'is_recurring',
        'is_active',
        'description',
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',

        'is_taxable' => 'boolean',
        'is_paye_taxable' => 'boolean',
        'is_napsa_pensionable' => 'boolean',
        'is_nhima_liable' => 'boolean',
        'is_sdl_liable' => 'boolean',

        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
