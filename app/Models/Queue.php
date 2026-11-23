<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    use HasFactory;

    protected $fillable = [
        'token_number',
        'status',
        'priority',
        'consultation_note',
        'arrived_at',
        'served_at',
        'completed_at',
    ];

    protected $casts = [
        'arrived_at' => 'datetime',
        'served_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}