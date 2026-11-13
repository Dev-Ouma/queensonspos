<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['brand_id','name','slug','sku_root','description','care_instructions','attributes','status','featured','low_stock_threshold','seo_title','seo_description','published_at'];

    protected function casts(): array { return ['attributes'=>'array','featured'=>'boolean','published_at'=>'datetime']; }

    public function brand() { return $this->belongsTo(Brand::class); }
    public function categories() { return $this->belongsToMany(Category::class); }
    public function variants() { return $this->hasMany(ProductVariant::class); }
    public function images() { return $this->hasMany(ProductImage::class)->orderBy('position'); }
    public function collections() { return $this->belongsToMany(Collection::class); }
    public function reviews() { return $this->hasMany(ProductReview::class); }
    public function scopePublished($q) { return $q->where('status', 'published'); }
}
