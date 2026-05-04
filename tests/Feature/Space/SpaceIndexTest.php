<?php

use App\Models\Space;
use App\Models\User;
use App\Space\Actions\CreateSpace;

describe('GET /spaces', function () {
    it('returns 200 for unauthenticated users', function () {
        $this->get(route('spaces.index'))->assertOk();
    });

    it('shows only public spaces to unauthenticated users', function () {
        $public = Space::factory()->public()->create();
        Space::factory()->create();           // private
        Space::factory()->secret()->create(); // secret

        $this->get(route('spaces.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('spaces/Index')
                ->has('spaces', 1)
                ->where('spaces.0.id', $public->id)
            );
    });

    it('shows public and member spaces to authenticated users', function () {
        $user = User::factory()->create();
        Space::factory()->public()->create();

        // CreateSpace makes the user the owner, which creates an admin permission row
        app(CreateSpace::class)->handle(['name' => 'Private One', 'description' => null], $user);
        Space::factory()->secret()->create();

        $this->actingAs($user)
            ->get(route('spaces.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('spaces/Index')
                ->has('spaces', 2)
            );
    });

    it('does not show archived spaces', function () {
        $space = Space::factory()->public()->create();
        $space->delete();

        $this->get(route('spaces.index'))
            ->assertInertia(fn ($page) => $page->has('spaces', 0));
    });
});
