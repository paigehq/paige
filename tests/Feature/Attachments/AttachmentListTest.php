<?php

use App\Models\Page;
use App\Models\Space;
use App\Models\User;
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
