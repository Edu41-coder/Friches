# 🗄️ Base de données MariaDB pour les Friches

Ce dossier contient les scripts SQL et Python pour créer et alimenter la base de données MariaDB des friches.

## 📋 Fichiers

- **`create_database.sql`** : Script SQL de création de la base de données
- **`import_csv_to_mariadb.py`** : Script Python d'import des données CSV
- **`README.md`** : Ce fichier

## 🚀 Installation et Configuration

### Prérequis

1. **MariaDB/MySQL** installé et configuré
2. **Python 3.x** avec les packages suivants :
   ```bash
   pip install pandas mysql-connector-python
   ```

### Étape 1 : Créer la base de données

Exécutez le script SQL pour créer la structure de la base :

```bash
mysql -u root -p < create_database.sql
```

Ou depuis MySQL/MariaDB :

```sql
source F:/Friches/database/create_database.sql
```

### Étape 2 : Configurer les paramètres de connexion

Éditez le fichier `import_csv_to_mariadb.py` et modifiez la configuration :

```python
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',           # Votre utilisateur MySQL
    'password': 'votre_mdp',  # Votre mot de passe
    'database': 'friches_db',
    'charset': 'utf8mb4',
    'use_unicode': True
}
```

### Étape 3 : Importer les données

Exécutez le script Python d'import :

```bash
python import_csv_to_mariadb.py
```

Le script va :
- Charger le fichier `friches-standard-geom.csv`
- Nettoyer les données
- Insérer environ 28 700 lignes dans la base
- Afficher les statistiques d'import

## 📊 Structure de la base de données

### Table principale : `friches`

Contient toutes les informations sur les friches avec 36 colonnes :

| Colonne | Type | Description |
|---------|------|-------------|
| `id` | INT | Clé primaire auto-incrémentée |
| `site_id` | VARCHAR(50) | Identifiant unique du site |
| `longitude` | DECIMAL(10,7) | Longitude (coordonnée X) |
| `latitude` | DECIMAL(10,7) | Latitude (coordonnée Y) |
| `site_nom` | VARCHAR(500) | Nom du site |
| `site_type` | VARCHAR(100) | Type de friche |
| `site_statut` | VARCHAR(100) | Statut (avec/sans projet, reconvertie) |
| `comm_nom` | VARCHAR(200) | Nom de la commune |
| `comm_insee` | VARCHAR(10) | Code INSEE de la commune |
| ... | ... | (et 27 autres colonnes) |

### Vues créées

1. **`friches_metropole`** : Friches en France métropolitaine uniquement
2. **`friches_domtom`** : Friches dans les DOM-TOM
3. **`stats_par_commune`** : Statistiques agrégées par commune
4. **`stats_par_type`** : Statistiques par type de friche

### Fonctions et Procédures

1. **`distance_haversine(lat1, lon1, lat2, lon2)`** : Calcule la distance en km entre deux points
2. **`chercher_friches_rayon(lat, lon, rayon_km)`** : Recherche les friches dans un rayon donné
3. **`chercher_friches_bbox(lat_min, lon_min, lat_max, lon_max)`** : Recherche dans une zone rectangulaire

## 🔍 Exemples de requêtes SQL

### Compter les friches par statut

```sql
SELECT site_statut, COUNT(*) as nombre
FROM friches
GROUP BY site_statut
ORDER BY nombre DESC;
```

### Trouver les friches dans un rayon de 10 km autour de Paris

```sql
CALL chercher_friches_rayon(48.8566, 2.3522, 10);
```

### Friches dans une zone rectangulaire (bounding box)

```sql
CALL chercher_friches_bbox(48.8, 2.2, 48.9, 2.4);
```

### Top 10 des communes avec le plus de friches

```sql
SELECT comm_nom, nb_friches, nb_sans_projet, nb_avec_projet
FROM stats_par_commune
LIMIT 10;
```

### Friches polluées en France métropolitaine

```sql
SELECT site_nom, comm_nom, longitude, latitude
FROM friches_metropole
WHERE sol_pollution_existe = 'pollution avérée'
ORDER BY comm_nom;
```

### Friches dans une zone géographique (SELECT simple)

```sql
SELECT site_nom, comm_nom, longitude, latitude
FROM friches
WHERE latitude BETWEEN 48.8 AND 48.9
  AND longitude BETWEEN 2.2 AND 2.4
ORDER BY comm_nom;
```

## 🛠️ Optimisations

### Index créés automatiquement

- Index sur `site_id` (clé unique)
- Index composé sur `(longitude, latitude)` pour les requêtes géographiques
- Index sur `comm_nom` et `comm_insee`
- Index sur `site_statut`, `site_type`, `sol_pollution_existe`

### Améliorer les performances

Pour des requêtes géospatiales plus rapides, vous pouvez ajouter une colonne de type POINT :

```sql
ALTER TABLE friches ADD COLUMN geom_point POINT NULL;

UPDATE friches 
SET geom_point = POINT(longitude, latitude)
WHERE longitude IS NOT NULL AND latitude IS NOT NULL;

CREATE SPATIAL INDEX idx_geom ON friches(geom_point);
```

## 🔐 Sécurité

### Créer un utilisateur dédié pour l'application PHP

```sql
CREATE USER 'friches_app'@'localhost' IDENTIFIED BY 'mot_de_passe_securise';
GRANT SELECT, INSERT, UPDATE ON friches_db.* TO 'friches_app'@'localhost';
GRANT EXECUTE ON PROCEDURE friches_db.chercher_friches_rayon TO 'friches_app'@'localhost';
GRANT EXECUTE ON PROCEDURE friches_db.chercher_friches_bbox TO 'friches_app'@'localhost';
FLUSH PRIVILEGES;
```

## 📈 Statistiques de la base

Après l'import, vous devriez avoir :

- **~28 728** friches au total
- **~28 000+** avec coordonnées géographiques
- **~27 000+** en France métropolitaine
- **~1 700** dans les DOM-TOM

## 🐛 Dépannage

### Erreur : "Table 'friches' doesn't exist"

Assurez-vous d'avoir exécuté `create_database.sql` avant l'import.

### Erreur : "Access denied for user"

Vérifiez les paramètres de connexion dans `DB_CONFIG`.

### Import très lent

Augmentez la taille des lots dans le script Python :

```python
batch_size = 5000  # Au lieu de 1000
```

### Problèmes d'encodage

La base est configurée en UTF-8 (utf8mb4). Si vous avez des caractères spéciaux manquants, vérifiez l'encodage de votre connexion.

## 📝 Notes

- Le format des coordonnées est WGS84 (EPSG:4326)
- Les coordonnées sont en degrés décimaux
- La précision est de 7 décimales (~1 cm)
- Les dates sont au format ISO (YYYY-MM-DD)

## 🔄 Mise à jour des données

Pour mettre à jour les données :

1. Générez un nouveau fichier CSV avec les données à jour
2. Exécutez le script d'import (il vous demandera si vous voulez vider la table)
3. Ou utilisez `TRUNCATE TABLE friches;` puis réimportez

## 📚 Ressources

- [Documentation MariaDB](https://mariadb.com/kb/en/)
- [Fonctions spatiales MySQL](https://dev.mysql.com/doc/refman/8.0/en/spatial-function-reference.html)
- [mysql-connector-python](https://dev.mysql.com/doc/connector-python/en/)

---

**Prochaine étape** : Créer l'application PHP pour exploiter cette base de données ! 🚀
