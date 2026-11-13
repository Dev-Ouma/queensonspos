<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['reference','customer_id','guest_email','guest_name','guest_phone','status','payment_status','subtotal_cents','discount_cents','delivery_cents','total_cents','coupon_id','delivery_method','delivery_address','warehouse_id','placed_at'];

    protected function casts(): array { return ['delivery_address'=>'array','placed_at'=>'datetime']; }

    public function customer() { return $this->belongsTo(Customer::class); }
    public function items() { return $this->hasMany(OrderItem::class); }
    public function statusHistory() { return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at'); }
    public function payments() { return $this->morphMany(Payment::class, 'subject'); }
    public function delivery() { return $this->hasOne(Delivery::class); }
    public function customerName(): string { return $this->customer?->name ?? $this->guest_name ?? 'Guest'; }
    public function customerEmail(): ?string { return $this->customer?->email ?? $this->guest_email; }
}
