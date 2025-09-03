<?php

use Core\Router;


$router = Router::getInstance();

$router->get('/', 'HomeController@index')->name('home');

// Admin routes - Fully secured and non-indexable
$router->group(['prefix' => 'admin/awais-mehnga', 'middleware' => 'App\Middleware\SecurityHeadersMiddleware'], function($router) {
    $router->get('/', 'AdminController@index');
    $router->get('/login', 'AdminController@login');
    $router->post('/login', 'AdminController@login');
    $router->get('/logout', 'AdminController@logout');
    
    // Protected admin routes with double middleware protection
    $router->get('/dashboard', 'AdminController@dashboard')->middleware(['App\Middleware\AdminMiddleware', 'App\Middleware\SecurityHeadersMiddleware']);
});

