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
        require __DIR__ . '/../views/analytics/index.php';
    }
    
    /**
     * Retourne les statistiques globales en JSON
     */
    public function getGlobalStatsJson() {
        header('Content-Type: application/json');
        
        try {
            $stats = $this->fricheModel->getGlobalStats();
            
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
            $data = [];
            
            switch ($chartType) {
                case 'types':
                    $data = $this->fricheModel->getTypeDistribution();
                    break;
                    
                case 'statuts':
                    $data = $this->fricheModel->getStatusDistribution();
                    break;
                    
                case 'communes':
                    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
                    $data = $this->fricheModel->getTopCommunes($limit);
                    break;
                    
                case 'soil_pollution':
                    $data = $this->fricheModel->getSoilPollutionDistribution();
                    break;
                    
                case 'building_pollution':
                    $data = $this->fricheModel->getBuildingPollutionDistribution();
                    break;
                    
                case 'owner_types':
                    $data = $this->fricheModel->getOwnerTypeDistribution();
                    break;
                    
                case 'surfaces':
                    $data = $this->fricheModel->getSurfaceDistribution();
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
}
