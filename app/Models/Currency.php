<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'rate_to_usd', 'active'];

    protected $casts = [
        'rate_to_usd' => 'decimal:12',
        'active' => 'boolean',
    ];
}
