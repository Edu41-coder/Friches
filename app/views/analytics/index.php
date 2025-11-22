<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analyses & Statistiques - Application Friches</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="/Friches/public/css/style.css">
    <style>
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 2rem;
        }
        .chart-container-small {
            position: relative;
            height: 300px;
            margin-bottom: 2rem;
        }
        .section-title {
            border-left: 4px solid var(--primary-color);
            padding-left: 1rem;
            margin: 2rem 0 1.5rem 0;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg friches-navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php?page=dashboard">Application Friches</a>
            <div class="d-flex align-items-center">
                <div class="me-3 text-end">
                    <div class="fw-semibold"><?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?></div>
                    <div class="small text-muted text-uppercase"><?= htmlspecialchars($_SESSION['role']) ?></div>
                </div>
                <a href="index.php?page=logout" class="btn btn-outline-secondary">Déconnexion</a>
            </div>
        </div>
    </nav>

    <main class="container-fluid my-4">
        <div class="card friches-card shadow-sm">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="bi bi-bar-chart-fill"></i> Analyses & Statistiques
                </h4>
                <a href="index.php?page=dashboard" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>
            <div class="card-body">
                
                <!-- Loading indicator -->
                <div id="loadingStats" class="text-center py-5">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-3 text-muted">Chargement des statistiques...</p>
                </div>

                <!-- Statistiques globales -->
                <div id="globalStatsSection" style="display: none;">
                    <h5 class="section-title">Vue d'ensemble</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="card stat-card h-100 border-primary">
                                <div class="card-body text-center">
                                    <i class="bi bi-geo-fill stat-icon text-primary"></i>
                                    <div class="stat-value" id="statTotalFriches">-</div>
                                    <div class="text-muted">Friches recensées</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card h-100 border-success">
                                <div class="card-body text-center">
                                    <i class="bi bi-building stat-icon text-success"></i>
                                    <div class="stat-value" id="statTotalCommunes">-</div>
                                    <div class="text-muted">Communes concernées</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card h-100 border-warning">
                                <div class="card-body text-center">
                                    <i class="bi bi-bounding-box stat-icon text-warning"></i>
                                    <div class="stat-value" id="statSurfaceTotale">-</div>
                                    <div class="text-muted">Surface totale (m²)</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stat-card h-100 border-info">
                                <div class="card-body text-center">
                                    <i class="bi bi-arrow-down-up stat-icon text-info"></i>
                                    <div class="stat-value" id="statSurfaceMoyenne">-</div>
                                    <div class="text-muted">Surface moyenne (m²)</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card stat-card h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-pin-map-fill stat-icon" style="color: #e74c3c;"></i>
                                    <div class="stat-value" style="font-size: 1.5rem; color: #e74c3c;" id="statCommuneMax">-</div>
                                    <div class="text-muted">Commune la plus touchée</div>
                                    <div class="mt-2"><span class="badge bg-danger" id="statCommuneMaxCount">0 friches</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stat-card h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-tags-fill stat-icon" style="color: #9b59b6;"></i>
                                    <div class="stat-value" style="color: #9b59b6;" id="statTotalTypes">-</div>
                                    <div class="text-muted">Types de friches</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stat-card h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-flag-fill stat-icon" style="color: #3498db;"></i>
                                    <div class="stat-value" style="color: #3498db;" id="statTotalStatuts">-</div>
                                    <div class="text-muted">Statuts différents</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panneau de filtres -->
                    <div class="card mb-4 border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-funnel"></i> Filtres d'analyse</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-5">
                                    <label class="form-label fw-bold">Filtrer par Commune</label>
                                    <select id="filterCommune" class="form-select">
                                        <option value="">Toutes les communes</option>
                                        <?php if (isset($communes) && is_array($communes)):
                                            foreach ($communes as $commune): ?>
                                                <option value="<?= htmlspecialchars($commune) ?>"><?= htmlspecialchars($commune) ?></option>
                                            <?php endforeach;
                                        endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fw-bold">Filtrer par Code INSEE <small class="text-muted">(ou département)</small></label>
                                    <select id="filterCodeInsee" class="form-select">
                                        <option value="">Tous les codes INSEE</option>
                                        <?php if (isset($codesInsee) && is_array($codesInsee)):
                                            $departements = [];
                                            foreach ($codesInsee as $code) {
                                                $dept = substr($code, 0, 2);
                                                if (!isset($departements[$dept])) {
                                                    $departements[$dept] = [];
                                                }
                                                $departements[$dept][] = $code;
                                            }
                                            ksort($departements);
                                            
                                            foreach ($departements as $dept => $codes): ?>
                                                <optgroup label="Département <?= htmlspecialchars($dept) ?>">
                                                    <option value="dept:<?= htmlspecialchars($dept) ?>">✓ Tout le département <?= htmlspecialchars($dept) ?> (<?= count($codes) ?> communes)</option>
                                                    <?php foreach ($codes as $code): ?>
                                                        <option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($code) ?></option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach;
                                        endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" id="resetAnalyticsFilters" class="btn btn-outline-danger w-100">
                                        <i class="bi bi-x-circle"></i> Réinitialiser
                                    </button>
                                </div>
                            </div>
                            <div id="activeFiltersAnalytics" class="mt-3"></div>
                        </div>
                    </div>

                    <!-- Section Types et Statuts -->
                    <h5 class="section-title">Types et Statuts des Friches</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-diagram-3"></i> Répartition par Type</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="chartTypes"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-pie-chart"></i> Répartition par Statut</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="chartStatuts"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Géographique -->
                    <h5 class="section-title">Répartition Géographique</h5>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-bar-chart-line"></i> Top 10 des Communes</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="chartCommunes"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Pollution -->
                    <h5 class="section-title">État de Pollution</h5>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-droplet"></i> Pollution du Sol</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container-small">
                                        <canvas id="chartSoilPollution"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-house-door"></i> Pollution des Bâtiments</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container-small">
                                        <canvas id="chartBuildingPollution"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Propriétaires -->
                    <h5 class="section-title">Types de Propriétaires</h5>
                    <div class="row mb-4">
                        <div class="col-md-6 mx-auto">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-people"></i> Répartition par Type de Propriétaire</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="chartOwnerTypes"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Surfaces -->
                    <h5 class="section-title">Distribution des Surfaces</h5>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="bi bi-graph-up"></i> Répartition par Tranche de Surface</h6>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="chartSurfaces"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Configuration de la langue française pour Select2
        $.fn.select2.defaults.set('language', {
            noResults: function() { return "Aucun résultat trouvé"; },
            searching: function() { return "Recherche en cours..."; },
            loadingMore: function() { return "Chargement de résultats supplémentaires..."; },
            inputTooShort: function(args) {
                return "Veuillez saisir au moins " + args.minimum + " caractère" + (args.minimum > 1 ? "s" : "");
            }
        });
    </script>
    <script src="/Friches/public/js/utils.js"></script>
    <script src="/Friches/public/js/analytics.js"></script>
</body>
</html>
