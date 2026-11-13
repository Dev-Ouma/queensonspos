<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $fillable = ['branch_id','name','code','active'];

    protected function casts(): array { return ['active'=>'boolean']; }

    public function branch() { return $this->belongsTo(Branch::class); }
}
