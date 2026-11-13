<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['term', 'filters', 'results', 'ai', 'customer_id'];

    protected $casts = ['filters' => 'array', 'ai' => 'boolean'];
}
