<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosSession extends Model
{
    protected $fillable = ['register_id','user_id','opening_cash_cents','closing_cash_cents','expected_cash_cents','variance_cents','status','opened_at','closed_at'];

    protected function casts(): array { return ['opened_at'=>'datetime','closed_at'=>'datetime']; }

    public function register() { return $this->belongsTo(Register::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function sales() { return $this->hasMany(PosSale::class); }
    public function cashMovements() { return $this->hasMany(PosCashMovement::class); }
}
