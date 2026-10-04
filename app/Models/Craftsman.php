<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Craftsman extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'craft_id',
        'personal_photo_path',
        'id_selfie_path',
        'bio',
        'status',
        'is_available',
        'rejection_reason',
        'verified_at',
        'verified_by',
        'avg_rating',
        'reviews_count',
    ];

    /**
     * id_selfie_path is admin-only and must never leave the model by default.
     * It is only ever read explicitly for the admin review endpoints.
     */
    protected $hidden = [
        'id_selfie_path',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'avg_rating' => 'decimal:2',
            'is_available' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function craft(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'craft_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function additionalServices(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'craftsman_services');
    }

    public function serviceAreas(): BelongsToMany
    {
        return $this->belongsToMany(Area::class, 'craftsman_areas');
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
