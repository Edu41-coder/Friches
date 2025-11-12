<?php
/**
 * Modèle User
 * Fichier : app/models/User.php
 */

require_once __DIR__ . '/../../config/database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Authentifie un utilisateur
     * @param string $username
     * @param string $password
     * @return array|false Retourne les données utilisateur ou false
     */
    public function authenticate($username, $password) {
        $sql = "SELECT * FROM users WHERE username = :username AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['username' => $username]);
        
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Met à jour la date de dernière connexion
            $this->updateLastLogin($user['id']);
            
            // Ne pas retourner le hash du mot de passe
            unset($user['password_hash']);
            return $user;
        }
        
        return false;
    }
    
    /**
     * Récupère un utilisateur par son ID
     * @param int $id
     * @return array|false
     */
    public function findById($id) {
        $sql = "SELECT id, username, email, first_name, last_name, role, is_active, 
                       last_login, created_at, updated_at 
                FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        return $stmt->fetch();
    }
    
    /**
     * Récupère un utilisateur par son nom d'utilisateur
     * @param string $username
     * @return array|false
     */
    public function findByUsername($username) {
        $sql = "SELECT id, username, email, first_name, last_name, role, is_active, 
                       last_login, created_at, updated_at 
                FROM users WHERE username = :username";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['username' => $username]);
        
        return $stmt->fetch();
    }
    
    /**
     * Récupère tous les utilisateurs avec pagination et tri
     * @param array $filters Filtres de recherche
     * @param string $sortColumn Colonne de tri
     * @param string $sortDirection Direction du tri (ASC ou DESC)
     * @param int $limit Nombre d'éléments par page
     * @param int $offset Décalage
     * @return array
     */
    public function findAll($filters = [], $sortColumn = 'created_at', $sortDirection = 'DESC', $limit = 25, $offset = 0) {
        $sql = "SELECT id, username, email, first_name, last_name, role, is_active, 
                       last_login, created_at, updated_at 
                FROM users WHERE 1=1";
        $params = [];
        
        // Filtre de recherche globale
        if (!empty($filters['search'])) {
            $sql .= " AND (username LIKE :search OR email LIKE :search 
                      OR first_name LIKE :search OR last_name LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        // Filtre par rôle
        if (!empty($filters['role'])) {
            $sql .= " AND role = :role";
            $params['role'] = $filters['role'];
        }
        
        // Filtre par statut actif
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $sql .= " AND is_active = :is_active";
            $params['is_active'] = (int)$filters['is_active'];
        }
        
        // Colonnes autorisées pour le tri
        $allowedColumns = ['id', 'username', 'email', 'first_name', 'last_name', 'role', 'is_active', 'last_login', 'created_at'];
        if (!in_array($sortColumn, $allowedColumns)) {
            $sortColumn = 'created_at';
        }
        
        $sortDirection = strtoupper($sortDirection) === 'ASC' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY $sortColumn $sortDirection";
        
        $sql .= " LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        
        // Bind des paramètres
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Compte le nombre total d'utilisateurs avec filtres
     * @param array $filters
     * @return int
     */
    public function count($filters = []) {
        $sql = "SELECT COUNT(*) FROM users WHERE 1=1";
        $params = [];
        
        // Filtre de recherche globale
        if (!empty($filters['search'])) {
            $sql .= " AND (username LIKE :search OR email LIKE :search 
                      OR first_name LIKE :search OR last_name LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        // Filtre par rôle
        if (!empty($filters['role'])) {
            $sql .= " AND role = :role";
            $params['role'] = $filters['role'];
        }
        
        // Filtre par statut actif
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $sql .= " AND is_active = :is_active";
            $params['is_active'] = (int)$filters['is_active'];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Crée un nouvel utilisateur
     * @param array $data
     * @return int|false ID du nouvel utilisateur ou false
     */
    public function create($data) {
        $sql = "INSERT INTO users (username, email, password_hash, first_name, last_name, role, is_active) 
                VALUES (:username, :email, :password_hash, :first_name, :last_name, :role, :is_active)";
        
        $stmt = $this->db->prepare($sql);
        
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
        
        $params = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => $passwordHash,
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'role' => $data['role'] ?? 'user',
            'is_active' => $data['is_active'] ?? 1
        ];
        
        if ($stmt->execute($params)) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Met à jour un utilisateur
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        $fields = [];
        $params = ['id' => $id];
        
        if (isset($data['username'])) {
            $fields[] = "username = :username";
            $params['username'] = $data['username'];
        }
        
        if (isset($data['email'])) {
            $fields[] = "email = :email";
            $params['email'] = $data['email'];
        }
        
        if (isset($data['password']) && !empty($data['password'])) {
            $fields[] = "password_hash = :password_hash";
            $params['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        
        if (isset($data['first_name'])) {
            $fields[] = "first_name = :first_name";
            $params['first_name'] = $data['first_name'];
        }
        
        if (isset($data['last_name'])) {
            $fields[] = "last_name = :last_name";
            $params['last_name'] = $data['last_name'];
        }
        
        if (isset($data['role'])) {
            $fields[] = "role = :role";
            $params['role'] = $data['role'];
        }
        
        if (isset($data['is_active'])) {
            $fields[] = "is_active = :is_active";
            $params['is_active'] = $data['is_active'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    /**
     * Supprime un utilisateur
     * @param int $id
     * @return bool
     * @throws Exception Si tentative de suppression du dernier admin
     */
    public function delete($id) {
        // Empêcher la suppression du dernier admin
        $sql = "SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1";
        $stmt = $this->db->query($sql);
        $adminCount = $stmt->fetchColumn();
        
        if ($adminCount <= 1) {
            $sql = "SELECT role FROM users WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            $user = $stmt->fetch();
            
            if ($user && $user['role'] === 'admin') {
                throw new Exception("Impossible de supprimer le dernier administrateur");
            }
        }
        
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute(['id' => $id]);
    }
    
    /**
     * Vérifie si un nom d'utilisateur existe déjà
     * @param string $username
     * @param int|null $excludeId ID à exclure de la vérification (pour les mises à jour)
     * @return bool
     */
    public function usernameExists($username, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM users WHERE username = :username";
        
        if ($excludeId) {
            $sql .= " AND id != :id";
        }
        
        $stmt = $this->db->prepare($sql);
        $params = ['username' => $username];
        
        if ($excludeId) {
            $params['id'] = $excludeId;
        }
        
        $stmt->execute($params);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Vérifie si un email existe déjà
     * @param string $email
     * @param int|null $excludeId ID à exclure de la vérification (pour les mises à jour)
     * @return bool
     */
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
        
        if ($excludeId) {
            $sql .= " AND id != :id";
        }
        
        $stmt = $this->db->prepare($sql);
        $params = ['email' => $email];
        
        if ($excludeId) {
            $params['id'] = $excludeId;
        }
        
        $stmt->execute($params);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Met à jour la date de dernière connexion
     * @param int $userId
     * @return bool
     */
    private function updateLastLogin($userId) {
        $sql = "UPDATE users SET last_login = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute(['id' => $userId]);
    }
}
