<?php

namespace App\Http\Controllers;

use App\Enums\SpaceVisibility;
use App\Http\Requests\StoreAttachmentRequest;
use App\Models\Page;
use App\Models\Space;
use App\Models\User;
use App\Permission\Exceptions\PermissionDeniedException;
use App\Permission\PermissionChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttachmentController extends Controller
{
    public function __construct(protected readonly PermissionChecker $checker)
    {
        //
    }

    /**
     * @throws PermissionDeniedException
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
     */
    public function store(StoreAttachmentRequest $request, Space $space, Page $page): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->checker->authorize($user, 'write', $space);

        $file = $request->file('file');

        $collection = str_starts_with($file->getMimeType() ?? '', 'image/')
            ? 'images'
            : 'attachments';

        $media = $page
            ->addMediaFromRequest('file')
            ->withCustomProperties(['uploader_id' => $user->id])
            ->toMediaCollection($collection);

        $signedUrl = URL::temporarySignedRoute(
            'attachments.download',
            now()->addMinutes(60),
            ['media' => $media->id],
        );

        return response()->json([
            'url' => $signedUrl,
            'filename' => $media->file_name,
            'mime_type' => $media->mime_type,
            'id' => $media->id,
        ], 201);
    }

    public function download(Request $request, Media $media): BinaryFileResponse
    {
        /** @var Page $page */
        $page = $media->model;
        $space = $page->space;

        if ($space->visibility !== SpaceVisibility::Public) {
            /** @var User|null $user */
            $user = $request->user();

            if ($user === null || ! $this->checker->can($user, 'read', $space)) {
                abort(403);
            }
        }

        $conversion = $request->query('conversion');

        if ($conversion && $media->hasGeneratedConversion((string) $conversion)) {
            return response()->file($media->getPath((string) $conversion));
        }

        return response()->file($media->getPath());
    }

    public function destroy(Request $request, Space $space, Page $page, Media $media): Response
    {
        /** @var User $user */
        $user = $request->user();

        $isUploader = $media->getCustomProperty('uploader_id') === $user->id;
        $isAdmin = $this->checker->can($user, 'admin', $space);

        if (! $isAdmin && ! $isUploader) {
            abort(403);
        }

        $media->delete();

        return response()->noContent();
    }
}
