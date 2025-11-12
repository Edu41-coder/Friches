<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Application Friches</title>
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

    <main class="container my-5" style="max-width:var(--max-width);">
        <div class="card friches-card shadow-sm">
            <div class="card-body text-center p-5">
                <h2 class="text-primary">Bienvenue sur l'application de gestion des friches !</h2>
                <p class="mt-3">
                    Vous êtes connecté en tant que 
                    <strong><?= htmlspecialchars($_SESSION['role'] === 'admin' ? 'Administrateur' : 'Utilisateur') ?></strong>
                </p>
                <p class="text-muted mt-3">
                    Accédez aux différents modules de l'application ci-dessous.
                </p>

                <!-- Boutons d'accès aux modules -->
                <div class="row mt-4 g-3">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-table" style="font-size: 3rem; color: var(--primary-color);"></i>
                                <h5 class="card-title mt-3">Tableau des Friches</h5>
                                <p class="card-text text-muted small">Visualisez, filtrez et triez les données des friches</p>
                                <a href="index.php?page=friches" class="btn btn-primary">Accéder</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-bar-chart-fill" style="font-size: 3rem; color: #28a745;"></i>
                                <h5 class="card-title mt-3">Analyses & Statistiques</h5>
                                <p class="card-text text-muted small">Graphiques et visualisations des données</p>
                                <a href="index.php?page=analytics" class="btn btn-success">Accéder</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-map-fill" style="font-size: 3rem; color: #17a2b8;"></i>
                                <h5 class="card-title mt-3">Carte Interactive</h5>
                                <p class="card-text text-muted small">Explorez les friches sur une carte</p>
                                <a href="index.php?page=map" class="btn btn-info">Accéder</a>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <!-- Section Administration (visible uniquement pour les admins) -->
                    <hr class="my-5">
                    <h4 class="text-secondary mb-4">
                        <i class="bi bi-shield-lock-fill"></i> Administration
                    </h4>
                    <div class="row g-3">
                        <div class="col-md-6 mx-auto">
                            <div class="card h-100 border-warning">
                                <div class="card-body text-center">
                                    <i class="bi bi-people-fill" style="font-size: 3rem; color: #ff6b6b;"></i>
                                    <h5 class="card-title mt-3">Gestion des Utilisateurs</h5>
                                    <p class="card-text text-muted small">Créer, modifier et supprimer des utilisateurs</p>
                                    <a href="index.php?page=users" class="btn btn-danger">Gérer</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mt-4">
                        <p class="text-muted">Vous avez un accès en lecture seule aux données.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
