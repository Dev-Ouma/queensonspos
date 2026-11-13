<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        $audited = [
            \App\Models\Product::class, \App\Models\ProductVariant::class, \App\Models\Order::class,
            \App\Models\PosSale::class, \App\Models\Payment::class, \App\Models\Refund::class,
            \App\Models\Expense::class, \App\Models\Setting::class, \App\Models\User::class,
            \App\Models\Coupon::class, \App\Models\PurchaseOrder::class,
        ];
        foreach ($audited as $model) {
            $model::created(fn ($m) => $this->audit('created', $m));
            $model::updated(fn ($m) => $this->audit('updated', $m, $m->getChanges()));
            $model::deleted(fn ($m) => $this->audit('deleted', $m));
        }
    }

    private function audit(string $action, $model, ?array $changes = null): void
    {
        try {
            \App\Models\AuditLog::create([
                'user_id' => auth()->id(),
                'action' => $action.'_'.\Illuminate\Support\Str::snake(class_basename($model)),
                'subject_type' => $model::class,
                'subject_id' => $model->getKey(),
                'changes' => $changes ? collect($changes)->except(['updated_at', 'password', 'remember_token'])->all() : null,
                'ip' => request()->ip(),
            ]);
        } catch (\Throwable) {
            // Audit must never break the business operation.
        }
    }
}
