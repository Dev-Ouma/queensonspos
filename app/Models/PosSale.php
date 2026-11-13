<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosSale extends Model
{
    protected $fillable = ['reference','pos_session_id','customer_id','subtotal_cents','discount_cents','total_cents','status'];

    public function session() { return $this->belongsTo(PosSession::class, 'pos_session_id'); }
    public function items() { return $this->hasMany(PosSaleItem::class); }
    public function payments() { return $this->morphMany(Payment::class, 'subject'); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function returns() { return $this->hasMany(PosReturn::class); }
}
