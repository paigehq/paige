<?php

use App\Enums\PageStatus;
use App\Enums\PermissionAction;
use App\Enums\SpaceVisibility;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Space;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

// ---------- Migration sanity ----------

describe('database schema', function () {
    it('has no legacy attachments table and has the media table', function () {
        expect(Schema::hasTable('attachments'))->toBeFalse()
            ->and(Schema::hasTable('media'))->toBeTrue();
    });
});

// ---------- POST /s/{space}/{page}/attachments ----------

describe('POST /s/{space}/{page}/attachments', function () {
    it('uploads a valid image, creates a media record, and returns a signed URL', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Public]);
        $page = Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'author_id' => $user->id,
            'last_editor_id' => $user->id,
        ]);

        Permission::create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Write,
            'granted' => true,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/s/$space->slug/$page->slug/attachments", [
                'file' => UploadedFile::fake()->image('photo.jpg', 200, 200),
            ]);

        $response->assertCreated()
            ->assertJsonStructure(['url', 'filename', 'mime_type', 'id']);

        expect(Media::count())->toBe(1)
            ->and($response->json('filename'))->toBe('photo.jpg')
            ->and($response->json('url'))->toContain('/attachments/');
    });

    it('returns 403 when the user does not have write permission', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        $page = Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'author_id' => $user->id,
            'last_editor_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->postJson("/s/$space->slug/$page->slug/attachments", [
                'file' => UploadedFile::fake()->image('photo.jpg'),
            ])
            ->assertForbidden();

        expect(Media::count())->toBe(0);
    });

    it('returns 422 for an unsupported mime type', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Public]);
        $page = Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'author_id' => $user->id,
            'last_editor_id' => $user->id,
        ]);

        Permission::create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Write,
            'granted' => true,
        ]);

        $this->actingAs($user)
            ->postJson("/s/$space->slug/$page->slug/attachments", [
                'file' => UploadedFile::fake()->create('malware.exe', 100, 'application/x-msdownload'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    });

    it('returns 422 when the file exceeds 10 MB', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Public]);
        $page = Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'author_id' => $user->id,
            'last_editor_id' => $user->id,
        ]);

        Permission::create([
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Write,
            'granted' => true,
        ]);

        // 10,241 KB = just over 10 MB
        $this->actingAs($user)
            ->postJson("/s/$space->slug/$page->slug/attachments", [
                'file' => UploadedFile::fake()->create('big.pdf', 10_241, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    });
});

// ---------- GET /attachments/{media}/download ----------

describe('GET /attachments/{media}/download', function () {
    it('streams the file for a valid signed URL with read permission', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Public]);
        $page = Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'author_id' => $user->id,
            'last_editor_id' => $user->id,
        ]);

        $media = $page->addMedia(UploadedFile::fake()->image('test.jpg'))
            ->withCustomProperties(['uploader_id' => $user->id])
            ->toMediaCollection('images');

        $signedUrl = URL::temporarySignedRoute(
            'attachments.download',
            now()->addMinutes(60),
            ['media' => $media->id],
        );

        $this->actingAs($user)
            ->get($signedUrl)
            ->assertOk();
    });

    it('returns 403 for an expired signed URL', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Public]);
        $page = Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'author_id' => $user->id,
            'last_editor_id' => $user->id,
        ]);

        $media = $page->addMedia(UploadedFile::fake()->image('test.jpg'))
            ->withCustomProperties(['uploader_id' => $user->id])
            ->toMediaCollection('images');

        $expiredUrl = URL::temporarySignedRoute(
            'attachments.download',
            now()->subSecond(),
            ['media' => $media->id],
        );

        $this->get($expiredUrl)->assertForbidden();
    });

    it('returns 403 for an attachment in a private space when the user has no read permission', function () {
        $uploader = User::factory()->create();
        $requester = User::factory()->create();
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Private]);
        $page = Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'author_id' => $uploader->id,
            'last_editor_id' => $uploader->id,
        ]);

        $media = $page->addMedia(UploadedFile::fake()->image('private.jpg'))
            ->withCustomProperties(['uploader_id' => $uploader->id])
            ->toMediaCollection('images');

        $signedUrl = URL::temporarySignedRoute(
            'attachments.download',
            now()->addMinutes(60),
            ['media' => $media->id],
        );

        $this->actingAs($requester)
            ->get($signedUrl)
            ->assertForbidden();
    });
});

// ---------- DELETE /s/{space}/{page}/attachments/{media} ----------

describe('DELETE /s/{space}/{page}/attachments/{media}', function () {
    it('allows the uploader to delete their own attachment', function () {
        $user = User::factory()->create();
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Public]);
        $page = Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'author_id' => $user->id,
            'last_editor_id' => $user->id,
        ]);

        $media = $page->addMedia(UploadedFile::fake()->image('mine.jpg'))
            ->withCustomProperties(['uploader_id' => $user->id])
            ->toMediaCollection('images');

        $this->actingAs($user)
            ->deleteJson("/s/$space->slug/$page->slug/attachments/$media->id")
            ->assertNoContent();

        expect(Media::find($media->id))->toBeNull();
    });

    it('allows a space admin to delete any attachment', function () {
        $uploader = User::factory()->create();
        $admin = User::factory()->create();
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Public]);
        $page = Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'author_id' => $uploader->id,
            'last_editor_id' => $uploader->id,
        ]);

        Permission::create([
            'subject_type' => User::class,
            'subject_id' => $admin->id,
            'space_id' => $space->id,
            'action' => PermissionAction::Admin,
            'granted' => true,
        ]);

        $media = $page->addMedia(UploadedFile::fake()->image('theirs.jpg'))
            ->withCustomProperties(['uploader_id' => $uploader->id])
            ->toMediaCollection('images');

        $this->actingAs($admin)
            ->deleteJson("/s/$space->slug/$page->slug/attachments/$media->id")
            ->assertNoContent();

        expect(Media::find($media->id))->toBeNull();
    });

    it('returns 403 for a user who is neither the uploader nor a space admin', function () {
        $uploader = User::factory()->create();
        $other = User::factory()->create();
        $space = Space::factory()->create(['visibility' => SpaceVisibility::Public]);
        $page = Page::factory()->for($space)->create([
            'status' => PageStatus::Published,
            'author_id' => $uploader->id,
            'last_editor_id' => $uploader->id,
        ]);

        $media = $page->addMedia(UploadedFile::fake()->image('notmine.jpg'))
            ->withCustomProperties(['uploader_id' => $uploader->id])
            ->toMediaCollection('images');

        $this->actingAs($other)
            ->deleteJson("/s/$space->slug/$page->slug/attachments/$media->id")
            ->assertForbidden();

        expect(Media::find($media->id))->not->toBeNull();
    });
});
