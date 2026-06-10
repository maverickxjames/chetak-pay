<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'amount',
        'daily_commission',
        'duration_days',
        'category',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'daily_commission' => 'decimal:2',
        'duration_days' => 'integer',
    ];
}
