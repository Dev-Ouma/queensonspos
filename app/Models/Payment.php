<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['reference','subject_type','subject_id','amount_cents','currency','method','provider','provider_txn_id','status','meta','verified_at'];

    protected function casts(): array { return ['meta'=>'array','verified_at'=>'datetime']; }

    public function subject() { return $this->morphTo(); }
    public function refunds() { return $this->hasMany(Refund::class); }
}
