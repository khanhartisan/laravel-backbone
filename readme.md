# Laravel Backbone

**khanhartisan/laravel-backbone** is a Laravel package that provides structured conventions and reusable building blocks for backend development. It helps you ship consistent JSON APIs, organize Eloquent side effects, handle soft-delete cascades at scale, and record high-volume counters — with minimal boilerplate.

## Requirements

| Dependency | Version |
|---|---|
| PHP | `^8.2` |
| Laravel | `^11.0`, `^12.0`, or `^13.0` |

## Features

- **JsonController** — Convention-based REST API controllers with hooks for validation, transactions, query scopes, and response metadata
- **Repository** — A thin data-access layer used by JsonController, swappable per resource
- **Model Listeners** — Priority-ordered, event-scoped listeners as an alternative to monolithic model observers
- **Relation Cascade** — Application-layer, chunked cascade delete/restore for soft-deleted models
- **Counter** — Redis-backed recording with batched database persistence for high-traffic metrics
- **JsonApiTest** — One-liner CRUD feature tests for JSON API endpoints

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [JsonController](#jsoncontroller)
  - [Setup](#setup)
  - [Routes](#routes)
  - [CRUD Endpoints](#crud-endpoints)
  - [Extension Hooks](#extension-hooks)
  - [Resource Visitors](#resource-visitors)
  - [Additional Response Data](#additional-response-data)
  - [Index: Filtering & Pagination](#index-filtering--pagination)
  - [Nested Resources](#nested-resources)
  - [Authorization](#authorization)
- [Repository](#repository)
- [Model Listeners](#model-listeners)
- [Relation Cascade](#relation-cascade)
- [Counter](#counter)
- [Testing](#testing)
- [Artisan Commands](#artisan-commands)
- [Contributing](#contributing)

---

## Installation

Install via Composer:

```bash
composer require khanhartisan/laravel-backbone
```

The package auto-registers `KhanhArtisan\LaravelBackbone\BackboneServiceProvider`. No manual registration is required.

---

## Quick Start

**1. Create a controller and API resource:**

```bash
php artisan make:controller PostController
php artisan make:resource PostResource
```

**2. Extend `JsonController` and wire up your model:**

```php
<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use KhanhArtisan\LaravelBackbone\Http\Controllers\JsonController;

class PostController extends JsonController
{
    protected function modelClass(): string
    {
        return Post::class;
    }

    protected function resourceClass(): string
    {
        return PostResource::class;
    }

    public function index(Request $request): ResourceCollection
    {
        return $this->jsonIndex($request);
    }

    public function show(Request $request, Post $post): PostResource
    {
        return $this->jsonShow($request, $post);
    }

    public function store(StorePostRequest $request): PostResource
    {
        return $this->jsonStore($request);
    }

    public function update(UpdatePostRequest $request, Post $post): PostResource
    {
        return $this->jsonUpdate($request, $post);
    }

    public function destroy(Request $request, Post $post): PostResource
    {
        return $this->jsonDestroy($request, $post);
    }
}
```

**3. Register routes:**

```php
Route::resource('posts', PostController::class);
```

That is the full wiring for a paginated index, show, create, update, and delete API backed by Laravel API Resources.

---

## JsonController

`JsonController` is the core abstraction for building JSON REST APIs. Each public controller method delegates to a `json*` helper; customization happens through protected hook methods rather than overriding core logic.

### Setup

Your controller must:

1. Extend `KhanhArtisan\LaravelBackbone\Http\Controllers\JsonController`
2. Implement `modelClass()` returning the Eloquent model FQCN
3. Optionally override `resourceClass()` (defaults to `JsonResource::class`)
4. Optionally override `resourceCollectionClass()` for a custom collection resource
5. Optionally override `repository()` to use a custom repository

Ensure your model defines `$fillable` — only fillable attributes are persisted on store and update.

### Routes

Use Laravel's standard resource routing:

```php
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;

Route::resource('posts', PostController::class);
Route::resource('posts.comments', CommentController::class);
Route::resource('posts.comments', CommentController::class)->shallow();
```

### CRUD Endpoints

#### Index — `GET /posts`

```php
public function index(Request $request): ResourceCollection
{
    return $this->jsonIndex($request);
}
```

Uses `SimplePaginationExecutor` by default (Laravel's `paginate()`). Returns a paginated `ResourceCollection`.

#### Show — `GET /posts/{post}`

```php
public function show(Request $request, Post $post): PostResource
{
    return $this->jsonShow($request, $post);
}
```

Applies show visitors, then wraps the model in your API resource.

#### Store — `POST /posts`

Create a Form Request with validation rules, then:

```php
public function store(StorePostRequest $request): PostResource
{
    return $this->jsonStore($request);
}
```

Pass modified data as the second argument when needed:

```php
$data = $request->validated();
$data['user_id'] = $request->user()->id;

return $this->jsonStore($request, $data);
```

Returns a `JsonResource` wrapping the created model. Set an explicit status code in your controller if you need **201 Created** (the bundled `JsonApiTest` helper expects `201` by default).

#### Update — `PATCH /posts/{post}`

```php
public function update(UpdatePostRequest $request, Post $post): PostResource
{
    return $this->jsonUpdate($request, $post);
}
```

Optionally pass modified validated data as the third argument:

```php
return $this->jsonUpdate($request, $post, $request->validated());
```

#### Destroy — `DELETE /posts/{post}`

```php
public function destroy(Request $request, Post $post): PostResource
{
    return $this->jsonDestroy($request, $post);
}
```

Returns the deleted resource with a **200** status code.

### Extension Hooks

Override these protected methods to customize behavior without touching the core `json*` methods.

| Hook | Used by | Default | Purpose |
|---|---|---|---|
| `storeWithTransaction()` | Store | `true` | Wrap create in a DB transaction |
| `updateWithTransaction()` | Update | `true` | Wrap update in a DB transaction |
| `destroyWithTransaction()` | Destroy | `true` | Wrap delete in a DB transaction |
| `showResourceVisitors()` | Show | `[]` | Transform model before show response |
| `storeResourceSavingVisitors()` | Store | `[]` | Transform model before `save()` on create |
| `storeResourceSavedVisitors()` | Store | show visitors | Transform model after `save()` on create |
| `updateResourceSavingVisitors()` | Update | `[]` | Transform model before `save()` on update |
| `updateResourceSavedVisitors()` | Update | show visitors | Transform model after `save()` on update |
| `destroyResourceDeletingVisitors()` | Destroy | `[]` | Transform model before `delete()` |
| `destroyResourceDeletedVisitors()` | Destroy | show visitors | Transform model after `delete()` |
| `showAdditional()` | Show | `[]` | Extra top-level JSON keys on show |
| `storeAdditional()` | Store | `showAdditional()` | Extra keys on store response |
| `updateAdditional()` | Update | `showAdditional()` | Extra keys on update response |
| `destroyAdditional()` | Destroy | `showAdditional()` | Extra keys on destroy response |
| `indexQueryScopes()` | Index | `[]` | Filter/sort the index query |
| `indexCollectionVisitors()` | Index | `[]` | Transform the result collection |
| `indexGetQueryExecutor()` | Index | `SimplePaginationExecutor` | Control how results are fetched |
| `indexAdditional()` | Index | `$getData->additional()` | Extra keys on index response |
| `repository()` | All | Default `Repository` | Swap the data-access layer |

> **Note:** Store, update, and destroy responses inherit `showAdditional()` and `showResourceVisitors()` by default unless you override the action-specific hooks.

### Resource Visitors

Visitors let you mutate a model (or collection) at defined lifecycle points. They can be dedicated classes or inline closures.

**Single model visitor** — implement `ResourceVisitorInterface`:

```php
<?php

namespace App\Models\Visitors;

use App\Models\Post;
use Illuminate\Database\Eloquent\Model;
use KhanhArtisan\LaravelBackbone\Eloquent\ResourceVisitorInterface;

class PostVisitor implements ResourceVisitorInterface
{
    public function apply(Model $model): void
    {
        /** @var Post $post */
        $post = $model;
        $post->title = strtoupper($post->title);
    }
}
```

Register it in the controller:

```php
protected function showResourceVisitors(Request $request): array
{
    return [
        new PostVisitor(),
        fn (Post $post) => $post->loadCount('comments'),
    ];
}
```

**Collection visitor** — implement `CollectionVisitorInterface`:

```php
<?php

namespace App\Models\Visitors;

use Illuminate\Database\Eloquent\Collection;
use KhanhArtisan\LaravelBackbone\Eloquent\CollectionVisitorInterface;

class PostCollectionVisitor implements CollectionVisitorInterface
{
    public function apply(Collection $collection): void
    {
        $collection->load('author');
    }
}
```

```php
protected function indexCollectionVisitors(Request $request): array
{
    return [new PostCollectionVisitor()];
}
```

### Additional Response Data

Add [meta data to API resources](https://laravel.com/docs/eloquent-resources#adding-meta-data-when-constructing-resources) via the `*Additional()` hooks:

```php
protected function showAdditional(Request $request, Model $resource): array
{
    /** @var Post $post */
    $post = $resource;

    return [
        'meta' => ['view_count' => $post->views],
    ];
}
```

For index responses, `indexAdditional()` receives a `GetData` instance:

```php
use KhanhArtisan\LaravelBackbone\Eloquent\GetData;

protected function indexAdditional(Request $request, GetData $getData): array
{
    return [
        'meta' => [
            'total' => $getData->total(),
            'count' => $getData->getCollection()->count(),
        ],
    ];
}
```

### Index: Filtering & Pagination

#### Query scopes

Return an array of scopes from `indexQueryScopes()`. Keys are identifiers; values are `Scope` instances or closures `(Builder $query, Model $model) => void`.

**Using a scope class:**

```bash
php artisan make:scope PostStatusScope
```

```php
<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class PostStatusScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $status = request()->query('status');

        if (!$status || !in_array($status, ['draft', 'published', 'archived'])) {
            return;
        }

        $builder->where('status', $status);
    }
}
```

```php
protected function indexQueryScopes(Request $request): array
{
    return [
        PostStatusScope::class => new PostStatusScope(),
    ];
}
```

**Using a closure:**

```php
protected function indexQueryScopes(Request $request): array
{
    return [
        'post-title' => function (Builder $query, Post $post) use ($request) {
            if ($title = $request->query('title')) {
                $query->where('title', 'like', "%{$title}%");
            }
        },
    ];
}
```

#### Custom resource collection

```php
protected function resourceCollectionClass(): string
{
    return PostCollection::class;
}
```

#### Custom query executor

Implement `GetQueryExecutorInterface` to control how records are fetched and counted:

```php
<?php

namespace App\GetQueryExecutors;

use Illuminate\Database\Eloquent\Builder;
use KhanhArtisan\LaravelBackbone\Eloquent\GetData;
use KhanhArtisan\LaravelBackbone\Eloquent\GetQueryExecutorInterface;

class PostQueryExecutor implements GetQueryExecutorInterface
{
    public function execute(Builder $query): GetData
    {
        $paginator = $query->paginate(25);

        return new GetData(
            collect($paginator->items()),
            $paginator->total(),
            ['per_page' => 25]
        );
    }
}
```

```php
protected function indexGetQueryExecutor(Request $request): GetQueryExecutorInterface
{
    return new PostQueryExecutor();
}
```

Built-in executors:

| Class | Behavior |
|---|---|
| `SimplePaginationExecutor` | `paginate()` — **default** |
| `DefaultExecutor` | `get()` + `count()` — no pagination |

### Nested Resources

Nested APIs work with standard Laravel nested routing. Scope child records in `indexQueryScopes()` and authorize against the parent in each action:

```php
public function index(Request $request, Post $post): ResourceCollection
{
    $this->authorize('view', $post);

    return $this->jsonIndex($request);
}

protected function indexQueryScopes(Request $request): array
{
    return [
        'by-post' => function (Builder $query, Comment $comment) use ($request) {
            $post = $request->route('post');
            $query->where('post_id', $post->id);
        },
    ];
}

public function store(StoreCommentRequest $request, Post $post): CommentResource
{
    $this->authorize('view', $post);

    $data = $request->validated();
    $data['post_id'] = $post->id;

    return $this->jsonStore($request, $data);
}

public function show(Request $request, Post $post, Comment $comment): CommentResource
{
    $this->authorize('view', $post);

    return $this->jsonShow($request, $comment);
}
```

For show, update, and destroy on nested routes, use Laravel's [scoped bindings](https://laravel.com/docs/routing#scoped-nested-resources) so the child is automatically constrained to the parent.

### Authorization

Authorization is handled in your controller using standard Laravel gates and policies:

```php
public function show(Request $request, Post $post): PostResource
{
    $this->authorize('view', $post);

    return $this->jsonShow($request, $post);
}
```

---

## Repository

The repository abstracts data access from controller logic. `JsonController` uses the default `KhanhArtisan\LaravelBackbone\Eloquent\Repository` unless you override `repository()`.

To customize, extend `Repository` and implement `RepositoryInterface`:

```php
<?php

namespace App\Repositories;

use App\Models\Post;
use KhanhArtisan\LaravelBackbone\Eloquent\Repository;
use KhanhArtisan\LaravelBackbone\Eloquent\RepositoryInterface;

class PostRepository extends Repository implements RepositoryInterface
{
    public function __construct()
    {
        parent::__construct(Post::class);
    }

    // Override any RepositoryInterface method as needed
}
```

Register it in your controller:

```php
protected function repository(): RepositoryInterface
{
    return $this->repository ?? $this->repository = new PostRepository();
}
```

### Available methods

| Method | Description |
|---|---|
| `modelClass()` | Returns the bound model class |
| `query()` | New Eloquent query builder |
| `applyQueryScopes()` | Apply scopes to a builder |
| `applyResourceVisitors()` | Run visitors on a single model |
| `applyCollectionVisitors()` | Run visitors on a collection |
| `find()` | Find by ID with optional scopes and visitors |
| `get()` | Fetch a collection via a query executor |
| `create()` | Create a model with before/after visitors |
| `save()` | Update a model with before/after visitors |
| `delete()` | Delete by ID or model instance |
| `massDelete()` | Delete all records matching a scoped query |
| `withoutEvents()` | Run a callback without firing model events |

---

## Model Listeners

Laravel observers work well for simple cases, but complex domain logic can become hard to maintain in a single class. Model Listeners provide a structured alternative: one class per concern, with explicit event subscriptions and priority ordering.

### Setup

**1. Mark the model as observable:**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use KhanhArtisan\LaravelBackbone\ModelListener\ObservableModel;

class Post extends Model implements ObservableModel
{
    //
}
```

**2. Generate a listener:**

```bash
php artisan make:model-listener PostNotificationListener --model=Post --events=created,deleted
```

**3. Implement the generated class:**

```php
<?php

namespace App\ModelListeners\Post;

use App\Models\Post;
use KhanhArtisan\LaravelBackbone\ModelListener\ModelListener;
use KhanhArtisan\LaravelBackbone\ModelListener\ModelListenerInterface;

class PostNotificationListener extends ModelListener implements ModelListenerInterface
{
    public function priority(): int
    {
        return 0; // Higher values run first
    }

    public function modelClass(): string
    {
        return Post::class;
    }

    public function events(): array
    {
        return ['created', 'deleted'];
    }

    protected function _handle(Post $post, string $event): void
    {
        if ($event === 'created') {
            // Notify subscribers
        }

        if ($event === 'deleted') {
            // Clean up related data
        }
    }
}
```

**4. Verify registration:**

```bash
php artisan model-listener:show
```

If the model does not implement `ObservableModel`, the command warns that listeners may not fire.

### Custom model paths

By default, models are discovered in `app/Models` and listeners in `app/ModelListeners`. Register additional paths in `AppServiceProvider`:

```php
use KhanhArtisan\LaravelBackbone\ModelListener\Observer;

Observer::registerModelsFrom(
    $this->app->getNamespace().'CustomModels',
    app_path('CustomModels')
);
```

### Singleton listeners

Override `isSingleton()` to return `false` if a fresh listener instance should be created for each event dispatch (default is `true`).

---

## Relation Cascade

Database-level `ON DELETE CASCADE` does not work with soft deletes, can lock large tables, and is unavailable on some databases. Relation Cascade performs cascade operations in the application layer, processing records in configurable chunks via queued jobs.

> Only models using Laravel's [SoftDeletes](https://laravel.com/docs/eloquent#soft-deleting) trait can use this feature.

### Migration

Add soft deletes and cascade tracking columns using the provided Blueprint macro:

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    // ... other columns

    $table->softDeletes();
    $table->cascades(); // cascade_status, cascade_updated_at, and index

    $table->index(['cascade_status', 'deleted_at']);
});
```

The `cascades()` macro adds:

- `cascade_status` — tracks idle, pending delete, pending restore, etc.
- `cascade_updated_at` — last cascade state change
- An index on `(cascade_status, cascade_updated_at)`

### Model configuration

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use KhanhArtisan\LaravelBackbone\RelationCascade\Cascades;
use KhanhArtisan\LaravelBackbone\RelationCascade\CascadeDetails;
use KhanhArtisan\LaravelBackbone\RelationCascade\ShouldCascade;

class Post extends Model implements ShouldCascade
{
    use SoftDeletes, Cascades;

    public function getCascadeDetails(): CascadeDetails|array
    {
        return [
            (new CascadeDetails($this->comments()))
                ->setShouldDelete(true)
                ->setShouldRestore(true)
                ->setShouldForceDelete(false)
                ->setShouldUseTransaction(true)
                ->setShouldDeletePerItem(true),
        ];
    }

    public function autoForceDeleteWhenAllRelationsAreDeleted(): bool
    {
        return false;
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
```

| Option | Default | Description |
|---|---|---|
| `setShouldDelete()` | `true` | Cascade soft-delete to related records |
| `setShouldRestore()` | `true` | Cascade restore when parent is restored |
| `setShouldForceDelete()` | `false` | Force-delete related records instead of soft-deleting |
| `setShouldUseTransaction()` | `true` | Wrap each cascade batch in a transaction |
| `setShouldDeletePerItem()` | `true` | Delete one-by-one (fires events) vs. batch delete |

### Scheduling

Schedule the background jobs in `routes/console.php` or your scheduler:

```php
use Illuminate\Support\Facades\Schedule;
use KhanhArtisan\LaravelBackbone\RelationCascade\Jobs\CascadeDelete;
use KhanhArtisan\LaravelBackbone\RelationCascade\Jobs\CascadeRestore;

$recordsLimit = 10000; // max records per job run
$chunkSize = 100;      // records processed per iteration

Schedule::job(new CascadeDelete($recordsLimit, $chunkSize))->everyMinute();
Schedule::job(new CascadeRestore($recordsLimit, $chunkSize))->everyMinute();
```

When a post is soft-deleted, its comments are queued for cascade deletion. Restoring the post queues a cascade restore.

### Custom model paths

```php
use KhanhArtisan\LaravelBackbone\RelationCascade\RelationCascadeManager;

$this->app->make(RelationCascadeManager::class)->registerModelsFrom(
    $this->app->getNamespace().'CustomModels',
    app_path('CustomModels')
);
```

---

## Counter

The Counter subsystem records high-frequency events (page views, impressions, etc.) in Redis and periodically flushes aggregated data to the database. This avoids write amplification on hot rows during traffic spikes.

> The default recorder driver requires **Redis**. Ensure Redis is configured as your cache (or counter) connection.

### Setup

**1. Publish and run the migration:**

```bash
php artisan vendor:publish --tag=laravel-backbone-counter-migration
php artisan migrate
```

**2. Optionally publish configuration:**

```bash
php artisan vendor:publish --tag=laravel-backbone-counter-config
```

Default configuration (`config/counter.php`):

| Key | Env variable | Default |
|---|---|---|
| `default_recorder` | `COUNTER_DEFAULT_RECORDER` | `redis` |
| `default_store` | `COUNTER_DEFAULT_STORE` | `database` |
| `recorders.redis.connection` | `COUNTER_RECORDER_REDIS_CONNECTION` | `cache` |
| `recorders.redis.expiration` | `COUNTER_RECORDER_REDIS_EXPIRATION` | `86400` |
| `stores.database.connection` | `COUNTER_STORE_DATABASE_CONNECTION` | default DB |
| `stores.database.table` | `COUNTER_STORE_DATABASE_TABLE` | `counter` |

### Recording events

Use the `Recorder` facade in your application code:

```php
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Interval;
use KhanhArtisan\LaravelBackbone\Support\Facades\Counter\Recorder;

public function show(Request $request, Post $post): PostResource
{
    Recorder::record(
        partitionKey: 'post-views',
        interval: Interval::ONE_MINUTE,
        reference: $post->id,
        value: 1,
    );

    return $this->jsonShow($request, $post);
}
```

| Parameter | Description |
|---|---|
| `partitionKey` | Logical grouping for related counters (e.g. `'post-views'`) |
| `interval` | How often data is rolled up to the database |
| `reference` | Entity identifier (e.g. post ID) |
| `value` | Increment amount (default `1`) |
| `shardSize` | Max references per Redis shard (default `1000`) |
| `eventTime` | Unix timestamp override (default: now) |

### Available intervals

| Constant | Roll-up period |
|---|---|
| `Interval::ONE_MINUTE` | Every minute |
| `Interval::FIVE_MINUTES` | Every 5 minutes |
| `Interval::TEN_MINUTES` | Every 10 minutes |
| `Interval::FIFTEEN_MINUTES` | Every 15 minutes |
| `Interval::THIRTY_MINUTES` | Every 30 minutes |
| `Interval::HOURLY` | Every hour |
| `Interval::DAILY` | Every day |
| `Interval::WEEKLY` | Every week |
| `Interval::MONTHLY` | Every month |
| `Interval::YEARLY` | Every year |

Longer intervals reduce database write frequency. Choose based on how fresh your metrics need to be.

### Scheduling background jobs

Flush Redis data to the database and prune old records via scheduled jobs:

```php
use KhanhArtisan\LaravelBackbone\Contracts\Counter\Interval;
use KhanhArtisan\LaravelBackbone\Counter\Jobs\ClearData;
use KhanhArtisan\LaravelBackbone\Counter\Jobs\StoreData;

// Persist recorded counts from Redis to the database
Schedule::job(new StoreData('post-views', Interval::ONE_MINUTE))->everyMinute();

// Prune old counter records (optional)
Schedule::job(new ClearData(
    interval: Interval::DAILY,
    olderThanTime: now()->subMonths(3),
    partitionKey: 'post-views',
))->daily();
```

---

## Testing

The package includes `JsonApiTest` for end-to-end CRUD feature tests against JSON API endpoints.

```bash
php artisan make:test PostApiTest
```

```php
<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use KhanhArtisan\LaravelBackbone\Testing\JsonApiTest;
use KhanhArtisan\LaravelBackbone\Testing\JsonCrudTestData;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_basic_crud(): void
    {
        $testData = (new JsonCrudTestData())
            ->setStoreData(['title' => Str::random()])
            ->setUpdateData(['title' => Str::random()])
            ->actingAs(User::factory()->create());

        (new JsonApiTest($this))->testBasicCrud('/api/posts', $testData);
    }
}
```

`testBasicCrud()` runs store → update → index → show → destroy in sequence, asserting response codes and data by default.

### Customizing expectations

| Method | Default | Description |
|---|---|---|
| `setStoreData()` | — | Payload for POST (required) |
| `setUpdateData()` | — | Payload for PATCH (required) |
| `actingAs()` | guest | Authenticated user |
| `setExpectedStoreResponseCode()` | `201` | Expected POST status |
| `setExpectedUpdateResponseCode()` | `200` | Expected PATCH status |
| `setExpectedIndexResponseCode()` | `200` | Expected GET index status |
| `setExpectedShowResponseCode()` | `200` | Expected GET show status |
| `setExpectedDestroyResponseCode()` | `200` | Expected DELETE status |
| `setExpected*ResponseData()` | auto-match | Override expected JSON body |

Set custom URIs per action (`setStoreUri()`, `setUpdateUri()`, etc.) when testing nested or non-standard routes.

---

## Artisan Commands

| Command | Description |
|---|---|
| `make:model-listener` | Scaffold a model listener class |
| `model-listener:show` | List registered listeners and their priorities |

### `make:model-listener` options

```bash
php artisan make:model-listener PostNotificationListener \
    --model=App\\Models\\Post \
    --events=created,updated,deleted \
    --path=ModelListeners\\Post
```

| Option | Description |
|---|---|
| `--model` | Fully qualified model class |
| `--events` | Comma-separated Eloquent events |
| `--path` | Namespace path under `app/` |

---

## Contributing

Bug reports, feature requests, and pull requests are welcome. Please open an issue before submitting large changes so we can discuss the approach.

When contributing:

1. Follow existing code style and conventions
2. Add or update tests for behavioral changes
3. Run the test suite before submitting:

```bash
composer test
```

---

## License

This package is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
