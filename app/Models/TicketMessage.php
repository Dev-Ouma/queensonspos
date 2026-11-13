<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketMessage extends Model
{
    protected $fillable = ['support_ticket_id','sender_type','sender_id','body'];

    public $timestamps = false;

    protected function casts(): array { return ['created_at' => 'datetime']; }
}
