<?php
/**
 * Contrôleur Users
 * Fichier : app/controllers/UsersController.php
 * Gestion des utilisateurs (CRUD) - Réservé aux administrateurs
 */

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/AuthController.php';

class UsersController {
    private $userModel;
    private $authController;
    
    public function __construct() {
        $this->userModel = new User();
        $this->authController = new AuthController();
        
        // Vérifier que l'utilisateur est connecté et admin
        $this->authController->requireAuth();
        $this->requireAdmin();
    }
    
    /**
     * Vérifie que l'utilisateur est administrateur
     */
    private function requireAdmin() {
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            $_SESSION['error'] = "Accès refusé : droits d'administrateur requis";
            header('Location: /Friches/public/index.php?page=dashboard');
            exit;
        }
    }
    
    /**
     * Affiche la liste des utilisateurs
     */
    public function index() {
        // Générer le token CSRF pour les actions AJAX (suppression)
        $csrfToken = $this->authController->generateCsrfToken();
        
        require __DIR__ . '/../views/users/index.php';
    }
    
    /**
     * Retourne les données des utilisateurs en JSON pour AJAX
     */
    public function getUsersJson() {
        header('Content-Type: application/json');
        
        try {
            // Paramètres de pagination
            $page = isset($_GET['pageNum']) ? (int)$_GET['pageNum'] : 1;
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
            $perPage = min(max($perPage, 10), 100); // Entre 10 et 100
            
            $offset = ($page - 1) * $perPage;
            
            // Paramètres de tri
            $sortColumn = $_GET['sort_column'] ?? 'created_at';
            $sortDirection = $_GET['sort_direction'] ?? 'DESC';
            
            // Filtres
            $filters = [];
            if (!empty($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }
            if (!empty($_GET['role'])) {
                $filters['role'] = $_GET['role'];
            }
            if (isset($_GET['is_active']) && $_GET['is_active'] !== '') {
                $filters['is_active'] = $_GET['is_active'];
            }
            
            // Récupération des données
            $users = $this->userModel->findAll($filters, $sortColumn, $sortDirection, $perPage, $offset);
            $totalItems = $this->userModel->count($filters);
            $totalPages = ceil($totalItems / $perPage);
            
            // Pagination info
            $pagination = [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $totalItems)
            ];
            
            // Formater les données pour l'affichage
            foreach ($users as &$user) {
                // Masquer les infos sensibles
                unset($user['password_hash']);
                
                // Formater les dates
                if ($user['created_at']) {
                    $user['created_at_formatted'] = date('d/m/Y H:i', strtotime($user['created_at']));
                }
                if ($user['last_login']) {
                    $user['last_login_formatted'] = date('d/m/Y H:i', strtotime($user['last_login']));
                } else {
                    $user['last_login_formatted'] = 'Jamais';
                }
                
                // Rôle en français
                $user['role_fr'] = $user['role'] === 'admin' ? 'Administrateur' : 'Utilisateur';
                
                // Statut actif
                $user['is_active_text'] = $user['is_active'] ? 'Actif' : 'Inactif';
            }
            
            echo json_encode([
                'success' => true,
                'data' => $users,
                'pagination' => $pagination,
                'sort' => [
                    'column' => $sortColumn,
                    'direction' => $sortDirection
                ]
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    
    /**
     * Affiche le formulaire de création d'un utilisateur
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
        } else {
            // Générer le token CSRF
            require_once __DIR__ . '/AuthController.php';
            $auth = new AuthController();
            $csrfToken = $auth->generateCsrfToken();
            
            require __DIR__ . '/../views/users/create.php';
        }
    }
    
    /**
     * Traite la création d'un utilisateur
     */
    private function handleCreate() {
        try {
            // Validation CSRF
            require_once __DIR__ . '/AuthController.php';
            $auth = new AuthController();
            
            if (!isset($_POST['csrf_token']) || !$auth->validateCsrfToken($_POST['csrf_token'])) {
                $_SESSION['error'] = "Token de sécurité invalide. Veuillez réessayer.";
                header('Location: /Friches/public/index.php?page=users&action=create');
                exit;
            }
            
            // Validation
            $errors = [];
            
            if (empty($_POST['username'])) {
                $errors[] = "Le nom d'utilisateur est requis";
            } elseif ($this->userModel->usernameExists($_POST['username'])) {
                $errors[] = "Ce nom d'utilisateur existe déjà";
            }
            
            if (empty($_POST['email'])) {
                $errors[] = "L'email est requis";
            } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "L'email n'est pas valide";
            } elseif ($this->userModel->emailExists($_POST['email'])) {
                $errors[] = "Cet email existe déjà";
            }
            
            if (empty($_POST['password'])) {
                $errors[] = "Le mot de passe est requis";
            } elseif (strlen($_POST['password']) < 6) {
                $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
            }
            
            if ($_POST['password'] !== $_POST['password_confirm']) {
                $errors[] = "Les mots de passe ne correspondent pas";
            }
            
            if (!empty($errors)) {
                $_SESSION['error'] = implode('<br>', $errors);
                header('Location: /Friches/public/index.php?page=users&action=create');
                exit;
            }
            
            // Création de l'utilisateur
            $data = [
                'username' => $_POST['username'],
                'email' => $_POST['email'],
                'password' => $_POST['password'],
                'first_name' => $_POST['first_name'] ?? null,
                'last_name' => $_POST['last_name'] ?? null,
                'role' => $_POST['role'] ?? 'user',
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            $userId = $this->userModel->create($data);
            
            if ($userId) {
                $_SESSION['success'] = "Utilisateur créé avec succès";
                header('Location: /Friches/public/index.php?page=users');
            } else {
                $_SESSION['error'] = "Erreur lors de la création de l'utilisateur";
                header('Location: /Friches/public/index.php?page=users&action=create');
            }
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur : " . $e->getMessage();
            header('Location: /Friches/public/index.php?page=users&action=create');
            exit;
        }
    }
    
    /**
     * Affiche le formulaire d'édition d'un utilisateur
     */
    public function edit() {
        $userId = $_GET['id'] ?? null;
        
        if (!$userId) {
            $_SESSION['error'] = "ID utilisateur manquant";
            header('Location: /Friches/public/index.php?page=users');
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleEdit($userId);
        } else {
            $user = $this->userModel->findById($userId);
            
            if (!$user) {
                $_SESSION['error'] = "Utilisateur introuvable";
                header('Location: /Friches/public/index.php?page=users');
                exit;
            }
            
            // Générer le token CSRF
            require_once __DIR__ . '/AuthController.php';
            $auth = new AuthController();
            $csrfToken = $auth->generateCsrfToken();
            
            require __DIR__ . '/../views/users/edit.php';
        }
    }
    
    /**
     * Traite la modification d'un utilisateur
     */
    private function handleEdit($userId) {
        try {
            // Validation CSRF
            require_once __DIR__ . '/AuthController.php';
            $auth = new AuthController();
            
            if (!isset($_POST['csrf_token']) || !$auth->validateCsrfToken($_POST['csrf_token'])) {
                $_SESSION['error'] = "Token de sécurité invalide. Veuillez réessayer.";
                header('Location: /Friches/public/index.php?page=users&action=edit&id=' . $userId);
                exit;
            }
            
            // Validation
            $errors = [];
            
            if (empty($_POST['username'])) {
                $errors[] = "Le nom d'utilisateur est requis";
            } elseif ($this->userModel->usernameExists($_POST['username'], $userId)) {
                $errors[] = "Ce nom d'utilisateur existe déjà";
            }
            
            if (empty($_POST['email'])) {
                $errors[] = "L'email est requis";
            } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "L'email n'est pas valide";
            } elseif ($this->userModel->emailExists($_POST['email'], $userId)) {
                $errors[] = "Cet email existe déjà";
            }
            
            if (!empty($_POST['password']) && strlen($_POST['password']) < 6) {
                $errors[] = "Le mot de passe doit contenir au moins 6 caractères";
            }
            
            if (!empty($_POST['password']) && $_POST['password'] !== $_POST['password_confirm']) {
                $errors[] = "Les mots de passe ne correspondent pas";
            }
            
            if (!empty($errors)) {
                $_SESSION['error'] = implode('<br>', $errors);
                header('Location: /Friches/public/index.php?page=users&action=edit&id=' . $userId);
                exit;
            }
            
            // Mise à jour de l'utilisateur
            $data = [
                'username' => $_POST['username'],
                'email' => $_POST['email'],
                'first_name' => $_POST['first_name'] ?? null,
                'last_name' => $_POST['last_name'] ?? null,
                'role' => $_POST['role'] ?? 'user',
                'is_active' => isset($_POST['is_active']) ? 1 : 0
            ];
            
            if (!empty($_POST['password'])) {
                $data['password'] = $_POST['password'];
            }
            
            if ($this->userModel->update($userId, $data)) {
                $_SESSION['success'] = "Utilisateur modifié avec succès";
                header('Location: /Friches/public/index.php?page=users');
            } else {
                $_SESSION['error'] = "Erreur lors de la modification de l'utilisateur";
                header('Location: /Friches/public/index.php?page=users&action=edit&id=' . $userId);
            }
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur : " . $e->getMessage();
            header('Location: /Friches/public/index.php?page=users&action=edit&id=' . $userId);
            exit;
        }
    }
    
    /**
     * Supprime un utilisateur (AJAX)
     */
    public function delete() {
        header('Content-Type: application/json');
        
        try {
            // Validation CSRF
            if (!isset($_POST['csrf_token']) || !$this->authController->validateCsrfToken($_POST['csrf_token'])) {
                throw new Exception("Token de sécurité invalide");
            }
            
            $userId = $_POST['id'] ?? null;
            
            if (!$userId) {
                throw new Exception("ID utilisateur manquant");
            }
            
            // Empêcher la suppression de son propre compte
            if ($userId == $_SESSION['user_id']) {
                throw new Exception("Vous ne pouvez pas supprimer votre propre compte");
            }
            
            if ($this->userModel->delete($userId)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Utilisateur supprimé avec succès'
                ], JSON_UNESCAPED_UNICODE);
            } else {
                throw new Exception("Erreur lors de la suppression");
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
