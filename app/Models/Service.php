<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'order_id', 'plan_id', 'name', 'module', 'external_id', 'username', 'password',
        'status', 'config', 'metadata', 'monthly_price', 'suspended_at', 'terminated_at',
        'next_due_date', 'cancellation_reason', 'cancelled_by'
    ];

    protected $casts = [
        'config' => 'array',
        'metadata' => 'array',
        'suspended_at' => 'datetime',
        'terminated_at' => 'datetime',
        'next_due_date' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function addons()
    {
        return $this->hasMany(ServiceAddon::class);
    }
}
