<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    protected $fillable = ['customer_id','label','line1','line2','city','phone','is_default'];

    protected function casts(): array { return ['is_default'=>'boolean']; }

    public function customer() { return $this->belongsTo(Customer::class); }
}
