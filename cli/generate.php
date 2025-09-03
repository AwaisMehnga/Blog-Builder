<?php

// cli/generate.php

declare(strict_types=1);

const BASE_PATH = __DIR__ . '/../';

require_once BASE_PATH . 'vendor/autoload.php';


$commands = [
    'make:controller' => 'Generate a new controller',
    'make:model' => 'Generate a new model',
    'make:middleware' => 'Generate a new middleware',
    'make:view' => 'Generate a new view',
];


$usage = <<<USAGE
Available commands:
  make:controller ControllerName
  make:model ModelName
  make:middleware MiddlewareName
  make:view ViewName [--type=server|react]
USAGE;

// Check CLI input
if ($argc < 2) {
    echo $usage . PHP_EOL;
    exit(1);
}

$command = $argv[1];
$name = $argv[2] ?? null;

switch ($command) {
    case 'make:controller':
        if (!$name) {
            echo "Controller name required." . PHP_EOL;
            exit(1);
        }
        generateController($name);
        break;

    case 'make:model':
        if (!$name) {
            echo "Model name required." . PHP_EOL;
            exit(1);
        }
        generateModel($name);
        break;

    case 'make:middleware':
        if (!$name) {
            echo "Middleware name required." . PHP_EOL;
            exit(1);
        }
        generateMiddleware($name);
        break;

    case 'make:view':
        if (!$name) {
            echo "View name required." . PHP_EOL;
            exit(1);
        }
        $type = getViewType($argv);
        generateView($name, $type);
        break;



    default:
        echo "Unknown command: {$command}" . PHP_EOL;
        echo $usage . PHP_EOL;
        exit(1);
}

function generateController(string $name): void
{
    $class = ucfirst($name);
    $dir = BASE_PATH . "app/Controllers/";
    $file = "{$dir}{$class}.php";

    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (file_exists($file)) {
        echo "Controller already exists: {$file}" . PHP_EOL;
        return;
    }

    $template = <<<PHP
<?php

namespace App\Controllers;

use Core\Controller;
use Core\Request;
use Core\Response;

class {$class} extends Controller
{
    public function index(Request \$request): Response
    {
        return \$this->view(strtolower('{$class}'));
    }
}
PHP;

    file_put_contents($file, $template);
    echo "Controller created: {$file}" . PHP_EOL;
}

function generateModel(string $name): void
{
    $class = ucfirst($name);
    $dir = BASE_PATH . "app/Models/";
    $file = "{$dir}{$class}.php";

    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (file_exists($file)) {
        echo "Model already exists: {$file}" . PHP_EOL;
        return;
    }

    $table = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name)) . 's';

    $template = <<<PHP
<?php

namespace App\Models;

use Core\Model;

class {$class} extends Model
{
    protected string \$table = '{$table}';
    protected string \$primaryKey = 'id';
    public bool \$timestamps = true;
    protected array \$fillable = [];
}
PHP;

    file_put_contents($file, $template);
    echo "Model created: {$file}" . PHP_EOL;
}

function generateMiddleware(string $name): void
{
    $class = ucfirst($name);
    $dir = BASE_PATH . "app/Middleware/";
    $file = "{$dir}{$class}.php";

    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (file_exists($file)) {
        echo "Middleware already exists: {$file}" . PHP_EOL;
        return;
    }


    $template = <<<PHP
<?php

namespace App\Middleware;

use Core\Middleware;
use Core\Request;
use Core\Response;
use Closure;

class {$class} implements Middleware
{
    public function handle(Request \$request, Closure \$next, ...\$params): Response
    {
        // Add middleware logic here
        return \$next(\$request);
    }
}
PHP;

    file_put_contents($file, $template);
    echo "Middleware created: {$file}" . PHP_EOL;
}

function getViewType(array $argv): string
{
    foreach ($argv as $arg) {
        if (strpos($arg, '--type=') === 0) {
            $type = substr($arg, 7);
            return in_array($type, ['server', 'react']) ? $type : 'server';
        }
    }
    return 'server';
}

function generateView(string $name, string $type = 'server'): void
{
    $viewName = strtolower($name);
    $viewPath = str_replace('.', '/', $viewName);
    $dir = BASE_PATH . "app/Views/" . dirname($viewPath);
    $fileName = basename($viewPath) . '.php';
    $file = $dir . '/' . $fileName;

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    if (file_exists($file)) {
        echo "View already exists: {$file}" . PHP_EOL;
        return;
    }

    $title = ucwords(str_replace(['.', '_', '-'], ' ', $viewName));

    if ($type === 'react') {
        $reactPath = 'resources/js/' . ucfirst($viewName) . '/app.jsx';
        $template = <<<PHP
<?php 
\$title = '{$title} - BilloCraft';
\$description = 'Description for {$title}';
\$viteEntry = '{$reactPath}';
?>
<?php ob_start(); ?>

<div id="app"></div>

<?php \$content = ob_get_clean(); ?>
<?php include view_path('layouts.main'); ?>
PHP;
        
        createReactComponent($reactPath);
    } else {
        $template = <<<PHP
<?php 
\$title = '{$title} - BilloCraft';
\$description = 'Description for {$title}';
?>
<?php ob_start(); ?>

<div class="container">
    <h1>{$title}</h1>
    <p>Welcome to the {$viewName} page!</p>
</div>

<?php \$content = ob_get_clean(); ?>
<?php include view_path('layouts.main'); ?>
PHP;
    }

    file_put_contents($file, $template);
    echo "View created: {$file}" . PHP_EOL;
    
    if ($type === 'react') {
        echo "React component created: {$reactPath}" . PHP_EOL;
        echo "Don't forget to build your assets with: npm run dev" . PHP_EOL;
    }
}

function createReactComponent(string $path): void
{
    $fullPath = BASE_PATH . $path;
    $dir = dirname($fullPath);
    
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    if (file_exists($fullPath)) {
        return;
    }
    
    $componentName = basename(dirname($path));
    
    $template = <<<JSX
import React from "react";
import { createRoot } from "react-dom/client";

function {$componentName}App() {
  return (
    <div className="container">
      <h1>Welcome to {$componentName}</h1>
      <p>This is a React component!</p>
    </div>
  );
}

// Mount React app
const rootElement = document.getElementById("app");
if (rootElement) {
  createRoot(rootElement).render(<{$componentName}App />);
}
JSX;


    file_put_contents($fullPath, $template);
}


