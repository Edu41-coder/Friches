-- ============================================================================
-- Script de création de la table users pour l'authentification
-- Date de création : 2025-11-09
-- ============================================================================

USE friches_db;

-- ============================================================================
-- Table : users
-- ============================================================================
CREATE TABLE IF NOT EXISTS users (
    -- Clé primaire
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Informations d'identification
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    
    -- Informations personnelles
    first_name VARCHAR(100) NULL,
    last_name VARCHAR(100) NULL,
    
    -- Rôle et permissions
    role ENUM('admin', 'user') DEFAULT 'user' NOT NULL,
    
    -- Statut du compte
    is_active TINYINT(1) DEFAULT 1 NOT NULL,
    
    -- Tracking
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Index
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_active (is_active)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Insertion d'un utilisateur administrateur par défaut
-- Mot de passe : admin123 (À CHANGER EN PRODUCTION !)
-- ============================================================================
INSERT INTO users (username, email, password_hash, first_name, last_name, role, is_active)
VALUES (
    'admin',
    'admin@friches.local',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- admin123
    'Administrateur',
    'Système',
    'admin',
    1
);

-- ============================================================================
-- Insertion d'un utilisateur standard de test
-- Mot de passe : user123
-- ============================================================================
INSERT INTO users (username, email, password_hash, first_name, last_name, role, is_active)
VALUES (
    'user_test',
    'user@friches.local',
    '$2y$10$6Tlb0aYpGPR5RjXqhvQNr.xfVxLBQqNk1jh5F3VdGqKvGpLvxIUCm', -- user123
    'Utilisateur',
    'Test',
    'user',
    1
);

-- ============================================================================
-- Table pour les sessions utilisateur (optionnel)
-- ============================================================================
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (session_token),
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Vue pour les statistiques utilisateurs
-- ============================================================================
CREATE VIEW user_stats AS
SELECT 
    role,
    COUNT(*) as total_users,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_users,
    SUM(CASE WHEN last_login IS NOT NULL THEN 1 ELSE 0 END) as users_logged_in
FROM users
GROUP BY role;

-- ============================================================================
-- Affichage des utilisateurs créés
-- ============================================================================
SELECT 'Table users créée avec succès !' AS message;
SELECT id, username, email, role, is_active, created_at FROM users;

-- ============================================================================
-- FIN DU SCRIPT
-- ============================================================================
