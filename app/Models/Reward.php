<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    protected $fillable = [
        'title',
        'description',
        'amount',
        'category',
        'required_milestone',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'required_milestone' => 'integer',
    ];
}
