<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrnItem extends Model
{
    protected $fillable = ['goods_received_note_id','purchase_order_item_id','qty_received','qty_damaged'];

    public $timestamps = false;
}
