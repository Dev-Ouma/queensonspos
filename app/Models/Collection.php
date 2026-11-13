<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    protected $fillable = ['name','slug','description','published'];

    protected function casts(): array { return ['published'=>'boolean']; }

    public function products() { return $this->belongsToMany(Product::class); }
}
