<?php
/**
 * Configuration de la base de données
 * Fichier : config/database.php
 */

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'friches_db');
define('DB_USER', 'root');  // À modifier selon votre configuration
define('DB_PASS', '');      // À modifier selon votre configuration
define('DB_CHARSET', 'utf8mb4');

// Configuration de la session
define('SESSION_LIFETIME', 7200); // 2 heures en secondes

// Configuration de sécurité
define('PASSWORD_MIN_LENGTH', 6);
define('CSRF_TOKEN_NAME', 'csrf_token');

/**
 * Classe Database pour gérer la connexion PDO
 */
class Database {
    private static $instance = null;
    private $pdo;
    
    /**
     * Constructeur privé (Singleton)
     */
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }
    
    /**
     * Récupère l'instance unique de Database (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Récupère la connexion PDO
     */
    public function getConnection() {
        return $this->pdo;
    }
    
    /**
     * Empêche le clonage de l'instance
     */
    private function __clone() {}
    
    /**
     * Empêche la désérialisation de l'instance
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
