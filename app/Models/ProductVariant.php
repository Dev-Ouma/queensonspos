<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = ['product_id','sku','barcode','size','color','price_cents','compare_at_cents','cost_cents','active'];

    protected function casts(): array { return ['active'=>'boolean']; }

    public function product() { return $this->belongsTo(Product::class); }
    public function balances() { return $this->hasMany(InventoryBalance::class, 'variant_id'); }
    public function availableQty(): int { return $this->balances->sum(fn ($b) => $b->on_hand - $b->reserved - $b->damaged); }
}
