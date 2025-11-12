<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un utilisateur - Application Friches</title>
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

    <main class="container my-4" style="max-width: 800px;">
        <div class="card friches-card shadow-sm">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <i class="bi bi-plus-circle"></i> Créer un nouvel utilisateur
                </h4>
                <a href="index.php?page=users" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-left"></i> Retour
                </a>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <form method="POST" action="index.php?page=users&action=create">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Nom d'utilisateur <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" 
                                   required minlength="3" maxlength="50">
                            <div class="form-text">Minimum 3 caractères, uniquement lettres, chiffres et underscore</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" 
                                   required maxlength="100">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" 
                                   maxlength="50">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" 
                                   maxlength="50">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   required minlength="6">
                            <div class="form-text">Minimum 6 caractères</div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="password_confirm" class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" 
                                   required minlength="6">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Rôle <span class="text-danger">*</span></label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="user" <?= (($_POST['role'] ?? 'user') === 'user') ? 'selected' : '' ?>>Utilisateur</option>
                                <option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Administrateur</option>
                            </select>
                            <div class="form-text">
                                <strong>Utilisateur :</strong> Accès en lecture seule<br>
                                <strong>Administrateur :</strong> Accès complet (création, modification, suppression)
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label d-block">Statut</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                       <?= (isset($_POST['is_active']) || !isset($_POST['username'])) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">
                                    Compte actif
                                </label>
                            </div>
                            <div class="form-text">Si décoché, l'utilisateur ne pourra pas se connecter</div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-flex justify-content-between">
                        <a href="index.php?page=users" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Annuler
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Créer l'utilisateur
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validation côté client
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;
            
            if (password !== passwordConfirm) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas');
                return false;
            }
        });
    </script>
</body>
</html>
