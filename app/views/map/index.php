<?php
/**
 * Vue de la carte interactive
 * Fichier : app/views/map/index.php
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carte Interactive - Application Friches</title>
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <!-- Leaflet MarkerCluster CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    
    <link rel="stylesheet" href="/Friches/public/css/style.css">
    
    <style>
        #map {
            height: calc(100vh - 200px);
            width: 100%;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .map-controls {
            background: white;
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        
        .cluster-control {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .cluster-control label {
            margin: 0;
            font-weight: 500;
            min-width: 150px;
        }
        
        .cluster-control input[type="range"] {
            flex: 1;
            max-width: 300px;
        }
        
        .cluster-control .badge {
            min-width: 60px;
            text-align: center;
        }
        
        .map-legend {
            background: white;
            padding: 1rem;
            border-radius: 0.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .map-legend h6 {
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .map-legend .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.3rem;
        }
        
        .map-legend .legend-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: #1877F2;
            border: 2px solid white;
            box-shadow: 0 0 4px rgba(0,0,0,0.3);
        }
        
        .loading-map {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .leaflet-popup-content {
            min-width: 250px;
        }
        
        .leaflet-popup-content h6 {
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .leaflet-popup-content .info-row {
            display: flex;
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
        }
        
        .leaflet-popup-content .info-label {
            font-weight: 600;
            min-width: 100px;
            color: #666;
        }
        
        .leaflet-popup-content .info-value {
            color: #333;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg friches-navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php?page=dashboard">Application Friches</a>
            <div class="d-flex align-items-center">
                <div class="me-3 text-end">
                    <div class="fw-semibold"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?></div>
                    <div class="small text-muted text-uppercase"><?= htmlspecialchars($_SESSION['role']) ?></div>
                </div>
                <a href="index.php?page=dashboard" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="bi bi-house-door"></i> Tableau de bord
                </a>
                <a href="index.php?page=logout" class="btn btn-outline-secondary btn-sm">Déconnexion</a>
            </div>
        </div>
    </nav>

    <main class="container-fluid my-4">
        <div class="card friches-card shadow-sm">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="bi bi-map-fill"></i> Carte Interactive des Friches
                </h4>
                <a href="index.php?page=dashboard" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>
            <div class="card-body">
                
                <!-- Contrôles de la carte -->
                <div class="map-controls">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="cluster-control">
                                <label for="clusterRadius">
                                    <i class="bi bi-diagram-3"></i> Rayon de clustering :
                                </label>
                                <input type="range" class="form-range" id="clusterRadius" 
                                       min="20" max="200" value="80" step="10">
                                <span class="badge bg-primary" id="clusterRadiusValue">80m</span>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="map-legend">
                                <h6><i class="bi bi-info-circle"></i> Légende</h6>
                                <div class="legend-item">
                                    <div class="legend-icon"></div>
                                    <span>Friche industrielle</span>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <strong id="totalFrichesCount">0</strong> friches affichées
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Carte -->
                <div class="position-relative">
                    <div class="loading-map" id="loadingMap">
                        <div class="text-center">
                            <div class="spinner-border text-info" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            <p class="mt-3 text-muted">Chargement de la carte...</p>
                        </div>
                    </div>
                    <div id="map"></div>
                </div>
                
                <div class="mt-3 text-muted">
                    <small>
                        <i class="bi bi-lightbulb"></i> 
                        <strong>Astuce :</strong> Cliquez sur un marqueur pour voir les détails. 
                        Utilisez le slider pour ajuster le regroupement des friches proches.
                    </small>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Leaflet MarkerCluster JS -->
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    
    <script>
        // Passer l'ID de friche spécifique si fourni
        const specificFricheId = <?= json_encode($fricheId) ?>;
    </script>
    <script src="/Friches/public/js/utils.js"></script>
    <script src="/Friches/public/js/map.js"></script>
</body>
</html>
