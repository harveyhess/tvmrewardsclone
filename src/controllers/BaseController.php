<?php
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../config/config.php';

class BaseController {
    protected $db;
    protected $session;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->startSession();
    }

    protected function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params(SESSION_LIFETIME);
            session_start();
        }
    }

    protected function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    protected function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . SITE_URL . 'admin/login');
            exit;
        }
    }

    protected function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }

    protected function requireAdmin() {
        if (!$this->isAdmin()) {
            header('Location: ' . SITE_URL . 'admin/login');
            exit;
        }
    }

    public function render($view, $data = []) {
        extract($data);
        $viewPath = __DIR__ . '/../views/' . $view . '.php';
        
        if (!file_exists($viewPath)) {
            throw new Exception("View {$view} not found");
        }

        ob_start();
        require $viewPath;
        return ob_get_clean();
    }

    protected function jsonResponse($data, $status = 200) {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
        exit;
    }

    protected function redirect($url) {
        header('Location: ' . SITE_URL . ltrim($url, '/'));
        exit;
    }

    protected function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
} 