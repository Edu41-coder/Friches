<?php
/**
 * Modèle Friche
 * Fichier : app/models/Friche.php
 * Gestion des données de friches
 */

require_once __DIR__ . '/../../config/database.php';

class Friche {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Récupère toutes les colonnes disponibles pour le tableau
     * @return array
     */
    public function getColumns() {
        return [
            // Identification
            'id' => 'ID',
            'site_id' => 'Identifiant site',
            
            // Coordonnées
            'longitude' => 'Longitude',
            'latitude' => 'Latitude',
            
            // Informations générales du site
            'site_nom' => 'Nom du site',
            'site_type' => 'Type de site',
            'site_identif_date' => 'Date identification',
            'site_actu_date' => 'Date actualisation',
            'site_url' => 'URL site',
            'site_securite' => 'Sécurité',
            'site_occupation' => 'Occupation',
            'site_statut' => 'Statut',
            
            // Activité
            'activite_libelle' => 'Activité',
            
            // Localisation communale
            'comm_nom' => 'Commune',
            'comm_insee' => 'Code INSEE',
            
            // Informations bâtiments
            'bati_type' => 'Type bâtiment',
            'bati_nombre' => 'Nombre bâtiments',
            'bati_pollution' => 'Pollution bâtiment',
            'bati_vacance' => 'Vacance bâtiment',
            'bati_patrimoine' => 'Patrimoine',
            'bati_etat' => 'État bâtiment',
            'local_ancien_annee' => 'Année local ancien',
            'local_recent_annee' => 'Année local récent',
            
            // Propriétaire
            'proprio_type' => 'Type propriétaire',
            'proprio_personne' => 'Propriétaire personne',
            'proprio_nom' => 'Nom propriétaire',
            
            // Pollution sol
            'sol_pollution_existe' => 'Pollution sol',
            'sol_pollution_origine' => 'Origine pollution',
            
            // Unité foncière
            'unite_fonciere_surface' => 'Surface (m²)',
            'unite_fonciere_refcad' => 'Référence cadastrale',
            
            // Urbanisme
            'urba_zone_type' => 'Type zone urbanisme',
            'urba_zone_lib' => 'Libellé zone',
            'urba_doc_type' => 'Type document urbanisme',
            
            // Source
            'source_nom' => 'Source nom',
            'source_url' => 'Source URL',
            'source_producteur' => 'Source producteur',
            
            // Timestamps
            'created_at' => 'Date création',
            'updated_at' => 'Date modification'
        ];
    }
    
    /**
     * Récupère les friches avec filtres, tri et pagination
     * @param array $filters Filtres à appliquer
     * @param string $sortColumn Colonne de tri
     * @param string $sortDirection Direction du tri (ASC/DESC)
     * @param int $limit Nombre d'éléments par page
     * @param int $offset Décalage pour la pagination
     * @return array
     */
    public function findAll($filters = [], $sortColumn = 'id', $sortDirection = 'ASC', $limit = 25, $offset = 0) {
        $sql = "SELECT * FROM friches WHERE 1=1";
        $params = [];
        
        // Application des filtres
        if (!empty($filters['search'])) {
            $sql .= " AND site_id LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['site_type'])) {
            $sql .= " AND site_type = :site_type";
            $params['site_type'] = $filters['site_type'];
        }
        
        if (!empty($filters['site_statut'])) {
            $sql .= " AND site_statut = :site_statut";
            $params['site_statut'] = $filters['site_statut'];
        }
        
        if (!empty($filters['comm_nom'])) {
            $sql .= " AND comm_nom = :comm_nom";
            $params['comm_nom'] = $filters['comm_nom'];
        }
        
        if (!empty($filters['comm_insee'])) {
            // Vérifier si c'est un filtre par département (dept:XX)
            if (strpos($filters['comm_insee'], 'dept:') === 0) {
                $dept = substr($filters['comm_insee'], 5); // Extraire le code département
                $sql .= " AND comm_insee LIKE :comm_insee";
                $params['comm_insee'] = $dept . '%';
            } else {
                $sql .= " AND comm_insee = :comm_insee";
                $params['comm_insee'] = $filters['comm_insee'];
            }
        }
        
        if (!empty($filters['sol_pollution_existe'])) {
            $sql .= " AND sol_pollution_existe = :sol_pollution_existe";
            $params['sol_pollution_existe'] = $filters['sol_pollution_existe'];
        }
        
        if (!empty($filters['surface_min'])) {
            $sql .= " AND unite_fonciere_surface >= :surface_min";
            $params['surface_min'] = $filters['surface_min'];
        }
        
        if (!empty($filters['surface_max'])) {
            $sql .= " AND unite_fonciere_surface <= :surface_max";
            $params['surface_max'] = $filters['surface_max'];
        }
        
        if (!empty($filters['date_min'])) {
            $sql .= " AND site_identif_date >= :date_min";
            $params['date_min'] = $filters['date_min'];
        }
        
        if (!empty($filters['date_max'])) {
            $sql .= " AND site_identif_date <= :date_max";
            $params['date_max'] = $filters['date_max'];
        }
        
        // Validation et ajout du tri
        $allowedColumns = array_keys($this->getColumns());
        if (!in_array($sortColumn, $allowedColumns)) {
            $sortColumn = 'id';
        }
        
        $sortDirection = strtoupper($sortDirection) === 'DESC' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY {$sortColumn} {$sortDirection}";
        
        // Pagination
        $limit = min(max(1, (int)$limit), 100); // Entre 1 et 100
        $offset = max(0, (int)$offset);
        $sql .= " LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        
        // Bind des paramètres
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Compte le nombre total de friches avec les filtres appliqués
     * @param array $filters Filtres à appliquer
     * @return int
     */
    public function count($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM friches WHERE 1=1";
        $params = [];
        
        // Application des mêmes filtres que findAll
        if (!empty($filters['search'])) {
            $sql .= " AND site_id LIKE :search";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['site_type'])) {
            $sql .= " AND site_type = :site_type";
            $params['site_type'] = $filters['site_type'];
        }
        
        if (!empty($filters['site_statut'])) {
            $sql .= " AND site_statut = :site_statut";
            $params['site_statut'] = $filters['site_statut'];
        }
        
        if (!empty($filters['comm_nom'])) {
            $sql .= " AND comm_nom = :comm_nom";
            $params['comm_nom'] = $filters['comm_nom'];
        }
        
        if (!empty($filters['comm_insee'])) {
            // Vérifier si c'est un filtre par département (dept:XX)
            if (strpos($filters['comm_insee'], 'dept:') === 0) {
                $dept = substr($filters['comm_insee'], 5); // Extraire le code département
                $sql .= " AND comm_insee LIKE :comm_insee";
                $params['comm_insee'] = $dept . '%';
            } else {
                $sql .= " AND comm_insee = :comm_insee";
                $params['comm_insee'] = $filters['comm_insee'];
            }
        }
        
        if (!empty($filters['sol_pollution_existe'])) {
            $sql .= " AND sol_pollution_existe = :sol_pollution_existe";
            $params['sol_pollution_existe'] = $filters['sol_pollution_existe'];
        }
        
        if (!empty($filters['surface_min'])) {
            $sql .= " AND unite_fonciere_surface >= :surface_min";
            $params['surface_min'] = $filters['surface_min'];
        }
        
        if (!empty($filters['surface_max'])) {
            $sql .= " AND unite_fonciere_surface <= :surface_max";
            $params['surface_max'] = $filters['surface_max'];
        }
        
        if (!empty($filters['date_min'])) {
            $sql .= " AND site_identif_date >= :date_min";
            $params['date_min'] = $filters['date_min'];
        }
        
        if (!empty($filters['date_max'])) {
            $sql .= " AND site_identif_date <= :date_max";
            $params['date_max'] = $filters['date_max'];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch();
        return (int)$result['total'];
    }
    
    /**
     * Récupère les valeurs distinctes pour les filtres
     * @param string $column Nom de la colonne
     * @return array
     */
    public function getDistinctValues($column) {
        $allowedColumns = ['site_type', 'site_statut', 'sol_pollution_existe', 'bati_pollution', 'proprio_type'];
        
        if (!in_array($column, $allowedColumns)) {
            return [];
        }
        
        $sql = "SELECT DISTINCT {$column} as value FROM friches WHERE {$column} IS NOT NULL ORDER BY {$column}";
        $stmt = $this->db->query($sql);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Récupère les communes distinctes pour les filtres
     * @return array
     */
    public function getDistinctCommunes() {
        $sql = "SELECT DISTINCT comm_nom as value FROM friches WHERE comm_nom IS NOT NULL ORDER BY comm_nom";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Récupère les codes INSEE distincts pour les filtres
     * @return array
     */
    public function getDistinctCodesInsee() {
        $sql = "SELECT DISTINCT comm_insee as value FROM friches WHERE comm_insee IS NOT NULL ORDER BY comm_insee";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Récupère une friche par son ID
     * @param int $id
     * @return array|false
     */
    public function findById($id) {
        $sql = "SELECT * FROM friches WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        return $stmt->fetch();
    }
    
    // ==================== MÉTHODES STATISTIQUES ====================
    
    /**
     * Récupère les statistiques globales
     * @return array
     */
    public function getGlobalStats($filters = []) {
        $stats = [];
        $whereConditions = [];
        $params = [];
        
        // Construire les conditions WHERE selon les filtres
        if (!empty($filters['comm_nom'])) {
            $whereConditions[] = "comm_nom = :comm_nom";
            $params[':comm_nom'] = $filters['comm_nom'];
        } elseif (!empty($filters['comm_insee'])) {
            // Vérifier si c'est un filtre de département
            if (strpos($filters['comm_insee'], 'dept:') === 0) {
                $deptCode = substr($filters['comm_insee'], 5);
                $whereConditions[] = "comm_insee LIKE :comm_insee";
                $params[':comm_insee'] = $deptCode . '%';
            } else {
                $whereConditions[] = "comm_insee = :comm_insee";
                $params[':comm_insee'] = $filters['comm_insee'];
            }
        }
        
        $whereClause = !empty($whereConditions) ? ' WHERE ' . implode(' AND ', $whereConditions) : '';
        
        // Nombre total de friches
        $sql = "SELECT COUNT(*) as total FROM friches" . $whereClause;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $stats['total_friches'] = $stmt->fetchColumn();
        
        // Nombre de communes concernées
        $whereClauseWithComm = $whereClause ? $whereClause . ' AND comm_nom IS NOT NULL' : ' WHERE comm_nom IS NOT NULL';
        $sql = "SELECT COUNT(DISTINCT comm_nom) as total FROM friches" . $whereClauseWithComm;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $stats['total_communes'] = $stmt->fetchColumn();
        
        // Nombre de types de friches
        $whereClauseWithType = $whereClause ? $whereClause . ' AND site_type IS NOT NULL' : ' WHERE site_type IS NOT NULL';
        $sql = "SELECT COUNT(DISTINCT site_type) as total FROM friches" . $whereClauseWithType;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $stats['total_types'] = $stmt->fetchColumn();
        
        // Nombre de statuts différents
        $whereClauseWithStatut = $whereClause ? $whereClause . ' AND site_statut IS NOT NULL' : ' WHERE site_statut IS NOT NULL';
        $sql = "SELECT COUNT(DISTINCT site_statut) as total FROM friches" . $whereClauseWithStatut;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $stats['total_statuts'] = $stmt->fetchColumn();
        
        // Surface totale et moyenne
        $whereClauseWithSurface = $whereClause ? $whereClause . ' AND unite_fonciere_surface IS NOT NULL AND unite_fonciere_surface > 0' 
                                                : ' WHERE unite_fonciere_surface IS NOT NULL AND unite_fonciere_surface > 0';
        $sql = "SELECT 
                    SUM(unite_fonciere_surface) as surface_totale,
                    AVG(unite_fonciere_surface) as surface_moyenne
                FROM friches" . $whereClauseWithSurface;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        $stats['surface_totale'] = round($result['surface_totale'] ?? 0);
        $stats['surface_moyenne'] = round($result['surface_moyenne'] ?? 0);
        
        // Commune la plus touchée
        $whereClauseWithComm2 = $whereClause ? $whereClause . ' AND comm_nom IS NOT NULL' : ' WHERE comm_nom IS NOT NULL';
        $sql = "SELECT comm_nom, COUNT(*) as count 
                FROM friches" . $whereClauseWithComm2 . "
                GROUP BY comm_nom 
                ORDER BY count DESC 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        $stats['commune_max'] = $result['comm_nom'] ?? 'N/A';
        $stats['commune_max_count'] = $result['count'] ?? 0;
        
        return $stats;
    }
    
    /**
     * Récupère la distribution des types de friches
     * @param array $filters
     * @return array
     */
    public function getTypeDistribution($filters = []) {
        $whereConditions = ['site_type IS NOT NULL'];
        $params = [];
        
        // Construire les conditions WHERE selon les filtres
        if (!empty($filters['comm_nom'])) {
            $whereConditions[] = "comm_nom = :comm_nom";
            $params[':comm_nom'] = $filters['comm_nom'];
        } elseif (!empty($filters['comm_insee'])) {
            // Vérifier si c'est un filtre de département
            if (strpos($filters['comm_insee'], 'dept:') === 0) {
                $deptCode = substr($filters['comm_insee'], 5);
                $whereConditions[] = "comm_insee LIKE :comm_insee";
                $params[':comm_insee'] = $deptCode . '%';
            } else {
                $whereConditions[] = "comm_insee = :comm_insee";
                $params[':comm_insee'] = $filters['comm_insee'];
            }
        }
        
        $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
        
        $sql = "SELECT 
                    site_type as label, 
                    COUNT(*) as count 
                FROM friches" . $whereClause . "
                GROUP BY site_type 
                ORDER BY count DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Récupère la distribution des statuts
     * @param array $filters
     * @return array
     */
    public function getStatusDistribution($filters = []) {
        $whereConditions = ['site_statut IS NOT NULL'];
        $params = [];
        
        // Construire les conditions WHERE selon les filtres
        if (!empty($filters['comm_nom'])) {
            $whereConditions[] = "comm_nom = :comm_nom";
            $params[':comm_nom'] = $filters['comm_nom'];
        } elseif (!empty($filters['comm_insee'])) {
            // Vérifier si c'est un filtre de département
            if (strpos($filters['comm_insee'], 'dept:') === 0) {
                $deptCode = substr($filters['comm_insee'], 5);
                $whereConditions[] = "comm_insee LIKE :comm_insee";
                $params[':comm_insee'] = $deptCode . '%';
            } else {
                $whereConditions[] = "comm_insee = :comm_insee";
                $params[':comm_insee'] = $filters['comm_insee'];
            }
        }
        
        $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
        
        $sql = "SELECT 
                    site_statut as label, 
                    COUNT(*) as count 
                FROM friches" . $whereClause . "
                GROUP BY site_statut 
                ORDER BY count DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Récupère le top N des communes avec le plus de friches
     * @param int $limit
     * @param array $filters
     * @return array
     */
    public function getTopCommunes($limit = 10, $filters = []) {
        $whereConditions = ['comm_nom IS NOT NULL'];
        $params = [':limit' => (int)$limit];
        
        // Construire les conditions WHERE selon les filtres
        if (!empty($filters['comm_nom'])) {
            $whereConditions[] = "comm_nom = :comm_nom";
            $params[':comm_nom'] = $filters['comm_nom'];
        } elseif (!empty($filters['comm_insee'])) {
            // Vérifier si c'est un filtre de département
            if (strpos($filters['comm_insee'], 'dept:') === 0) {
                $deptCode = substr($filters['comm_insee'], 5);
                $whereConditions[] = "comm_insee LIKE :comm_insee";
                $params[':comm_insee'] = $deptCode . '%';
            } else {
                $whereConditions[] = "comm_insee = :comm_insee";
                $params[':comm_insee'] = $filters['comm_insee'];
            }
        }
        
        $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
        
        $sql = "SELECT 
                    comm_nom as label, 
                    COUNT(*) as count 
                FROM friches" . $whereClause . "
                GROUP BY comm_nom 
                ORDER BY count DESC 
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            if ($key === ':limit') {
                $stmt->bindValue($key, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Récupère la distribution de la pollution du sol
     * @param array $filters
     * @return array
     */
    public function getSoilPollutionDistribution($filters = []) {
        $whereConditions = [];
        $params = [];
        
        // Construire les conditions WHERE selon les filtres
        if (!empty($filters['comm_nom'])) {
            $whereConditions[] = "comm_nom = :comm_nom";
            $params[':comm_nom'] = $filters['comm_nom'];
        } elseif (!empty($filters['comm_insee'])) {
            // Vérifier si c'est un filtre de département
            if (strpos($filters['comm_insee'], 'dept:') === 0) {
                $deptCode = substr($filters['comm_insee'], 5);
                $whereConditions[] = "comm_insee LIKE :comm_insee";
                $params[':comm_insee'] = $deptCode . '%';
            } else {
                $whereConditions[] = "comm_insee = :comm_insee";
                $params[':comm_insee'] = $filters['comm_insee'];
            }
        }
        
        $whereClause = !empty($whereConditions) ? ' WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $sql = "SELECT 
                    CASE 
                        WHEN sol_pollution_existe = 'Oui' THEN 'Pollué'
                        WHEN sol_pollution_existe = 'Non' THEN 'Non pollué'
                        ELSE 'Inconnu'
                    END as label,
                    COUNT(*) as count 
                FROM friches" . $whereClause . "
                GROUP BY sol_pollution_existe 
                ORDER BY count DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Récupère la distribution de la pollution des bâtiments
     * @param array $filters
     * @return array
     */
    public function getBuildingPollutionDistribution($filters = []) {
        $whereConditions = ['bati_pollution IS NOT NULL'];
        $params = [];
        
        // Construire les conditions WHERE selon les filtres
        if (!empty($filters['comm_nom'])) {
            $whereConditions[] = "comm_nom = :comm_nom";
            $params[':comm_nom'] = $filters['comm_nom'];
        } elseif (!empty($filters['comm_insee'])) {
            // Vérifier si c'est un filtre de département
            if (strpos($filters['comm_insee'], 'dept:') === 0) {
                $deptCode = substr($filters['comm_insee'], 5);
                $whereConditions[] = "comm_insee LIKE :comm_insee";
                $params[':comm_insee'] = $deptCode . '%';
            } else {
                $whereConditions[] = "comm_insee = :comm_insee";
                $params[':comm_insee'] = $filters['comm_insee'];
            }
        }
        
        $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
        
        $sql = "SELECT 
                    bati_pollution as label, 
                    COUNT(*) as count 
                FROM friches" . $whereClause . "
                GROUP BY bati_pollution 
                ORDER BY count DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Récupère la distribution des types de propriétaires
     * @param array $filters
     * @return array
     */
    public function getOwnerTypeDistribution($filters = []) {
        $whereConditions = ['proprio_personne IS NOT NULL'];
        $params = [];
        
        // Construire les conditions WHERE selon les filtres
        if (!empty($filters['comm_nom'])) {
            $whereConditions[] = "comm_nom = :comm_nom";
            $params[':comm_nom'] = $filters['comm_nom'];
        } elseif (!empty($filters['comm_insee'])) {
            // Vérifier si c'est un filtre de département
            if (strpos($filters['comm_insee'], 'dept:') === 0) {
                $deptCode = substr($filters['comm_insee'], 5);
                $whereConditions[] = "comm_insee LIKE :comm_insee";
                $params[':comm_insee'] = $deptCode . '%';
            } else {
                $whereConditions[] = "comm_insee = :comm_insee";
                $params[':comm_insee'] = $filters['comm_insee'];
            }
        }
        
        $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
        
        $sql = "SELECT 
                    proprio_personne as label, 
                    COUNT(*) as count 
                FROM friches" . $whereClause . "
                GROUP BY proprio_personne 
                ORDER BY count DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Récupère la distribution des surfaces (pour histogramme)
     * @param array $filters
     * @return array
     */
    public function getSurfaceDistribution($filters = []) {
        $whereConditions = ['unite_fonciere_surface IS NOT NULL', 'unite_fonciere_surface > 0'];
        $params = [];
        
        // Construire les conditions WHERE selon les filtres
        if (!empty($filters['comm_nom'])) {
            $whereConditions[] = "comm_nom = :comm_nom";
            $params[':comm_nom'] = $filters['comm_nom'];
        } elseif (!empty($filters['comm_insee'])) {
            // Vérifier si c'est un filtre de département
            if (strpos($filters['comm_insee'], 'dept:') === 0) {
                $deptCode = substr($filters['comm_insee'], 5);
                $whereConditions[] = "comm_insee LIKE :comm_insee";
                $params[':comm_insee'] = $deptCode . '%';
            } else {
                $whereConditions[] = "comm_insee = :comm_insee";
                $params[':comm_insee'] = $filters['comm_insee'];
            }
        }
        
        $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);
        
        $sql = "SELECT 
                    CASE 
                        WHEN unite_fonciere_surface < 1000 THEN '0-1k'
                        WHEN unite_fonciere_surface < 5000 THEN '1k-5k'
                        WHEN unite_fonciere_surface < 10000 THEN '5k-10k'
                        WHEN unite_fonciere_surface < 50000 THEN '10k-50k'
                        WHEN unite_fonciere_surface < 100000 THEN '50k-100k'
                        ELSE '100k+'
                    END as label,
                    COUNT(*) as count 
                FROM friches" . $whereClause . "
                GROUP BY label 
                ORDER BY 
                    CASE label
                        WHEN '0-1k' THEN 1
                        WHEN '1k-5k' THEN 2
                        WHEN '5k-10k' THEN 3
                        WHEN '10k-50k' THEN 4
                        WHEN '50k-100k' THEN 5
                        WHEN '100k+' THEN 6
                    END";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Crée une nouvelle friche
     * @param array $data Données de la friche
     * @return int|false ID de la friche créée ou false en cas d'erreur
     */
    public function create($data) {
        // Validation des coordonnées
        if (!$this->validateCoordinates($data['longitude'], $data['latitude'])) {
            return false;
        }
        
        $sql = "INSERT INTO friches (
                    site_id, 
                    longitude, 
                    latitude, 
                    site_nom,
                    site_type,
                    site_statut,
                    comm_nom,
                    comm_insee,
                    dept_nom,
                    dept_code,
                    reg_nom,
                    unite_fonciere_surface
                ) VALUES (
                    :site_id, 
                    :longitude, 
                    :latitude, 
                    :site_nom,
                    :site_type,
                    :site_statut,
                    :comm_nom,
                    :comm_insee,
                    :dept_nom,
                    :dept_code,
                    :reg_nom,
                    :unite_fonciere_surface
                )";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'site_id' => $data['site_id'],
                'longitude' => $data['longitude'],
                'latitude' => $data['latitude'],
                'site_nom' => $data['site_nom'],
                'site_type' => $data['site_type'] ?? null,
                'site_statut' => $data['site_statut'] ?? null,
                'comm_nom' => $data['comm_nom'] ?? null,
                'comm_insee' => $data['comm_insee'] ?? null,
                'dept_nom' => $data['dept_nom'] ?? null,
                'dept_code' => $data['dept_code'] ?? null,
                'reg_nom' => $data['reg_nom'] ?? null,
                'unite_fonciere_surface' => $data['unite_fonciere_surface'] ?? null
            ]);
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de la friche : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Met à jour une friche existante
     * @param int $id ID de la friche
     * @param array $data Nouvelles données
     * @return bool Succès de l'opération
     */
    public function update($id, $data) {
        // Validation des coordonnées
        if (!$this->validateCoordinates($data['longitude'], $data['latitude'])) {
            return false;
        }
        
        $sql = "UPDATE friches SET 
                    site_id = :site_id,
                    longitude = :longitude,
                    latitude = :latitude,
                    site_nom = :site_nom,
                    site_type = :site_type,
                    site_statut = :site_statut,
                    comm_nom = :comm_nom,
                    comm_insee = :comm_insee,
                    dept_nom = :dept_nom,
                    dept_code = :dept_code,
                    reg_nom = :reg_nom,
                    unite_fonciere_surface = :unite_fonciere_surface
                WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'id' => $id,
                'site_id' => $data['site_id'],
                'longitude' => $data['longitude'],
                'latitude' => $data['latitude'],
                'site_nom' => $data['site_nom'],
                'site_type' => $data['site_type'] ?? null,
                'site_statut' => $data['site_statut'] ?? null,
                'comm_nom' => $data['comm_nom'] ?? null,
                'comm_insee' => $data['comm_insee'] ?? null,
                'dept_nom' => $data['dept_nom'] ?? null,
                'dept_code' => $data['dept_code'] ?? null,
                'reg_nom' => $data['reg_nom'] ?? null,
                'unite_fonciere_surface' => $data['unite_fonciere_surface'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de la friche : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprime une friche
     * @param int $id ID de la friche à supprimer
     * @return bool Succès de l'opération
     */
    public function delete($id) {
        $sql = "DELETE FROM friches WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de la friche : " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Valide les coordonnées géographiques
     * @param float $longitude Longitude (-180 à 180)
     * @param float $latitude Latitude (-90 à 90)
     * @return bool Coordonnées valides
     */
    private function validateCoordinates($longitude, $latitude) {
        // Vérifier que ce sont des nombres
        if (!is_numeric($longitude) || !is_numeric($latitude)) {
            return false;
        }
        
        $lon = floatval($longitude);
        $lat = floatval($latitude);
        
        // Vérifier les plages valides
        if ($lon < -180 || $lon > 180) {
            return false;
        }
        
        if ($lat < -90 || $lat > 90) {
            return false;
        }
        
        return true;
    }
}
