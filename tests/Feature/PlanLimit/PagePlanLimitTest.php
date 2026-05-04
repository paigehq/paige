<?php

use App\Exceptions\PlanLimitException;
use App\Models\Page;
use App\Models\Space;
use App\Models\User;
use App\Wiki\Actions\CreatePage;

describe('CreatePage plan limit', function () {
    it('throws PlanLimitException when a free space already has 100 pages', function () {
        $user = User::factory()->withPlan('free')->create();
        $space = Space::factory()->create();

        Page::factory()->count(100)->create([
            'space_id' => $space->id,
            'author_id' => $user->id,
            'last_editor_id' => $user->id,
        ]);

        expect(fn () => app(CreatePage::class)->handle($space, $user, 'Page 101'))
            ->toThrow(PlanLimitException::class, 'pages');
    });

    it('allows creating the 100th page (boundary)', function () {
        $user = User::factory()->withPlan('free')->create();
        $space = Space::factory()->create();

        Page::factory()->count(99)->create([
            'space_id' => $space->id,
            'author_id' => $user->id,
            'last_editor_id' => $user->id,
        ]);

        $page = app(CreatePage::class)->handle($space, $user, 'Page 100');
        expect($page->exists)->toBeTrue();
    });

    it('does not enforce the limit for pro users', function () {
        $user = User::factory()->withPlan('pro')->create();
        $space = Space::factory()->create();

        Page::factory()->count(100)->create([
            'space_id' => $space->id,
            'author_id' => $user->id,
            'last_editor_id' => $user->id,
        ]);

        $page = app(CreatePage::class)->handle($space, $user, 'Page 101');
        expect($page->exists)->toBeTrue();
    });
});
