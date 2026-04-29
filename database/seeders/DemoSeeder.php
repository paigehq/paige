<?php

namespace Database\Seeders;

use App\Enums\SpaceVisibility;
use App\Models\Page;
use App\Models\Space;
use App\Models\User;
use App\Wiki\Actions\CreatePage;
use App\Wiki\Actions\PublishPage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@paige.local'],
            [
                'name' => 'Paige Admin',
                'email' => 'admin@paige.local',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $space = Space::firstOrCreate(
            ['slug' => 'paige-docs'],
            [
                'name' => 'Paige Documentation',
                'slug' => 'paige-docs',
                'description' => 'Official documentation for the Paige knowledge base.',
                'owner_id' => $admin->id,
                'visibility' => SpaceVisibility::Public,
            ]
        );

        // ── Root pages ─────────────────────────────────────────────
        $gettingStarted = $this->ensurePage($space, $admin, 'Getting Started', null, $this->contentGettingStarted(), 3);
        $installation = $this->ensurePage($space, $admin, 'Installation', null, $this->contentInstallation());
        $architecture = $this->ensurePage($space, $admin, 'Architecture', null, $this->contentArchitecture(), 3);
        $contributing = $this->ensurePage($space, $admin, 'Contributing', null, $this->contentContributing());
        $this->ensurePage($space, $admin, 'FAQ', null, $this->contentFaq());

        // ── Getting Started children ────────────────────────────────
        $this->ensurePage($space, $admin, 'Quick Start', $gettingStarted, $this->contentQuickStart(), 3);
        $this->ensurePage($space, $admin, 'Key Concepts', $gettingStarted, $this->contentKeyConcepts());

        // ── Installation children ───────────────────────────────────
        $this->ensurePage($space, $admin, 'Requirements', $installation, $this->contentRequirements());
        $dockerSetup = $this->ensurePage($space, $admin, 'Docker Setup', $installation, $this->contentDockerSetup());
        $this->ensurePage($space, $admin, 'Manual Setup', $installation, $this->contentManualSetup());
        $this->ensurePage($space, $admin, 'Environment Variables', $installation, $this->contentEnvVars());

        // ── Docker Setup child (3rd level) ──────────────────────────
        $this->ensurePage($space, $admin, 'Docker Compose Reference', $dockerSetup, $this->contentDockerComposeRef());

        // ── Architecture children ───────────────────────────────────
        $pageEngine = $this->ensurePage($space, $admin, 'Page Engine', $architecture, $this->contentPageEngine());
        $this->ensurePage($space, $admin, 'Version History', $architecture, $this->contentVersionHistory());
        $this->ensurePage($space, $admin, 'Storage and Cache', $architecture, $this->contentStorageCache());

        // ── Page Engine child (3rd level) ───────────────────────────
        $this->ensurePage($space, $admin, 'Slug Handling', $pageEngine, $this->contentSlugHandling());

        // ── Contributing children ───────────────────────────────────
        $this->ensurePage($space, $admin, 'Development Setup', $contributing, $this->contentDevSetup());
        $this->ensurePage($space, $admin, 'Testing Guide', $contributing, $this->contentTestingGuide());
        $this->ensurePage($space, $admin, 'Submitting a PR', $contributing, $this->contentSubmittingPr());
    }

    /**
     * Idempotent page creator. Creates once via actions; skips if slug already exists.
     * $targetRevisions is the minimum revision_number the page should reach.
     */
    private function ensurePage(Space $space, User $admin, string $title, ?Page $parent, string $content, int $targetRevisions = 2): Page
    {
        $slug = Str::slug($title);
        $existing = Page::where('space_id', $space->id)->where('slug', $slug)->withTrashed()->first();

        if ($existing !== null) {
            return $existing;
        }

        /** @var CreatePage $createPage */
        $createPage = app(CreatePage::class);
        /** @var PublishPage $publishPage */
        $publishPage = app(PublishPage::class);

        $page = $createPage->handle($space, $admin, $title, $content, $parent);

        for ($rev = 2; $rev <= $targetRevisions; $rev++) {
            $page = $publishPage->handle($page->fresh(), $admin, $title, $content);
        }

        return $page->fresh();
    }

    // ── Content methods ────────────────────────────────────────────

    private function contentGettingStarted(): string
    {
        return json_encode([
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'Getting Started with Paige']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Paige is a self-hostable knowledge base built for teams that care about their tooling. Organise knowledge into Spaces, nest pages to any depth, and keep every change tracked through automatic version history.']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'What you get']]],
                ['type' => 'bulletList', 'content' => [
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Spaces — top-level containers, each with its own page tree and visibility settings']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Nested pages — unlimited depth, ordered by position']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Automatic revision history — every publish creates an immutable revision row']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Slug redirects — rename a page and old links redirect automatically']]]]],
                ]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Where to go next']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Follow the Installation guide to spin up your own instance, or read Key Concepts for a deeper introduction to how Paige models knowledge.']]],
            ],
        ]);
    }

    private function contentQuickStart(): string
    {
        return json_encode([
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'Quick Start']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'The fastest path to a running Paige instance uses Docker Compose.']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => '1. Clone the repository']]],
                ['type' => 'codeBlock', 'attrs' => ['language' => 'bash'], 'content' => [['type' => 'text', 'text' => "git clone https://github.com/your-org/paige.git\ncd paige"]]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => '2. Copy environment file']]],
                ['type' => 'codeBlock', 'attrs' => ['language' => 'bash'], 'content' => [['type' => 'text', 'text' => 'cp .env.example .env']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => '3. Start the stack']]],
                ['type' => 'codeBlock', 'attrs' => ['language' => 'bash'], 'content' => [['type' => 'text', 'text' => "docker compose up -d\ndocker compose exec app php artisan key:generate\ndocker compose exec app php artisan migrate --seed"]]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Paige is now running at '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'http://localhost'], ['type' => 'text', 'text' => '. Log in with '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'admin@paige.local'], ['type' => 'text', 'text' => ' / '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'password'], ['type' => 'text', 'text' => '.']]],
            ],
        ]);
    }

    private function contentKeyConcepts(): string
    {
        return json_encode([
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'Key Concepts']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Spaces']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'A Space is the top-level organisational unit. Think of it like a Confluence space or a Notion teamspace. Each space has a slug, a visibility setting (public, private, or secret), and its own independent page tree.']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Pages']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Pages live inside Spaces. They have a title, a slug (unique per space), and rich content stored as Tiptap JSON. A page can be a draft or published. Only published pages are visible to unauthenticated visitors.']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Revisions']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Every time you publish a page, Paige appends an immutable revision row to the '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'page_revisions'], ['type' => 'text', 'text' => ' table. Revision rows are never updated — they are a permanent audit trail. You can view any historical revision or diff two revisions side by side.']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Slugs']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Page slugs are auto-generated from titles and are unique per space. When you rename a page, the old slug is stored in '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'page_slug_history'], ['type' => 'text', 'text' => ' and any request for the old URL is permanently redirected to the new one.']]],
            ],
        ]);
    }

    private function contentInstallation(): string
    {
        return json_encode([
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'Installation']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Paige can be installed using Docker Compose (recommended) or manually on any server that meets the requirements. Choose the approach that best fits your infrastructure.']]],
                ['type' => 'bulletList', 'content' => [
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Docker Setup — recommended for most teams; no PHP or Node toolchain required on the host']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Manual Setup — for teams that manage their own server stack']]]]],
                ]],
            ],
        ]);
    }

    private function contentRequirements(): string
    {
        return json_encode([
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'Requirements']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Paige requires the following to run:']]],
                ['type' => 'bulletList', 'content' => [
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => 'PHP 8.3+'], ['type' => 'text', 'text' => ' with extensions: pdo_mysql, redis, intl, mbstring, bcmath, xml']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => 'MySQL 8.0+'], ['type' => 'text', 'text' => ' — recursive CTEs are required for the page tree']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => 'Redis 6+'], ['type' => 'text', 'text' => ' — used for HTML cache and queue backend']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => 'Node.js 20+'], ['type' => 'text', 'text' => ' — only needed to build frontend assets; not required at runtime']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => 'Meilisearch'], ['type' => 'text', 'text' => ' — optional; required for full-text search (Milestone 2)']]]]],
                ]],
            ],
        ]);
    }

    private function contentDockerSetup(): string
    {
        return json_encode([
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'Docker Setup']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'The recommended way to run Paige in production is using the provided Docker Compose configuration. It bundles the app, MySQL, Redis, and a queue worker into a single stack.']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Start the stack']]],
                ['type' => 'codeBlock', 'attrs' => ['language' => 'bash'], 'content' => [['type' => 'text', 'text' => "docker compose up -d\ndocker compose exec app php artisan key:generate\ndocker compose exec app php artisan migrate --force"]]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'See the Docker Compose Reference page for a complete explanation of each service.']]],
            ],
        ]);
    }

    private function contentDockerComposeRef(): string
    {
        return json_encode([
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'Docker Compose Reference']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'The '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'docker-compose.yml'], ['type' => 'text', 'text' => ' at the project root defines four services:']]],
                ['type' => 'bulletList', 'content' => [
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'app'], ['type' => 'text', 'text' => ' — Laravel application served by FrankenPHP or Nginx + PHP-FPM']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'db'], ['type' => 'text', 'text' => ' — MySQL 8; data persisted in a named volume']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'redis'], ['type' => 'text', 'text' => ' — Redis 7; used for cache and queue']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'worker'], ['type' => 'text', 'text' => ' — Laravel Horizon queue worker; runs alongside the app service']]]]],
                ]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'All environment variables are injected from '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => '.env'], ['type' => 'text', 'text' => '. Never commit secrets to the repository.']]],
            ],
        ]);
    }

    private function contentManualSetup(): string
    {
        return json_encode([
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'Manual Setup']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'If you prefer to manage your own server stack, follow these steps after ensuring the Requirements are met.']]],
                ['type' => 'orderedList', 'content' => [
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Clone the repository and install PHP dependencies: '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'composer install --no-dev']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Install and build frontend assets: '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'npm ci && npm run build']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Copy '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => '.env.example'], ['type' => 'text', 'text' => ' to '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => '.env'], ['type' => 'text', 'text' => ' and configure your database and Redis connection']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Run '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'php artisan key:generate && php artisan migrate']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Configure a queue worker via systemd or Supervisor pointing at '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'php artisan horizon']]]]],
                ]],
            ],
        ]);
    }

    private function contentEnvVars(): string
    {
        return json_encode([
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'Environment Variables']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'All configuration is driven by the '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => '.env'], ['type' => 'text', 'text' => ' file. The following variables are required:']]],
                ['type' => 'bulletList', 'content' => [
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'APP_KEY'], ['type' => 'text', 'text' => ' — generate with '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'php artisan key:generate']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'DB_HOST'], ['type' => 'text', 'text' => ', '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'DB_DATABASE'], ['type' => 'text', 'text' => ', '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'DB_USERNAME'], ['type' => 'text', 'text' => ', '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'DB_PASSWORD']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'REDIS_HOST'], ['type' => 'text', 'text' => ' — Redis server for cache and queue']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'QUEUE_CONNECTION=redis'], ['type' => 'text', 'text' => ' — use Redis-backed queues']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'CACHE_STORE=redis'], ['type' => 'text', 'text' => ' — use Redis for HTML cache']]]]],
                ]],
            ],
        ]);
    }

    private function contentArchitecture(): string
    {
        return json_encode([
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'Architecture']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Paige is a Laravel 13 application with an Inertia.js + Vue 3 frontend. This section explains the major architectural decisions that shape the codebase.']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Domain organisation']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Code is organised by domain, not by technical layer. Each domain lives in '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'app/{Domain}/'], ['type' => 'text', 'text' => ' and contains its Actions, Services, and Contracts. No flat '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'app/Services/'], ['type' => 'text', 'text' => ' directory.']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Content storage']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Page content is stored as '], ['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => 'Tiptap JSON'], ['type' => 'text', 'text' => ', never as HTML. HTML is rendered at read time by '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'TiptapRenderer'], ['type' => 'text', 'text' => ' and cached in Redis for 24 hours. The cache is invalidated on every publish.']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Read the sub-pages']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Each sub-page covers a specific architectural system in depth.']]],
            ],
        ]);
    }

    private function contentPageEngine(): string
    {
        return json_encode([
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'Page Engine']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'The page engine is responsible for the full lifecycle of a Page: creation, editing, publishing, moving, and soft-deletion.']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Actions']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Each operation is a dedicated Action class in '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'app/Wiki/Actions/'], ['type' => 'text', 'text' => '.']]],
                ['type' => 'bulletList', 'content' => [
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'CreatePage'], ['type' => 'text', 'text' => ' — creates a page as a draft with revision_number 1']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'PublishPage'], ['type' => 'text', 'text' => ' — publishes, increments revision_number, appends a revision row, invalidates cache']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'MovePage'], ['type' => 'text', 'text' => ' — updates parent_id and rebuilds ancestor relationships; prevents circular references']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'DeletePage'], ['type' => 'text', 'text' => ' — soft-deletes; children are orphaned, not deleted']]]]],
                ]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Page tree']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'The sidebar tree is built server-side by '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'PageTreeBuilder'], ['type' => 'text', 'text' => ' in a single SQL query. Tree assembly from the flat result set is O(n) using PHP references. No per-node API calls are ever made from the frontend.']]],
            ],
        ]);
    }

    private function contentSlugHandling(): string
    {
        return json_encode([
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'Slug Handling']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Page slugs are unique per space and auto-generated from the page title via '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'Str::slug()'], ['type' => 'text', 'text' => '.']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Collision resolution']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'When a slug already exists in the space, Paige appends '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => '-2'], ['type' => 'text', 'text' => ', '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => '-3'], ['type' => 'text', 'text' => ', … up to '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => '-10'], ['type' => 'text', 'text' => '. Beyond that, a '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'SlugExhaustedException'], ['type' => 'text', 'text' => ' is thrown. Soft-deleted pages still hold their slug in the unique constraint, so the collision check always uses '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'Page::withTrashed()']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Redirect middleware']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'When a page is published with a new title, the old slug is written to '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'page_slug_history'], ['type' => 'text', 'text' => '. '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'SlugRedirectMiddleware'], ['type' => 'text', 'text' => ' intercepts requests for '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => '/s/{space}/{slug}'], ['type' => 'text', 'text' => ', looks up the history table, and issues a 301 redirect to the current slug URL.']]],
            ],
        ]);
    }

    private function contentVersionHistory(): string
    {
        return json_encode([
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'Version History']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Every publish creates an immutable row in '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'page_revisions'], ['type' => 'text', 'text' => '. This table has no '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'updated_at'], ['type' => 'text', 'text' => ' column — a deliberate signal that rows are append-only and must never be updated.']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Diffs']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Diffs are computed lazily on request, never pre-computed. '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'RevisionService::diff()'], ['type' => 'text', 'text' => ' extracts plain text from both revision\'s Tiptap JSON using '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'TiptapExtractor'], ['type' => 'text', 'text' => ', then runs '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'jfcherng/php-diff'], ['type' => 'text', 'text' => ' to produce a flat array of '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => '{tag, line}'], ['type' => 'text', 'text' => ' objects for the Vue diff view.']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Draft saves']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Autosaves (draft) do not create a revision. Only '], ['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => 'publishing'], ['type' => 'text', 'text' => ' creates a revision. This keeps the history clean and meaningful.']]],
            ],
        ]);
    }

    private function contentStorageCache(): string
    {
        return json_encode([
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'Storage & Cache']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Content storage']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Page content is stored as Tiptap JSON in the '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'pages.content'], ['type' => 'text', 'text' => ' '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'longtext'], ['type' => 'text', 'text' => ' column. HTML is never persisted to the database.']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Redis HTML cache']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Rendered HTML is cached in Redis under key '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'page:{id}:html'], ['type' => 'text', 'text' => ' with a 24-hour TTL. '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'TiptapRenderer::renderCached()'], ['type' => 'text', 'text' => ' implements cache-aside: check Redis, render on miss, store result. The key is invalidated immediately after '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'PublishPage'], ['type' => 'text', 'text' => ' commits the transaction.']]],
            ],
        ]);
    }

    private function contentContributing(): string
    {
        return json_encode([
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'Contributing']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Paige is open-source and welcomes contributions. Before starting work on a non-trivial feature, open an issue to discuss the approach. Small bug fixes and documentation improvements can go straight to a PR.']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Guiding principles']]],
                ['type' => 'bulletList', 'content' => [
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => 'TDD'], ['type' => 'text', 'text' => ' — write the test before the implementation']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => 'YAGNI'], ['type' => 'text', 'text' => ' — only implement what the current milestone requires']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'bold']], 'text' => 'Domain organisation'], ['type' => 'text', 'text' => ' — code belongs in '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'app/{Domain}/'], ['type' => 'text', 'text' => ', not in flat directories']]]]],
                ]],
            ],
        ]);
    }

    private function contentDevSetup(): string
    {
        return json_encode([
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'Development Setup']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Set up a local development environment with Laravel Sail or a manual PHP install.']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Using Sail']]],
                ['type' => 'codeBlock', 'attrs' => ['language' => 'bash'], 'content' => [['type' => 'text', 'text' => "composer install\ncp .env.example .env\n./vendor/bin/sail up -d\n./vendor/bin/sail artisan key:generate\n./vendor/bin/sail artisan migrate --seed\nnpm install && npm run dev"]]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Visit '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'http://localhost'], ['type' => 'text', 'text' => ' and log in with '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'admin@paige.local'], ['type' => 'text', 'text' => ' / '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'password'], ['type' => 'text', 'text' => '.']]],
            ],
        ]);
    }

    private function contentTestingGuide(): string
    {
        return json_encode([
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'Testing Guide']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Paige uses Pest 4 for all tests. The test suite covers unit tests for domain logic and feature tests for HTTP endpoints.']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Running tests']]],
                ['type' => 'codeBlock', 'attrs' => ['language' => 'bash'], 'content' => [['type' => 'text', 'text' => "# Run everything\nphp artisan test --compact\n\n# Run a specific file\nphp artisan test --compact tests/Feature/Wiki/PageReadingTest.php\n\n# Filter by test name\nphp artisan test --compact --filter=\"breadcrumb\""]]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Conventions']]],
                ['type' => 'bulletList', 'content' => [
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Use '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'describe/it'], ['type' => 'text', 'text' => ' blocks — no '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'test()'], ['type' => 'text', 'text' => ' or PHPUnit syntax']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Unit tests that use any Laravel facade need '], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'uses(Tests\\TestCase::class)'], ['type' => 'text', 'text' => ' — see CLAUDE.md for the gotcha']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Always use factories, never manually insert model data']]]]],
                ]],
            ],
        ]);
    }

    private function contentSubmittingPr(): string
    {
        return json_encode([
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'Submitting a PR']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Before opening a pull request, run the full checklist:']]],
                ['type' => 'bulletList', 'content' => [
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'php artisan test'], ['type' => 'text', 'text' => ' — zero failures']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'vendor/bin/pint --dirty'], ['type' => 'text', 'text' => ' — code formatted']]]]],
                    ['type' => 'listItem', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'composer analyse'], ['type' => 'text', 'text' => ' — Larastan passes']]]]],
                ]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'PR titles should be short and descriptive. Reference the milestone and task number in the description. One logical change per PR — avoid bundling unrelated fixes.']]],
            ],
        ]);
    }

    private function contentFaq(): string
    {
        return json_encode([
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => 'FAQ']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Can I use Paige for free?']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Yes. Paige is open-source. Self-hosting is free forever. A hosted plan (Pro/Business) will be available for teams who prefer not to run their own infrastructure.']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Does Paige support real-time collaboration?']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Not yet. Milestone 1 records a "someone is editing" warning in Redis. Full Y.js collaborative editing is planned for a future milestone.']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Which databases are supported?']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'MySQL 8+ only in Milestone 1. The page tree uses recursive CTEs ('], ['type' => 'text', 'marks' => [['type' => 'code']], 'text' => 'WITH RECURSIVE'], ['type' => 'text', 'text' => ') which require MySQL 8 or PostgreSQL. PostgreSQL support is planned.']]],
                ['type' => 'heading', 'attrs' => ['level' => 2], 'content' => [['type' => 'text', 'text' => 'Can I import from Confluence or Notion?']]],
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Not yet. Export/import is on the roadmap for Milestone 3. Manual migration via Markdown paste into the editor works today.']]],
            ],
        ]);
    }
}
