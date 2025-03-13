**khanhartisan/laravel-backbone** is a PHP package that provides a structured approach to writing backend code in Laravel. By establishing a set of best practices and optimized conventions, it helps developers build cleaner, more maintainable, and scalable applications with minimal effort.

# Table of Contents

- [Installation](#installation)
- [REST API](#rest-api)
  - [Resource Controller](#resource-controller)
    - [Show API](#show-api)
      - [Visiting the Resource](#visiting-the-resource)
      - [Additional Data](#additional-data)
    - [Store API](#store-api)
      - [Modifying the Store Data](#modifying-the-store-data)
      - [Store with Transaction](#store-with-transaction)
      - [Visiting the Resource before Store](#visiting-the-resource-before-store)
      - [Visiting the Resource after Store](#visiting-the-resource-after-store)
      - [Additional Data for Store Response](#additional-data-for-store-response)
    - [Update API](#update-api)
      - [Modifying the Update Data](#modifying-the-update-data)
      - [Update with Transaction](#update-with-transaction)
      - [Visiting the Resource before Update](#visiting-the-resource-before-update)
      - [Visiting the Resource after Update](#visiting-the-resource-after-update)
      - [Additional Data for Update Response](#additional-data-for-update-response)
    - [Destroy API](#destroy-api)
      - [Destroy with Transaction](#destroy-with-transaction)
      - [Visiting the Resource before Destroy](#visiting-the-resource-before-destroy)
      - [Visiting the Resource after Destroy](#visiting-the-resource-after-destroy)
      - [Additional Data for Destroy Response](#additional-data-for-destroy-response)
    - [Index API](#index-api)
      - [Modifying the Index Query](#modifying-the-index-query)
      - [Visiting the Resource Collection](#visiting-the-resource-collection)
      - [Additional Data for Index Response](#additional-data-for-index-response)
      - [Using a custom Resource Collection](#using-a-custom-resource-collection)
      - [Using a custom Get Query Executor](#using-a-custom-get-query-executor)
    - [Nested API](#nested-api)
  - [Route](#route)
  - [Authorization](#authorization)
- [Repository](#repository)
- [Model Listener](#model-listener)
- [Relation Cascade](#relation-cascade)
- [Counter](#counter)

# Installation

Requirements:
- PHP >= 8.2
- Laravel ^11.0 | ^12.0

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

## Show API

To get a single resource, we need to implement the `show` method in the controller.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends JsonController
{
    // ...
    
    public function show(Request $request, Post $post): PostResource
    {
        // The method below will return the PostResource instance
        /** @var PostResource */
        return $this->jsonShow($request, $post);
    }
}
```

Once the `show` method is implemented, and if you defined the [route](#route), you can now access the resource by sending a `GET` request to the `/posts/{post}` endpoint.

### Visiting the Resource

Before returning the resource, you can visit the Eloquent model instance by implementing the `showModelVisitors` method in the controller.

First, let's create a new visitor class that implements the `ModelVisitorInterface` interface like below:

```php
<?php

namespace App\Models\Visitors;

use App\Models\Post;
use Illuminate\Database\Eloquent\Model;

class PostVisitor implements ModelVisitorInterface
{
    /**
     * Handle an eloquent model
     *
     * @param Model $model
     * @return void
     */
    public function apply(Model $model): void
    {
        // Because PHP doesn't support generic types,
        // so the type of the $model parameter is typed as Model,
        // But in this case, we know that the model is a Post instance.
        /** @var Post $post */
        $post = $model;

        // Do something with the post
        $post->title = strtoupper($post->title);
    }
}
```

Then let's add the visitor to the `showResourceVisitors` method in the controller.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Models\Visitors\PostVisitor;

class PostController extends JsonController
{
    // ...
    
    protected function showModelVisitors(Request $request): array
    {
        return [
            new PostVisitor(),
        ];
    }
}
```

Additionally, you can use a closure to visit the model directly in the `showModelVisitors` method. The closure must accept one parameter: the eloquent model instance.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Models\Post;

class PostController extends JsonController
{
    // ...
    
    protected function showModelVisitors(Request $request): array
    {
        return [
            fn (Post $post) => $post->title = strtoupper($post->title),
        ];
    }
}
```

### Additional Data

You can add [additional data](https://laravel.com/docs/eloquent-resources#adding-meta-data-when-constructing-resources) to the json response data by implementing the `showAdditional` method in the controller.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends JsonController
{
    // ...
    
    protected function showAdditional(Request $request, Post $post): array
    {
        return [
            'custom_key' => 'custom_value',
            'meta' => [
                'key' => 'value',
            ]
        ];
    }
}
```

## Store API

To create a new resource, we need to implement the `store` method in the controller.

Before that, you need to make sure that you have defined the `$fillable` property in the model class. For security reasons, only the attributes in the `$fillable` property will be allowed to be stored.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'title',
        'content',
        'status',
    ];
}
```

Now, let's create a new request class using the `php artisan make:request` command.

```bash
php artisan make:request StorePostRequest
```

Open the `StorePostRequest` class and add the validation rules.

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        // You may want to add an authorization logic here,
        // or you can leave it as true and implement the authorization in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:65535'],
            'status' => ['required', 'string', 'in:draft,published,archived'],
        ];
    }
}
```

Then, implement the `store` method in the controller.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Http\Resources\PostResource;
use App\Models\Post;

class PostController extends JsonController
{
    // ...
    
    public function store(StorePostRequest $request): PostResource
    {
        return $this->jsonStore($request);
    }
}
```

Once the `store` method is implemented, and if you defined the [route](#route), you can now create a new resource by sending a `POST` request to the `/posts` endpoint.

### Modifying the Store Data

Sometimes you may want to modify the data before storing it in the database. You can simply pass your array data as the second argument to the `jsonStore` method like below:

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Http\Requests\StorePostRequest;
use App\Http\Resources\PostResource;

class PostController extends JsonController
{
    // ...
    
    public function store(StorePostRequest $request): PostResource
    {
        $validatedData = $request->validated();
        
        // Modify the data
        $validatedData['title'] = strtoupper($validatedData['title']);
        
        return $this->jsonStore($request, $validatedData);
    }
}
```

### Store with Transaction

You can decide whether to use a transaction when storing the data by implementing the `storeWithTransaction` method in the controller. By default, the transaction is enabled.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Http\Requests\StorePostRequest;
use App\Http\Resources\PostResource;

class PostController extends JsonController
{
    // ...
    
    protected function storeWithTransaction(StorePostRequest $request): bool
    {
        return true; // default is true
    }
}
```

### Visiting the Resource before Store

Before the `save()` method is called, you can visit the Eloquent model instance by implementing the `storeResourceSavingVisitors` method in the controller.

This method returns an array of visitor instances or closures. It is similar to the [showResourceVisitors](#visiting-the-resource) method.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Models\Visitors\PostVisitor;

class PostController extends JsonController
{
    // ...
    
    protected function storeResourceSavingVisitors(Request $request): array
    {
        return [
            new PostVisitor(),
            fn (Post $post) => $post->title = strtoupper($post->title),
        ];
    }
}
```

### Visiting the Resource after Store

Just like [visiting the resource before store](#visiting-the-resource-before-store), you can visit the Eloquent model instance after the `save()` method is called by implementing the `storeResourceSavedVisitors` method in the controller.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Models\Visitors\PostVisitor;

class PostController extends JsonController
{
    // ...
    
    protected function storeResourceSavedVisitors(Request $request): array
    {
        return [
            new PostVisitor(),
            fn (Post $post) => $post->title = strtoupper($post->title),
        ];
    }
}
```

### Additional Data for Store Response

Just like [additional data](#additional-data) for the show API, you can add additional data to the store response by implementing the `storeAdditional` method in the controller.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends JsonController
{
    // ...
    
    protected function storeAdditional(Request $request, Post $post): array
    {
        return [
            'custom_key' => 'custom_value',
            'meta' => [
                'key' => 'value',
            ]
        ];
    }
}
```

## Update API

To update an existing resource, we need to implement the `update` method in the controller.

Before that, you need to make sure that you have defined the `$fillable` property in the model class just as we did in the [store API](#store-api).

Now, let's create a new request class using the `php artisan make:request` command.

```bash
php artisan make:request UpdatePostRequest
```

Open the `UpdatePostRequest` class and add the validation rules.

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        // You may want to add an authorization logic here,
        // or you can leave it as true and implement the authorization in the controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['string', 'max:255'],
            'content' => ['string', 'max:65535'],
            'status' => ['string', 'in:draft,published,archived'],
        ];
    }
}
```

Then, implement the `update` method in the controller.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;

class PostController extends JsonController
{
    // ...
    
    public function update(UpdatePostRequest $request, Post $post): PostResource
    {
        return $this->jsonUpdate($request, $post);
    }
}
```

Once the `update` method is implemented, and if you defined the [route](#route), you can now update the resource by sending a `PATCH` request to the `/posts/{post}` endpoint.

### Modifying the Update Data

Sometimes you may want to modify the data before updating it in the database. You can simply pass your array data as the third argument to the `jsonUpdate` method like below:

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;

class PostController extends JsonController
{
    // ...
    
    public function update(UpdatePostRequest $request, Post $post): PostResource
    {
        $validatedData = $request->validated();
        
        // Modify the data
        $validatedData['title'] = strtoupper($validatedData['title']);
        
        return $this->jsonUpdate($request, $post, $validatedData);
    }
}
```

### Update with Transaction

You can decide whether to use a transaction when updating the data by implementing the `updateWithTransaction` method in the controller. By default, the transaction is enabled.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostResource;

class PostController extends JsonController
{
    // ...
    
    protected function updateWithTransaction(UpdatePostRequest $request): bool
    {
        return true; // default is true
    }
}
```

### Visiting the Resource before Update

Before the `save()` method is called, you can visit the Eloquent model instance by implementing the `updateResourceSavingVisitors` method in the controller.

This method returns an array of visitor instances or closures. It is similar to the [showResourceVisitors](#visiting-the-resource) method.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Models\Visitors\PostVisitor;

class PostController extends JsonController
{
    // ...
    
    protected function updateResourceSavingVisitors(Request $request): array
    {
        return [
            new PostVisitor(),
            fn (Post $post) => $post->title = strtoupper($post->title),
        ];
    }
}
```

### Visiting the Resource after Update

Just like [visiting the resource after store](#visiting-the-resource-after-store), you can visit the Eloquent model instance after the `save()` method is called by implementing the `updateResourceSavedVisitors` method in the controller.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Models\Visitors\PostVisitor;

class PostController extends JsonController
{
    // ...
    
    protected function updateResourceSavedVisitors(Request $request): array
    {
        return [
            new PostVisitor(),
            fn (Post $post) => $post->title = strtoupper($post->title),
        ];
    }
}
```

### Additional Data for Update Response

Just like [additional data](#additional-data) for the show API, you can add additional data to the update response by implementing the `updateAdditional` method in the controller.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Models\Post;

class PostController extends JsonController
{
    // ...
    
    protected function updateAdditional(Request $request, Post $post): array
    {
        return [
            'custom_key' => 'custom_value',
            'meta' => [
                'key' => 'value',
            ]
        ];
    }
}
```

## Destroy API

To delete an existing resource, we need to implement the `destroy` method in the controller.

This method will return the deleted resource with a `200` status code.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends JsonController
{
    // ...
    
    public function destroy(Request $request, Post $post): PostResource
    {
        $this->jsonDestroy($request, $post);
    }
}
```

Once the `destroy` method is implemented, and if you defined the [route](#route), you can now delete the resource by sending a `DELETE` request to the `/posts/{post}` endpoint.

### Destroy with Transaction

You can decide whether to use a transaction when deleting the resource by implementing the `destroyWithTransaction` method in the controller. By default, the transaction is enabled.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends JsonController
{
    // ...
    
    protected function destroyWithTransaction(Request $request): bool
    {
        return true; // default is true
    }
}
```

### Visiting the Resource before Destroy

Before the `delete()` method is called, you can visit the Eloquent model instance by implementing the `destroyResourceDeletingVisitors` method in the controller.

This method returns an array of visitor instances or closures. It is similar to the [showResourceVisitors](#visiting-the-resource) method.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Models\Visitors\PostVisitor;

class PostController extends JsonController
{
    // ...
    
    protected function destroyResourceDeletingVisitors(Request $request): array
    {
        return [
            new PostVisitor(),
            fn (Post $post) => $post->title = strtoupper($post->title),
        ];
    }
}
```

### Visiting the Resource after Destroy

Just like [visiting the resource before destroy](#visiting-the-resource-before-destroy), you can visit the Eloquent model instance after the `delete()` method is called by implementing the `destroyResourceDeletedVisitors` method in the controller.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Models\Visitors\PostVisitor;

class PostController extends JsonController
{
    // ...
    
    protected function destroyResourceDeletedVisitors(Request $request): array
    {
        return [
            new PostVisitor(),
            fn (Post $post) => $post->title = strtoupper($post->title),
        ];
    }
}
```

### Additional Data for Destroy Response

Just like [additional data](#additional-data) for the show API, you can add additional data to the destroy response by implementing the `destroyAdditional` method in the controller.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends JsonController
{
    // ...
    
    protected function destroyAdditional(Request $request, Post $post): array
    {
        return [
            'custom_key' => 'custom_value',
            'meta' => [
                'key' => 'value',
            ]
        ];
    }
}
```

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

The most common use case is to filter the resources based on the query parameters. For example, you can filter the posts by status.

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
use KhanhArtisan\LaravelBackbone\Eloquent\CollectionVisitorInterface;

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

### Additional Data for Index Response

You can add [additional data](https://laravel.com/docs/eloquent-resources#adding-meta-data-when-constructing-resources) to the json response data by implementing the `indexAdditional` method in the controller.

Different from the [additional data](#additional-data) for the show API, the `indexAdditional` method will accept two parameters: the request instance and the `GetData` instance.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Models\Post;
use Illuminate\Http\Request;
use KhanhArtisan\LaravelBackbone\Eloquent\GetData;

class PostController extends JsonController
{
    // ...
    
    protected function indexAdditional(Request $request, GetData $getData): array
    {
        return [
            'custom_key' => 'custom_value',
            'meta' => [
                'total' => $getData->total(),
            ],
            'additional' => $getData->additional(),
            'resources_in_this_page' => $getData->getCollection()->count(),
        ];
    }
}
```

### Using a Custom Resource Collection

By default, the `jsonIndex` method will use the [collection()](https://laravel.com/docs/eloquent-resources#resource-collections) method from the resource class defined in the `resourceClass` method of your controller. 

If you want to use a custom resource collection, you can define the `resourceCollectionClass` method in the controller.

First we need to create a new resource collection class using the `php artisan make:resource` command.

```bash
php artisan make:resource PostCollection
```

Then register the resource collection class in the `resourceCollectionClass` method in the controller.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Http\Resources\PostCollection;
use Illuminate\Http\Request;

class PostController extends JsonController
{
    // ...
    
    protected function resourceCollectionClass(Request $request): string
    {
        return PostCollection::class;
    }
}
```

### Using a custom Get Query Executor

Get Query Executor is a class that executes the query to retrieve the resources from the database and return an instance of `GetData`.

`GetData` is a class that contains the collection of resources, the total number of resources, and additional data that you may want to add.

Get Query Executor must implement the `GetQueryExecutorInterface` interface.

Let's create a new query executor:

```php
<?php

namespace App\GetQueryExecutors;

use Illuminate\Database\Eloquent\Builder;
use KhanhArtisan\LaravelBackbone\Eloquent\GetData;
use KhanhArtisan\LaravelBackbone\Eloquent\GetQueryExecutorInterface;

class TestQueryExecutor implements GetQueryExecutorInterface
{
    public function execute(Builder $query): Builder
    {
        return new GetData(
            $query->get(),
            $query->count(),
            ['custom_key' => 'custom_value']
        );
    }
}
```

Then register the query executor in the `indexGetQueryExecutor` method in the controller.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\GetQueryExecutors\TestQueryExecutor;
use Illuminate\Http\Request;
use KhanhArtisan\LaravelBackbone\Eloquent\GetQueryExecutorInterface;

class PostController extends JsonController
{
    // ...
    
    protected function indexGetQueryExecutor(Request $request): GetQueryExecutorInterface
    {
        return new TestQueryExecutor();
    }
}
```

The default GetQueryExecutor will use the [paginate()](https://laravel.com/docs/eloquent-resources#pagination) method to retrieve the resources from the database.

## Nested API

We can also implement [Nested API Resources](https://laravel.com/docs/controllers#restful-nested-resources) using this package.

Simply create a [Resource Controller](#resource-controller) like we did above, and then define the nested route just like you would in Laravel.

Then, inside your controller methods, you will need to accept the parent resource as a parameter.

Continue from the `PostController` example above, let's create a new controller for the `Comment` model.

Take a closer look at the `indexQueryScopes` method in the `CommentController` below. We need to define a query scope to filter the comments by the parent resource (here is the post).

For other methods like `show`, `store`, `update`, `destroy`, you can use the Laravel's [scoped method](https://laravel.com/docs/controllers#restful-scoping-resource-routes) when registering the route to scope the comment by the post.

```php
<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Http\Resources\CommentResource;

class CommentController extends JsonController
{
    protected function modelClass(): string
    {
        return Comment::class;
    }

    protected function resourceClass(): string
    {
        return CommentResource::class;
    }
    
    public function index(Request $request, Post $post): ResourceCollection    
    {
        // You may authorize the request against the parent resource
        $this->authorize('view', $post);
   
        return $this->jsonIndex($request);
    }
    
    // IMPORTANT:
    // We need to define a query scope to filter the comments by the parent resource (here is the post)
    protected function indexQueryScopes(Request $request): array
    {
        return [
            function (Builder $query, Comment $commentModel) use ($request) {
                
                // Retrieve the post from the request
                /** @var Post $post */
                $post = $request->route('post');
            
                // Filter the comments by the post id
                $query->where('post_id', $post->id);
            },
        ];
    }
    
    public function store(StoreCommentRequest $request, Post $post): CommentResource
    {
        // You may authorize the request against the parent resource
        $this->authorize('view', $post);
        
        // And then automatically set the post_id attribute
        $data = $request->validated();
        $data['post_id'] = $post->id;
    
        return $this->jsonStore($request, $data);
    }
    
    public function show(Request $request, Post $post, Comment $comment): CommentResource
    {
        // You may authorize the request against the parent resource
        $this->authorize('view', $post);

        return $this->jsonShow($request, $comment);
    }
    
    public function update(UpdateCommentRequest $request, Post $post, Comment $comment): CommentResource
    {
        // You may authorize the request against the parent resource,
        // For example, you may want to check if the user can update the post in order to update the comment
        $this->authorize('update', $post);

        return $this->jsonUpdate($request, $comment);
    }
    
    public function destroy(Request $request, Post $post, Comment $comment): CommentResource
    {
        // You may authorize the request against the parent resource
        // For example, you may want to check if the user can update the post in order to delete the comment
        $this->authorize('update', $post);

        return $this->jsonDestroy($request, $comment);
    }
}
```

You can also use the Laravel's [shallow nesting](https://laravel.com/docs/controllers#shallow-nesting) feature and it will work just fine with this package.

# ...Updating...