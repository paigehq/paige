<?php

namespace App\Models;

use App\Editor\TiptapExtractor;
use App\Enums\PageStatus;
use Carbon\CarbonImmutable;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Laravel\Scout\Searchable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

/**
 * @property int $id
 * @property int $space_id
 * @property int|null $parent_id
 * @property string $title
 * @property string $slug
 * @property string|null $content
 * @property string $content_type
 * @property PageStatus $status
 * @property int $author_id
 * @property int $last_editor_id
 * @property int $revision_number
 * @property int $position
 * @property Space $space
 * @property CarbonImmutable $updated_at
 */
#[Fillable([
    'space_id',
    'parent_id',
    'title',
    'slug',
    'content',
    'content_type',
    'status',
    'author_id',
    'last_editor_id',
    'revision_number',
    'position',
])]
class Page extends Model implements HasMedia
{
    /** @use HasFactory<PageFactory> */
    use HasFactory, HasRecursiveRelationships, InteractsWithMedia, Searchable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PageStatus::class,
            'revision_number' => 'integer',
            'position' => 'integer',
        ];
    }

    public function getParentKeyName(): string
    {
        return 'parent_id';
    }

    /**
     * @return BelongsTo<Space, $this>
     */
    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function lastEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_editor_id');
    }

    /**
     * @return HasMany<PageRevision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(PageRevision::class)->orderByDesc('revision_number');
    }

    /**
     * @return HasMany<PageSlugHistory, $this>
     */
    public function slugHistory(): HasMany
    {
        return $this->hasMany(PageSlugHistory::class);
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'page_tag');
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => app(TiptapExtractor::class)->plainText($this->content),
            'space_name' => $this->space->name,
            'space_slug' => $this->space->slug,
            'space_id' => $this->space_id,
            'tags' => $this->tags->pluck('name')->implode(', '),
            'page_url' => route('pages.show', ['space' => $this->space->slug, 'page' => $this->slug]),
            'status' => $this->status->value,
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === PageStatus::Published && $this->deleted_at === null;
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    protected function makeAllSearchableUsing(Builder $query): Builder
    {
        return $query->with(['space', 'tags']);
    }

    /**
     * @param  Collection<int, static>  $models
     * @return Collection<int, static>
     */
    public function makeSearchableUsing(Collection $models): Collection
    {
        if ($models instanceof EloquentCollection) {
            $models->load(['space' => fn (Relation $q) => $q->withoutGlobalScope(SoftDeletingScope::class)]);
            $models->load(['tags']);
        }

        return $models;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

        $this->addMediaCollection('attachments');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->performOnCollections('images')
            ->fit(Fit::Contain, 300, 300);
    }
}
