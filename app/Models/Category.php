<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['parent_id','name','slug','position','published','seo_title','seo_description'];

    protected function casts(): array { return ['published'=>'boolean']; }

    public function parent() { return $this->belongsTo(Category::class, 'parent_id'); }
    public function children() { return $this->hasMany(Category::class, 'parent_id')->orderBy('position'); }
    public function products() { return $this->belongsToMany(Product::class); }
}
