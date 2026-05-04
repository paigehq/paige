<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserInvitationController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PageHistoryController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Space\SpaceGroupController;
use App\Http\Controllers\Space\SpaceMemberController;
use App\Http\Controllers\SpaceController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('/spaces', [SpaceController::class, 'index'])->name('spaces.index');

Route::get('/search', [SearchController::class, 'index'])->name('search');

Route::get('/attachments/{media}/download', [AttachmentController::class, 'download'])
    ->middleware('signed')
    ->name('attachments.download');

Route::prefix('s')->middleware('space.visibility')->group(function () {
    Route::get('{space:slug}', [SpaceController::class, 'show'])->name('spaces.show');

    Route::middleware('auth')->group(function () {
        Route::get('{space:slug}/new', [PageController::class, 'create'])->name('pages.create');
        Route::post('{space:slug}/pages', [PageController::class, 'store'])->name('pages.store');
    });

    Route::scopeBindings()->group(function () {
        Route::get('{space:slug}/{page:slug}', [PageController::class, 'show'])->name('pages.show');
    });

    Route::middleware('auth')->scopeBindings()->group(function () {
        Route::get('{space:slug}/{page:slug}/edit', [PageController::class, 'edit'])->name('pages.edit');
        Route::put('{space:slug}/{page:slug}', [PageController::class, 'update'])->name('pages.update');
        Route::delete('{space:slug}/{page:slug}', [PageController::class, 'destroy'])->name('pages.destroy');
        Route::get('{space:slug}/{page:slug}/history', [PageHistoryController::class, 'index'])->name('pages.history');
        Route::get('{space:slug}/{page:slug}/history/{revision}',
            [PageHistoryController::class, 'show'])->name('pages.history.show');
        Route::get('{space:slug}/{page:slug}/history/{a}/diff/{b}',
            [PageHistoryController::class, 'diff'])->name('pages.history.diff');
        Route::post('{space:slug}/{page:slug}/attachments',
            [AttachmentController::class, 'store'])
            ->name('pages.attachments.store');
        Route::delete('{space:slug}/{page:slug}/attachments/{media}',
            [AttachmentController::class, 'destroy'])
            ->name('pages.attachments.destroy');
    });

    Route::middleware('auth')->group(function () {
        Route::get('{space:slug}/settings/members',
            [SpaceMemberController::class, 'index'])
            ->name('spaces.settings.members');
        Route::post('{space:slug}/settings/members',
            [SpaceMemberController::class, 'store'])
            ->name('spaces.settings.members.store');
        Route::put('{space:slug}/settings/members/{member}',
            [SpaceMemberController::class, 'update'])
            ->name('spaces.settings.members.update');
        Route::delete('{space:slug}/settings/members/{member}',
            [SpaceMemberController::class, 'destroy'])
            ->name('spaces.settings.members.destroy');

        Route::post('{space:slug}/settings/groups',
            [SpaceGroupController::class, 'store'])
            ->name('spaces.settings.groups.store');
        Route::put('{space:slug}/settings/groups/{group}/permission',
            [SpaceGroupController::class, 'updatePermission'])
            ->name('spaces.settings.groups.permission');
        Route::delete('{space:slug}/settings/groups/{group}',
            [SpaceGroupController::class, 'destroy'])
            ->name('spaces.settings.groups.destroy');
        Route::post('{space:slug}/settings/groups/{group}/members',
            [SpaceGroupController::class, 'addMember'])
            ->name('spaces.settings.groups.members.store');
        Route::delete('{space:slug}/settings/groups/{group}/members/{member}',
            [SpaceGroupController::class, 'removeMember'])
            ->name('spaces.settings.groups.members.destroy');
    });
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {
        Route::resource('spaces', Admin\SpaceController::class)
            ->except(['show'])
            ->names([
                'index' => 'spaces.index',
                'create' => 'spaces.create',
                'store' => 'spaces.store',
                'edit' => 'spaces.edit',
                'update' => 'spaces.update',
                'destroy' => 'spaces.destroy',
            ]);

        Route::get('users', [UserController::class, 'index'])
            ->name('users.index');
        Route::post('users/invite', [UserInvitationController::class, 'store'])
            ->name('users.invite');
        Route::patch('users/invitations/{invitation}/resend',
            [UserInvitationController::class, 'resend'])
            ->name('users.invitations.resend');
        Route::get('users/{user}', [UserController::class, 'show'])
            ->name('users.show');
        Route::patch('users/{user}/role', [UserController::class, 'updateRole'])
            ->name('users.role');
        Route::delete('users/{user}', [UserController::class, 'destroy'])
            ->name('users.destroy');
    });

Route::get('/invitations/{token}/accept', [InvitationController::class, 'show'])
    ->name('invitations.accept.show');
Route::post('/invitations/{token}/accept', [InvitationController::class, 'store'])
    ->name('invitations.accept.store');

Route::get('/tags', [TagController::class, 'index'])->name('tags.index');
Route::get('/tags/{tag:slug}', [TagController::class, 'show'])->name('tags.show');
