<?php
// app/Models/MonthlyBillingConfig.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyBillingConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'month',
        'full_label',
        'rent_enabled',
        'mess_enabled',
        
        'cooler_enabled',
        'default_mess_amount',
        'default_cooler_amount',
        'custom_charges',
        'generation_date',
        'due_date',
        'late_fee_amount',
        'late_fee_per_day',
        'late_fee_enabled',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'rent_enabled' => 'boolean',
        'mess_enabled' => 'boolean',
        'cooler_enabled' => 'boolean',
        'late_fee_enabled' => 'boolean',
        'late_fee_amount' => 'decimal:2',
        'late_fee_per_day' => 'decimal:2',
        'custom_charges' => 'array',
        'generation_date' => 'date:Y-m-d',
        'due_date' => 'date:Y-m-d',
        'default_mess_amount' => 'decimal:2',
        'default_cooler_amount' => 'decimal:2',
    ];

    public function invoices()
    {
        return $this->hasMany(FeeInvoice::class, 'monthly_config_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getMonthNameAttribute(): string
    {
        return date('F', mktime(0, 0, 0, $this->month, 1));
    }

    public function getFullLabelAttribute(): string
    {
        return "{$this->month_name} {$this->year}";
    }
}