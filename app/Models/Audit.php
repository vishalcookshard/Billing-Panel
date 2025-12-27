<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    use HasFactory;

    protected $fillable = ['invoice_id', 'user_id', 'event', 'action', 'meta'];

    protected $casts = [
        'meta' => 'array',
    ];

    // Ensure sensitive fields are not accidentally exposed
    protected $hidden = ['meta'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public static function log($invoiceId, $event, array $meta = [])
    {
        return static::create(['invoice_id' => $invoiceId, 'event' => $event, 'meta' => $meta]);
    }
}
