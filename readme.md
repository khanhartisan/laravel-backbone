**khanhartisan/laravel-backbone** is a PHP package that provides a structured approach to writing backend code in Laravel. By establishing a set of best practices and optimized conventions, it helps developers build cleaner, more maintainable, and scalable applications with minimal effort.

# Table of Contents

- [Installation](#installation)
- [REST API](#rest-api)
  - [Resource Controller](#resource-controller)
    - [Index API](#index-api)
      - [Modifying the Index Query](#modifying-the-index-query)
      - [Visiting the Resource Collection](#visiting-the-resource-collection)
    - [Show API](#show-api)
    - [Store API](#store-api)
    - [Update API](#update-api)
    - [Destroy API](#destroy-api)
    - [Shallow Nesting API](#shallow-nesting-api)
  - [Route](#route)
  - [Authorization](#authorization)
- [Repository](#repository)
- [Model Listener](#model-listener)
- [Relation Cascade](#relation-cascade)
- [Counter](#counter)

# Installation

Requirements:
- PHP >= 8.2
- Laravel >= 11.0

Install the package via Composer:

```bash
composer require khanhartisan/laravel-backbone
```

# REST API

This package provides a set of tools to help you build RESTful APIs in Laravel with just a few lines of code. It includes a resource controller, authorization, query scopes, and more.

## Resource Controller

To get started, create a new controller normally using the `php artisan make:controller` command.

```bash
php artisan make:controller PostController
```

Now generate a Laravel API Resource using the `php artisan make:resource` command.

```bash
php artisan make:resource PostResource
```

Then extends the controller from the `KhanhArtisan\LaravelBackbone\Http\Controllers\JsonController` class and implement the `modelClass` and `resourceClass` methods.

```php
<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
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
}
```

That's it! We just completed the setup for the `PostController`. Now let's continue reading to make the specific API.

## Index API

To get a list of resources, we need to implement the `index` method in the controller.

We will continue to use the `PostController` above as an example.

```php
<?php

namespace App\Http\Controllers;

// ...
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PostController extends JsonController
{
    // ...
    
    public function index(Request $request): ResourceCollection
    {
        return $this->jsonIndex($request);
    }
}
```

Once the `index` method is implemented, and if you defined the [route](#route), you can now access the list of resources by sending a `GET` request to the `/posts` endpoint.

### Modifying the Index Query

You can modify the query used to fetch the resources by overriding the `indexQueryScopes` method in the controller using the Laravel query Scope classes.

First, let's create a new query scope class using the `php artisan make:scope` command.

```bash
php artisan make:scope PostStatusScope
```

Then, implement the `apply` method in the `PostStatusScope` class like so:

```php
<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class PostStatusScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Get the status from the request
        if (!$status = request()->query('status')
            or !in_array($status, ['draft', 'published', 'archived'])
        ) {
            return;       
        }
        
        // Apply the status filter
        $builder->where('status', $status);
    }
}
```

Finally, let's add the query scope to the `indexQueryScopes` method in the controller.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Models\Scopes\PostStatusScope;
use Illuminate\Http\Request;

class PostController extends JsonController
{
    // ...
    
    public function indexQueryScopes(Request $request): array
    {
        return [
            new PostStatusScope(),
        ];
    }
}
```

You can also use a closure to apply the query scope directly in the `indexQueryScopes` method. The closure must accept two parameters: the query builder and the model instance.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PostController extends JsonController
{
    // ...
    
    public function indexQueryScopes(Request $request): array
    {
        return [
            function (Builder $query, Post $postModel) {

                // Get the status from the request
                if (!$status = request()->query('status')
                    or !in_array($status, ['draft', 'published', 'archived'])
                ) {
                    return;       
                }
                
                // Apply the status filter
                $query->where('status', $status);
            },            
        ];
    }
}
```

### Visiting the Resource Collection

Before returning the resource collection, you can visit the Eloquent Collection instance by implementing the `indexCollectionVisitors` method in the controller.

First, let's create a new visitor class that implements the `CollectionVisitorInterface` interface like below:

```php
<?php

namespace App\Models\Visitors;

use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;

class PostCollectionVisitor implements CollectionVisitorInterface
{
    /**
     * Handle an eloquent collection
     *
     * @param Collection $posts
     * @return void
     */
    public function apply(Collection $posts): void
    {
        // Do something with the collection of posts
        $posts->each(function (Post $post) {
            $post->title = strtoupper($post->title);
        });
    }
}
```

Then let's add the visitor to the `indexCollectionVisitors` method in the controller.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\ModelListeners\PostCollectionVisitor;
use Illuminate\Http\Request;
use KhanhArtisan\LaravelBackbone\Eloquent\CollectionVisitorInterface;

class PostController extends JsonController
{
    // ...
    
    /**
     * @param Request $request
     * @return array<CollectionVisitorInterface>
     */
    protected function indexCollectionVisitors(Request $request): array
    {
        return [
            new PostCollectionVisitor(),
        ];
    }
}
```

Additionally, you can use a closure to visit the collection directly in the `indexCollectionVisitors` method. The closure must accept one parameter: the eloquent collection instance.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use KhanhArtisan\LaravelBackbone\Eloquent\CollectionVisitorInterface;

class PostController extends JsonController
{
    // ...

    protected function indexCollectionVisitors(Request $request): array
    {
        return [
            fn (Collection $posts) => $posts->each(fn (Post $post) => $post->title = strtoupper($post->title)),
        ];
    }
}
```

# ...Updating...