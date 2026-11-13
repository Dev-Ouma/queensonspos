<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    protected $fillable = ['order_id','courier','tracking_no','status','failure_reason','dispatched_at','delivered_at'];

    protected function casts(): array { return ['dispatched_at'=>'datetime','delivered_at'=>'datetime']; }

    public function order() { return $this->belongsTo(Order::class); }
}
