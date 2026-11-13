<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = ['user_id','action','subject_type','subject_id','changes','ip'];

    protected function casts(): array { return ['changes' => 'array', 'created_at' => 'datetime']; }

    public $timestamps = false;
    public function user() { return $this->belongsTo(User::class); }
}
