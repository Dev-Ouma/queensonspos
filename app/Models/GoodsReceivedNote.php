<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoodsReceivedNote extends Model
{
    protected $fillable = ['reference','purchase_order_id','supplier_delivery_ref','received_by'];

    public function purchaseOrder() { return $this->belongsTo(PurchaseOrder::class); }
    public function items() { return $this->hasMany(GrnItem::class); }
}
