<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = ['purchase_order_id','variant_id','qty_ordered','qty_received','unit_cost_cents'];

    public $timestamps = false;
    public function variant() { return $this->belongsTo(ProductVariant::class, 'variant_id'); }
}
