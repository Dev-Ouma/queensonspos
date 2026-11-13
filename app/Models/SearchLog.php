<?php

namespace App\Models;

use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class SearchLog extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    protected $fillable = ['term', 'filters', 'results', 'ai', 'customer_id'];

    protected $casts = ['filters' => 'array', 'ai' => 'boolean'];

    /** Keep six months of search history; older rows are pruned nightly. */
    public function prunable()
    {
        return static::where('created_at', '<', now()->subMonths(6));
    }
}
