<?php
/**
 * Script pour réinitialiser les mots de passe des utilisateurs
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Réinitialisation des mots de passe</h2>";

try {
    require_once __DIR__ . '/../config/database.php';
    
    $db = Database::getInstance()->getConnection();
    
    // Générer les nouveaux hash
    $adminHash = password_hash('admin123', PASSWORD_BCRYPT);
    $userHash = password_hash('user123', PASSWORD_BCRYPT);
    
    // Mettre à jour admin
    $stmt = $db->prepare("UPDATE users SET password_hash = :hash WHERE username = 'admin'");
    $stmt->execute(['hash' => $adminHash]);
    echo "✓ Mot de passe admin mis à jour (admin123)<br>";
    
    // Mettre à jour user_test
    $stmt = $db->prepare("UPDATE users SET password_hash = :hash WHERE username = 'user_test'");
    $stmt->execute(['hash' => $userHash]);
    echo "✓ Mot de passe user_test mis à jour (user123)<br>";
    
    echo "<br><strong style='color: green;'>✓ Mots de passe réinitialisés avec succès !</strong><br>";
    echo "<br>Vous pouvez maintenant vous connecter avec :<br>";
    echo "- <strong>admin</strong> / admin123<br>";
    echo "- <strong>user_test</strong> / user123<br>";
    echo "<br><a href='/Friches/public/index.php?page=login'>→ Aller à la page de connexion</a>";
    
} catch (Exception $e) {
    echo "<strong style='color: red;'>✗ Erreur : " . $e->getMessage() . "</strong>";
}
?>
