<?php 

/**
 * microfy
 * router.php
 * v0.1.3 
 * Author: SirCode
 */

// Routing table
$GLOBALS['MICROFY_ROUTES'] = [
    'GET'    => [],
    'POST'   => [],
    'PUT'    => [],
    'DELETE' => [],
];

/**
 * Register a GET route.
 */
function get(string $path, callable $handler): void
{
    $GLOBALS['MICROFY_ROUTES']['GET'][$path] = $handler;
}

/**
 * Register a POST route.
 */
function post(string $path, callable $handler): void
{
    $GLOBALS['MICROFY_ROUTES']['POST'][$path] = $handler;
}

/**
 * Dispatch incoming request to the matching route.
 */
function handleRequest(): void
{
    $method     = $_SERVER['REQUEST_METHOD'];
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Strip base path
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    if ($basePath !== '' && strpos($requestUri, $basePath) === 0) {
        $path = substr($requestUri, strlen($basePath));
    } else {
        $path = $requestUri;
    }

    $uri = rtrim($path, '/') ?: '/';
    $routes = $GLOBALS['MICROFY_ROUTES'][$method] ?? [];

        // **DEBUG OUTPUT**:
    // echo "<pre>";
    // echo "REQUEST_URI = {$requestUri}\n";
    // echo "SCRIPT_NAME = {$_SERVER['SCRIPT_NAME']}\n";
    // echo "BASE_PATH   = {$basePath}\n";
    // echo "STRIPPED    = {$path}\n";
    // echo "URI         = {$uri}\n";
    // echo "ROUTES      = " . implode(', ', array_keys($routes)) . "\n";
    // echo "</pre>";
 

    // Exact match
    if (isset($routes[$uri])) {
        echo $routes[$uri]();
        return;
    }

    // Parameterized routes
    foreach ($routes as $route => $handler) {
        $pattern = '#^' . preg_replace('#\{[^}]+\}#', '([^/]+)', $route) . '$#';
        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches);
            echo call_user_func_array($handler, $matches);
            return;
        }
    }

    // No match: 404
    http_response_code(404);
    echo "404 Not Found";
}

?>