<?php

use App\Models\User;

describe('deactivated user login', function () {
    it('rejects a deactivated user with a clear validation error', function () {
        User::factory()->deactivated()->create([
            'email' => 'bob@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post('/login', [
            'email' => 'bob@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors(['email']);

        $errors = session('errors')->getBag('default');
        expect($errors->first('email'))
            ->toContain('deactivated');
    });

    it('allows login for an active user with correct credentials', function () {
        $user = User::factory()->create([
            'email' => 'alice@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->post('/login', [
            'email' => 'alice@example.com',
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    });
});
