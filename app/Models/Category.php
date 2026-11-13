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

    /** Smart search caches the category, colour and size lists; refresh them on change. */
    protected static function booted(): void
    {
        static::saved(fn () => cache()->forget('ai_search.vocabulary'));
        static::deleted(fn () => cache()->forget('ai_search.vocabulary'));
    }
}
