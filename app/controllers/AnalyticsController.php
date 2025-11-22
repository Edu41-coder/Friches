<?php
/**
 * Contrôleur Analytics
 * Fichier : app/controllers/AnalyticsController.php
 * Gestion des analyses et statistiques
 */

require_once __DIR__ . '/../models/Friche.php';
require_once __DIR__ . '/AuthController.php';

class AnalyticsController {
    private $fricheModel;
    private $authController;
    
    public function __construct() {
        $this->fricheModel = new Friche();
        $this->authController = new AuthController();
        
        // Vérifier que l'utilisateur est connecté
        $this->authController->requireAuth();
    }
    
    /**
     * Affiche la page d'analytics
     */
    public function index() {
        // Récupérer les listes pour les filtres
        $communes = $this->fricheModel->getDistinctCommunes();
        $codesInsee = $this->fricheModel->getDistinctCodesInsee();
        
        require __DIR__ . '/../views/analytics/index.php';
    }
    
    /**
     * Retourne les statistiques globales en JSON
     */
    public function getGlobalStatsJson() {
        header('Content-Type: application/json');
        
        try {
            // Récupérer les filtres
            $filters = $this->getFiltersFromRequest();
            $stats = $this->fricheModel->getGlobalStats($filters);
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    
    /**
     * Retourne les données pour un graphique spécifique
     */
    public function getChartDataJson() {
        header('Content-Type: application/json');
        
        $chartType = $_GET['type'] ?? '';
        
        try {
            // Récupérer les filtres
            $filters = $this->getFiltersFromRequest();
            $data = [];
            
            switch ($chartType) {
                case 'types':
                    $data = $this->fricheModel->getTypeDistribution($filters);
                    break;
                    
                case 'statuts':
                    $data = $this->fricheModel->getStatusDistribution($filters);
                    break;
                    
                case 'communes':
                    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
                    $data = $this->fricheModel->getTopCommunes($limit, $filters);
                    break;
                    
                case 'soil_pollution':
                    $data = $this->fricheModel->getSoilPollutionDistribution($filters);
                    break;
                    
                case 'building_pollution':
                    $data = $this->fricheModel->getBuildingPollutionDistribution($filters);
                    break;
                    
                case 'owner_types':
                    $data = $this->fricheModel->getOwnerTypeDistribution($filters);
                    break;
                    
                case 'surfaces':
                    $data = $this->fricheModel->getSurfaceDistribution($filters);
                    break;
                    
                default:
                    throw new Exception("Type de graphique inconnu: {$chartType}");
            }
            
            echo json_encode([
                'success' => true,
                'data' => $data
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    
    /**
     * Extrait les filtres de la requête HTTP
     */
    private function getFiltersFromRequest() {
        $filters = [];
        
        if (!empty($_GET['comm_nom'])) {
            $filters['comm_nom'] = trim($_GET['comm_nom']);
        }
        
        if (!empty($_GET['comm_insee'])) {
            $filters['comm_insee'] = trim($_GET['comm_insee']);
        }
        
        return $filters;
    }
}
