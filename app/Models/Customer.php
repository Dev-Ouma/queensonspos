<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'phone', 'password', 'loyalty_points', 'status'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['email_verified_at' => 'datetime', 'password' => 'hashed'];
    }

    public function addresses() { return $this->hasMany(CustomerAddress::class); }
    public function orders() { return $this->hasMany(Order::class); }
    public function wishlistItems() { return $this->hasMany(WishlistItem::class); }
    public function savedSearches() { return $this->hasMany(SavedSearch::class); }
    public function loyaltyTransactions() { return $this->hasMany(LoyaltyTransaction::class); }
}
