<?php
/**
 * Vue d'édition d'une friche
 * Fichier : app/views/friches/edit.php
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier une Friche - Application Friches</title>
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
                <a href="index.php?page=logout" class="btn btn-outline-secondary btn-sm">Déconnexion</a>
            </div>
        </div>
    </nav>

    <main class="container my-4">
        <div class="card friches-card shadow-sm">
            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="bi bi-pencil-square"></i> Modifier une Friche
                </h4>
                <a href="index.php?page=friches" class="btn btn-dark btn-sm">
                    <i class="bi bi-arrow-left"></i> Retour au tableau
                </a>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" action="index.php?page=friches&action=update&id=<?= $friche['id'] ?>">
                    <div class="row g-3">
                        <!-- Informations générales -->
                        <div class="col-12">
                            <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-info-circle"></i> Informations générales</h5>
                        </div>

                        <div class="col-md-6">
                            <label for="site_nom" class="form-label">Nom du site <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="site_nom" name="site_nom" 
                                   value="<?= htmlspecialchars($friche['site_nom'] ?? '') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label for="site_type" class="form-label">Type de site</label>
                            <input type="text" class="form-control" id="site_type" name="site_type" 
                                   value="<?= htmlspecialchars($friche['site_type'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="site_statut" class="form-label">Statut</label>
                            <input type="text" class="form-control" id="site_statut" name="site_statut" 
                                   value="<?= htmlspecialchars($friche['site_statut'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="unite_fonciere_surface" class="form-label">Surface (m²)</label>
                            <input type="number" class="form-control" id="unite_fonciere_surface" 
                                   name="unite_fonciere_surface" step="0.01"
                                   value="<?= htmlspecialchars($friche['unite_fonciere_surface'] ?? '') ?>">
                        </div>

                        <!-- Localisation -->
                        <div class="col-12 mt-4">
                            <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-geo-alt"></i> Localisation</h5>
                        </div>

                        <div class="col-md-6">
                            <label for="comm_nom" class="form-label">Commune</label>
                            <input type="text" class="form-control" id="comm_nom" name="comm_nom" 
                                   value="<?= htmlspecialchars($friche['comm_nom'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="comm_insee" class="form-label">Code INSEE</label>
                            <input type="text" class="form-control" id="comm_insee" name="comm_insee" 
                                   value="<?= htmlspecialchars($friche['comm_insee'] ?? '') ?>">
                        </div>

                        <div class="col-md-12">
                            <label for="site_adresse" class="form-label">Adresse</label>
                            <textarea class="form-control" id="site_adresse" name="site_adresse" 
                                      rows="2"><?= htmlspecialchars($friche['site_adresse'] ?? '') ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label for="latitude" class="form-label">Latitude</label>
                            <input type="number" class="form-control" id="latitude" name="latitude" 
                                   step="0.000001" value="<?= htmlspecialchars($friche['latitude'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="longitude" class="form-label">Longitude</label>
                            <input type="number" class="form-control" id="longitude" name="longitude" 
                                   step="0.000001" value="<?= htmlspecialchars($friche['longitude'] ?? '') ?>">
                        </div>

                        <!-- Pollution -->
                        <div class="col-12 mt-4">
                            <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-droplet"></i> Pollution</h5>
                        </div>

                        <div class="col-md-6">
                            <label for="sol_pollution_existe" class="form-label">Pollution du sol</label>
                            <select class="form-select" id="sol_pollution_existe" name="sol_pollution_existe">
                                <option value="">Non renseigné</option>
                                <option value="Oui" <?= ($friche['sol_pollution_existe'] ?? '') === 'Oui' ? 'selected' : '' ?>>Oui</option>
                                <option value="Non" <?= ($friche['sol_pollution_existe'] ?? '') === 'Non' ? 'selected' : '' ?>>Non</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="bati_pollution" class="form-label">Pollution des bâtiments</label>
                            <input type="text" class="form-control" id="bati_pollution" name="bati_pollution" 
                                   value="<?= htmlspecialchars($friche['bati_pollution'] ?? '') ?>">
                        </div>

                        <!-- Propriétaire -->
                        <div class="col-12 mt-4">
                            <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-person"></i> Propriétaire</h5>
                        </div>

                        <div class="col-md-6">
                            <label for="proprio_type" class="form-label">Type de propriétaire</label>
                            <input type="text" class="form-control" id="proprio_type" name="proprio_type" 
                                   value="<?= htmlspecialchars($friche['proprio_type'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label for="proprio_personne" class="form-label">Personne (physique/morale)</label>
                            <input type="text" class="form-control" id="proprio_personne" name="proprio_personne" 
                                   value="<?= htmlspecialchars($friche['proprio_personne'] ?? '') ?>">
                        </div>

                        <!-- Dates -->
                        <div class="col-12 mt-4">
                            <h5 class="border-bottom pb-2 mb-3"><i class="bi bi-calendar"></i> Dates</h5>
                        </div>

                        <div class="col-md-6">
                            <label for="site_date_identification" class="form-label">Date d'identification</label>
                            <input type="date" class="form-control" id="site_date_identification" 
                                   name="site_date_identification" 
                                   value="<?= htmlspecialchars($friche['site_date_identification'] ?? '') ?>">
                        </div>

                        <!-- Boutons -->
                        <div class="col-12 mt-4">
                            <hr>
                            <div class="d-flex justify-content-between">
                                <a href="index.php?page=friches" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Annuler
                                </a>
                                <button type="submit" class="btn btn-warning">
                                    <i class="bi bi-save"></i> Enregistrer les modifications
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
