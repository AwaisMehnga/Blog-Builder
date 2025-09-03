<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->view('home');
    }

}
