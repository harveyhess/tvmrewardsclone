<?php
class Router {
    private $routes;
    private $currentRoute;

    public function __construct($routes) {
        $this->routes = $routes;
    }

    public function match($path) {
        // Remove leading and trailing slashes
        $path = trim($path, '/');
        
        // Check if route exists
        if (isset($this->routes[$path])) {
            $this->currentRoute = $this->routes[$path];
            return true;
        }
        
        return false;
    }

    public function dispatch() {
        if (!$this->currentRoute) {
            throw new Exception('No route matched');
        }

        // Load the controller
        $controllerName = $this->currentRoute['controller'];
        $controllerFile = __DIR__ . '/../controllers/' . $controllerName . '.php';
        
        if (!file_exists($controllerFile)) {
            throw new Exception("Controller file not found: $controllerFile");
        }

        require_once $controllerFile;
        
        // Create controller instance
        $controller = new $controllerName();
        $action = $this->currentRoute['action'];
        
        // Call the action
        if (!method_exists($controller, $action)) {
            throw new Exception("Action not found: $action");
        }
        
        return $controller->$action();
    }

    public function getCurrentRoute() {
        return $this->currentRoute;
    }
} 