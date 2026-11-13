<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = ['expense_category_id','branch_id','description','amount_cents','incurred_on','status','created_by','approved_by'];

    protected function casts(): array { return ['incurred_on'=>'date']; }

    public function category() { return $this->belongsTo(ExpenseCategory::class, 'expense_category_id'); }
    public function branch() { return $this->belongsTo(Branch::class); }
}
