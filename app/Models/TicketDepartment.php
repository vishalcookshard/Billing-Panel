<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketDepartment extends Model
{
    protected $fillable = ['name'];

    public function staff()
    {
        return $this->belongsToMany(User::class, 'department_staff');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'department_id');
    }
}
