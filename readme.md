**khanhartisan/laravel-backbone** is a PHP package that provides a structured approach to writing backend code in Laravel. By establishing a set of best practices and optimized conventions, it helps developers build cleaner, more maintainable, and scalable applications with minimal effort.

I'm open to bug reports, feature requests, and contributions. Feel free to create an issue or pull request.

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
  - [Authorization](#authorization)
  - [Route](#route)
- [Model Listener](#model-listener)
  - [Creating a Model Listener](#creating-a-model-listener)
  - [Registering Models in a custom path](#registering-models-in-a-custom-path)
- [Relation Cascade](#relation-cascade)
  - [Using Relation Cascade](#using-relation-cascade)
  - [Registering ShouldCascade Models in a custom path](#registering-shouldcascade-models-in-a-custom-path)
- [Repository](#repository)

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

Finally, let's add the query scope to the `indexQueryScopes` method in the controller. This method should return an array of query scopes with the key being the identifier of the scope and the value being the scope instance.

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
            
            // Here we use the class name as the identifier
            get_class($postStatusScope = new PostStatusScope()) => $postStatusScope,
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
            'post-status-scope' => function (Builder $query, Post $postModel) {

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

## Authorization

You can use Laravel's [Authorization](https://laravel.com/docs/authorization) feature to authorize the request.

In this document, we only give some practical examples of how to authorize the request in the controller.

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Models\Post;
use Illuminate\Support\Facades\Gate;

class PostController extends JsonController
{
    // ...
    
    public function show(Request $request, Post $post)
    {
        // Make sure that you defined the gate logic.
        // Now this will throw an exception if the user is not authorized
        Gate::authorize('view-post', $post);
        
        // Or if you defined the PostPolicy
        Gate::authorize('view', $post);
        
        // Or you can also do this
        if ($request->user()->cannot('view', $post)) {
            abort(403);
        }
        
        return $this->jsonShow($request, $post);
    }
}
```

## Route

As we implemented the [Resource Controller](#resource-controller) above, we can now use [Laravel's Route::resource()](https://laravel.com/docs/12.x/controllers#resource-controllers) method to register the routes.

```php
<?php

use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;

// This will register the following routes: show, store, update, destroy, index
Route::resource('posts', PostController::class);

// And for nested resources
Route::resource('posts.comments', CommentController::class);

// Or for shallow nested resources
Route::resource('posts.comments', CommentController::class)->shallow();
```

# Model Listener

Laravel provided the [Model Observers](https://laravel.com/docs/eloquent#observers) feature to listen for Eloquent events. However, if we have a lot of logic to handle, it can be difficult to maintain.

This package provides a more structured approach to handle the Eloquent events by using the Model Listener.

## Creating a Model Listener

Before creating a new listener, you need to update your model to implement the `KhanhArtisan\LaravelBackbone\ModelListener\ObservableModel` interface. This interface is a flag to let the package know that the model should be observed.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use KhanhArtisan\LaravelBackbone\ModelListener\ObservableModel;

class Post extends Model implements ObservableModel
{
    // ...
}
```

Now, create a new listener class using the `php artisan make:model-listener` command.

```bash
php artisan make:model-listener
```

Then it will ask you to enter the listener name, the model class, and the events you want to listen to (separated by commas).

Assume that we just created a new listener class named `PostNotificationListener` for the `Post` model and it listens to the `created` and `deleted` events. Now let's take a look at the generated class.

```php
<?php

namespace App\ModelListeners\Post;

use App\Models\Post;
use KhanhArtisan\LaravelBackbone\ModelListener\ModelListener;
use KhanhArtisan\LaravelBackbone\ModelListener\ModelListenerInterface;

class PostNotification extends ModelListener implements ModelListenerInterface
{
    /**
     * Listeners with higher priority will run first.
     *
     * @return int
     */
    public function priority(): int
    {
        return 0;
    }

    /**
     * Listen to the events of the given model.
     *
     * @return string
     */
    public function modelClass(): string
    {
        return Post::class;
    }

    /**
     * The list of all the events to listen to.
     *
     * @return array<string>
     */
    public function events(): array
    {
        return ["created","deleted"];
    }

    /**
     * Handle the event.
     *
     * @param Post $post
     * @param string $event
     * @return void
     */
    protected function _handle(Post $post, string $event): void
    {
        // Send notification when post is created
        if ($event === 'created') {
            // TODO: Send "created" notification
        }
        
        // Log when post is deleted
        if ($event === 'deleted') {
            // TODO: Send "deleted" notification
        }
    }
}
```

Finally, let's confirm if the listener is registered by using this command

```bash
php artisan model-listener:show
```

If you forgot to implement the `ObservableModel` interface in the model, you will see a warning message: **App\Models\Post model is not registered, the listeners may not be triggered.** Otherwise, you will see the registered listeners sorted by priority (higher priority will run first).

## Registering Models in a custom path

By default, this package will look for models in the `app/Models` directory. If you want to register models in a custom path, you can do it in your `AppServiceProvider.php`

```php
<?php

namespace App\Providers;

// ...
use KhanhArtisan\LaravelBackbone\ModelListener\Observer;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // ...
        Observer::registerModelsFrom(
            // The first parameter is the namespace prefix of the models
            $this->app->getNamespace().'CustomModels', // App\CustomerModels
            
            // The second parameter is the path to the models
            app_path('CustomModels');
        );
    }
}
```

# Relation Cascade

The traditional approach to handling the cascade operation is to use the foreign key constraints with the `ON DELETE CASCADE` option. However, this approach has some limitations, such as the inability to handle the `softDeletes` and the lack of flexibility.

And that may lead to some performance issues, especially when you have a lot of records to delete. Let's say you have a post with a few millions of comments, and you want to delete the post. The `ON DELETE CASCADE` option will delete all the comments in one query, which can be slow. And some databases even don't support foreign key constraints.

This package provides a more flexible approach to handle the cascade operation by using the `Relation Cascade`. It works by performing the cascade operation in the application layer, and in chunks to avoid performance issues.

## Using Relation Cascade

Only models which implement the [Laravel's SoftDeletes](#https://laravel.com/docs/12.x/eloquent#soft-deleting) can use the Relation Cascade feature.

First, you need to add a migration do your model table to support the cascade operation.

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            
            //...
            
            // SoftDeletes is required
            $table->softDeletes();
            
            // Add the cascade columns
            $table->cascades();
            
            // We recommend you to add this index to improve the performance
            $table->index(['cascade_status', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
```

Now, open your model class, implement the `ShouldCascade` interface, add the `Cascades` trait, and implement the `getCascadeDetails()` method.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use KhanhArtisan\LaravelBackbone\RelationCascade\Cascades;
use KhanhArtisan\LaravelBackbone\RelationCascade\CascadeDetails;
use KhanhArtisan\LaravelBackbone\RelationCascade\ShouldCascade;

class Post extends Model implements ShouldCascade
{
    // ...
    use Cascades;
    
    public function getCascadeDetails(): CascadeDetails|array
    {
        return [
        
            // Register the cascade operation for the "comments" relation
            (new CascadeDetails($this->comments()))
                
                // Cascade delete, default is true
                ->setShouldDelete(true)
                
                // Cascade restore, only work if comments also support soft-delete,
                // default is true
                ->setShouldRestore(true)
                
                // If true, the comments will be force deleted instead of soft-delete,
                // default is false
                ->setShouldForceDelete(false) 
                
                // If true, the cascade operation will be wrapped in a transaction,
                // default is true
                ->setShouldUseTransaction(true)
                
                // If true, the cascade operation will be performed per item,
                // If you set it to false, the cascade operation will be performed in batch,
                // which can be faster but the model events will not be triggered.
                // default is true.
                ->setShouldDeletePerItem(true)
        ];
    }
    
    // Optionally, you can define this method to automatically force-delete the model
    // when all the relations are deleted.
    // By default, this method returns false.
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

Finally, you need to schedule two jobs to handle the cascade operation in the background.

```php
<?php

use Illuminate\Support\Facades\Schedule;
use KhanhArtisan\LaravelBackbone\RelationCascade\Jobs\CascadeDelete;
use KhanhArtisan\LaravelBackbone\RelationCascade\Jobs\CascadeRestore;

// Optional, you may define the records limit and the chunk size
$recordsLimit = 10000; // default is 10000, this is the maximum number of records to handle per job
$chunkSize = 100; // default is 100, this is the number of records to handle per execution
Schedule::job(new CascadeDelete($recordsLimit, $chunkSize))->everyMinute();
Schedule::job(new CascadeRestore($recordsLimit, $chunkSize))->everyMinute();
```

Now, when you delete a post, all the comments will be deleted in the background. And when you restore a post, all the comments will be restored.

### Registering ShouldCascade Models in a custom path

By default, this package will look for models in the `app/Models` directory. If you want to register models in a custom path, you can do it in your `AppServiceProvider.php`

```php
<?php

namespace App\Providers;

// ...
use KhanhArtisan\LaravelBackbone\RelationCascade\RelationCascadeManager;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // ...
        
        $this->app->make(RelationCascadeManager::class)
            ->registerModelsFrom(
                // The first parameter is the namespace prefix of the models
                $this->app->getNamespace().'CustomModels', // App\CustomerModels
                
                // The second parameter is the path to the models
                app_path('CustomModels');
            );
    }
}
```

# Repository

The Repository pattern is a design pattern that abstracts the data access logic from the business logic.

Behind the scenes, the [Resource Controller](#resource-controller) above uses the default Repository to handle the data access logic.

However, if you want to customize the Repository, you can create your own Repository class by implementing the `RepositoryInterface`.

Let's create a new Repository class for the `Post` model.

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
    
    // Check the RepositoryInterface for the available methods to override
}
```

By extending the default Repository class, you already implemented the `RepositoryInterface` and you can use the `PostRepository` class in the [Resource Controller](#resource-controller) above like so:

```php
<?php

namespace App\Http\Controllers;

// ...
use App\Repositories\PostRepository;
use KhanhArtisan\LaravelBackbone\Eloquent\RepositoryInterface;

class PostController extends JsonController
{
    // ...

    protected function repository(): RepositoryInterface
    {
        return $this->repository ?? $this->repository = new PostRepository();
    }
}
```

[//]: # (## Counter)

[//]: # ()
[//]: # (When I build my applications, I often need to count the number of views for a post, a product, or for the whole website. And I found that it's not easy to handle the counting operation in a scalable way.)

[//]: # ()
[//]: # (Imagine you have a blog with millions of visitors daily, and you want to count the number of views for each post as well as for the whole website. The simple approach is to increment the counter in the database every time a visitor views the post. However, this approach can be slow and can lead to performance issues.)

[//]: # ()
[//]: # (This package provides a more scalable approach to handle the counting operation, called the **Counter**. It works by storing the counting data in the cache and then periodically updating the database in the background.)

[//]: # ()
[//]: # (### Using Counter)

[//]: # ()
[//]: # (Currently, the Counter feature only support Redis as the cache driver. Make sure you have Redis installed and configured in your Laravel application.)

[//]: # ()
[//]: # (First, let's publish the database migration file.)

[//]: # ()
[//]: # (```bash)

[//]: # (php artisan vendor:publish --tag=laravel-backbone-counter-migrations)

[//]: # (```)

[//]: # ()
[//]: # (Then, run the migration to create the `counter` table.)

[//]: # ()
[//]: # (```bash)

[//]: # (php artisan migrate)

[//]: # (```)

[//]: # ()
[//]: # (Optionally, you can publish the configuration file if you want to customize the configuration. By default, the counter will use Redis from "cache" connection configured in your Laravel application. If you are happy with the default configuration, you can skip this step.)

[//]: # ()
[//]: # (```bash)

[//]: # (php artisan vendor:publish --tag=laravel-backbone-counter-config)

[//]: # (```)

[//]: # ()
[//]: # (Now in your application, you can use the `Counter` facade to increment the counter. Let's get back to the sample PostController above, and we will increment the view counter every time the post is viewed.)

[//]: # ()
[//]: # (```php)

[//]: # (<?php)

[//]: # ()
[//]: # (namespace App\Http\Controllers;)

[//]: # ()
[//]: # (use App\Models\Post;)

[//]: # (use KhanhArtisan\LaravelBackbone\Support\Facades\Counter\Recorder;)

[//]: # (use KhanhArtisan\LaravelBackbone\Contracts\Counter\Interval;)

[//]: # ()
[//]: # (class PostController extends JsonController)

[//]: # ({)

[//]: # (    // ...)

[//]: # (    )
[//]: # (    public function show&#40;Request $request, Post $post&#41;)

[//]: # (    {)

[//]: # (        // Increment the view counter)

[//]: # (        Recorder::record&#40;)

[//]: # (            'post-views', // The "partition" key, you can choose your own key)

[//]: # (            Interval::ONE_MINUTE, // How frequent the data should be updated in the database)

[//]: # (            $post->id // The "reference" key, here we use the post id)

[//]: # (            1, // The increment value, default is 1)

[//]: # (        &#41;;)

[//]: # (        )
[//]: # (        return $this->jsonShow&#40;$request, $post&#41;;)

[//]: # (    })

[//]: # (})

[//]: # (```)

[//]: # ()
[//]: # (Below is the list of available intervals:)

[//]: # ()
[//]: # (| Interval                  | Description            |)

[//]: # (|---------------------------|------------------------|)

[//]: # (| Interval::ONE_MINUTE      | Every minute           |)

[//]: # (| Interval::FIVE_MINUTES    | Every five minutes     |)

[//]: # (| Interval::TEN_MINUTES     | Every ten minutes      |)

[//]: # (| Interval::FIFTEEN_MINUTES | Every fifteen minutes  |)

[//]: # (| Interval::THIRTY_MINUTES  | Every thirty minutes   |)

[//]: # (| Interval::HOURLY          | Every hour             |)

[//]: # (| Interval::DAILY           | Every day              |)

[//]: # ()
[//]: # (The greater the interval, the less frequent the data will be updated in the database. You can choose the interval that best suits your application.)

[//]: # ()
[//]: # (Finally, you need to schedule a job to import data from the cache to the database in the background.)

[//]: # ()
[//]: # (```php)

[//]: # (```)