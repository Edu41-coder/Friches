-- ============================================================================
-- Script de création de la base de données MariaDB pour les Friches
-- Fichier source : friches-standard-geom.csv
-- Date de création : 2025-11-08
-- ============================================================================

-- Suppression de la base si elle existe déjà (pour réinitialisation)
DROP DATABASE IF EXISTS friches_db;

-- Création de la base de données
CREATE DATABASE friches_db 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

-- Sélection de la base de données
USE friches_db;

-- ============================================================================
-- Table principale : friches
-- ============================================================================
CREATE TABLE friches (
    -- Clé primaire auto-incrémentée
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Identifiant du site
    site_id VARCHAR(50) NOT NULL UNIQUE,
    
    -- Coordonnées géographiques (colonnes séparées pour faciliter les requêtes)
    longitude DECIMAL(10, 7) NULL,
    latitude DECIMAL(10, 7) NULL,
    
    -- Informations générales du site
    site_nom VARCHAR(500) NULL,
    site_type VARCHAR(100) NULL,
    site_identif_date DATE NULL,
    site_actu_date DATE NULL,
    site_url TEXT NULL,
    site_securite VARCHAR(100) NULL,
    site_occupation VARCHAR(100) NULL,
    site_statut VARCHAR(100) NULL,
    
    -- Activité
    activite_libelle TEXT NULL,
    
    -- Localisation communale
    comm_nom VARCHAR(200) NULL,
    comm_insee VARCHAR(10) NULL,
    
    -- Informations sur les bâtiments
    bati_type VARCHAR(100) NULL,
    bati_nombre DECIMAL(10, 2) NULL,
    bati_pollution VARCHAR(100) NULL,
    bati_vacance VARCHAR(100) NULL,
    bati_patrimoine VARCHAR(100) NULL,
    bati_etat VARCHAR(100) NULL,
    local_ancien_annee DECIMAL(10, 2) NULL,
    local_recent_annee DECIMAL(10, 2) NULL,
    
    -- Propriétaire
    proprio_type VARCHAR(255) NULL,
    proprio_personne VARCHAR(100) NULL,
    proprio_nom VARCHAR(500) NULL,
    
    -- Pollution du sol
    sol_pollution_existe VARCHAR(100) NULL,
    sol_pollution_origine VARCHAR(255) NULL,
    
    -- Unité foncière
    unite_fonciere_surface DECIMAL(15, 2) NULL,
    unite_fonciere_refcad TEXT NULL,
    
    -- Urbanisme
    urba_zone_type VARCHAR(100) NULL,
    urba_zone_lib TEXT NULL,
    urba_doc_type VARCHAR(100) NULL,
    
    -- Source des données
    source_nom VARCHAR(255) NULL,
    source_url TEXT NULL,
    source_producteur VARCHAR(255) NULL,
    
    -- Coordonnées au format texte (POINT WKT) - conservé pour compatibilité
    geompoint VARCHAR(255) NULL,
    
    -- Index pour améliorer les performances
    INDEX idx_site_id (site_id),
    INDEX idx_coords (longitude, latitude),
    INDEX idx_commune (comm_nom, comm_insee),
    INDEX idx_statut (site_statut),
    INDEX idx_type (site_type),
    INDEX idx_pollution (sol_pollution_existe),
    
    -- Index géospatial (optionnel mais recommandé pour les requêtes spatiales)
    -- SPATIAL INDEX idx_geo (geom_point)
    
    -- Timestamps pour suivi
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Commentaires sur la table et les colonnes
-- ============================================================================
ALTER TABLE friches COMMENT = 'Table principale contenant les informations sur les friches en France';

-- ============================================================================
-- Vue pour les sites en France métropolitaine uniquement
-- ============================================================================
CREATE VIEW friches_metropole AS
SELECT * FROM friches
WHERE longitude >= -5.5 
  AND longitude <= 10.0
  AND latitude >= 41.0
  AND latitude <= 51.5
  AND longitude IS NOT NULL
  AND latitude IS NOT NULL;

-- ============================================================================
-- Vue pour les sites DOM-TOM
-- ============================================================================
CREATE VIEW friches_domtom AS
SELECT * FROM friches
WHERE (longitude < -5.5 OR longitude > 10.0 OR latitude < 41.0 OR latitude > 51.5)
  AND longitude IS NOT NULL
  AND latitude IS NOT NULL;

-- ============================================================================
-- Vue avec statistiques par commune
-- ============================================================================
CREATE VIEW stats_par_commune AS
SELECT 
    comm_nom,
    comm_insee,
    COUNT(*) as nb_friches,
    SUM(CASE WHEN site_statut = 'friche sans projet' THEN 1 ELSE 0 END) as nb_sans_projet,
    SUM(CASE WHEN site_statut = 'friche avec projet' THEN 1 ELSE 0 END) as nb_avec_projet,
    SUM(CASE WHEN site_statut = 'friche reconvertie' THEN 1 ELSE 0 END) as nb_reconverties,
    SUM(CASE WHEN sol_pollution_existe = 'pollution avérée' THEN 1 ELSE 0 END) as nb_pollues,
    SUM(unite_fonciere_surface) as surface_totale,
    AVG(longitude) as longitude_moyenne,
    AVG(latitude) as latitude_moyenne
FROM friches
WHERE comm_nom IS NOT NULL
GROUP BY comm_nom, comm_insee
ORDER BY nb_friches DESC;

-- ============================================================================
-- Vue avec statistiques par type de friche
-- ============================================================================
CREATE VIEW stats_par_type AS
SELECT 
    site_type,
    COUNT(*) as nb_sites,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM friches), 2) as pourcentage,
    SUM(unite_fonciere_surface) as surface_totale,
    AVG(unite_fonciere_surface) as surface_moyenne
FROM friches
WHERE site_type IS NOT NULL
GROUP BY site_type
ORDER BY nb_sites DESC;

-- ============================================================================
-- Table pour l'historique des modifications (audit trail)
-- ============================================================================
CREATE TABLE friches_audit (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    site_id VARCHAR(50) NOT NULL,
    action_type ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    old_data JSON NULL,
    new_data JSON NULL,
    user_name VARCHAR(100) NULL,
    action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_site_id (site_id),
    INDEX idx_action_date (action_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Fonction pour calculer la distance entre deux points (en km)
-- Formule de Haversine
-- ============================================================================
DELIMITER //

CREATE FUNCTION distance_haversine(
    lat1 DECIMAL(10,7), 
    lon1 DECIMAL(10,7), 
    lat2 DECIMAL(10,7), 
    lon2 DECIMAL(10,7)
) RETURNS DECIMAL(10,2)
DETERMINISTIC
BEGIN
    DECLARE R DECIMAL(10,2) DEFAULT 6371; -- Rayon de la Terre en km
    DECLARE dLat DECIMAL(10,7);
    DECLARE dLon DECIMAL(10,7);
    DECLARE a DECIMAL(20,10);
    DECLARE c DECIMAL(20,10);
    
    SET dLat = RADIANS(lat2 - lat1);
    SET dLon = RADIANS(lon2 - lon1);
    
    SET a = SIN(dLat/2) * SIN(dLat/2) + 
            COS(RADIANS(lat1)) * COS(RADIANS(lat2)) * 
            SIN(dLon/2) * SIN(dLon/2);
    
    SET c = 2 * ATAN2(SQRT(a), SQRT(1-a));
    
    RETURN R * c;
END //

DELIMITER ;

-- ============================================================================
-- Procédure stockée pour rechercher les friches dans un rayon donné
-- ============================================================================
DELIMITER //

CREATE PROCEDURE chercher_friches_rayon(
    IN p_latitude DECIMAL(10,7),
    IN p_longitude DECIMAL(10,7),
    IN p_rayon_km DECIMAL(10,2)
)
BEGIN
    SELECT 
        *,
        distance_haversine(p_latitude, p_longitude, latitude, longitude) as distance_km
    FROM friches
    WHERE latitude IS NOT NULL 
      AND longitude IS NOT NULL
      AND distance_haversine(p_latitude, p_longitude, latitude, longitude) <= p_rayon_km
    ORDER BY distance_km;
END //

DELIMITER ;

-- ============================================================================
-- Procédure stockée pour rechercher les friches dans une zone rectangulaire
-- (Bounding Box)
-- ============================================================================
DELIMITER //

CREATE PROCEDURE chercher_friches_bbox(
    IN p_lat_min DECIMAL(10,7),
    IN p_lon_min DECIMAL(10,7),
    IN p_lat_max DECIMAL(10,7),
    IN p_lon_max DECIMAL(10,7)
)
BEGIN
    SELECT *
    FROM friches
    WHERE latitude BETWEEN p_lat_min AND p_lat_max
      AND longitude BETWEEN p_lon_min AND p_lon_max
    ORDER BY comm_nom, site_nom;
END //

DELIMITER ;

-- ============================================================================
-- Utilisateur pour l'application PHP (à adapter selon vos besoins)
-- ============================================================================
-- Décommenter et adapter ces lignes selon votre configuration

-- CREATE USER IF NOT EXISTS 'friches_app'@'localhost' IDENTIFIED BY 'votre_mot_de_passe_securise';
-- GRANT SELECT, INSERT, UPDATE ON friches_db.* TO 'friches_app'@'localhost';
-- GRANT SELECT ON friches_db.stats_par_commune TO 'friches_app'@'localhost';
-- GRANT SELECT ON friches_db.stats_par_type TO 'friches_app'@'localhost';
-- GRANT EXECUTE ON PROCEDURE friches_db.chercher_friches_rayon TO 'friches_app'@'localhost';
-- GRANT EXECUTE ON PROCEDURE friches_db.chercher_friches_bbox TO 'friches_app'@'localhost';
-- FLUSH PRIVILEGES;

-- ============================================================================
-- Affichage des informations de création
-- ============================================================================
SELECT 'Base de données créée avec succès !' AS message;
SELECT TABLE_NAME, TABLE_ROWS, TABLE_COMMENT 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'friches_db' AND TABLE_TYPE = 'BASE TABLE';

SELECT TABLE_NAME AS 'Vues créées'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'friches_db' AND TABLE_TYPE = 'VIEW';

-- ============================================================================
-- FIN DU SCRIPT
-- ============================================================================
