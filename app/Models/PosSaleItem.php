<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosSaleItem extends Model
{
    protected $fillable = ['pos_sale_id','variant_id','name_snapshot','sku_snapshot','qty','unit_price_cents','cost_cents_snapshot','line_total_cents'];

    public $timestamps = false;
    public function sale() { return $this->belongsTo(PosSale::class, 'pos_sale_id'); }
    public function variant() { return $this->belongsTo(ProductVariant::class, 'variant_id'); }
}
