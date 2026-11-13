<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyTransaction extends Model
{
    protected $fillable = ['customer_id','points','reason','subject_type','subject_id'];

    public $timestamps = false;

    protected function casts(): array { return ['created_at' => 'datetime']; }
}
