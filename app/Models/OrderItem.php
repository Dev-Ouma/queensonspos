<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = ['order_id','variant_id','name_snapshot','sku_snapshot','qty','unit_price_cents','cost_cents_snapshot','line_total_cents'];

    public $timestamps = false;
    public function order() { return $this->belongsTo(Order::class); }
    public function variant() { return $this->belongsTo(ProductVariant::class, 'variant_id'); }
}
