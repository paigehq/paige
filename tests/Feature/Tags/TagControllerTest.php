<?php

use App\Enums\PageStatus;
use App\Models\Page;
use App\Models\Space;
use App\Models\Tag;
use App\Models\User;
use App\Wiki\Actions\PublishPage;

describe('TagController::index', function () {
    it('lists tags ordered by page count', function () {
        $space = Space::factory()->create(['visibility' => 'public']);
        $popular = Tag::factory()->create(['name' => 'popular', 'slug' => 'popular']);
        $rare = Tag::factory()->create(['name' => 'rare', 'slug' => 'rare']);
        $user = User::factory()->create();

        // 2 pages tagged popular
        foreach (range(1, 2) as $_) {
            $page = Page::factory()->for($space)->create();
            app(PublishPage::class)->handle($page, $user, tagNames: ['popular']);
        }
        // 1 page tagged rare
        $page = Page::factory()->for($space)->create();
        app(PublishPage::class)->handle($page, $user, tagNames: ['rare']);

        $response = $this->get('/tags');

        $response->assertInertia(fn ($assert) => $assert
            ->component('tags/Index')
            ->where('tags.data.0.slug', 'popular')
            ->where('tags.data.0.pages_count', 2)
            ->where('tags.data.1.slug', 'rare')
            ->where('tags.data.1.pages_count', 1)
        );
    });

    it('excludes draft pages from page counts', function () {
        $space = Space::factory()->create(['visibility' => 'public']);
        $tag = Tag::factory()->create(['name' => 'drafts', 'slug' => 'drafts']);
        $user = User::factory()->create();

        // Draft page with tag (synced via SaveDraft)
        $draftPage = Page::factory()->for($space)->create(['status' => PageStatus::Draft]);
        $draftPage->tags()->attach($tag);

        $response = $this->get('/tags');

        $response->assertInertia(fn ($assert) => $assert
            ->component('tags/Index')
            ->where('tags.data.0.pages_count', 0)
        );
    });

    it('excludes pages in private spaces for unauthenticated users', function () {
        $publicSpace = Space::factory()->create(['visibility' => 'public']);
        $privateSpace = Space::factory()->create(['visibility' => 'private']);
        $tag = Tag::factory()->create(['name' => 'mixed', 'slug' => 'mixed']);
        $user = User::factory()->create();

        $publicPage = Page::factory()->for($publicSpace)->create();
        app(PublishPage::class)->handle($publicPage, $user, tagNames: ['mixed']);

        $privatePage = Page::factory()->for($privateSpace)->create();
        app(PublishPage::class)->handle($privatePage, $user, tagNames: ['mixed']);

        $response = $this->get('/tags');

        $response->assertInertia(fn ($assert) => $assert
            ->component('tags/Index')
            ->where('tags.data.0.pages_count', 1) // only public page
        );
    });
});

describe('TagController::show', function () {
    it('lists published pages with this tag filtered to readable spaces', function () {
        $publicSpace = Space::factory()->create(['visibility' => 'public']);
        $secretSpace = Space::factory()->create(['visibility' => 'secret']);
        $tag = Tag::factory()->create(['name' => 'php', 'slug' => 'php']);
        $user = User::factory()->create();

        $publicPage = Page::factory()->for($publicSpace)->create();
        app(PublishPage::class)->handle($publicPage, $user, tagNames: ['php']);

        $secretPage = Page::factory()->for($secretSpace)->create();
        app(PublishPage::class)->handle($secretPage, $user, tagNames: ['php']);

        $response = $this->get("/tags/{$tag->slug}");

        $response->assertInertia(fn ($assert) => $assert
            ->component('tags/Show')
            ->where('pages.total', 1)
            ->where('pages.data.0.spaceSlug', $publicSpace->slug)
        );
    });

    it('returns 404 for a non-existent tag slug', function () {
        $this->get('/tags/does-not-exist')->assertNotFound();
    });
});
