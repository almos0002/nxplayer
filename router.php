<?php

class Router {
    private $routes = [];
    private $notFoundCallback;
    private $config;

    public function __construct($config = []) {
        $this->config = $config;
    }

    public function get($path, $callback) {
        $this->routes['GET'][$path] = $callback;
    }

    public function post($path, $callback) {
        $this->routes['POST'][$path] = $callback;
    }

    public function notFound($callback) {
        $this->notFoundCallback = $callback;
    }

    private function getCurrentUri() {
        $uri = $_SERVER['REQUEST_URI'];
        $base = dirname($_SERVER['PHP_SELF']);
        
        // Remove base path from URI if it exists
        if (strpos($uri, $base) === 0) {
            $uri = substr($uri, strlen($base));
        }
        
        // Remove query string
        if (strpos($uri, '?') !== false) {
            $uri = strstr($uri, '?', true);
        }
        
        return '/' . trim($uri, '/');
    }

    private function matchRoute($route, $uri) {
        // If exact match
        if ($route === $uri) {
            return true;
        }

        // Check if route has parameters
        if (strpos($route, ':') !== false) {
            $routeParts = explode('/', trim($route, '/'));
            $uriParts = explode('/', trim($uri, '/'));

            // If different number of parts, not a match
            if (count($routeParts) !== count($uriParts)) {
                return false;
            }

            // Check each part
            for ($i = 0; $i < count($routeParts); $i++) {
                // If this part is a parameter (starts with :)
                if (strpos($routeParts[$i], ':') === 0) {
                    continue; // Skip validation for parameters
                }
                // If static part doesn't match
                if ($routeParts[$i] !== $uriParts[$i]) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    private function extractParameters($route, $uri) {
        $params = [];
        $routeParts = explode('/', trim($route, '/'));
        $uriParts = explode('/', trim($uri, '/'));

        for ($i = 0; $i < count($routeParts); $i++) {
            if (strpos($routeParts[$i], ':') === 0) {
                $paramName = substr($routeParts[$i], 1);
                $params[$paramName] = $uriParts[$i];
            }
        }

        return $params;
    }

    public function run() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $this->getCurrentUri();
        
        // Make config variables available to included files
        extract($this->config);
        
        // Check each route for a match
        foreach ($this->routes[$method] ?? [] as $route => $callback) {
            if ($this->matchRoute($route, $uri)) {
                $params = $this->extractParameters($route, $uri);
                if (is_callable($callback)) {
                    call_user_func($callback, $params);
                } else {
                    // For file includes, make params available
                    foreach ($params as $key => $value) {
                        ${$key} = $value;
                    }
                    require $callback;
                }
                return;
            }
        }

        // Route not found
        if ($this->notFoundCallback) {
            call_user_func($this->notFoundCallback);
        } else {
            header("HTTP/1.0 404 Not Found");
            echo "404 Not Found";
        }
    }
}
