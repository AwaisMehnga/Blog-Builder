# BilloCraft Framework Documentation

## Table of Contents

1. [Introduction](#introduction)
2. [Installation & Setup](#installation--setup)
3. [Configuration](#configuration)
4. [CLI Commands](#cli-commands)
5. [Routing](#routing)
6. [Controllers](#controllers)
7. [Requests](#requests)
8. [Responses](#responses)
9. [Views](#views)
10. [Database & Models](#database--models)
11. [Middleware](#middleware)
12. [Authentication](#authentication)
13. [Helper Functions](#helper-functions)
14. [Core Classes](#core-classes)
15. [Error Handling](#error-handling)
16. [Pagination](#pagination)
17. [Best Practices](#best-practices)
18. [Email](#email)

---

## Introduction

BilloCraft is a lightweight, modern PHP framework designed for building web applications and APIs. It follows MVC architecture patterns and provides essential features like routing, database ORM, authentication, middleware, and more.

### Features
- Clean MVC architecture
- Flexible routing system
- Database ORM with query builder
- Middleware support
- Session management
- Authentication system
- Template engine
- Environment configuration
- Error handling and logging

---

## Installation & Setup

### Requirements
- PHP 8.0 or higher
- Composer
- Web server (Apache/Nginx)
- MySQL/PostgreSQL (optional)

### Installation Steps

1. **Set up environment file:**
```bash
cp .env.example .env
```

2. **Configure your environment variables:**
```env
APP_NAME=BilloCraft
APP_DEBUG=true
APP_TIMEZONE=UTC

DB_DSN=mysql:host=localhost;dbname=your_database;charset=utf8mb4
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

3. **Install dependencies:**
```bash
composer install
```

4. **Configure web server** to point to the `public/` directory

---

## Configuration

### Application Configuration

Configuration files are located in the `config/` directory.

#### `config/app.php`
```php
<?php
return [
    'name' => env('APP_NAME', 'BilloCraft'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),
    'debug' => filter_var(env('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN),
    'database' => include __DIR__ . '/database.php',
    'middleware' => [
        // Global middleware
    ],
];
```

#### `config/database.php`
```php
<?php
return [
    'dsn' => env('DB_DSN', 'mysql:host=localhost;dbname=billocraft;charset=utf8mb4'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
    'persistent' => env('DB_PERSISTENT', false),
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
```

### Accessing Configuration

Use the `config()` helper function or App methods:

```php
// Using helper function
$appName = config('name');
$debugMode = config('debug', false);

// Using App instance
$app = App::getInstance();
$timezone = $app->config('timezone');
```

---

## CLI Commands

BilloCraft includes a CLI generator to quickly create framework components.

### Available Commands

```bash
# Generate a controller
php cli/generate.php make:controller UserController

# Generate a model
php cli/generate.php make:model User

# Generate middleware
php cli/generate.php make:middleware AuthMiddleware

# Generate a server-rendered view
php cli/generate.php make:view products

# Generate a React-based view
php cli/generate.php make:view dashboard --type=react

# Generate nested views
php cli/generate.php make:view admin.users.index
```

### View Generation Types

#### Server-Rendered Views (default)
```bash
php cli/generate.php make:view products
```
Creates a view using the layout system with server-side content.

#### React Views  
```bash
php cli/generate.php make:view dashboard --type=react
```
Creates both a PHP view file and a React component with Vite integration.

---

## Routing

Routes are defined in the `routes/` directory. The framework supports web routes (`routes/web.php`) and API routes (`routes/api.php`).

### Basic Routing

```php
<?php
use Core\Router;

$router = Router::getInstance();

// GET route
$router->get('/', 'HomeController@index');

// POST route
$router->post('/users', 'UserController@store');

// PUT route
$router->put('/users/{id}', 'UserController@update');

// DELETE route
$router->delete('/users/{id}', 'UserController@destroy');

// Multiple HTTP methods
$router->match(['GET', 'POST'], '/contact', 'ContactController@handle');

// All HTTP methods
$router->any('/webhook', 'WebhookController@handle');
```

### Route Parameters

```php
// Required parameters
$router->get('/users/{id}', 'UserController@show');

// Optional parameters
$router->get('/posts/{id?}', 'PostController@show');

// Multiple parameters
$router->get('/users/{userId}/posts/{postId}', 'PostController@userPost');

// Wildcard parameters
$router->get('/files/*', 'FileController@serve');
```

#### Wildcard Routes

Wildcard routes allow you to match both a base path and any subpaths with a single route definition:

```php
// This matches both /auth and /auth/* patterns
$router->get('/auth/*', 'AuthController@index');

// Examples of what this matches:
// /auth (base route)
// /auth/login
// /auth/register  
// /auth/password/reset
```

In your controller, access the wildcard path:

```php
public function index(Request $request): Response
{
    $wildcardPath = $request->getWildcardPath();
    
    if ($wildcardPath) {
        // Handle subpaths like /auth/login
        return $this->handleSubPath($wildcardPath);
    }
    
    // Handle base /auth route
    return $this->showAuthPage();
}
```

### Named Routes

```php
// Define named route
$router->get('/dashboard', 'DashboardController@index')->name('dashboard');

// Generate URLs
$url = route('dashboard'); // Returns: /dashboard
$url = route('user.profile', ['id' => 123]); // Returns: /users/123/profile
```

### Route Groups

```php
// Prefix grouping
$router->group(['prefix' => 'admin'], function($router) {
    $router->get('/dashboard', 'AdminController@dashboard');
    $router->get('/users', 'AdminController@users');
});

// Middleware grouping
$router->group(['middleware' => 'auth'], function($router) {
    $router->get('/profile', 'ProfileController@show');
    $router->post('/profile', 'ProfileController@update');
});

// Combined grouping
$router->group(['prefix' => 'api', 'middleware' => 'api'], function($router) {
    $router->get('/users', 'Api\UserController@index'); // Fetch all users
    $router->post('/users', 'Api\UserController@store'); // Create a new user
});
```

### Controller-Based Route Groups

You can group routes by controller using the `controller` attribute in a route group. 
This allows you to define multiple routes for a single controller without repeating the controller name:

```php
$router->group(['controller' => 'UserController', 'prefix' => 'users'], function($router) {
    $router->get('/', 'index');        // Maps to UserController@index
    $router->post('/', 'store');       // Maps to UserController@store
    $router->get('/{id}', 'show');     // Maps to UserController@show
    $router->put('/{id}', 'update');   // Maps to UserController@update
    $router->delete('/{id}', 'destroy'); // Maps to UserController@destroy
});
```

You can combine `controller` with other group attributes like `prefix`, `middleware`, and `name`.If the route action is a string and does not contain an `@`, the controller name from the group will be prepended automatically. If you provide a closure or a full `Controller@method` string, it will be used as-is.This feature helps keep your route files clean and organized when working with RESTful controllers.

### Closure Routes

```php
$router->get('/hello', function() {
    return 'Hello World!';
});

$router->get('/json', function() {
    return ['message' => 'Hello JSON'];
});

$router->get('/user/{id}', function($id) {
    return "User ID: $id";
});
```

### Route Middleware

```php
// Single middleware
$router->get('/admin', 'AdminController@index')->middleware('auth');

// Multiple middleware
$router->get('/admin', 'AdminController@index')->middleware(['auth', 'admin']);

// Middleware with parameters
$router->get('/premium', 'PremiumController@index')->middleware('role:premium');
```

---

## Controllers

Controllers handle HTTP requests and return responses. They are located in `app/Controllers/`.

### Creating Controllers

```php
<?php
namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::all();
        return $this->view('users.index', ['users' => $users]);
    }

    public function show(Request $request, int $id): Response
    {
        $user = User::findOrFail($id);
        return $this->view('users.show', ['user' => $user]);
    }

    public function store(Request $request): Response
    {
        $data = $request->all();
        $user = User::create($data);
        
        return $this->json([
            'message' => 'User created successfully',
            'user' => $user->toArray()
        ], 201);
    }

    public function update(Request $request, int $id): Response
    {
        $user = User::findOrFail($id);
        $user->update($request->all());
        
        return $this->json(['message' => 'User updated successfully']);
    }

    public function destroy(Request $request, int $id): Response
    {
        $user = User::findOrFail($id);
        $user->delete();
        
        return $this->noContent();
    }
}
```

### Controller Base Class Methods

The `Core\Controller` class provides several helper methods:

#### `json(array $data, int $status = 200): Response`
Returns a JSON response.

```php
return $this->json(['success' => true], 200);
```

#### `view(string $view, array $data = [], int $status = 200): Response`
Returns a view response.

```php
return $this->view('users.profile', ['user' => $user]);
```

#### `redirect(string $url, int $status = 302): Response`
Returns a redirect response.

```php
return $this->redirect('/dashboard');
```

#### `noContent(int $status = 204): Response`
Returns an empty response.

```php
return $this->noContent(204);
```

### Controller Input Methods

#### `input(string $key, mixed $default = null): mixed`
Get single input value.

```php
$email = $this->input('email');
$name = $this->input('name', 'Anonymous');
```

#### `all(): array`
Get all input data.

```php
$data = $this->all();
```

#### `has(string $key): bool`
Check if input key exists.

```php
if ($this->has('email')) {
    // Process email
}
```

### Constructor Boot Method

Controllers can define a `boot()` method for initialization:

```php
class UserController extends Controller
{
    protected function boot(): void
    {
        // Initialize controller-specific logic
        $this->middleware('auth');
    }
}
```

---

## Requests

The Request class handles HTTP request data and provides various methods to access it.

### Creating Request Instance

The Request object is automatically injected into controller methods:

```php
public function store(Request $request): Response
{
    // Use request object
}
```

### Request Methods

#### HTTP Method Detection

```php
$method = $request->method(); // GET, POST, PUT, etc.

// Method checking
$request->isGet();
$request->isPost();
$request->isPut();
$request->isPatch();
$request->isDelete();
$request->isMethod('POST');
```

#### URI and URL Information

```php
$uri = $request->uri(); // /users/123
$fullUrl = $request->fullUrl(); // https://example.com/users/123?page=1
$isSecure = $request->isSecure(); // true for HTTPS
```

#### Input Data Access

```php
// Get all input (query + body)
$all = $request->all();

// Get specific input
$email = $request->input('email');
$email = $request->input('email', 'default@example.com');

// Get only query parameters
$page = $request->query('page', 1);
$queryData = $request->query(); // All query params

// Get raw body data
$raw = $request->raw();
$rawValue = $request->raw('field_name');
```

#### Input Filtering

```php
// Get only specific keys
$data = $request->only(['name', 'email', 'age']);

// Get all except specific keys
$data = $request->except(['password', 'confirm_password']);

// Check if input exists
$hasEmail = $request->has('email');

// Check if input has value (not null/empty)
$emailFilled = $request->filled('email');
```

#### Headers

```php
// Get header
$contentType = $request->header('Content-Type');
$userAgent = $request->header('User-Agent', 'Unknown');

// Bearer token
$token = $request->bearerToken();

// Check request type
$isJson = $request->isJson();
$isAjax = $request->isAjax();
```

#### File Uploads

```php
// Get uploaded file
$file = $request->file('avatar');

// Get all files
$files = $request->files();

// Working with uploaded files
if ($file && $file->isValid()) {
    $path = '/uploads/' . $file->getClientName();
    $file->move($path);
}
```

#### Cookies and Server Data

```php
// Cookies
$sessionId = $request->cookie('session_id');

// Server information
$serverName = $request->server('SERVER_NAME');
$userIp = $request->ip();
```

#### Route Parameters

```php
// Get route parameter
$userId = $request->routeParam('id');

// Get all route parameters
$params = $request->routeParams();
```

### UploadedFile Class

The framework provides an `UploadedFile` class for handling file uploads:

#### Methods

```php
$file = $request->file('upload');

// File information
$tempPath = $file->getTempPath();
$originalName = $file->getClientName();
$size = $file->getSize();
$mimeType = $file->getMimeType();
$error = $file->getError();

// Validation
$isValid = $file->isValid();

// Move file
$success = $file->move('/path/to/destination/file.ext');
```

---

## Responses

The Response class handles HTTP responses and provides methods for different response types.

### Creating Responses

#### Manual Response Creation

```php
use Core\Response;

// Basic response
$response = new Response('Hello World', 200, ['Content-Type' => 'text/plain']);

// JSON response
$response = Response::json(['message' => 'Success'], 200);

// View response
$response = Response::view('welcome', ['name' => 'John']);

// Redirect response
$response = Response::redirect('/dashboard', 302);

// No content response
$response = Response::noContent(204);
```

#### Controller Helper Methods

Controllers provide shorthand methods:

```php
// JSON response
return $this->json(['data' => $data]);

// View response
return $this->view('template', $data);

// Redirect
return $this->redirect('/path');

// No content
return $this->noContent();
```

### Response Methods

#### Setting Response Properties

```php
$response = new Response();

// Set status code
$response->setStatus(404);

// Set headers
$response->setHeader('Cache-Control', 'no-cache');
$response->setHeader('X-Custom-Header', 'value');

// Set content
$response->setContent('<h1>Hello World</h1>');

// Send response
$response->send();
```

#### Static Factory Methods

```php
// JSON response
Response::json([
    'success' => true,
    'data' => $data
], 201);

// View response
Response::view('users.profile', [
    'user' => $user,
    'title' => 'User Profile'
]);

// Redirect response
Response::redirect('https://example.com', 301);

// Raw response with custom headers
Response::sendRaw('Custom content', 200, [
    'Content-Type' => 'text/plain',
    'X-Custom' => 'Header'
]);

// No content response
Response::noContent(204);
```

### Response Helper Function

Use the global `response()` helper:

```php
// String response
return response('Hello World');

// Array response (automatically converts to JSON)
return response(['message' => 'Success']);

// Custom status and headers
return response('Not Found', 404, ['X-Error' => 'Resource missing']);

// Response object passthrough
return response($existingResponse);
```

---

## Views

Views are PHP templates located in `app/Views/`. The framework supports a simple template system with layout inheritance.

### Basic View Usage

#### Creating Views

Create view files in `app/Views/`:

```php
<!-- app/Views/welcome.php -->
<h1>Welcome, <?= $name ?>!</h1>
<p>Today is <?= date('Y-m-d') ?></p>
```

#### Rendering Views

```php
// In controller
return $this->view('welcome', ['name' => 'John']);

// Using helper function
return view('welcome', ['name' => 'John']);

// Using Response class
return Response::view('welcome', ['name' => 'John']);
```

### View Organization

#### Nested Views

Organize views in subdirectories:

```
app/Views/
├── layouts/
│   └── main.php
├── partials/
│   ├── header.php
│   └── footer.php
├── users/
│   ├── index.php
│   ├── show.php
│   └── create.php
└── home.php
```

Access nested views with dot notation:

```php
return $this->view('users.index', $data);
return $this->view('layouts.main', $data);
```

### Layout System

#### Creating a Layout

```php
<!-- app/Views/layouts/main.php -->
<!DOCTYPE html>
<html>
<head>
    <title><?= $title ?? 'BilloCraft App' ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
    <?php include view_path('partials.header'); ?>
    
    <main>
        <?= $content ?? '' ?>
    </main>
    
    <?php include view_path('partials.footer'); ?>
</body>
</html>
```

#### Using Layouts in Views

```php
<!-- app/Views/users/index.php -->
<?php $title = 'Users List'; ?>
<?php ob_start(); ?>

<h1>Users</h1>
<div class="users-grid">
    <?php foreach ($users as $user): ?>
        <div class="user-card">
            <h3><?= htmlspecialchars($user->name) ?></h3>
            <p><?= htmlspecialchars($user->email) ?></p>
        </div>
    <?php endforeach; ?>
</div>

<?php $content = ob_get_clean(); ?>
<?php include view_path('layouts.main'); ?>
```

### View Helpers

#### Available Helper Functions

```php
// Get view file path
$path = view_path('users.profile'); // Returns full file path

// Escape HTML
<?= htmlspecialchars($user->name) ?>

// Include partials
<?php include view_path('partials.navigation'); ?>

// Check if user is authenticated
<?php if (auth()->check()): ?>
    <p>Welcome, <?= auth()->user()->name ?>!</p>
<?php endif; ?>
```

#### Security Practices

Always escape output to prevent XSS:

```php
<!-- Safe output -->
<h1><?= htmlspecialchars($title) ?></h1>
<p><?= htmlspecialchars($user->bio) ?></p>

<!-- For HTML content (be careful) -->
<div><?= $trustedHtmlContent ?></div>
```

### Example Views

#### User Profile View

```php
<!-- app/Views/users/profile.php -->
<?php $title = 'User Profile - ' . $user->name; ?>
<?php ob_start(); ?>

<div class="profile-container">
    <div class="profile-header">
        <h1><?= htmlspecialchars($user->name) ?></h1>
        <p class="email"><?= htmlspecialchars($user->email) ?></p>
    </div>
    
    <div class="profile-content">
        <h2>About</h2>
        <p><?= htmlspecialchars($user->bio ?? 'No bio available.') ?></p>
        
        <h2>Recent Posts</h2>
        <?php if (!empty($user->posts)): ?>
            <ul>
                <?php foreach ($user->posts as $post): ?>
                    <li>
                        <a href="<?= route('posts.show', ['id' => $post->id]) ?>">
                            <?= htmlspecialchars($post->title) ?>
                        </a>
                        <small><?= $post->created_at ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No posts yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php $content = ob_get_clean(); ?>
<?php include view_path('layouts.main'); ?>
```

---

## Database & Models

The framework provides an ORM system with a query builder for database operations.

### Database Configuration

Configure database connection in `config/database.php`:

```php
<?php
return [
    'dsn' => 'mysql:host=localhost;dbname=myapp;charset=utf8mb4',
    'username' => 'root',
    'password' => 'password',
    'persistent' => false,
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];
```

### Database Class

The `Core\Database` class provides database connectivity:

#### Usage

```php
use Core\Database;

$db = Database::getInstance();

// Execute queries
$db->execute('UPDATE users SET active = ? WHERE id = ?', [1, 123]);

// Fetch data
$users = $db->fetchAll('SELECT * FROM users WHERE active = ?', [1]);
$user = $db->fetchOne('SELECT * FROM users WHERE id = ?', [123]);

// Get connection
$pdo = $db->getConnection();

// Transactions
$db->beginTransaction();
try {
    $db->execute('INSERT INTO users (name, email) VALUES (?, ?)', ['John', 'john@example.com']);
    $db->execute('INSERT INTO profiles (user_id, bio) VALUES (?, ?)', [$db->lastInsertId(), 'Bio']);
    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    throw $e;
}
```

### Models

Models represent database tables and provide an ORM interface.

#### Creating Models

```php
<?php
namespace App\Models;

use Core\Model;

class User extends Model
{
    /**
     * Table name (optional - auto-detected from class name)
     */
    protected string $table = 'users';

    /**
     * Primary key column
     */
    protected string $primaryKey = 'id';

    /**
     * Enable timestamps (created_at, updated_at)
     */
    public bool $timestamps = true;

    /**
     * Mass assignable attributes
     */
    protected array $fillable = [
        'name',
        'email',
        'password',
        'bio'
    ];

    /**
     * Hash password before saving
     */
    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = password_hash($value, PASSWORD_DEFAULT);
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    /**
     * Find user by email
     */
    public static function findByEmail(string $email): ?self
    {
        return static::where('email', $email)->first();
    }

    /**
     * Get user's posts
     */
    public function posts(): array
    {
        return Post::where('user_id', $this->id)->get();
    }
}
```

#### Model Methods

##### Static Query Methods

```php
// Get all records
$users = User::all();

// Find by primary key
$user = User::find(123);
$user = User::findOrFail(123); // Throws exception if not found

// Query builder
$users = User::where('active', 1)->get();
$user = User::where('email', 'john@example.com')->first();

// Create new record
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'secret123'
]);

// Get query builder instance
$query = User::query();
```

##### Instance Methods

```php
$user = new User([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Save (insert or update)
$user->save();

// Update attributes
$user->update([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com'
]);

// Delete
$user->delete();

// Convert to array
$data = $user->toArray();

// Access attributes
echo $user->name;
echo $user->email;

// Set attributes
$user->name = 'New Name';
$user->bio = 'New bio';
```

### Query Builder

The query builder provides a fluent interface for database queries.

#### Basic Queries

```php
use Core\QueryBuilder;

// Get all users
$users = User::query()->get();

// Get specific columns
$users = User::query()->select(['name', 'email'])->get();

// Get first result
$user = User::query()->where('id', 123)->first();

// Count records
$count = User::query()->where('active', 1)->count();

// Check if records exist
$exists = User::query()->where('email', 'test@example.com')->exists();
```

#### Where Clauses

```php
// Basic where
$users = User::where('active', 1)->get();
$users = User::where('age', '>', 18)->get();
$users = User::where('name', 'like', '%john%')->get();

// Multiple where conditions (AND)
$users = User::where('active', 1)
             ->where('age', '>', 18)
             ->get();

// OR where
$users = User::where('role', 'admin')
             ->orWhere('role', 'moderator')
             ->get();
```

#### Ordering and Limiting

```php
// Order by
$users = User::orderBy('name')->get();
$users = User::orderBy('created_at', 'desc')->get();

// Limit and offset
$users = User::limit(10)->get();
$users = User::limit(10)->offset(20)->get();

// Pagination
$page = 2;
$perPage = 10;
$users = User::limit($perPage)->offset(($page - 1) * $perPage)->get();
```

#### Insert, Update, Delete

```php
// Insert
$userId = User::query()->insert([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
]);

// Update
$affected = User::where('id', 123)->update([
    'name' => 'Jane Doe',
    'updated_at' => date('Y-m-d H:i:s')
]);

// Delete
$deleted = User::where('active', 0)->delete();
```

#### Advanced Queries

```php
// Complex conditions
$users = User::where('age', '>', 18)
             ->where(function($query) {
                 $query->where('city', 'New York')
                       ->orWhere('city', 'Los Angeles');
             })
             ->orderBy('name')
             ->limit(50)
             ->get();

// Raw queries using Database class
$db = Database::getInstance();
$results = $db->fetchAll(
    'SELECT u.*, COUNT(p.id) as post_count 
     FROM users u 
     LEFT JOIN posts p ON u.id = p.user_id 
     WHERE u.active = ? 
     GROUP BY u.id',
    [1]
);
```

---

## Middleware

Middleware provides a convenient mechanism for filtering HTTP requests entering your application.

### Creating Middleware

Create middleware classes in `app/Middleware/`:

```php
<?php
namespace App\Middleware;

use Core\Middleware;
use Core\Request;
use Closure;

class AuthMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next, ...$params): mixed
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            // Redirect to login or return unauthorized
            if ($request->isJson()) {
                return response(['error' => 'Unauthorized'], 401);
            }
            return redirect('/login');
        }

        // Continue to next middleware/controller
        return $next();
    }
}
```

#### Middleware with Parameters

```php
<?php
namespace App\Middleware;

use Core\Middleware;
use Core\Request;
use Closure;

class RoleMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next, ...$params): mixed
    {
        $requiredRole = $params[0] ?? null;
        
        if (!$requiredRole) {
            return $next();
        }

        $user = auth()->user();
        
        if (!$user || $user->role !== $requiredRole) {
            return response(['error' => 'Forbidden'], 403);
        }

        return $next();
    }
}
```

#### CORS Middleware Example

```php
<?php
namespace App\Middleware;

use Core\Middleware;
use Core\Request;
use Closure;

class CorsMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next, ...$params): mixed
    {
        // Handle preflight OPTIONS requests
        if ($request->isMethod('OPTIONS')) {
            return response('', 200, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
            ]);
        }

        // Process request
        $response = $next();

        // Add CORS headers to response
        if ($response instanceof \Core\Response) {
            $response->setHeader('Access-Control-Allow-Origin', '*');
            $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        }

        return $response;
    }
}
```

### Registering Middleware

#### Global Middleware

Register in `config/app.php`:

```php
<?php
return [
    // ... other config
    'middleware' => [
        'App\Middleware\CorsMiddleware',
        'App\Middleware\TrimStringsMiddleware',
    ],
];
```

#### Route Middleware

```php
// Single middleware
$router->get('/admin', 'AdminController@index')
       ->middleware('App\Middleware\AuthMiddleware');

// Multiple middleware
$router->get('/admin', 'AdminController@index')
       ->middleware(['App\Middleware\AuthMiddleware', 'App\Middleware\AdminMiddleware']);

// Middleware with parameters
$router->get('/premium', 'PremiumController@index')
       ->middleware('App\Middleware\RoleMiddleware:premium');

// Multiple parameters
$router->get('/content', 'ContentController@index')
       ->middleware('App\Middleware\PermissionMiddleware:read,write');
```

#### Group Middleware

```php
$router->group(['middleware' => 'App\Middleware\AuthMiddleware'], function($router) {
    $router->get('/dashboard', 'DashboardController@index');
    $router->get('/profile', 'ProfileController@show');
    $router->post('/profile', 'ProfileController@update');
});
```

### Middleware Execution Order

Middleware executes in the order defined:

1. Global middleware (from config)
2. Group middleware (outer to inner)
3. Route-specific middleware

Example execution flow:

```php
// Global: CORS -> Auth
// Group: RateLimit
// Route: Admin

// Request flow: CORS -> Auth -> RateLimit -> Admin -> Controller
// Response flow: Controller -> Admin -> RateLimit -> Auth -> CORS
```

### Common Middleware Examples

#### Rate Limiting Middleware

```php
<?php
namespace App\Middleware;

use Core\Middleware;
use Core\Request;
use Closure;

class RateLimitMiddleware implements Middleware
{
    private const RATE_LIMIT_KEY = 'rate_limit_';
    private int $maxAttempts;
    private int $timeWindow;

    public function __construct(int $maxAttempts = 60, int $timeWindow = 60)
    {
        $this->maxAttempts = $maxAttempts;
        $this->timeWindow = $timeWindow;
    }

    public function handle(Request $request, Closure $next, ...$params): mixed
    {
        $key = self::RATE_LIMIT_KEY . $request->ip();
        $attempts = session()->get($key, []);
        $now = time();
        
        // Remove old attempts
        $attempts = array_filter($attempts, fn($time) => ($now - $time) < $this->timeWindow);
        
        if (count($attempts) >= $this->maxAttempts) {
            return response(['error' => 'Rate limit exceeded'], 429);
        }
        
        // Record this attempt
        $attempts[] = $now;
        session()->put($key, $attempts);
        
        return $next();
    }
}
```

#### Request Logging Middleware

```php
<?php
namespace App\Middleware;

use Core\Middleware;
use Core\Request;
use Closure;

class RequestLogMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next, ...$params): mixed
    {
        $startTime = microtime(true);
        
        // Log request
        error_log(sprintf(
            '[%s] %s %s from %s',
            date('Y-m-d H:i:s'),
            $request->method(),
            $request->uri(),
            $request->ip()
        ));
        
        $response = $next();
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        
        // Log response
        error_log(sprintf(
            '[%s] Response completed in %sms',
            date('Y-m-d H:i:s'),
            $duration
        ));
        
        return $response;
    }
}
```

---

## Authentication

The framework provides a simple authentication system for managing user sessions.

### Auth Class

The `Core\Auth` class handles authentication:

#### Methods

```php
use Core\Auth;

$auth = new Auth();

// Check if user is authenticated
$isAuthenticated = $auth->check();
$isGuest = $auth->guest();

// Get current user
$user = $auth->user(); // Returns User model or null
$userId = $auth->id(); // Returns user ID or null

// Login user
$user = User::findByEmail('user@example.com');
$auth->login($user);

// Logout user
$auth->logout();

// Attempt login with credentials
$success = $auth->attempt([
    'email' => 'user@example.com',
    'password' => 'password123'
]);

// Validate credentials without logging in
$user = $auth->validate([
    'email' => 'user@example.com',
    'password' => 'password123'
]);

// Temporarily set user (doesn't persist in session)
$auth->once($user);
```

### Using Auth Helper

The `auth()` helper function provides access to the Auth instance:

```php
// Check authentication
if (auth()->check()) {
    echo "User is logged in";
}

// Get current user
$user = auth()->user();
if ($user) {
    echo "Hello, " . $user->name;
}

// Login
$success = auth()->attempt($credentials);

// Logout
auth()->logout();
```

### Authentication in Controllers

#### Login Controller Example

```php
<?php
namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Response;

class AuthController extends Controller
{
    public function showLogin(): Response
    {
        return $this->view('auth.login');
    }

    public function login(Request $request): Response
    {
        $credentials = $request->only(['email', 'password']);
        
        if (auth()->attempt($credentials)) {
            return $this->redirect('/dashboard');
        }
        
        return $this->view('auth.login', [
            'error' => 'Invalid credentials',
            'email' => $request->input('email')
        ]);
    }

    public function logout(): Response
    {
        auth()->logout();
        return $this->redirect('/');
    }

    public function register(Request $request): Response
    {
        // Validate input (simplified)
        $data = $request->only(['name', 'email', 'password']);
        
        // Check if user exists
        if (User::findByEmail($data['email'])) {
            return $this->view('auth.register', [
                'error' => 'Email already exists'
            ]);
        }
        
        // Create user
        $user = User::create($data);
        
        // Log them in
        auth()->login($user);
        
        return $this->redirect('/dashboard');
    }
}
```

#### Protecting Routes

```php
// In routes/web.php
$router->group(['middleware' => 'App\Middleware\AuthMiddleware'], function($router) {
    $router->get('/dashboard', 'DashboardController@index');
    $router->get('/profile', 'ProfileController@show');
    $router->post('/profile', 'ProfileController@update');
});

// Individual route protection
$router->get('/admin', 'AdminController@index')
       ->middleware('App\Middleware\AuthMiddleware');
```

### User Model for Authentication

Your User model should implement password hashing and verification:

```php
<?php
namespace App\Models;

use Core\Model;

class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email', 'password'];

    /**
     * Hash password when setting
     */
    public function __set(string $key, mixed $value): void
    {
        if ($key === 'password') {
            $value = password_hash($value, PASSWORD_DEFAULT);
        }
        parent::__set($key, $value);
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    /**
     * Find user by email
     */
    public static function findByEmail(string $email): ?self
    {
        return static::where('email', $email)->first();
    }
}
```

### Login Form Example

```php
<!-- app/Views/auth/login.php -->
<?php $title = 'Login'; ?>
<?php ob_start(); ?>

<div class="login-form">
    <h2>Login</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="/login">
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" 
                   id="email" 
                   name="email" 
                   value="<?= htmlspecialchars($email ?? '') ?>" 
                   required>
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit">Login</button>
    </form>
    
    <p><a href="/register">Don't have an account? Register here</a></p>
</div>

<?php $content = ob_get_clean(); ?>
<?php include view_path('layouts.main'); ?>
```

---

## Helper Functions

The framework provides several helper functions for common tasks.

### Environment Helpers

#### `env(string $key, $default = null)`
Get environment variable value.

```php
$appName = env('APP_NAME', 'My App');
$debug = env('APP_DEBUG', false);
$dbHost = env('DB_HOST', 'localhost');
```

#### `config(string $key, $default = null)`
Get configuration value.

```php
$appName = config('name');
$debug = config('debug', false);
$timezone = config('timezone');
```

### Response Helpers

#### `response(mixed $content = '', int $status = 200, array $headers = []): Response`
Create a response.

```php
// String response
return response('Hello World');

// JSON response (array auto-converts)
return response(['message' => 'Success']);

// Custom status and headers
return response('Not Found', 404);

// With headers
return response('OK', 200, ['X-Custom' => 'Header']);
```

#### `view(string $template, array $data = []): string`
Render a view template.

```php
// Render view
$html = view('welcome', ['name' => 'John']);

// Nested views
$html = view('users.profile', ['user' => $user]);

// In controller
return response(view('welcome', $data));
```

#### `redirect(string $url, int $status = 302): void`
Redirect to URL.

```php
// Simple redirect
redirect('/dashboard');

// Permanent redirect
redirect('/new-url', 301);

// With query params
redirect('/search?q=' . urlencode($query));
```

### Routing Helpers

#### `route(string $name, array $params = []): string`
Generate URL for named route.

```php
// Simple named route
$url = route('home'); // Returns: /

// Route with parameters
$url = route('user.profile', ['id' => 123]); // Returns: /users/123

// Route with optional parameters
$url = route('posts.show', ['id' => 456, 'slug' => 'hello-world']);
```

### Authentication Helpers

#### `auth(): Auth`
Get Auth instance.

```php
// Check if authenticated
if (auth()->check()) {
    echo 'Logged in';
}

// Get current user
$user = auth()->user();

// Login attempt
$success = auth()->attempt($credentials);

// Logout
auth()->logout();
```

### Session Helpers

#### `session()`
Get session manager.

```php
// Get session value
$value = session()->get('key', 'default');

// Set session value
session()->put('key', 'value');

// Check if session has key
if (session()->has('key')) {
    echo 'Key exists';
}

// Remove session key
session()->forget('key');
```

### Utility Helpers

#### `abort(int $statusCode, string $message = ''): void`
Abort request with HTTP status.

```php
// 404 error
abort(404);

// Custom message
abort(403, 'Access denied');

// 500 error
abort(500, 'Something went wrong');
```

#### `view_path(string $name): string`
Get full path to view file.

```php
$path = view_path('users.profile');
// Returns: /path/to/app/Views/users/profile.php

// Use in includes
include view_path('partials.header');
```

### Using Helpers in Views

```php
<!-- app/Views/dashboard.php -->
<?php $title = 'Dashboard'; ?>
<?php ob_start(); ?>

<h1>Welcome to Dashboard</h1>

<?php if (auth()->check()): ?>
    <p>Hello, <?= htmlspecialchars(auth()->user()->name) ?>!</p>
    <a href="<?= route('profile') ?>">View Profile</a>
    <a href="<?= route('logout') ?>">Logout</a>
<?php else: ?>
    <p><a href="<?= route('login') ?>">Please login</a></p>
<?php endif; ?>

<div class="stats">
    <h2>Application Info</h2>
    <p>App Name: <?= config('name') ?></p>
    <p>Debug Mode: <?= config('debug') ? 'On' : 'Off' ?></p>
    <p>Environment: <?= env('APP_ENV', 'production') ?></p>
</div>

<?php $content = ob_get_clean(); ?>
<?php include view_path('layouts.main'); ?>
```

---

## Core Classes

### App Class (`Core\App`)

The main application class that bootstraps and runs the application.

#### Constructor

```php
public function __construct(array $routeFiles = [], array $globalMiddleware = [])
```

Creates new App instance with route files and global middleware.

#### Methods

##### `getInstance(): ?self`
Get singleton App instance.

##### `run(): void`
Run the application - dispatch request and emit response.

##### `config(string $key, $default = null): mixed`
Get configuration value.

##### `session()`
Get session manager object.

##### `bind(string $key, callable $resolver): void`
Bind service to container.

##### `resolve(string $key): mixed`
Resolve service from container.

##### `route(string $name, array $params = []): ?string`
Generate URL for named route.

##### `getRouter(): Router`
Get router instance.

##### `getRequest(): Request`
Get current request instance.

##### `getBasePath(): string`
Get application base path.

### Router Class (`Core\Router`)

Handles route registration and request dispatching.

#### Route Registration Methods

```php
public function get(string $uri, $action): Route
public function post(string $uri, $action): Route
public function put(string $uri, $action): Route
public function patch(string $uri, $action): Route
public function delete(string $uri, $action): Route
public function match(array $methods, string $uri, $action): Route
public function any(string $uri, $action): Route
```

#### Grouping

```php
public function group(array $attributes, Closure $callback): void
```

Group routes with shared attributes (prefix, middleware, etc.).

#### Dispatch

```php
public function dispatch(Request $request): mixed
```

Dispatch current request to appropriate route handler.

#### URL Generation

```php
public function generateUrl(string $name, array $params = []): string
public function resolveName(string $name, array $params = []): ?string
```

### Route Class (`Core\Route`)

Represents individual route with methods, URI pattern, and action.

#### Methods

```php
public function name(string $name): self
public function middleware(string|array $middleware): self
public function match(string $uri): array|false
public function buildUri(array $params = []): string
```

### Request Class (`Core\Request`)

Handles HTTP request data and provides access methods.

#### HTTP Information

```php
public function method(): string
public function uri(): string
public function fullUrl(): string
public function isSecure(): bool
public function isMethod(string $verb): bool
public function isGet(): bool
public function isPost(): bool
// ... other HTTP method checks
```

#### Input Data

```php
public function input(string $key = null, $default = null): mixed
public function query(string $key = null, $default = null): mixed
public function all(): array
public function only(array $keys): array
public function except(array $keys): array
public function has(string $key): bool
public function filled(string $key): bool
```

#### Headers and Files

```php
public function header(string $key, $default = null): mixed
public function bearerToken(): ?string
public function file(string $key): mixed
public function files(): array
public function cookie(string $key, $default = null): mixed
```

### Response Class (`Core\Response`)

Handles HTTP responses.

#### Instance Methods

```php
public function setStatus(int $code): self
public function setHeader(string $name, string $value): self
public function setContent(string $content): self
public function send(): void
```

#### Static Factory Methods

```php
public static function json(array $data, int $status = 200): self
public static function view(string $view, array $data = [], int $status = 200): self
public static function redirect(string $url, int $status = 302): self
public static function sendRaw(string $content, int $status = 200, array $headers = []): self
public static function noContent(int $status = 204): self
```

### Database Class (`Core\Database`)

Manages database connections and provides query execution methods.

#### Methods

```php
public static function getInstance(): self
public function getConnection(): PDO
public function execute(string $sql, array $bindings = []): bool
public function fetchAll(string $sql, array $bindings = []): array
public function fetchOne(string $sql, array $bindings = []): ?array
public function beginTransaction(): void
public function commit(): void
public function rollBack(): void
public function lastInsertId(): string
```

### Model Class (`Core\Model`)

Base class for database models with ORM functionality.

#### Static Methods

```php
public static function all(): array
public static function find(mixed $id): ?static
public static function findOrFail(mixed $id): static
public static function where(string $column, mixed $operator = null, mixed $value = null): QueryBuilder
public static function create(array $attributes): static
public static function query(): QueryBuilder
```

#### Instance Methods

```php
public function save(): bool
public function update(array $attributes): bool
public function delete(): bool
public function fill(array $attributes): static
public function toArray(): array
```

### QueryBuilder Class (`Core\QueryBuilder`)

Provides fluent interface for building database queries.

#### Query Building

```php
public function select(array|string $columns): static
public function where(string $column, mixed $operator = null, mixed $value = null): static
public function orWhere(string $column, mixed $operator = null, mixed $value = null): static
public function orderBy(string $column, string $direction = 'asc'): static
public function limit(int $limit): static
public function offset(int $offset): static
```

#### Execution

```php
public function get(): array
public function first(): mixed
public function count(): int
public function exists(): bool
public function insert(array $data): int|false
public function update(array $data): bool
public function delete(): bool
```

---

## Error Handling

The framework provides built-in error handling and logging capabilities.

### Error Display

Error display is controlled by the `debug` configuration:

```php
// config/app.php
'debug' => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
```

#### Development Mode (debug = true)
- Full error details with stack trace
- File and line information
- Helpful for debugging

#### Production Mode (debug = false)
- Generic "Internal Server Error" message
- Errors logged but not displayed
- Secure for live applications

### Logging

Errors are automatically logged to `storage/logs/app.log`:

```
[2024-01-15 10:30:45] Exception: Database connection failed in /path/to/Database.php:25
Stack trace:
#0 /path/to/Model.php(15): Core\Database->__construct()
#1 /path/to/UserController.php(10): App\Models\User::all()
...
```

### Custom Error Handling

#### Throwing Exceptions

```php
// In controllers
if (!$user) {
    throw new \Exception('User not found', 404);
}

// In models
if (!$this->isValid()) {
    throw new \RuntimeException('Invalid data provided');
}
```

#### Using Abort Helper

```php
// Quick HTTP error responses
abort(404); // Returns 404 Not Found
abort(403, 'Access denied'); // Returns 403 with message
abort(500, 'Database error'); // Returns 500 with message
```

#### Custom Exception Handling

Create custom exception handlers:

```php
<?php
namespace App\Exceptions;

class ValidationException extends \Exception
{
    private array $errors;

    public function __construct(array $errors, string $message = 'Validation failed')
    {
        $this->errors = $errors;
        parent::__construct($message);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
```

Use in controllers:

```php
public function store(Request $request): Response
{
    $errors = $this->validateInput($request->all());
    
    if (!empty($errors)) {
        if ($request->isJson()) {
            return $this->json(['errors' => $errors], 422);
        }
        return $this->view('form', ['errors' => $errors]);
    }
    
    // Process valid data...
}

private function validateInput(array $data): array
{
    $errors = [];
    
    if (empty($data['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    if (empty($data['password']) || strlen($data['password']) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }
    
    return $errors;
}
```

### 404 Handling

The router automatically returns 404 responses for unmatched routes:

```php
// Returns: HTTP 404 with "404 Not Found" message
```

To customize 404 handling, create a fallback route:

```php
// In routes/web.php
$router->any('*', function() {
    return view('errors.404');
});
```

### Error Pages

Create custom error pages in `app/Views/errors/`:

```php
<!-- app/Views/errors/404.php -->
<?php $title = '404 - Page Not Found'; ?>
<?php ob_start(); ?>

<div class="error-page">
    <h1>404</h1>
    <h2>Page Not Found</h2>
    <p>The page you're looking for doesn't exist.</p>
    <a href="<?= route('home') ?>">Go Home</a>
</div>

<?php $content = ob_get_clean(); ?>
<?php include view_path('layouts.main'); ?>
```

```php
<!-- app/Views/errors/500.php -->
<?php $title = '500 - Server Error'; ?>
<?php ob_start(); ?>

<div class="error-page">
    <h1>500</h1>
    <h2>Internal Server Error</h2>
    <p>Something went wrong. Please try again later.</p>
    <a href="<?= route('home') ?>">Go Home</a>
</div>

<?php $content = ob_get_clean(); ?>
<?php include view_path('layouts.main'); ?>
```

---

## Pagination

The BilloCraft framework provides built-in, Laravel-style pagination for both web and API responses. Pagination is available via the `paginate()` and `simplePaginate()` methods on the QueryBuilder and Model classes.

### Basic Usage

#### In Controllers (Web or API)

```php
$users = User::query()
    ->where('active', 1)
    ->orderBy('name')
    ->paginate(20); // 20 per page
```

#### In Views (Web)

```php
<!-- Show pagination info -->
<p><?= pagination_info($users) ?></p>

<!-- Display users -->
<?php foreach ($users['data'] as $user): ?>
    <div><?= htmlspecialchars($user->name) ?></div>
<?php endforeach; ?>

<!-- Show pagination links -->
<?= paginate_links($users) ?>
```

#### In APIs

```php
public function api(Request $request): Response
{
    $users = User::query()->paginate(15);
    return $this->json([
        'data' => array_map(fn($u) => $u->toArray(), $users['data']),
        'pagination' => [
            'current_page' => $users['current_page'],
            'total' => $users['total'],
            'last_page' => $users['last_page']
        ]
    ]);
}
```

### Pagination Data Structure

The result of `paginate()` is an array with these keys:

- `data`: The current page's records
- `current_page`: Current page number
- `per_page`: Items per page
- `total`: Total records
- `last_page`: Last page number
- `from`, `to`: Range of items shown
- `has_more_pages`: Boolean
- `prev_page`, `next_page`: Navigation
- `links`: Array of navigation links (for HTML)

### Helper Functions

- `paginate_links($pagination, $options = [])`: Generates HTML pagination links for views
- `pagination_info($pagination)`: Shows "Showing X to Y of Z results"

### Customization

You can customize the HTML classes for pagination links:

```php
<?= paginate_links($users, [
    'class' => 'my-pagination',
    'link_class' => 'btn',
    'active_class' => 'btn-primary',
    'disabled_class' => 'btn-disabled'
]) ?>
```

### Simple Pagination

For simple previous/next pagination:

```php
$users = User::query()->simplePaginate(20);
```

---

## Best Practices

### Security

#### Input Validation
Always validate and sanitize user input:

```php
public function store(Request $request): Response
{
    // Validate required fields
    $data = $request->only(['name', 'email', 'password']);
    
    // Sanitize and validate
    $data['email'] = filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL);
    if (!$data['email']) {
        return $this->json(['error' => 'Invalid email'], 400);
    }
    
    // Hash passwords
    $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
    
    $user = User::create($data);
    return $this->json(['user' => $user->toArray()], 201);
}
```

#### XSS Prevention
Always escape output in views:

```php
<!-- Safe -->
<h1><?= htmlspecialchars($title) ?></h1>
<p><?= htmlspecialchars($user->bio) ?></p>

<!-- Dangerous -->
<div><?= $user->bio ?></div>
```

#### SQL Injection Prevention
Use parameterized queries (automatically handled by QueryBuilder):

```php
// Safe
$users = User::where('email', $email)->get();

// Also safe
$db = Database::getInstance();
$users = $db->fetchAll('SELECT * FROM users WHERE email = ?', [$email]);
```

#### CSRF Protection
Implement CSRF middleware for forms:

```php
<?php
namespace App\Middleware;

use Core\Middleware;
use Core\Request;
use Closure;

class CsrfMiddleware implements Middleware
{
    public function handle(Request $request, Closure $next, ...$params): mixed
    {
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('DELETE')) {
            $token = $request->input('_token');
            $sessionToken = session()->get('csrf_token');
            
            if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
                return response(['error' => 'CSRF token mismatch'], 419);
            }
        }
        
        return $next();
    }
}
```

### Performance

#### Database Optimization

```php
// Use specific columns instead of *
$users = User::query()->select(['id', 'name', 'email'])->get();

// Limit results
$users = User::query()->limit(20)->get();

// Use indexes for WHERE clauses
$users = User::where('active', 1)->where('role', 'admin')->get();
```

#### Caching Strategies

```php
// Simple session-based caching
public function getExpensiveData(): array
{
    $cacheKey = 'expensive_data';
    
    if (session()->has($cacheKey)) {
        return session()->get($cacheKey);
    }
    
    $data = $this->calculateExpensiveData();
    session()->put($cacheKey, $data);
    
    return $data;
}
```

### Code Organization

#### Controller Structure

```php
<?php
namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Response;
use App\Models\User;

class UserController extends Controller
{
    // List users
    public function index(Request $request): Response
    {
        $users = User::all();
        return $this->view('users.index', compact('users'));
    }

    // Show single user
    public function show(Request $request, int $id): Response
    {
        $user = User::findOrFail($id);
        return $this->view('users.show', compact('user'));
    }

    // Show create form
    public function create(): Response
    {
        return $this->view('users.create');
    }

    // Store new user
    public function store(Request $request): Response
    {
        $data = $this->validateUserData($request);
        $user = User::create($data);
        
        return $this->redirect(route('users.show', ['id' => $user->id]));
    }

    // Show edit form
    public function edit(Request $request, int $id): Response
    {
        $user = User::findOrFail($id);
        return $this->view('users.edit', compact('user'));
    }

    // Update user
    public function update(Request $request, int $id): Response
    {
        $user = User::findOrFail($id);
        $data = $this->validateUserData($request);
        $user->update($data);
        
        return $this->redirect(route('users.show', ['id' => $user->id]));
    }

    // Delete user
    public function destroy(Request $request, int $id): Response
    {
        $user = User::findOrFail($id);
        $user->delete();
        
        return $this->redirect(route('users.index'));
    }

    private function validateUserData(Request $request): array
    {
        $data = $request->only(['name', 'email', 'password']);
        
        // Add validation logic here
        if (empty($data['name'])) {
            abort(400, 'Name is required');
        }
        
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            abort(400, 'Invalid email');
        }
        
        return $data;
    }
}
```

#### Route Organization

```php
// routes/web.php
<?php
use Core\Router;

$router = Router::getInstance();

// Public routes
$router->get('/', 'HomeController@index')->name('home');
$router->get('/about', 'PageController@about')->name('about');

// Auth routes
$router->get('/login', 'AuthController@showLogin')->name('login');
$router->post('/login', 'AuthController@login');
$router->get('/register', 'AuthController@showRegister')->name('register');
$router->post('/register', 'AuthController@register');
$router->post('/logout', 'AuthController@logout')->name('logout');

// Protected routes
$router->group(['middleware' => 'App\Middleware\AuthMiddleware'], function($router) {
    // User management
    $router->get('/users', 'UserController@index')->name('users.index');
    $router->get('/users/create', 'UserController@create')->name('users.create');
    $router->post('/users', 'UserController@store')->name('users.store');
    $router->get('/users/{id}', 'UserController@show')->name('users.show');
    $router->get('/users/{id}/edit', 'UserController@edit')->name('users.edit');
    $router->put('/users/{id}', 'UserController@update')->name('users.update');
    $router->delete('/users/{id}', 'UserController@destroy')->name('users.destroy');
    
    // Dashboard
    $router->get('/dashboard', 'DashboardController@index')->name('dashboard');
});
```

### Testing

#### Simple Testing

Create test files to verify functionality:

```php
<?php
// tests/UserModelTest.php

require_once __DIR__ . '/../vendor/autoload.php';

class UserModelTest
{
    public function testUserCreation()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ];
        
        $user = new App\Models\User($userData);
        
        assert($user->name === 'Test User');
        assert($user->email === 'test@example.com');
        
        echo "✓ User creation test passed\n";
    }
    
    public function testPasswordHashing()
    {
        $user = new App\Models\User();
        $user->password = 'plaintext';
        
        assert($user->verifyPassword('plaintext'));
        assert(!$user->verifyPassword('wrong'));
        
        echo "✓ Password hashing test passed\n";
    }
}

$test = new UserModelTest();
$test->testUserCreation();
$test->testPasswordHashing();
```

This completes the comprehensive documentation for the BilloCraft framework. The framework provides a solid foundation for building web applications with clean MVC architecture, proper security practices, and modern PHP features.

---

## email

```php

$result = send_email(
    [], // empty array uses defaults from .env
    'recipient@example.com',
    'Recipient Name',
    'Test Email',
    '<h1>Hello</h1><p>This is a test email</p>'
);

if ($result === true) {
    echo "Email sent successfully!";
} else {
    echo $result;
}

// Or override SMTP for this specific email
$result = send_email(
    [
        'host' => 'smtp.office365.com',
        'port' => 587,
        'username' => 'user@outlook.com',
        'password' => 'password123',
        'encryption' => 'tls',
        'fromEmail' => 'user@outlook.com',
        'fromName' => 'Outlook App'
    ],
    'recipient2@example.com',
    'Recipient Two',
    'Outlook Email',
    '<p>Hello from Outlook SMTP</p>'
);
```