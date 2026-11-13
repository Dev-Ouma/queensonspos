<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    protected $fillable = ['order_id','from_status','to_status','user_id','note'];

    public $timestamps = false;

    protected function casts(): array { return ['created_at' => 'datetime']; }
    protected $table = 'order_status_history';
}
