<?php

namespace App\Models;

use Database\Factories\PageRevisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $page_id
 * @property string|null $title
 * @property string|null $content
 * @property int $editor_id
 * @property int $revision_number
 * @property string|null $change_summary
 * @property Carbon $created_at
 */
#[Fillable([
    'page_id',
    'title',
    'content',
    'editor_id',
    'revision_number',
    'change_summary',
    'created_at',
])]
class PageRevision extends Model
{
    /** @use HasFactory<PageRevisionFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'revision_number' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'editor_id');
    }
}
