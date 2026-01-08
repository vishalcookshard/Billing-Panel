<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketAttachment extends Model
{
    protected $fillable = [
        'reply_id', 'filename', 'path'
    ];

    public function reply()
    {
        return $this->belongsTo(TicketReply::class, 'reply_id');
    }
}
