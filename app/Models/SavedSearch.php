<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SavedSearch extends Model
{
    /** Filter keys a saved search may hold (mirrors the /shop query string). */
    public const KEYS = ['q', 'category', 'collection', 'color', 'size', 'sort', 'min_price', 'max_price'];

    protected $fillable = ['customer_id', 'label', 'filters', 'filters_hash'];

    protected $casts = ['filters' => 'array'];

    /** Normalise filters (drop empties, sort keys) so equal searches hash the same. */
    public static function normalise(array $input): array
    {
        return collect($input)->only(self::KEYS)->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v) => (string) $v)->sortKeys()->all();
    }

    public static function hashFor(array $filters): string
    {
        return hash('sha256', json_encode($filters));
    }

    public function customer() { return $this->belongsTo(Customer::class); }

    public function url(): string
    {
        return '/shop'.($this->filters ? '?'.http_build_query($this->filters) : '');
    }
}
