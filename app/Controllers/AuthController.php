<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Response;

class AuthController extends Controller
{
    public function index(Request $request): Response
    {
        $is_logged_in = auth()->check();
        if($is_logged_in) {
            return Response::redirect('/');
        }
        return $this->view('auth');
    }
}