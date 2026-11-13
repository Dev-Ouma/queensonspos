<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = ['name','contact_person','phone','email','address','payment_terms','active'];

    protected function casts(): array { return ['active'=>'boolean']; }

    public function purchaseOrders() { return $this->hasMany(PurchaseOrder::class); }
}
