<?php

namespace App\Admin\Actions;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeactivateUser
{
    /**
     * @throws Throwable
     */
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->forceFill([
                'deactivated_at' => now(),
                'remember_token' => null,
            ])->saveQuietly();

            DB::table('sessions')->where('user_id', $user->id)->delete();

            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->where('tokenable_id', $user->id)
                ->delete();

            Permission::where('subject_type', User::class)
                ->where('subject_id', $user->id)
                ->delete();

            $user->roles()->detach();
            $user->permissions()->detach();
        });
    }
}
