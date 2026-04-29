<?php

use App\Models\PageSlugHistory;
use App\Models\Space;
use App\Models\User;
use App\Wiki\Actions\CreatePage;
use App\Wiki\Actions\PublishPage;

describe('SlugRedirectMiddleware', function () {
    it('issues a 301 redirect to the current slug when a historical slug is requested', function () {
        $space = Space::factory()->create(['slug' => 'my-space']);
        $user = User::factory()->create();
        $page = app(CreatePage::class)->handle($space, $user, 'Getting Started');

        // Publish with a new title — this writes 'getting-started' to history
        app(PublishPage::class)->handle($page, $user, 'Installation Guide');

        $this->get('/s/my-space/getting-started')
            ->assertRedirect('/s/my-space/installation-guide')
            ->assertStatus(301);
    });

    it('passes through when the slug is not in page_slug_history', function () {
        $space = Space::factory()->create(['slug' => 'my-space']);
        $user = User::factory()->create();
        $page = app(CreatePage::class)->handle($space, $user, 'Installation Guide');
        app(PublishPage::class)->handle($page, $user);

        $this->get('/s/my-space/installation-guide')
            ->assertStatus(200);
    });

    it('passes through for paths deeper than /s/{space}/{page}', function () {
        Space::factory()->create(['slug' => 'my-space']);

        // Middleware should not redirect deeper paths; route has no match → 404
        $this->get('/s/my-space/some-slug/comments')
            ->assertStatus(404);
    });

    it('does not redirect when the historical slug belongs to a deleted page', function () {
        $space = Space::factory()->create(['slug' => 'my-space']);
        $user = User::factory()->create();
        $page = app(CreatePage::class)->handle($space, $user, 'Soon Deleted');

        // Manually insert a history entry for a soft-deleted page
        $page->delete();
        PageSlugHistory::create(['page_id' => $page->id, 'slug' => 'old-slug', 'created_at' => now()]);

        // Middleware passes through (no redirect); page is gone so the real controller 404s
        $this->get('/s/my-space/old-slug')
            ->assertStatus(404);
    });
});
