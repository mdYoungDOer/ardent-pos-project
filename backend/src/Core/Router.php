<?php

namespace ArdentPOS\Core;

class Router
{
    private array $routes = [];
    private array $middlewareStack = [];

    public function get(string $path, string $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, string $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, string $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, string $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    public function group(string $prefix, callable $callback, array $middleware = []): void
    {
        $previousMiddleware = $this->middlewareStack;
        $this->middlewareStack = array_merge($this->middlewareStack, $middleware);
        
        $previousRoutes = $this->routes;
        $this->routes = [];
        
        $callback($this);
        
        $groupRoutes = $this->routes;
        $this->routes = $previousRoutes;
        
        foreach ($groupRoutes as $method => $routes) {
            foreach ($routes as $path => $route) {
                $fullPath = $prefix . $path;
                $this->routes[$method][$fullPath] = $route;
            }
        }
        
        $this->middlewareStack = $previousMiddleware;
    }

    private function addRoute(string $method, string $path, string $handler): void
    {
        $this->routes[$method][$path] = [
            'handler' => $handler,
            'middleware' => $this->middlewareStack
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $route = $this->findRoute($method, $uri);
        
        if (!$route) {
            $this->sendResponse(404, ['error' => 'Route not found', 'method' => $method, 'uri' => $uri]);
            return;
        }

        try {
            // Apply middleware
            foreach ($route['middleware'] as $middleware) {
                if (is_string($middleware)) {
                    if (strpos($middleware, ':') !== false) {
                        [$middlewareClass, $param] = explode(':', $middleware, 2);
                        $middlewareInstance = new $middlewareClass();
                        $middlewareInstance->handle($param);
                    } else {
                        $middlewareInstance = new $middleware();
                        $middlewareInstance->handle();
                    }
                } else {
                    $middleware::handle();
                }
            }

            // Execute controller action
            [$controllerName, $action] = explode('@', $route['handler']);
            $controllerClass = "ArdentPOS\\Controllers\\{$controllerName}";
            
            if (!class_exists($controllerClass)) {
                throw new \Exception("Controller {$controllerClass} not found");
            }

            $controller = new $controllerClass();
            
            if (!method_exists($controller, $action)) {
                throw new \Exception("Method {$action} not found in {$controllerClass}");
            }

            // Get route parameters
            $params = $route['params'] ?? [];
            
            // Call the controller method with parameters
            if (!empty($params)) {
                $controller->$action(...array_values($params));
            } else {
                $controller->$action();
            }

        } catch (\Exception $e) {
            error_log("Router Error: " . $e->getMessage());
            error_log("Router Error Trace: " . $e->getTraceAsString());
            
            $this->sendResponse(500, [
                'error' => 'Internal Server Error',
                'message' => Config::get('app.debug') ? $e->getMessage() : 'Something went wrong'
            ]);
        }
    }

    private function findRoute(string $method, string $uri): ?array
    {
        if (!isset($this->routes[$method])) {
            return null;
        }

        // First try exact match
        if (isset($this->routes[$method][$uri])) {
            return $this->routes[$method][$uri];
        }

        // Try pattern matching for routes with parameters
        foreach ($this->routes[$method] as $pattern => $route) {
            $params = $this->matchRoute($pattern, $uri);
            if ($params !== null) {
                $route['params'] = $params;
                return $route;
            }
        }

        return null;
    }

    private function matchRoute(string $pattern, string $uri): ?array
    {
        // Convert route pattern to regex
        $regex = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $uri, $matches)) {
            return null;
        }

        // Extract parameter names
        preg_match_all('/\{([^}]+)\}/', $pattern, $paramNames);
        
        $params = [];
        for ($i = 1; $i < count($matches); $i++) {
            $paramName = $paramNames[1][$i - 1] ?? $i - 1;
            $params[$paramName] = $matches[$i];
        }

        return $params;
    }

    private function sendResponse(int $statusCode, $data): void
    {
        http_response_code($statusCode);
        
        if (is_array($data) || is_object($data)) {
            echo json_encode($data, JSON_PRETTY_PRINT);
        } else {
            echo $data;
        }
    }
}
