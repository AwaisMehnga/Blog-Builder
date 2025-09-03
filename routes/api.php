<?php

use Core\Router;

$router = Router::getInstance();

// API routes - All protected by admin middleware
$router->group(['prefix' => 'api/v1', 'middleware' => ['App\Middleware\AdminMiddleware', 'App\Middleware\SecurityHeadersMiddleware']], function($router) {
    
    // Dashboard & Analytics
    $router->get('/dashboard/stats', 'Api\DashboardController@stats');
    $router->get('/dashboard/analytics', 'Api\DashboardController@analytics');
    $router->get('/dashboard/activity', 'Api\DashboardController@activity');
    $router->get('/search', 'Api\DashboardController@search');
    
    // Blog Management
    $router->group(['controller' => 'Api\BlogController', 'prefix' => 'blogs'], function($router) {
        $router->get('/', 'index');           // GET /api/v1/blogs - List blogs with filters
        $router->post('/', 'store');          // POST /api/v1/blogs - Create blog
        $router->get('/{id}', 'show');        // GET /api/v1/blogs/{id} - Get single blog
        $router->put('/{id}', 'update');      // PUT /api/v1/blogs/{id} - Update blog
        $router->delete('/{id}', 'destroy');  // DELETE /api/v1/blogs/{id} - Delete blog
        $router->post('/bulk', 'bulk');       // POST /api/v1/blogs/bulk - Bulk operations
    });
    
    // Category Management
    $router->group(['controller' => 'Api\CategoryController', 'prefix' => 'categories'], function($router) {
        $router->get('/', 'index');           // GET /api/v1/categories - List categories
        $router->post('/', 'store');          // POST /api/v1/categories - Create category
        $router->get('/hierarchy', 'hierarchy'); // GET /api/v1/categories/hierarchy - Get hierarchical view
        $router->get('/{id}', 'show');        // GET /api/v1/categories/{id} - Get single category
        $router->put('/{id}', 'update');      // PUT /api/v1/categories/{id} - Update category
        $router->delete('/{id}', 'destroy');  // DELETE /api/v1/categories/{id} - Delete category
    });
    
    // Tag Management
    $router->group(['controller' => 'Api\TagController', 'prefix' => 'tags'], function($router) {
        $router->get('/', 'index');           // GET /api/v1/tags - List tags
        $router->post('/', 'store');          // POST /api/v1/tags - Create tag
        $router->get('/search', 'search');    // GET /api/v1/tags/search - Search tags
        $router->delete('/bulk', 'bulkDelete'); // DELETE /api/v1/tags/bulk - Bulk delete
        $router->get('/{id}', 'show');        // GET /api/v1/tags/{id} - Get single tag
        $router->put('/{id}', 'update');      // PUT /api/v1/tags/{id} - Update tag
        $router->delete('/{id}', 'destroy');  // DELETE /api/v1/tags/{id} - Delete tag
    });
    
    // Media & File Management
    $router->group(['controller' => 'Api\MediaController', 'prefix' => 'media'], function($router) {
        $router->post('/upload', 'upload');           // POST /api/v1/media/upload - Upload single file
        $router->post('/upload/multiple', 'uploadMultiple'); // POST /api/v1/media/upload/multiple - Upload multiple files
        $router->get('/list', 'list');                // GET /api/v1/media/list - List uploaded files
        $router->delete('/{filename}', 'delete');     // DELETE /api/v1/media/{filename} - Delete file
    });
});
