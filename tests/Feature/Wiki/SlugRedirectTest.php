<?php

use App\Models\PageSlugHistory;
use App\Models\Space;
use App\Models\User;
use App\Wiki\Actions\CreatePage;
use App\Wiki\Actions\PublishPage;
use Illuminate\Support\Facades\Route;

describe('SlugRedirectMiddleware', function () {
    beforeEach(function (): void {
        // Stub route — replaced by a real controller in Session 5.
        Route::middleware('web')->get('/s/{spaceSlug}/{pageSlug}', fn () => response('ok'));
    });

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
        app(CreatePage::class)->handle($space, $user, 'Installation Guide');

        $this->get('/s/my-space/installation-guide')
            ->assertStatus(200);
    });

    it('passes through for paths deeper than /s/{space}/{page}', function () {
        Space::factory()->create(['slug' => 'my-space']);

        // No redirect for /s/{space}/{page}/edit even if slug matches history
        $this->get('/s/my-space/some-slug/edit')
            ->assertStatus(404); // No route registered for this deeper path
    });

    it('does not redirect when the historical slug belongs to a deleted page', function () {
        $space = Space::factory()->create(['slug' => 'my-space']);
        $user = User::factory()->create();
        $page = app(CreatePage::class)->handle($space, $user, 'Soon Deleted');

        // Manually insert a history entry for a soft-deleted page
        $page->delete();
        PageSlugHistory::create(['page_id' => $page->id, 'slug' => 'old-slug', 'created_at' => now()]);

        $this->get('/s/my-space/old-slug')
            ->assertStatus(200); // passes through; no valid live page to redirect to
    });
});
