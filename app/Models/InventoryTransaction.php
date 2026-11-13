<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    protected $fillable = ['reference','variant_id','warehouse_id','qty_delta','movement_type','subject_type','subject_id','user_id','reason'];

    public $timestamps = false;

    protected function casts(): array { return ['created_at' => 'datetime']; }
    public function variant() { return $this->belongsTo(ProductVariant::class, 'variant_id'); }
    public function warehouse() { return $this->belongsTo(Warehouse::class); }
    public function user() { return $this->belongsTo(User::class); }
}
