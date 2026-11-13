<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosCashMovement extends Model
{
    protected $fillable = ['pos_session_id','type','amount_cents','reason','user_id'];

    public $timestamps = false;

    protected function casts(): array { return ['created_at' => 'datetime']; }
}
