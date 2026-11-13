<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    protected $fillable = ['reference','payment_id','amount_cents','reason','status','approved_by'];

    public function payment() { return $this->belongsTo(Payment::class); }
}
