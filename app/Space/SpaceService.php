<?php

namespace App\Space;

use App\Enums\SpaceVisibility;
use App\Models\Page;
use App\Models\Space;
use App\Models\User;
use App\Wiki\Exceptions\SlugExhaustedException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class SpaceService
{
    public function generateSlug(string $name, ?int $excludeId = null): string
    {
        $base = Str::slug($name);

        if (! $this->slugExists($base, $excludeId)) {
            return $base;
        }

        for ($i = 2; $i <= 10; $i++) {
            $candidate = "$base-$i";

            if (! $this->slugExists($candidate, $excludeId)) {
                return $candidate;
            }
        }

        throw new SlugExhaustedException($base);
    }

    /**
     * @return Collection<int, Space>
     */
    public function listForUser(?User $user): Collection
    {
        return Space::query()
            ->where(function ($q) use ($user): void {
                $q->where('visibility', SpaceVisibility::Public);
                if ($user !== null) {
                    $q->orWhereHas('permissions', function ($pq) use ($user): void {
                        $pq->where('subject_type', User::class)
                            ->where('subject_id', $user->id)
                            ->where('granted', true);
                    });
                }
            })
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Space $space, array $data): Space
    {
        $space->fill($data)->save();

        return $space;
    }

    public function archive(Space $space): void
    {
        Page::where('space_id', $space->id)->unsearchable();

        $space->delete();
    }

    protected function slugExists(string $slug, ?int $excludeId): bool
    {
        return Space::withTrashed()
            ->where('slug', $slug)
            ->when($excludeId !== null, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }
}
