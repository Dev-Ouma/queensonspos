<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryBalance extends Model
{
    protected $fillable = ['variant_id','warehouse_id','on_hand','reserved','damaged'];

    public function variant() { return $this->belongsTo(ProductVariant::class, 'variant_id'); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function getAvailableAttribute(): int { return $this->on_hand - $this->reserved - $this->damaged; }
}
