<?php
/**
 * Contrôleur d'authentification
 * Fichier : app/controllers/AuthController.php
 */

require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $userModel;
    private $csrfTokenName = 'csrf_token';
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    /**
     * Affiche la page de connexion
     */
    public function showLoginForm() {
        // Si l'utilisateur est déjà connecté, rediriger vers le tableau de bord
        if ($this->isAuthenticated()) {
            header('Location: /Friches/public/index.php?page=dashboard');
            exit;
        }
        
        // Rendre $this disponible comme $auth dans la vue
        $auth = $this;
        require_once __DIR__ . '/../views/auth/login.php';
    }
    
    /**
     * Traite la soumission du formulaire de connexion
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /Friches/public/index.php?page=login');
            exit;
        }
        
        // Validation CSRF
        if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = "Token de sécurité invalide.";
            header('Location: /Friches/public/index.php?page=login');
            exit;
        }
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validation des données
        if (empty($username) || empty($password)) {
            $_SESSION['error'] = "Veuillez remplir tous les champs.";
            header('Location: /Friches/public/index.php?page=login');
            exit;
        }
        
        // Authentification
        $user = $this->userModel->authenticate($username, $password);
        
        if ($user) {
            // Stocker les informations de l'utilisateur en session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
            
            // Régénérer l'ID de session pour éviter la fixation de session
            session_regenerate_id(true);
            
            // Rediriger vers le tableau de bord
            header('Location: /Friches/public/index.php?page=dashboard');
            exit;
        } else {
            $_SESSION['error'] = "Nom d'utilisateur ou mot de passe incorrect.";
            header('Location: /Friches/public/index.php?page=login');
            exit;
        }
    }
    
    /**
     * Déconnecte l'utilisateur
     */
    public function logout() {
        // Détruire toutes les variables de session
        $_SESSION = [];
        
        // Détruire le cookie de session
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Détruire la session
        session_destroy();
        
        // Rediriger vers la page de connexion
        header('Location: /Friches/public/index.php?page=login');
        exit;
    }
    
    /**
     * Vérifie si l'utilisateur est authentifié
     * @return bool
     */
    public function isAuthenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Vérifie si l'utilisateur a le rôle administrateur
     * @return bool
     */
    public function isAdmin() {
        return $this->isAuthenticated() && $_SESSION['role'] === 'admin';
    }
    
    /**
     * Génère un token CSRF
     * @return string
     */
    public function generateCsrfToken() {
        if (!isset($_SESSION[$this->csrfTokenName])) {
            $_SESSION[$this->csrfTokenName] = bin2hex(random_bytes(32));
        }
        return $_SESSION[$this->csrfTokenName];
    }
    
    /**
     * Valide un token CSRF
     * @param string $token
     * @return bool
     */
    public function validateCsrfToken($token) {
        return isset($_SESSION[$this->csrfTokenName]) && hash_equals($_SESSION[$this->csrfTokenName], $token);
    }
    
    /**
     * Middleware pour protéger les pages nécessitant une authentification
     */
    public function requireAuth() {
        if (!$this->isAuthenticated()) {
            // Détecter si c'est une requête AJAX (plusieurs méthodes)
            $isAjax = (
                (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                (isset($_GET['action']) && in_array($_GET['action'], ['getData', 'getMapData', 'getFriche', 'getStats', 'getChartData', 'getFilterValues', 'delete']))
            );
            
            if ($isAjax) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Non authentifié',
                    'message' => 'Vous devez être connecté pour accéder à cette ressource.',
                    'redirect' => '/Friches/public/index.php?page=login'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Sinon, redirection classique
            $_SESSION['error'] = "Vous devez être connecté pour accéder à cette page.";
            header('Location: /Friches/public/index.php?page=login');
            exit;
        }
    }
    
    /**
     * Middleware pour protéger les pages réservées aux administrateurs
     */
    public function requireAdmin() {
        $this->requireAuth();
        
        if (!$this->isAdmin()) {
            // Détecter si c'est une requête AJAX (plusieurs méthodes)
            $isAjax = (
                (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
                (isset($_GET['action']) && in_array($_GET['action'], ['getData', 'getMapData', 'getFriche', 'getStats', 'getChartData', 'getFilterValues', 'delete']))
            );
            
            if ($isAjax) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Accès refusé',
                    'message' => 'Vous n\'avez pas les permissions nécessaires.'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // Sinon, redirection classique
            $_SESSION['error'] = "Vous n'avez pas les permissions nécessaires.";
            header('Location: /Friches/public/index.php?page=dashboard');
            exit;
        }
    }
}
