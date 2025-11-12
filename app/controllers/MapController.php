<?php
/**
 * Contrôleur Map
 * Fichier : app/controllers/MapController.php
 * Gestion de la carte interactive des friches
 */

require_once __DIR__ . '/../models/Friche.php';
require_once __DIR__ . '/../../config/database.php';

class MapController {
    private $frichesModel;
    
    public function __construct() {
        $this->frichesModel = new Friche();
    }
    
    /**
     * Affiche la page de la carte interactive
     */
    public function index() {
        // ID de friche spécifique (si lien depuis le tableau)
        $fricheId = $_GET['friche_id'] ?? null;
        
        require_once __DIR__ . '/../views/map/index.php';
    }
    
    /**
     * Retourne toutes les friches avec coordonnées en JSON pour la carte
     */
    public function getMapDataJson() {
        header('Content-Type: application/json');
        
        try {
            $db = Database::getInstance()->getConnection();
            
            $sql = "SELECT 
                        id,
                        site_id,
                        site_nom,
                        site_type,
                        site_statut,
                        comm_nom,
                        comm_insee,
                        longitude,
                        latitude,
                        unite_fonciere_surface,
                        activite_libelle,
                        sol_pollution_existe,
                        bati_pollution
                    FROM friches 
                    WHERE longitude IS NOT NULL 
                      AND latitude IS NOT NULL
                      AND longitude BETWEEN -180 AND 180
                      AND latitude BETWEEN -90 AND 90
                    ORDER BY id";
            
            $stmt = $db->query($sql);
            $friches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Formater les données pour GeoJSON
            $features = [];
            foreach ($friches as $friche) {
                $features[] = [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [
                            floatval($friche['longitude']),
                            floatval($friche['latitude'])
                        ]
                    ],
                    'properties' => [
                        'id' => $friche['id'],
                        'site_id' => $friche['site_id'],
                        'site_nom' => $friche['site_nom'] ?: 'Friche sans nom',
                        'site_type' => $friche['site_type'] ?: 'Non spécifié',
                        'site_statut' => $friche['site_statut'] ?: 'Non spécifié',
                        'comm_nom' => $friche['comm_nom'] ?: 'Non spécifié',
                        'comm_insee' => $friche['comm_insee'] ?: 'Non spécifié',
                        'surface' => $friche['unite_fonciere_surface'] ? number_format($friche['unite_fonciere_surface'], 0, ',', ' ') : 'Non spécifié',
                        'activite' => $friche['activite_libelle'] ?: 'Non spécifié',
                        'pollution_sol' => $friche['sol_pollution_existe'] ?: 'Non spécifié',
                        'pollution_bati' => $friche['bati_pollution'] ?: 'Non spécifié'
                    ]
                ];
            }
            
            $geojson = [
                'type' => 'FeatureCollection',
                'features' => $features
            ];
            
            echo json_encode($geojson, JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Erreur lors de la récupération des données',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    
    /**
     * Retourne une friche spécifique en JSON
     */
    public function getFricheJson() {
        header('Content-Type: application/json');
        
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID manquant'], JSON_UNESCAPED_UNICODE);
            return;
        }
        
        try {
            $friche = $this->frichesModel->findById($id);
            
            if (!$friche) {
                http_response_code(404);
                echo json_encode(['error' => 'Friche non trouvée'], JSON_UNESCAPED_UNICODE);
                return;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $friche
            ], JSON_UNESCAPED_UNICODE);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Erreur lors de la récupération de la friche',
                'message' => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
