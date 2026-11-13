<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosReturn extends Model
{
    protected $fillable = ['reference','pos_sale_id','pos_session_id','refund_cents','reason','condition','user_id'];

    public function sale() { return $this->belongsTo(PosSale::class, 'pos_sale_id'); }
    public function items() { return $this->hasMany(PosReturnItem::class); }
}
