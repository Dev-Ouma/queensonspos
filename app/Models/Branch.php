<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = ['name','code','address','phone','active'];

    protected function casts(): array { return ['active'=>'boolean']; }

    public function warehouses() { return $this->hasMany(Warehouse::class); }
    public function registers() { return $this->hasMany(Register::class); }
}
