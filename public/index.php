<?php
/**
 * Point d'entrée de l'application
 * Fichier : public/index.php
 */

// Afficher les erreurs pour le développement
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/FrichesController.php';
require_once __DIR__ . '/../app/controllers/UsersController.php';
require_once __DIR__ . '/../app/controllers/AnalyticsController.php';
require_once __DIR__ . '/../app/controllers/MapController.php';

// Instancier les contrôleurs
$auth = new AuthController();
$frichesController = new FrichesController();

// Router simple
$page = $_GET['page'] ?? 'login';
$action = $_GET['action'] ?? 'index';

// Routes de l'application
switch ($page) {
    case 'login':
        if ($action === 'submit') {
            $auth->login();
        } else {
            $auth->showLoginForm();
        }
        break;
        
    case 'logout':
        $auth->logout();
        break;
        
    case 'dashboard':
        // Vérifier l'authentification
        $auth->requireAuth();
        require_once __DIR__ . '/../app/views/dashboard.php';
        break;
        
    case 'friches':
        // Vérifier l'authentification
        $auth->requireAuth();
        
        if ($action === 'getData') {
            $frichesController->getDataJson();
        } elseif ($action === 'getFilterValues') {
            $frichesController->getFilterValues();
        } elseif ($action === 'create') {
            $frichesController->create();
        } elseif ($action === 'edit') {
            $frichesController->edit();
        } elseif ($action === 'delete') {
            $frichesController->delete();
        } else {
            $frichesController->index();
        }
        break;
        
    case 'users':
        // Vérifier l'authentification et droits admin (fait dans le contrôleur)
        $usersController = new UsersController();
        
        if ($action === 'getData') {
            $usersController->getUsersJson();
        } elseif ($action === 'create') {
            $usersController->create();
        } elseif ($action === 'edit') {
            $usersController->edit();
        } elseif ($action === 'delete') {
            $usersController->delete();
        } else {
            $usersController->index();
        }
        break;
        
    case 'analytics':
        // Vérifier l'authentification
        $auth->requireAuth();
        $analyticsController = new AnalyticsController();
        
        if ($action === 'getStats') {
            $analyticsController->getGlobalStatsJson();
        } elseif ($action === 'getChartData') {
            $analyticsController->getChartDataJson();
        } else {
            $analyticsController->index();
        }
        break;
        
    case 'map':
        // Vérifier l'authentification
        $auth->requireAuth();
        $mapController = new MapController();
        
        if ($action === 'getMapData') {
            $mapController->getMapDataJson();
        } elseif ($action === 'getFriche') {
            $mapController->getFricheJson();
        } else {
            $mapController->index();
        }
        break;
        
    default:
        // Page par défaut : rediriger vers login
        header('Location: index.php?page=login');
        exit;
}
