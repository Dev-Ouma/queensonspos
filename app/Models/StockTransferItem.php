<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    protected $fillable = ['stock_transfer_id','variant_id','qty','received_qty'];

    public $timestamps = false;
    public function variant() { return $this->belongsTo(ProductVariant::class, 'variant_id'); }
}
