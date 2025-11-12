<?php
/**
 * Vue du tableau des friches
 * Fichier : app/views/friches/index.php
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau des Friches - Application Friches</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/Friches/public/css/style.css">
    <style>
        .table-container {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        /* Double scroll horizontal */
        .scroll-top-wrapper {
            overflow-x: auto;
            overflow-y: hidden;
            height: 20px;
            margin-bottom: 1px;
        }
        .scroll-top-wrapper::-webkit-scrollbar {
            height: 12px;
        }
        .scroll-top-wrapper::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .scroll-top-wrapper::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        .scroll-top-wrapper::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        .scroll-top-inner {
            height: 1px;
        }
        .table-scroll-wrapper {
            position: relative;
            overflow-x: auto;
            overflow-y: visible;
        }
        .table-scroll-wrapper::-webkit-scrollbar {
            height: 12px;
        }
        .table-scroll-wrapper::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .table-scroll-wrapper::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        .table-scroll-wrapper::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            cursor: pointer;
            user-select: none;
            position: relative;
            padding-right: 25px;
            white-space: nowrap;
            min-width: 100px;
        }
        .table thead th:hover {
            background-color: #e9ecef;
        }
        .table tbody td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            position: relative;
        }
        
        /* Poignée de redimensionnement */
        .resize-handle {
            position: absolute;
            top: 0;
            right: 0;
            width: 8px;
            height: 100%;
            cursor: col-resize;
            z-index: 5;
            background: transparent;
            border-right: 2px solid transparent;
            transition: border-color 0.2s;
        }
        .resize-handle:hover {
            border-right-color: var(--primary-color);
        }
        .resize-handle.resizing {
            border-right-color: var(--primary-color);
            background: rgba(24, 119, 242, 0.1);
        }
        
        /* Indicateur visuel pendant le redimensionnement */
        .table thead th.resizing {
            background-color: #e3f2fd;
        }
        .sort-icon {
            position: absolute;
            right: 8px;
            opacity: 0.3;
        }
        .sort-icon.active {
            opacity: 1;
            color: var(--primary-color);
        }
        .filter-panel {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .loading-overlay {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.8);
            z-index: 10;
            justify-content: center;
            align-items: center;
        }
        .loading-overlay.active {
            display: flex;
        }
        .column-selector {
            max-height: 300px;
            overflow-y: auto;
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
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="bi bi-table"></i> Tableau des Friches
                </h4>
                <a href="index.php?page=dashboard" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>
            <div class="card-body">
                
                <!-- Messages de succès et d'erreur -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $_SESSION['success'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <!-- Panneau de contrôles -->
                <div class="row g-3 mb-3">
                    <!-- Recherche globale -->
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="searchInput" class="form-control" placeholder="Rechercher...">
                        </div>
                    </div>
                    
                    <!-- Éléments par page -->
                    <div class="col-md-2">
                        <select id="perPageSelect" class="form-select">
                            <option value="10">10 / page</option>
                            <option value="25" selected>25 / page</option>
                            <option value="50">50 / page</option>
                            <option value="100">100 / page</option>
                        </select>
                    </div>
                    
                    <!-- Boutons d'action -->
                    <div class="col-md-6 text-end">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="bi bi-funnel"></i> Filtres avancés
                        </button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#columnModal">
                            <i class="bi bi-layout-three-columns"></i> Colonnes
                        </button>
                        <button type="button" id="resetBtn" class="btn btn-outline-danger">
                            <i class="bi bi-arrow-clockwise"></i> Réinitialiser
                        </button>
                        <?php if ($_SESSION['role'] === 'admin'): ?>
                            <a href="index.php?page=friches&action=create" class="btn btn-success">
                                <i class="bi bi-plus-circle"></i> Créer une friche
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Filtres actifs -->
                <div id="activeFilters" class="mb-3"></div>

                <!-- Tableau -->
                <div class="table-container position-relative">
                    <!-- Loading overlay -->
                    <div class="loading-overlay" id="loadingOverlay">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                    </div>

                    <!-- Barre de scroll supérieure -->
                    <div class="scroll-top-wrapper" id="scrollTopWrapper">
                        <div class="scroll-top-inner" id="scrollTopInner"></div>
                    </div>

                    <!-- Tableau avec scroll inférieur -->
                    <div class="table-scroll-wrapper" id="tableScrollWrapper">
                        <table class="table table-hover mb-0" id="frichesTable">
                            <thead>
                                <tr id="tableHeader">
                                    <!-- Les en-têtes seront générées dynamiquement -->
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <!-- Les données seront chargées dynamiquement -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center p-3 border-top">
                        <div id="paginationInfo" class="text-muted">
                            <!-- Info pagination -->
                        </div>
                        <nav>
                            <ul class="pagination mb-0" id="paginationControls">
                                <!-- Contrôles pagination -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
    </main>

    <!-- Modal Filtres avancés -->
    <div class="modal fade" id="filterModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-funnel"></i> Filtres avancés</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Type de site -->
                        <div class="col-md-6">
                            <label class="form-label">Type de site</label>
                            <select id="filterType" class="form-select">
                                <option value="">Tous</option>
                                <?php foreach ($types as $type): ?>
                                    <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Statut -->
                        <div class="col-md-6">
                            <label class="form-label">Statut</label>
                            <select id="filterStatut" class="form-select">
                                <option value="">Tous</option>
                                <?php foreach ($statuts as $statut): ?>
                                    <option value="<?= htmlspecialchars($statut) ?>"><?= htmlspecialchars($statut) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Commune -->
                        <div class="col-md-6">
                            <label class="form-label">Commune</label>
                            <input type="text" id="filterCommune" class="form-control" placeholder="Nom de la commune">
                        </div>
                        
                        <!-- Pollution sol -->
                        <div class="col-md-6">
                            <label class="form-label">Pollution du sol</label>
                            <select id="filterPollution" class="form-select">
                                <option value="">Tous</option>
                                <?php foreach ($pollutions as $pollution): ?>
                                    <option value="<?= htmlspecialchars($pollution) ?>"><?= htmlspecialchars($pollution) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Surface min/max -->
                        <div class="col-md-6">
                            <label class="form-label">Surface minimale (m²)</label>
                            <input type="number" id="filterSurfaceMin" class="form-control" placeholder="0" min="0">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Surface maximale (m²)</label>
                            <input type="number" id="filterSurfaceMax" class="form-control" placeholder="999999" min="0">
                        </div>
                        
                        <!-- Dates -->
                        <div class="col-md-6">
                            <label class="form-label">Date identification (de)</label>
                            <input type="date" id="filterDateMin" class="form-control">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Date identification (à)</label>
                            <input type="date" id="filterDateMax" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" id="clearFiltersBtn">Effacer les filtres</button>
                    <button type="button" class="btn btn-primary" id="applyFiltersBtn">Appliquer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Sélection des colonnes -->
    <div class="modal fade" id="columnModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-layout-three-columns"></i> Sélection des colonnes</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body column-selector">
                    <?php foreach ($columns as $key => $label): ?>
                        <div class="form-check">
                            <input class="form-check-input column-checkbox" type="checkbox" 
                                   value="<?= $key ?>" id="col_<?= $key ?>"
                                   <?= in_array($key, ['site_nom', 'site_type', 'comm_nom', 'site_statut', 'unite_fonciere_surface']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="col_<?= $key ?>">
                                <?= htmlspecialchars($label) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <button type="button" class="btn btn-primary" id="saveColumnsBtn">Enregistrer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmation Suppression -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="bi bi-exclamation-triangle"></i> Confirmer la suppression
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer la friche suivante ?</p>
                    <div class="alert alert-warning">
                        <strong id="deleteFricheName"></strong><br>
                        <small id="deleteFricheDetails"></small>
                    </div>
                    <p class="text-danger"><strong>Cette action est irréversible !</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="bi bi-trash"></i> Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Passer les colonnes PHP à JavaScript
        const availableColumns = <?= json_encode($columns) ?>;
        // Passer le rôle de l'utilisateur à JavaScript pour les actions admin
        const userRole = '<?= $_SESSION['role'] ?? 'user' ?>';
    </script>
    <script src="/Friches/public/js/utils.js"></script>
    <script src="/Friches/public/js/friches-table.js"></script>
</body>
</html>
