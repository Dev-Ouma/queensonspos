<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosReturnItem extends Model
{
    protected $fillable = ['pos_return_id','pos_sale_item_id','qty'];

    public $timestamps = false;
    public function saleItem() { return $this->belongsTo(PosSaleItem::class, 'pos_sale_item_id'); }
}
