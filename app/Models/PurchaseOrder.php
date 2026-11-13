<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = ['reference','supplier_id','warehouse_id','status','total_cents','created_by','approved_by'];

    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function items() { return $this->hasMany(PurchaseOrderItem::class); }
    public function grns() { return $this->hasMany(GoodsReceivedNote::class); }
}
