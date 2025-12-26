<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    use HasFactory;

    protected $fillable = ['invoice_id', 'event', 'meta'];

    protected $casts = [
        'meta' => 'array',
    ];

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public static function log($invoiceId, $event, array $meta = [])
    {
        return static::create(['invoice_id' => $invoiceId, 'event' => $event, 'meta' => $meta]);
    }
}
