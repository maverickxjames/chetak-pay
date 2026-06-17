<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    // Custom order ID is a string (e.g. ORD123456)
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'plan_id',
        'amount',
        'status',
        'payment_method',
        'payment_txid',
        'commission_earned',
        'completed_at',
        'upi',
        'byteTransactionId',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'commission_earned' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
