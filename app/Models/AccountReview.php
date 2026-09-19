<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountReview extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'account_type',
        'account_id',
        'admin_id',
        'decision',
        'reason',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
