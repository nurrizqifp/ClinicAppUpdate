<?php
namespace App\App;

class Router {
    private array $routes = [];
    private string $basePath;

    public function __construct(string $basePath = '') {
        $this->basePath = rtrim($basePath, '/');
    }

    // Register a route: method, pattern, [Controller, method] or callable
    public function add(string $method, string $page, array|callable $handler): void {
        $this->routes[] = [
            'method'  => strtoupper($method),
            'page'    => $page,
            'handler' => $handler,
        ];
    }

    public function get(string $page, array|callable $handler): void {
        $this->add('GET', $page, $handler);
    }

    public function post(string $page, array|callable $handler): void {
        $this->add('POST', $page, $handler);
    }

    public function any(string $page, array|callable $handler): void {
        $this->add('ANY', $page, $handler);
    }

    /**
     * Dispatch the request using ?page= query param strategy.
     */
    public function dispatch(): void {
        $method = strtoupper($_SERVER['REQUEST_METHOD']);

        // Determine base folder path to support hosting in subdirectories
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $baseDir = dirname($scriptName);
        $baseDir = rtrim(str_replace('\\', '/', $baseDir), '/');

        // Parse path from current Request URI
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Strip base folder if present
        if ($baseDir !== '' && str_starts_with($uri, $baseDir)) {
            $uri = substr($uri, strlen($baseDir));
        }

        // Strip index.php if present
        if (str_starts_with($uri, '/index.php')) {
            $uri = substr($uri, 10);
        }

        $page = trim($uri, '/');

        // Fallback to page query parameter (legacy support)
        if ($page === '') {
            $page = trim(filter_input(INPUT_GET, 'page', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'dashboard');
        }
        if (str_contains($page, '?')) {
            $page = explode('?', $page)[0];
        }

        // 1. Try exact match first
        foreach ($this->routes as $route) {
            if (($route['method'] === 'ANY' || $route['method'] === $method)
                && $route['page'] === $page
            ) {
                // Run middleware for the matched route
                Middleware::handle($route['page']);
                $this->callHandler($route['handler']);
                return;
            }
        }

        // 2. Try segment fallback matching for clean URLs (e.g. /appointment/history -> matches route 'appointment')
        $segments = explode('/', $page);
        if (count($segments) > 1) {
            $baseRoute = $segments[0];
            $subAction = $segments[1];

            foreach ($this->routes as $route) {
                if (($route['method'] === 'ANY' || $route['method'] === $method)
                    && $route['page'] === $baseRoute
                ) {
                    // Automatically map clean URL segments to GET params for controller compatibility
                    if (!isset($_GET['action'])) {
                        $_GET['action'] = $subAction;
                    }
                    if (!isset($_GET['view'])) {
                        $_GET['view'] = $subAction;
                    }
                    // Run middleware for the matched base route
                    Middleware::handle($route['page']);
                    $this->callHandler($route['handler']);
                    return;
                }
            }
        }

        // 404 fallback
        http_response_code(404);
        $this->callHandler(function() {
            include BASE_PATH . '/src/Views/404.php';
        });
    }

    private function callHandler(array|callable $handler): void {
        if (is_callable($handler)) {
            call_user_func($handler);
        } elseif (is_array($handler) && count($handler) === 2) {
            [$controllerClass, $action] = $handler;
            $controller = new $controllerClass();
            $controller->$action();
        }
    }
}
