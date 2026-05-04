<?php

use App\Models\Page;
use App\Models\Space;
use App\Models\User;
use App\Wiki\Actions\PublishPage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;

describe('AttachmentController::download with conversion', function () {
    it('serves the thumbnail conversion when conversion param is valid', function () {
        $space = Space::factory()->create(['visibility' => 'public']);
        $page = Page::factory()->for($space)->create();
        $uploader = User::factory()->create();

        $fakeImage = UploadedFile::fake()->image('photo.jpg');

        $media = $page
            ->addMedia($fakeImage->getRealPath())
            ->preservingOriginal()
            ->withCustomProperties(['uploader_id' => $uploader->id])
            ->usingFileName('photo.jpg')
            ->toMediaCollection('images');

        // Mark the thumbnail conversion as generated so hasGeneratedConversion() returns true
        $media->markAsConversionGenerated('thumbnail');

        $url = URL::temporarySignedRoute(
            'attachments.download',
            now()->addMinutes(60),
            ['media' => $media->id, 'conversion' => 'thumbnail'],
        );

        // The thumbnail path must exist; create a placeholder file at the expected location
        $thumbPath = $media->getPath('thumbnail');
        if (! file_exists(dirname($thumbPath))) {
            mkdir(dirname($thumbPath), 0755, true);
        }
        file_put_contents($thumbPath, 'thumb');

        $response = $this->get($url);

        $response->assertOk();

        // Cleanup
        @unlink($thumbPath);
    });

    it('falls back to the original file when conversion param is absent', function () {
        $space = Space::factory()->create(['visibility' => 'public']);
        $page = Page::factory()->for($space)->create();
        $uploader = User::factory()->create();

        $media = $page
            ->addMediaFromString('fake content')
            ->withCustomProperties(['uploader_id' => $uploader->id])
            ->usingFileName('doc.pdf')
            ->toMediaCollection('attachments');

        $url = URL::temporarySignedRoute(
            'attachments.download',
            now()->addMinutes(60),
            ['media' => $media->id],
        );

        $response = $this->get($url);

        $response->assertOk();
    });
});

describe('page show — attachment prop', function () {
    it('returns empty attachments array when page has none', function () {
        $space = Space::factory()->create(['visibility' => 'public']);
        $page = Page::factory()->for($space)->create();

        // Publish the page
        app(PublishPage::class)->handle($page, User::factory()->create());

        $response = $this->get("/s/$space->slug/$page->slug");

        $response->assertInertia(fn ($assert) => $assert
            ->component('pages/Show')
            ->where('page.attachments', [])
        );
    });

    it('returns attachment data with correct fields', function () {
        $uploader = User::factory()->create();
        $space = Space::factory()->create(['visibility' => 'public']);
        $page = Page::factory()->for($space)->create();

        app(PublishPage::class)->handle($page, $uploader);

        $page->addMediaFromString('content')
            ->withCustomProperties(['uploader_id' => $uploader->id])
            ->usingFileName('report.pdf')
            ->toMediaCollection('attachments');

        $response = $this->actingAs($uploader)->get("/s/$space->slug/$page->slug");

        $response->assertInertia(fn ($assert) => $assert
            ->component('pages/Show')
            ->has('page.attachments', 1)
            ->where('page.attachments.0.filename', 'report.pdf')
            ->where('page.attachments.0.isImage', false)
            ->where('page.attachments.0.thumbnailUrl', null)
            ->has('page.attachments.0.downloadUrl')
            ->has('page.attachments.0.size')
            ->has('page.attachments.0.mimeType')
        );
    });

    it('sets canDelete=true for uploader and false for other users without admin', function () {
        $uploader = User::factory()->create();
        $other = User::factory()->create();
        $space = Space::factory()->create(['visibility' => 'public']);
        $page = Page::factory()->for($space)->create();

        app(PublishPage::class)->handle($page, $uploader);

        $page->addMediaFromString('content')
            ->withCustomProperties(['uploader_id' => $uploader->id])
            ->usingFileName('file.pdf')
            ->toMediaCollection('attachments');

        $uploaderResponse = $this->actingAs($uploader)->get("/s/$space->slug/$page->slug");
        $uploaderResponse->assertInertia(fn ($assert) => $assert
            ->where('page.attachments.0.canDelete', true)
        );

        $otherResponse = $this->actingAs($other)->get("/s/$space->slug/$page->slug");
        $otherResponse->assertInertia(fn ($assert) => $assert
            ->where('page.attachments.0.canDelete', false)
        );
    });
});
