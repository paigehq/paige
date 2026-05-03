# Configuration Guide

## Media / File Storage

Paige uses Laravel's Flysystem abstraction for all file storage. The Media Library
disk is controlled by the `MEDIA_DISK` environment variable (default: `public`).

### Local development (default)

The `public` disk stores files in `storage/app/public/`. No extra configuration
is required.

Run `php artisan storage:link` once after setup to symlink `public/storage` →
`storage/app/public` so files are web-accessible.

### Amazon S3

1. Add an S3 disk to `config/filesystems.php` (already present in Laravel's
   default config under the `s3` key).

2. Set the following environment variables:

```env
MEDIA_DISK=s3

AWS_ACCESS_KEY_ID=your-key-id
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_URL=https://your-bucket.s3.amazonaws.com   # optional: CDN or custom domain
```

3. Ensure the S3 bucket has the correct CORS policy to allow `GET` requests from your app's domain (required for images embedded in pages).
   No code changes are needed — the Flysystem abstraction handles the switch transparently.

### Other S3-compatible providers (Cloudflare R2, MinIO, DigitalOCean Spaces)

Set `AWS_ENDPOINT` to the provider's endpoint URL and use the `s3` disk as above. Cloudflare R2 example:

```env
AWS_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
AWS_URL=https://pub-<hash>.r2.dev   # public R2 URL
```

---

## Known limitation: signed URL expiry

Attachment URLs embedded in pages are signed and expire after **60 minutes**. After expiry, `<img>` tags referencing those URLs will return 403 and render as broken images. This is a deliberate trade-off in Milestone 2 to avoid exposing raw storage paths.

**Planned fix (future milestone):** Refresh signed URLs on page load, or issue long-lived tokens scoped to the reading user's session.
