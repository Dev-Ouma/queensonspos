<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockReservation extends Model
{
    protected $fillable = ['variant_id','warehouse_id','cart_token','order_id','qty','status','expires_at'];

    protected function casts(): array { return ['expires_at'=>'datetime']; }

    public function variant() { return $this->belongsTo(ProductVariant::class, 'variant_id'); }
}
