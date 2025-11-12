<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '') ?>">
    <title>Gestion des Utilisateurs - Application Friches</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/Friches/public/css/style.css">
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
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="bi bi-people-fill"></i> Gestion des Utilisateurs
                </h4>
                <a href="index.php?page=dashboard" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>
            <div class="card-body">
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

                <!-- Barre d'outils -->
                <div class="row mb-3 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="text" id="searchInput" class="form-control" placeholder="Rechercher...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select id="perPageSelect" class="form-select">
                            <option value="10">10 par page</option>
                            <option value="25" selected>25 par page</option>
                            <option value="50">50 par page</option>
                            <option value="100">100 par page</option>
                        </select>
                    </div>
                    <div class="col-md-5 text-end">
                        <button type="button" id="filterBtn" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                            <i class="bi bi-funnel"></i> Filtres
                        </button>
                        <button type="button" id="resetBtn" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Réinitialiser
                        </button>
                        <a href="index.php?page=users&action=create" class="btn btn-success">
                            <i class="bi bi-plus-circle"></i> Créer un utilisateur
                        </a>
                    </div>
                </div>

                <!-- Indicateur de chargement -->
                <div id="loadingOverlay" class="text-center py-4" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                </div>

                <!-- Scrollbar supérieure -->
                <div id="scrollTopWrapper" class="scroll-top-wrapper">
                    <div id="scrollTopInner" class="scroll-top-inner"></div>
                </div>

                <!-- Tableau -->
                <div id="tableScrollWrapper" class="table-scroll-wrapper">
                    <table id="usersTable" class="table table-striped table-hover">
                        <thead class="table-light">
                            <tr id="tableHeader">
                                <!-- Généré dynamiquement par JavaScript -->
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <!-- Généré dynamiquement par JavaScript -->
                        </tbody>
                    </table>
                </div>

                <!-- Informations de pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div>
                        <span id="paginationInfo" class="text-muted"></span>
                    </div>
                    <nav>
                        <ul id="paginationControls" class="pagination mb-0">
                            <!-- Généré dynamiquement par JavaScript -->
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Filtres -->
    <div class="modal fade" id="filterModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-funnel"></i> Filtres avancés</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="filterRole" class="form-label">Rôle</label>
                        <select id="filterRole" class="form-select">
                            <option value="">Tous les rôles</option>
                            <option value="admin">Administrateur</option>
                            <option value="user">Utilisateur</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="filterStatus" class="form-label">Statut</label>
                        <select id="filterStatus" class="form-select">
                            <option value="">Tous les statuts</option>
                            <option value="1">Actif</option>
                            <option value="0">Inactif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="clearFiltersBtn" class="btn btn-secondary">Effacer</button>
                    <button type="button" id="applyFiltersBtn" class="btn btn-primary">Appliquer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmation Suppression -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill"></i> Confirmer la suppression</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer cet utilisateur ?</p>
                    <p class="fw-bold" id="deleteUserName"></p>
                    <p class="text-danger"><i class="bi bi-exclamation-circle"></i> Cette action est irréversible.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Supprimer</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Wrapper pour le scroll du haut */
        .scroll-top-wrapper {
            overflow-x: auto;
            overflow-y: hidden;
            height: 16px;
            margin-bottom: 0;
        }

        /* Div interne pour définir la largeur scrollable */
        .scroll-top-inner {
            height: 1px;
        }

        /* Wrapper du tableau avec scroll */
        .table-scroll-wrapper {
            overflow-x: auto;
        }

        /* Style des scrollbars */
        .scroll-top-wrapper::-webkit-scrollbar,
        .table-scroll-wrapper::-webkit-scrollbar {
            height: 12px;
        }

        .scroll-top-wrapper::-webkit-scrollbar-track,
        .table-scroll-wrapper::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .scroll-top-wrapper::-webkit-scrollbar-thumb,
        .table-scroll-wrapper::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 6px;
        }

        .scroll-top-wrapper::-webkit-scrollbar-thumb:hover,
        .table-scroll-wrapper::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Icônes de tri */
        .sort-icon {
            font-size: 0.8rem;
            opacity: 0.3;
            transition: opacity 0.2s;
        }

        .sort-icon.active {
            opacity: 1;
        }

        /* Loading overlay */
        #loadingOverlay {
            position: relative;
            background: rgba(255, 255, 255, 0.8);
            z-index: 10;
        }

        /* Badge pour rôle et statut */
        .role-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .role-admin {
            background-color: #dc3545;
            color: white;
        }

        .role-user {
            background-color: #6c757d;
            color: white;
        }

        .status-active {
            color: #28a745;
        }

        .status-inactive {
            color: #dc3545;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/Friches/public/js/utils.js"></script>
    <script src="/Friches/public/js/users-table.js"></script>
</body>
</html>
