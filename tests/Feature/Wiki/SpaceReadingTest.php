<?php

use App\Models\Space;
use App\Models\User;
use App\Models\Page;

describe('SpaceController::show', function () {
    it('returns 404 for an unknown space slug', function () {
        $this->get(route('spaces.show', 'nonexistent'))
            ->assertNotFound();
    });
});
