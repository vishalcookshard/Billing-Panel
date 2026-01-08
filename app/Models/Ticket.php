<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'user_id', 'department_id', 'subject', 'priority', 'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(TicketDepartment::class, 'department_id');
    }

    public function replies()
    {
        return $this->hasMany(TicketReply::class);
    }
}
