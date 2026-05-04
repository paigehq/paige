<?php

namespace App\Models;

use Database\Factories\UserGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property Collection<int, Permission> $permissions
 * @property Collection<int, User> $members
 */
#[Fillable(['name', 'slug', 'space_id'])]
class UserGroup extends Model
{
    /** @use HasFactory<UserGroupFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Space, $this>
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * @return MorphMany<Permission, $this>
     */
    public function permissions(): MorphMany
    {
        return $this->morphMany(Permission::class, 'subject');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_group_members');
    }
}
