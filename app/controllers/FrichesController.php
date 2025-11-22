<?php
/**
 * Contrôleur Friches
 * Fichier : app/controllers/FrichesController.php
 * Gestion de l'affichage et des données des friches
 */

require_once __DIR__ . '/../models/Friche.php';

class FrichesController {
    private $frichesModel;
    
    public function __construct() {
        $this->frichesModel = new Friche();
    }
    
    /**
     * Affiche la page du tableau des friches
     */
    public function index() {
        // Récupérer les colonnes disponibles
        $columns = $this->frichesModel->getColumns();
        
        // Récupérer les valeurs distinctes pour les filtres
        $types = $this->frichesModel->getDistinctValues('site_type');
        $statuts = $this->frichesModel->getDistinctValues('site_statut');
        $pollutions = $this->frichesModel->getDistinctValues('sol_pollution_existe');
        $communes = $this->frichesModel->getDistinctCommunes();
        $codesInsee = $this->frichesModel->getDistinctCodesInsee();
        
        require_once __DIR__ . '/../views/friches/index.php';
    }
    
    /**
     * Retourne les données des friches en JSON pour AJAX
     */
    public function getDataJson() {
        header('Content-Type: application/json');
        
        try {
            // Récupération des paramètres de la requête
            $page = isset($_GET['pageNum']) ? max(1, (int)$_GET['pageNum']) : 1;
            $perPage = isset($_GET['per_page']) ? min(max(10, (int)$_GET['per_page']), 100) : 25;
            $sortColumn = $_GET['sort_column'] ?? 'id';
            $sortDirection = $_GET['sort_direction'] ?? 'ASC';
            
            // Récupération des filtres
            $filters = [];
            if (!empty($_GET['search'])) {
                $filters['search'] = trim($_GET['search']);
            }
            if (!empty($_GET['site_type'])) {
                $filters['site_type'] = $_GET['site_type'];
            }
            if (!empty($_GET['site_statut'])) {
                $filters['site_statut'] = $_GET['site_statut'];
            }
            if (!empty($_GET['comm_nom'])) {
                $filters['comm_nom'] = trim($_GET['comm_nom']);
            }
            if (!empty($_GET['comm_insee'])) {
                $filters['comm_insee'] = trim($_GET['comm_insee']);
            }
            if (!empty($_GET['sol_pollution_existe'])) {
                $filters['sol_pollution_existe'] = $_GET['sol_pollution_existe'];
            }
            if (!empty($_GET['surface_min'])) {
                $filters['surface_min'] = (float)$_GET['surface_min'];
            }
            if (!empty($_GET['surface_max'])) {
                $filters['surface_max'] = (float)$_GET['surface_max'];
            }
            if (!empty($_GET['date_min'])) {
                $filters['date_min'] = $_GET['date_min'];
            }
            if (!empty($_GET['date_max'])) {
                $filters['date_max'] = $_GET['date_max'];
            }
            
            // Calcul de l'offset
            $offset = ($page - 1) * $perPage;
            
            // Récupération des données
            $friches = $this->frichesModel->findAll($filters, $sortColumn, $sortDirection, $perPage, $offset);
            $totalCount = $this->frichesModel->count($filters);
            $totalPages = ceil($totalCount / $perPage);
            
            // Préparation de la réponse
            $response = [
                'success' => true,
                'data' => $friches,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total_items' => $totalCount,
                    'total_pages' => $totalPages,
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $totalCount)
                ],
                'sort' => [
                    'column' => $sortColumn,
                    'direction' => $sortDirection
                ]
            ];
            
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de la récupération des données',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    
    /**
     * Retourne les valeurs distinctes pour un filtre donné
     */
    public function getFilterValues() {
        header('Content-Type: application/json');
        
        $column = $_GET['column'] ?? '';
        
        try {
            $values = $this->frichesModel->getDistinctValues($column);
            
            echo json_encode([
                'success' => true,
                'values' => $values
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de la récupération des valeurs'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    
    /**
     * Affiche le formulaire de création d'une friche (admin seulement)
     */
    public function create() {
        // Vérifier que l'utilisateur est admin
        require_once __DIR__ . '/AuthController.php';
        $auth = new AuthController();
        $auth->requireAdmin();
        
        // Générer le token CSRF
        $csrfToken = $auth->generateCsrfToken();
        
        // Si c'est une soumission du formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->handleCreate();
        }
        
        // Afficher le formulaire
        require_once __DIR__ . '/../views/friches/create.php';
    }
    
    /**
     * Traite la création d'une friche
     */
    private function handleCreate() {
        require_once __DIR__ . '/AuthController.php';
        $auth = new AuthController();
        
        // Valider le token CSRF
        if (!isset($_POST['csrf_token']) || !$auth->validateCsrfToken($_POST['csrf_token'])) {
            $_SESSION['error'] = "Token de sécurité invalide. Veuillez réessayer.";
            header('Location: index.php?page=friches&action=create');
            exit;
        }
        
        // Validation des champs obligatoires
        $required = ['site_id', 'longitude', 'latitude', 'site_nom'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $_SESSION['error'] = "Le champ " . $field . " est obligatoire.";
                header('Location: index.php?page=friches&action=create');
                exit;
            }
        }
        
        // Validation des coordonnées
        $longitude = floatval($_POST['longitude']);
        $latitude = floatval($_POST['latitude']);
        
        if ($longitude < -180 || $longitude > 180) {
            $_SESSION['error'] = "La longitude doit être comprise entre -180 et 180.";
            header('Location: index.php?page=friches&action=create');
            exit;
        }
        
        if ($latitude < -90 || $latitude > 90) {
            $_SESSION['error'] = "La latitude doit être comprise entre -90 et 90.";
            header('Location: index.php?page=friches&action=create');
            exit;
        }
        
        // Préparer les données
        $data = [
            'site_id' => trim($_POST['site_id']),
            'longitude' => $longitude,
            'latitude' => $latitude,
            'site_nom' => trim($_POST['site_nom']),
            'site_type' => !empty($_POST['site_type']) ? trim($_POST['site_type']) : null,
            'site_statut' => !empty($_POST['site_statut']) ? trim($_POST['site_statut']) : null,
            'comm_nom' => !empty($_POST['comm_nom']) ? trim($_POST['comm_nom']) : null,
            'comm_insee' => !empty($_POST['comm_insee']) ? trim($_POST['comm_insee']) : null,
            'dept_nom' => !empty($_POST['dept_nom']) ? trim($_POST['dept_nom']) : null,
            'dept_code' => !empty($_POST['dept_code']) ? trim($_POST['dept_code']) : null,
            'reg_nom' => !empty($_POST['reg_nom']) ? trim($_POST['reg_nom']) : null,
            'unite_fonciere_surface' => !empty($_POST['unite_fonciere_surface']) ? floatval($_POST['unite_fonciere_surface']) : null
        ];
        
        // Créer la friche
        $id = $this->frichesModel->create($data);
        
        if ($id) {
            $_SESSION['success'] = "Friche créée avec succès.";
            header('Location: index.php?page=friches');
        } else {
            $_SESSION['error'] = "Erreur lors de la création de la friche.";
            header('Location: index.php?page=friches&action=create');
        }
        exit;
    }
    
    /**
     * Affiche le formulaire d'édition d'une friche (admin seulement)
     */
    public function edit() {
        // Vérifier que l'utilisateur est admin
        require_once __DIR__ . '/AuthController.php';
        $auth = new AuthController();
        $auth->requireAdmin();
        
        // Vérifier l'ID
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            $_SESSION['error'] = "ID de friche manquant.";
            header('Location: index.php?page=friches');
            exit;
        }
        
        $id = (int)$_GET['id'];
        
        // Si c'est une soumission du formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->handleEdit($id);
        }
        
        // Récupérer les données de la friche
        $friche = $this->frichesModel->findById($id);
        
        if (!$friche) {
            $_SESSION['error'] = "Friche non trouvée.";
            header('Location: index.php?page=friches');
            exit;
        }
        
        // Générer le token CSRF
        $csrfToken = $auth->generateCsrfToken();
        
        // Afficher le formulaire
        require_once __DIR__ . '/../views/friches/edit.php';
    }
    
    /**
     * Traite la modification d'une friche
     */
    private function handleEdit($id) {
        require_once __DIR__ . '/AuthController.php';
        $auth = new AuthController();
        
        // Valider le token CSRF
        if (!isset($_POST['csrf_token']) || !$auth->validateCsrfToken($_POST['csrf_token'])) {
            $_SESSION['error'] = "Token de sécurité invalide. Veuillez réessayer.";
            header('Location: index.php?page=friches&action=edit&id=' . $id);
            exit;
        }
        
        // Validation des champs obligatoires
        $required = ['site_id', 'longitude', 'latitude', 'site_nom'];
        foreach ($required as $field) {
            if (empty($_POST[$field])) {
                $_SESSION['error'] = "Le champ " . $field . " est obligatoire.";
                header('Location: index.php?page=friches&action=edit&id=' . $id);
                exit;
            }
        }
        
        // Validation des coordonnées
        $longitude = floatval($_POST['longitude']);
        $latitude = floatval($_POST['latitude']);
        
        if ($longitude < -180 || $longitude > 180) {
            $_SESSION['error'] = "La longitude doit être comprise entre -180 et 180.";
            header('Location: index.php?page=friches&action=edit&id=' . $id);
            exit;
        }
        
        if ($latitude < -90 || $latitude > 90) {
            $_SESSION['error'] = "La latitude doit être comprise entre -90 et 90.";
            header('Location: index.php?page=friches&action=edit&id=' . $id);
            exit;
        }
        
        // Préparer les données
        $data = [
            'site_id' => trim($_POST['site_id']),
            'longitude' => $longitude,
            'latitude' => $latitude,
            'site_nom' => trim($_POST['site_nom']),
            'site_type' => !empty($_POST['site_type']) ? trim($_POST['site_type']) : null,
            'site_statut' => !empty($_POST['site_statut']) ? trim($_POST['site_statut']) : null,
            'comm_nom' => !empty($_POST['comm_nom']) ? trim($_POST['comm_nom']) : null,
            'comm_insee' => !empty($_POST['comm_insee']) ? trim($_POST['comm_insee']) : null,
            'dept_nom' => !empty($_POST['dept_nom']) ? trim($_POST['dept_nom']) : null,
            'dept_code' => !empty($_POST['dept_code']) ? trim($_POST['dept_code']) : null,
            'reg_nom' => !empty($_POST['reg_nom']) ? trim($_POST['reg_nom']) : null,
            'unite_fonciere_surface' => !empty($_POST['unite_fonciere_surface']) ? floatval($_POST['unite_fonciere_surface']) : null
        ];
        
        // Mettre à jour la friche
        $success = $this->frichesModel->update($id, $data);
        
        if ($success) {
            $_SESSION['success'] = "Friche modifiée avec succès.";
            header('Location: index.php?page=friches');
        } else {
            $_SESSION['error'] = "Erreur lors de la modification de la friche.";
            header('Location: index.php?page=friches&action=edit&id=' . $id);
        }
        exit;
    }
    
    /**
     * Supprime une friche via AJAX (admin seulement)
     */
    public function delete() {
        header('Content-Type: application/json');
        
        // Vérifier que l'utilisateur est admin
        require_once __DIR__ . '/AuthController.php';
        $auth = new AuthController();
        
        if (!$auth->isAdmin()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Accès refusé. Droits administrateur requis.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Valider le token CSRF
        if (!isset($_POST['csrf_token']) || !$auth->validateCsrfToken($_POST['csrf_token'])) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Token de sécurité invalide.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Vérifier l'ID
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'ID de friche manquant.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $id = (int)$_POST['id'];
        
        // Supprimer la friche
        $success = $this->frichesModel->delete($id);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => 'Friche supprimée avec succès.'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erreur lors de la suppression de la friche.'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}
