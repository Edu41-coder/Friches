# Projet Application Friches - Instructions Copilot

## Description du projet
Application web en PHP et JavaScript pour gérer, filtrer, trier et visualiser les données de friches industrielles stockées dans la base de données `friches_db`.

## Fonctionnalités principales
- Visualisation des données sous forme de **tableau interactif**
- Visualisation des données sur une **carte interactive** (Leaflet.js)
- Filtrage et tri des données
- Système d'authentification avec deux types d'utilisateurs

### Tableau interactif des friches
Le tableau de données doit inclure les fonctionnalités suivantes :

1. **Pagination** :
   - Navigation par pages (Première, Précédente, Suivante, Dernière)
   - Sélecteur du nombre d'éléments par page (10, 25, 50, 100)
   - Affichage des informations de pagination (ex : "Affichage de 1 à 25 sur 150 résultats")
   - Pagination côté serveur (PHP) pour optimiser les performances

2. **Sélection des colonnes** :
   - Bouton/menu déroulant pour afficher/masquer les colonnes de façon interactive
   - Checkbox pour chaque colonne disponible
   - Sauvegarde des préférences utilisateur (session ou localStorage)
   - Au minimum 5-10 colonnes sélectionnables parmi les champs de la table `friches`

3. **Tri des colonnes** :
   - Clic sur l'en-tête de colonne pour trier (ASC/DESC)
   - Indicateurs visuels (flèches ↑↓) pour montrer la direction du tri
   - Tri côté serveur (SQL ORDER BY) pour gérer de grands volumes de données
   - Mémorisation de la colonne et direction de tri actuelle

4. **Filtres avancés** :
   - Panneau de filtres sous forme de menu déroulant/accordéon
   - Types de filtres :
     - **Texte** : recherche par nom, adresse, commune
     - **Sélection** : filtre par département, région, statut
     - **Numérique** : plage de surface (min/max)
     - **Date** : plage de dates (création, mise à jour)
   - Bouton "Appliquer les filtres" et "Réinitialiser"
   - Affichage du nombre de résultats filtrés
   - Filtres combinables (ET logique entre les différents critères)

## Architecture
- **Structure MVC simple** (Model-View-Controller)
- **Backend** : PHP 8+ avec PDO pour MariaDB/MySQL
- **Frontend** : HTML5, CSS3, JavaScript vanilla
- **Carte** : Leaflet.js pour la cartographie

## Structure des dossiers
```
app/
  models/       - Classes PHP pour la logique métier
  views/        - Templates HTML/PHP
  controllers/  - Contrôleurs PHP
config/
  database.php  - Configuration BDD
public/
  css/          - Fichiers CSS
  js/           - Fichiers JavaScript
  index.php     - Point d'entrée
```

## Base de données
- **Base** : `friches_db` (MariaDB/MySQL)
- **Tables principales** :
  - `friches` : données des friches
  - `users` : utilisateurs de l'application
  - `friches_audit` : historique des modifications

## Gestion des utilisateurs
### Deux types d'utilisateurs :
1. **Administrateur** (`role = 'admin'`)
   - Créer, modifier, supprimer des friches
   - Gérer les utilisateurs (CRUD)
   - Accès complet à toutes les fonctionnalités

2. **Utilisateur standard** (`role = 'user'`)
   - Visualisation en lecture seule
   - Accès au tableau et à la carte
   - Pas de modification des données

## Conventions de code
- **PHP** : PSR-12, nommage camelCase pour méthodes, PascalCase pour classes
- **SQL** : snake_case pour tables et colonnes
- **JavaScript** : camelCase, ES6+
- **Sécurité** : 
  - Utiliser PDO avec requêtes préparées
  - Hashage bcrypt pour les mots de passe
  - Sessions sécurisées
  - Protection CSRF
  - Validation des entrées utilisateur

## UI / Styles (Guidelines)

- Utiliser Bootstrap 5 (CDN ou version locale) pour la grille, les formulaires et les composants UI.
- Palette de couleurs principale (inspirée de Facebook) :
  - Couleur primaire (bleu) : `#1877F2`
  - Blanc : `#FFFFFF`
  - Noir/foncé : `#050505` (ou `#212121` pour variantes)
  - Couleur neutre/muted : `#6c757d`

- Préférences pour le code CSS :
  - Charger d'abord Bootstrap, puis un fichier personnalisé `public/css/style.css`.
  - Définir des variables CSS (:root) pour la palette et les réutiliser.
  - Favoriser les classes utilitaires Bootstrap (p-*, m-*, d-*, text-*, bg-*) pour la mise en page rapide.
  - Utiliser des classes sémantiques propres au projet pour les composants spécifiques (ex : `.friches-card`, `.friches-navbar`).

- Accessibilité et responsive :
  - Respecter les contrastes (texte sur fond bleu -> blanc), tailles de police lisibles et focus visibles.
  - Tester les formulaires et la navigation sur mobile (Bootstrap responsive).

- Inclusion recommandée dans les vues :
  - Dans le `<head>` :
    - Bootstrap CSS via CDN
    - `<link rel="stylesheet" href="css/style.css">` (fichier personnalisé)
  - Avant `</body>` :
    - Bootstrap JS bundle (pour les composants interactifs)

Ces directives aident l'agent IA à générer des vues cohérentes et stylées avec la palette bleu/blanc/noir demandée.

## Implémentation technique du tableau

### Backend (PHP)
- Créer un modèle `Friche.php` dans `app/models/` avec les méthodes :
  - `findAll($filters, $sort, $limit, $offset)` : récupère les friches avec filtres, tri et pagination
  - `count($filters)` : compte le nombre total de résultats (pour la pagination)
  - `getColumns()` : retourne la liste des colonnes disponibles
- Créer un contrôleur `FrichesController.php` dans `app/controllers/` :
  - `index()` : affiche la vue du tableau
  - `getDataJson()` : endpoint AJAX pour récupérer les données filtrées/triées/paginées en JSON

### Frontend (JavaScript)
- Créer `public/js/friches-table.js` pour gérer :
  - Les interactions utilisateur (tri, pagination, filtres)
  - Les requêtes AJAX vers le backend
  - La mise à jour dynamique du tableau sans rechargement de page
  - La sauvegarde des préférences (colonnes visibles) dans localStorage
- Utiliser Bootstrap pour les composants UI (dropdowns, modals, buttons)
- Prévoir des indicateurs de chargement (spinners) pendant les requêtes AJAX

### Sécurité et performances
- Valider et nettoyer toutes les entrées utilisateur (filtres, tri, pagination)
- Utiliser des requêtes préparées PDO pour éviter les injections SQL
- Limiter le nombre maximum d'éléments par page (max 100)
- Ajouter des index sur les colonnes fréquemment filtrées/triées
- Prévoir une pagination côté serveur pour gérer des milliers de lignes

## Module d'analyse et visualisation des données

En complément du tableau interactif, l'application doit offrir un **module d'analyse visuelle** inspiré des analyses Python du notebook `analyse/intro.ipynb`.

### Fonctionnalités du module d'analyse :

1. **Statistiques globales** :
   - Nombre total de friches recensées
   - Nombre de communes concernées
   - Nombre de types de friches et statuts différents
   - Surface totale et moyenne des friches
   - Commune la plus touchée

2. **Graphiques et visualisations** :
   - **Graphiques à barres horizontales** : Répartition des types de friches
   - **Graphiques circulaires (pie charts)** : Statuts des friches, types de propriétaires
   - **Graphiques à barres** : Top 10 des communes avec le plus de friches
   - **Graphiques doubles** : État de pollution (sol et bâtiments)
   - **Histogrammes** : Distribution des surfaces (échelle normale et logarithmique)

3. **Technologies recommandées** :
   - **Chart.js** ou **ApexCharts** pour les graphiques interactifs en JavaScript
   - **PHP** pour générer les données agrégées (GROUP BY, COUNT, SUM)
   - Endpoint JSON dédié dans le contrôleur pour alimenter les graphiques
   - Design responsive avec grille Bootstrap

4. **Structure d'implémentation** :
   - Créer une vue `app/views/analytics.php` pour afficher les graphiques
   - Ajouter des méthodes dans `FrichesController.php` :
     - `analytics()` : affiche la page d'analyse
     - `getStatsJson()` : retourne les statistiques globales en JSON
     - `getChartData($type)` : retourne les données pour un graphique spécifique
   - Créer `public/js/friches-analytics.js` pour gérer les graphiques côté client

5. **Ordre d'affichage recommandé** :
   - Panneau de statistiques globales en haut (cartes avec chiffres clés)
   - Section "Types et statuts" : graphiques sur types de friches et statuts
   - Section "Répartition géographique" : top communes
   - Section "Pollution" : graphiques doubles sol/bâtiments
   - Section "Propriétaires" : répartition par type
   - Section "Surfaces" : histogrammes de distribution

Cette vue analytique permet aux utilisateurs (admin et standard) de visualiser rapidement les tendances et statistiques clés sans avoir à manipuler Python ou Jupyter.

## Module cartographique interactif

L'application doit inclure une **vue cartographique interactive** utilisant Leaflet.js pour afficher les friches sur une carte de France.

### Fonctionnalités de la carte :

1. **Affichage des friches** :
   - Marqueurs (markers) pour chaque friche aux coordonnées `longitude` et `latitude`
   - Popup au clic sur un marqueur affichant les informations clés (nom, commune, type, statut, surface)
   - Clustering automatique des marqueurs pour les zones denses (plugin MarkerCluster)
   - Couleurs de marqueurs différentes selon le type ou statut de friche

2. **Sélection interactive de zones** :
   - Outils de dessin pour sélectionner une zone rectangulaire ou polygonale
   - Filtrage des friches dans la zone sélectionnée
   - Affichage du nombre de friches dans la zone sélectionnée
   - Bouton pour réinitialiser la sélection

3. **Contrôles de carte** :
   - Zoom et navigation (molette, double-clic, pinch)
   - Changement de fond de carte (Street, Satellite, Topographique)
   - Contrôle de couches pour afficher/masquer les friches par type
   - Bouton de recentrage sur la France métropolitaine
   - Recherche de commune/département pour centrer la carte

4. **Interactions avec le tableau** :
   - Synchronisation carte ↔ tableau : clic sur une ligne surligne le marqueur
   - Filtres appliqués au tableau se répercutent sur la carte
   - Bouton "Voir sur la carte" dans chaque ligne du tableau

5. **Informations contextuelles** :
   - Légende des couleurs/types de friches
   - Compteur de friches visibles sur la carte
   - Mini-carte de navigation (overview map)
   - Échelle graphique

### Technologies et bibliothèques :

- **Leaflet.js** (v1.9+) : bibliothèque cartographique principale
- **Leaflet.markercluster** : clustering de marqueurs pour performance
- **Leaflet.draw** : outils de dessin pour sélection de zones
- **Leaflet.awesome-markers** ou **Leaflet.ExtraMarkers** : marqueurs colorés personnalisés
- **Tuiles OpenStreetMap** : fond de carte par défaut (gratuit, pas de clé API requise)
- Alternative : tuiles Esri, Mapbox, CartoDB

### Structure d'implémentation :

1. **Backend (PHP)** :
   - Ajouter méthode `getMapDataJson()` dans `FrichesController.php`
   - Retourne un GeoJSON ou JSON avec `id`, `latitude`, `longitude`, `nom`, `commune`, `type`, `statut`
   - Endpoint pour filtrer par bounding box : `getMapDataByBounds($north, $south, $east, $west)`
   - Optimisation : limiter les champs retournés, pagination si > 10 000 points

2. **Frontend** :
   - Créer vue `app/views/map.php` pour la carte plein écran
   - Créer `public/js/friches-map.js` pour gérer :
     - Initialisation de la carte Leaflet
     - Chargement des données via AJAX
     - Création et gestion des marqueurs
     - Gestion du clustering
     - Outils de sélection de zones
     - Synchronisation avec le tableau (si vue combinée)
   - Inclure les CSS et JS de Leaflet et plugins

3. **Ordre d'affichage** :
   - Carte centrée sur la France métropolitaine (lat: 46.603354, lon: 1.888334, zoom: 6)
   - Chargement initial de toutes les friches (ou des 5000 premières si > 10k)
   - Panneau latéral optionnel avec filtres rapides et légende
   - Mode plein écran disponible

4. **Considérations de performance** :
   - Utiliser le clustering pour > 500 marqueurs visibles
   - Charger les données par zone visible (lazy loading) si dataset très volumineux
   - Simplifier les popups : données complètes seulement au clic
   - Désactiver les animations si > 1000 marqueurs

### Exemple de structure JSON attendu :

```json
{
  "type": "FeatureCollection",
  "features": [
    {
      "type": "Feature",
      "properties": {
        "id": 123,
        "nom": "Friche Industrielle XYZ",
        "commune": "Lyon",
        "type": "Site industriel",
        "statut": "En friche",
        "surface": 15000
      },
      "geometry": {
        "type": "Point",
        "coordinates": [4.835659, 45.764043]
      }
    }
  ]
}
```

Cette vue cartographique permet une exploration géographique intuitive des friches et complète parfaitement les vues tableau et analytique.