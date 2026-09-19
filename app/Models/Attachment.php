<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'owner_type',
        'owner_id',
        'file_path',
        'file_type',
    ];

    public function owner(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'owner_type', 'owner_id');
    }
}
