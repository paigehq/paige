<?php

namespace App\Models;

use App\Enums\SpaceVisibility;
use Database\Factories\SpaceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'description',
    'visibility',
    'owner_id',
    'settings',
])]
class Space extends Model
{
    /** @use HasFactory<SpaceFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility' => SpaceVisibility::class,
            'settings' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<Page, $this>
     */
    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    /**
     * @return HasMany<UserGroup, $this>
     */
    public function userGroups(): HasMany
    {
        return $this->hasMany(UserGroup::class);
    }

    /**
     * @return HasMany<Permission, $this>
     */
    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class);
    }
}
