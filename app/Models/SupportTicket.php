<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    protected $fillable = ['reference','customer_id','subject','status','assigned_to'];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function messages() { return $this->hasMany(TicketMessage::class)->orderBy('created_at'); }
}
