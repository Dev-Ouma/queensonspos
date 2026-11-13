<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Register extends Model
{
    protected $fillable = ['branch_id','name','active'];

    protected function casts(): array { return ['active'=>'boolean']; }

    public function branch() { return $this->belongsTo(Branch::class); }
}
