<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = ['code','type','value','min_order_cents','max_uses','used_count','starts_at','ends_at','active'];

    protected function casts(): array { return ['starts_at'=>'datetime','ends_at'=>'datetime','active'=>'boolean']; }

    public function isValid(int $subtotalCents): bool {
        if (!$this->active) return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->ends_at && $this->ends_at->isPast()) return false;
        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) return false;
        return $subtotalCents >= $this->min_order_cents;
    }
    public function discountFor(int $subtotalCents): int {
        return match ($this->type) {
            'percent' => intdiv($subtotalCents * $this->value, 100),
            'fixed' => min($this->value, $subtotalCents),
            default => 0,
        };
    }
}
