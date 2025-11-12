# Module Analyses & Statistiques

## Vue d'ensemble

Le module **Analyses & Statistiques** offre une visualisation complète et interactive des données de friches industrielles à travers des graphiques Chart.js et des cartes statistiques. Ce module permet aux utilisateurs (admin et standard) d'explorer les tendances, distributions et statistiques clés sans avoir à manipuler les données brutes.

## Accès

- **URL** : `http://localhost/Friches/public/index.php?page=analytics`
- **Restriction** : Utilisateurs authentifiés (admin et user)
- **Point d'entrée** : Bouton "Analyses & Statistiques" dans le dashboard

## Fonctionnalités

### 1. Statistiques Globales (Cartes)

#### 7 cartes statistiques interactives

**Première ligne (4 cartes)** :
1. **Friches recensées**
   - Icône : 📍 (géolocalisation)
   - Couleur : Bleu
   - Affiche : Nombre total de friches dans la base
   - Source : `COUNT(*)`

2. **Communes concernées**
   - Icône : 🏢 (bâtiment)
   - Couleur : Vert
   - Affiche : Nombre de communes distinctes avec au moins une friche
   - Source : `COUNT(DISTINCT comm_nom)`

3. **Surface totale (m²)**
   - Icône : 📦 (zone)
   - Couleur : Orange
   - Affiche : Somme des surfaces de toutes les friches
   - Format : Séparateurs de milliers (ex : 1 234 567)
   - Source : `SUM(unite_fonciere_surface)`

4. **Surface moyenne (m²)**
   - Icône : ↕️ (flèches)
   - Couleur : Cyan
   - Affiche : Surface moyenne par friche
   - Format : Séparateurs de milliers
   - Source : `AVG(unite_fonciere_surface)`

**Deuxième ligne (3 cartes)** :
5. **Commune la plus touchée**
   - Icône : 📌 (épingle)
   - Couleur : Rouge
   - Affiche : Nom de la commune + badge avec nombre de friches
   - Source : `GROUP BY comm_nom ORDER BY COUNT(*) DESC LIMIT 1`

6. **Types de friches**
   - Icône : 🏷️ (étiquettes)
   - Couleur : Violet
   - Affiche : Nombre de types distincts
   - Source : `COUNT(DISTINCT site_type)`

7. **Statuts différents**
   - Icône : 🚩 (drapeau)
   - Couleur : Bleu
   - Affiche : Nombre de statuts distincts
   - Source : `COUNT(DISTINCT site_statut)`

#### Animations
- **Hover** : Les cartes se soulèvent légèrement (`translateY(-5px)`)
- **Transition** : Transformation fluide en 0.2s

### 2. Graphiques Interactifs

#### Section : Types et Statuts des Friches

**A. Répartition par Type** (Barres horizontales)
- **Type de graphique** : Barres horizontales
- **Couleurs** : Dégradé de bleus (#1877F2 → #E0F0FF)
- **Données** : Nombre de friches par type
- **Tri** : Par nombre décroissant
- **Légende** : Masquée
- **Axe X** : Commence à zéro
- **Source SQL** :
  ```sql
  SELECT site_type as label, COUNT(*) as count 
  FROM friches 
  WHERE site_type IS NOT NULL 
  GROUP BY site_type 
  ORDER BY count DESC
  ```

**B. Répartition par Statut** (Camembert)
- **Type de graphique** : Pie chart
- **Couleurs** : Palette mixte multicolore (8 couleurs)
- **Données** : Nombre de friches par statut
- **Légende** : À droite du graphique
- **Bordure** : Blanche de 2px
- **Source SQL** :
  ```sql
  SELECT site_statut as label, COUNT(*) as count 
  FROM friches 
  WHERE site_statut IS NOT NULL 
  GROUP BY site_statut 
  ORDER BY count DESC
  ```

#### Section : Répartition Géographique

**Top 10 des Communes** (Barres verticales)
- **Type de graphique** : Barres verticales
- **Couleurs** : Dégradé de verts (#28a745 → #c3f7c5)
- **Données** : Les 10 communes avec le plus de friches
- **Légende** : Masquée
- **Axe Y** : Commence à zéro
- **Paramètre** : `limit=10` (configurable)
- **Source SQL** :
  ```sql
  SELECT comm_nom as label, COUNT(*) as count 
  FROM friches 
  WHERE comm_nom IS NOT NULL 
  GROUP BY comm_nom 
  ORDER BY count DESC 
  LIMIT 10
  ```

#### Section : État de Pollution

**A. Pollution du Sol** (Donut)
- **Type de graphique** : Doughnut chart
- **Couleurs** : 
  - Pollué : Rouge (#dc3545)
  - Non pollué : Vert (#28a745)
  - Inconnu : Orange (#ffc107)
- **Données** : Répartition pollution sol (Oui/Non/Inconnu)
- **Légende** : En bas du graphique
- **Hauteur** : 300px
- **Source SQL** :
  ```sql
  SELECT 
    CASE 
      WHEN sol_pollution_existe = 'Oui' THEN 'Pollué'
      WHEN sol_pollution_existe = 'Non' THEN 'Non pollué'
      ELSE 'Inconnu'
    END as label,
    COUNT(*) as count 
  FROM friches 
  GROUP BY sol_pollution_existe
  ```

**B. Pollution des Bâtiments** (Donut)
- **Type de graphique** : Doughnut chart
- **Couleurs** : Dégradé de cyans (#17a2b8 → #b3eefc)
- **Données** : Types de pollution des bâtiments
- **Légende** : En bas du graphique
- **Hauteur** : 300px
- **Source SQL** :
  ```sql
  SELECT bati_pollution as label, COUNT(*) as count 
  FROM friches 
  WHERE bati_pollution IS NOT NULL 
  GROUP BY bati_pollution 
  ORDER BY count DESC
  ```

#### Section : Types de Propriétaires

**Répartition par Type de Propriétaire** (Camembert)
- **Type de graphique** : Pie chart
- **Couleurs** : Dégradé de violets (#9b59b6 → #cfc1e2)
- **Données** : Nombre de friches par type de propriétaire
- **Légende** : À droite du graphique
- **Largeur** : 50% de la page (col-md-6 centré)
- **Source SQL** :
  ```sql
  SELECT proprio_type as label, COUNT(*) as count 
  FROM friches 
  WHERE proprio_type IS NOT NULL 
  GROUP BY proprio_type 
  ORDER BY count DESC
  ```

#### Section : Distribution des Surfaces

**Répartition par Tranche de Surface** (Barres)
- **Type de graphique** : Barres verticales
- **Couleurs** : Dégradé de jaunes (#ffc107 → #fff1cf)
- **Données** : Nombre de friches par tranche de surface
- **Tranches** :
  - 0-1k m²
  - 1k-5k m²
  - 5k-10k m²
  - 10k-50k m²
  - 50k-100k m²
  - 100k+ m²
- **Légende** : Masquée
- **Axe Y** : Commence à zéro
- **Source SQL** :
  ```sql
  SELECT 
    CASE 
      WHEN unite_fonciere_surface < 1000 THEN '0-1k'
      WHEN unite_fonciere_surface < 5000 THEN '1k-5k'
      WHEN unite_fonciere_surface < 10000 THEN '5k-10k'
      WHEN unite_fonciere_surface < 50000 THEN '10k-50k'
      WHEN unite_fonciere_surface < 100000 THEN '50k-100k'
      ELSE '100k+'
    END as label,
    COUNT(*) as count 
  FROM friches 
  WHERE unite_fonciere_surface IS NOT NULL 
  ORDER BY [ordre personnalisé]
  ```

### 3. Interactivité des Graphiques

#### Fonctionnalités Chart.js
- **Hover** : Info-bulle avec valeur exacte
- **Responsive** : Adaptation automatique à la taille de l'écran
- **Animation** : Apparition progressive au chargement
- **Redimensionnement** : Mise à jour automatique lors du resize de fenêtre

#### Options Chart.js communes
```javascript
{
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { ... },
    title: { ... }
  },
  scales: { ... }
}
```

## Architecture Technique

### Fichiers créés/modifiés

#### Modèle
**`app/models/Friche.php`** (modifié) :

Ajout de 8 méthodes statistiques :

1. **`getGlobalStats()`**
   - Retourne : Tableau associatif avec 8 statistiques
   - Champs : total_friches, total_communes, total_types, total_statuts, surface_totale, surface_moyenne, commune_max, commune_max_count
   - SQL : 7 requêtes distinctes avec COUNT, SUM, AVG, GROUP BY

2. **`getTypeDistribution()`**
   - Retourne : Array de [label, count]
   - SQL : `GROUP BY site_type ORDER BY count DESC`

3. **`getStatusDistribution()`**
   - Retourne : Array de [label, count]
   - SQL : `GROUP BY site_statut ORDER BY count DESC`

4. **`getTopCommunes($limit = 10)`**
   - Paramètre : Nombre de communes à retourner
   - Retourne : Array de [label, count]
   - SQL : `GROUP BY comm_nom ORDER BY count DESC LIMIT :limit`

5. **`getSoilPollutionDistribution()`**
   - Retourne : Array de [label, count]
   - Transformation : Oui → Pollué, Non → Non pollué, NULL → Inconnu
   - SQL : `CASE WHEN ... GROUP BY sol_pollution_existe`

6. **`getBuildingPollutionDistribution()`**
   - Retourne : Array de [label, count]
   - SQL : `GROUP BY bati_pollution ORDER BY count DESC`

7. **`getOwnerTypeDistribution()`**
   - Retourne : Array de [label, count]
   - SQL : `GROUP BY proprio_type ORDER BY count DESC`

8. **`getSurfaceDistribution()`**
   - Retourne : Array de [label, count]
   - Tranches : 6 intervalles de surfaces
   - SQL : `CASE WHEN ... GROUP BY + ORDER BY personnalisé`

#### Contrôleur
**`app/controllers/AnalyticsController.php`** (nouveau) :

**Méthodes** :
- `__construct()` : Vérifie l'authentification (requireAuth)
- `index()` : Affiche la vue analytics/index.php
- `getGlobalStatsJson()` : Endpoint AJAX pour statistiques globales
- `getChartDataJson()` : Endpoint AJAX pour données de graphiques

**Paramètres acceptés** :
- `?action=getStats` → statistiques globales
- `?action=getChartData&type=types` → données types
- `?action=getChartData&type=statuts` → données statuts
- `?action=getChartData&type=communes&limit=10` → top communes
- `?action=getChartData&type=soil_pollution` → pollution sol
- `?action=getChartData&type=building_pollution` → pollution bâtiments
- `?action=getChartData&type=owner_types` → types propriétaires
- `?action=getChartData&type=surfaces` → distribution surfaces

**Format de réponse JSON** :
```json
{
  "success": true,
  "data": [
    {"label": "...", "count": 123},
    ...
  ]
}
```

#### Vue
**`app/views/analytics/index.php`** (nouveau) :

**Structure HTML** :
```html
<nav>Navbar avec user info</nav>
<main>
  <div class="card">
    <div class="card-header">Titre + bouton retour</div>
    <div class="card-body">
      <!-- Loading indicator -->
      <div id="loadingStats">Spinner</div>
      
      <!-- Section globale -->
      <div id="globalStatsSection">
        <h5>Vue d'ensemble</h5>
        <div class="row">7 cartes statistiques</div>
        
        <h5>Types et Statuts</h5>
        <div class="row">2 graphiques</div>
        
        <h5>Répartition Géographique</h5>
        <div class="row">1 graphique</div>
        
        <h5>État de Pollution</h5>
        <div class="row">2 graphiques</div>
        
        <h5>Types de Propriétaires</h5>
        <div class="row">1 graphique</div>
        
        <h5>Distribution des Surfaces</h5>
        <div class="row">1 graphique</div>
      </div>
    </div>
  </div>
</main>
```

**Dépendances** :
- Bootstrap 5.3.2 CSS/JS
- Bootstrap Icons 1.11.1
- Chart.js 4.4.0
- analytics.js (custom)

**Styles inline** :
- `.stat-card` : Cartes statistiques avec hover effect
- `.stat-icon` : Icônes grandes (2.5rem)
- `.stat-value` : Valeurs en gras (2rem)
- `.chart-container` : Conteneurs graphiques (400px hauteur)
- `.chart-container-small` : Conteneurs petits (300px)
- `.section-title` : Titres de sections avec bordure gauche bleue

#### JavaScript
**`public/js/analytics.js`** (nouveau) :

**Objet principal** : `Analytics`

**Propriétés** :
- `charts` : Stocke les instances Chart.js
- `colors` : Palettes de couleurs pré-définies (8 palettes)

**Méthodes** :

1. **`init()`**
   - Point d'entrée principal
   - Charge statistiques globales
   - Charge tous les graphiques en parallèle (Promise.all)
   - Masque le loader, affiche le contenu

2. **`loadGlobalStats()`**
   - Fetch : `/index.php?page=analytics&action=getStats`
   - Met à jour les 7 cartes avec `textContent`
   - Formate les nombres avec `Intl.NumberFormat('fr-FR')`

3. **`loadAllCharts()`**
   - Appelle les 7 méthodes de création en parallèle
   - Utilise `Promise.all` pour attendre toutes les requêtes

4. **`fetchChartData(type, params)`**
   - Méthode utilitaire pour récupérer données
   - Construit l'URL avec URLSearchParams
   - Retourne le tableau de données ou lève une exception

5. **`createTypeChart()`**
   - Type : Barres horizontales (`indexAxis: 'y'`)
   - Canvas : `#chartTypes`
   - Couleurs : Palette primary (bleus)

6. **`createStatusChart()`**
   - Type : Camembert (`type: 'pie'`)
   - Canvas : `#chartStatuts`
   - Couleurs : Palette mixed (8 couleurs)

7. **`createCommunesChart()`**
   - Type : Barres verticales (`type: 'bar'`)
   - Canvas : `#chartCommunes`
   - Couleurs : Palette success (verts)

8. **`createSoilPollutionChart()`**
   - Type : Donut (`type: 'doughnut'`)
   - Canvas : `#chartSoilPollution`
   - Couleurs : Rouge/Vert/Orange

9. **`createBuildingPollutionChart()`**
   - Type : Donut (`type: 'doughnut'`)
   - Canvas : `#chartBuildingPollution`
   - Couleurs : Palette info (cyans)

10. **`createOwnerTypesChart()`**
    - Type : Camembert (`type: 'pie'`)
    - Canvas : `#chartOwnerTypes`
    - Couleurs : Palette purple (violets)

11. **`createSurfacesChart()`**
    - Type : Barres verticales (`type: 'bar'`)
    - Canvas : `#chartSurfaces`
    - Couleurs : Palette warning (jaunes)

12. **`showError(message)`**
    - Affiche une alerte Bootstrap danger
    - Remplace le spinner de chargement

**Palettes de couleurs** :
```javascript
colors: {
  primary: ['#1877F2', '#4A90E2', '#7FB3D5', '#B5D5F5', '#E0F0FF'],
  success: ['#28a745', '#48c774', '#71d98f', '#9aebaa', '#c3f7c5'],
  danger: ['#dc3545', '#e55b68', '#ee818b', '#f7a7ae', '#ffcdd1'],
  warning: ['#ffc107', '#ffcd39', '#ffd96b', '#ffe59d', '#fff1cf'],
  info: ['#17a2b8', '#3eb5c9', '#65c8da', '#8cdbeb', '#b3eefc'],
  purple: ['#9b59b6', '#a873c1', '#b58dcc', '#c2a7d7', '#cfc1e2'],
  mixed: ['#1877F2', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#9b59b6', '#fd7e14', '#6610f2']
}
```

#### Routeur
**`public/index.php`** (modifié) :

Ajout du case `analytics` :
```php
case 'analytics':
    $auth->requireAuth();
    $analyticsController = new AnalyticsController();
    
    if ($action === 'getStats') {
        $analyticsController->getGlobalStatsJson();
    } elseif ($action === 'getChartData') {
        $analyticsController->getChartDataJson();
    } else {
        $analyticsController->index();
    }
    break;
```

**`app/views/dashboard.php`** (modifié) :

Remplacement du bouton désactivé :
```php
// Avant
<a href="#" class="btn btn-success disabled">Bientôt disponible</a>

// Après
<a href="index.php?page=analytics" class="btn btn-success">Accéder</a>
```

### Flux de données

#### Chargement initial
```
Page chargée
  ↓
analytics.js (DOMContentLoaded)
  ↓
Analytics.init()
  ↓
loadGlobalStats() + loadAllCharts() (parallèle)
  ↓
7 requêtes AJAX simultanées
  ↓
├─ GET index.php?page=analytics&action=getStats
│    ↓
│    AnalyticsController::getGlobalStatsJson()
│    ↓
│    Friche::getGlobalStats()
│    ↓
│    7 requêtes SQL distinctes
│    ↓
│    JSON {success, data: {...}}
│    ↓
│    Mise à jour des 7 cartes statistiques
│
└─ GET index.php?page=analytics&action=getChartData&type=types
     ↓
     AnalyticsController::getChartDataJson()
     ↓
     Switch sur $chartType
     ↓
     Friche::getTypeDistribution()
     ↓
     SQL SELECT ... GROUP BY site_type
     ↓
     JSON {success, data: [{label, count}, ...]}
     ↓
     new Chart(ctx, {...})
     ↓
     Graphique rendu dans le canvas
```

## Performances

### Optimisations implémentées

1. **Chargement parallèle** :
   - `Promise.all()` pour charger tous les graphiques simultanément
   - Réduit le temps d'attente total

2. **Requêtes SQL optimisées** :
   - Utilisation de `COUNT(DISTINCT ...)` pour éviter les doublons
   - `GROUP BY` avec `ORDER BY count DESC` pour tri côté base
   - Limitation des résultats avec `LIMIT` (top communes)

3. **Cache Chart.js** :
   - Les instances Chart.js sont stockées dans `Analytics.charts`
   - Permet une destruction/recréation si nécessaire

4. **Responsive** :
   - `maintainAspectRatio: false` pour contrôle précis
   - Conteneurs avec hauteur fixe pour stabilité
   - `responsive: true` pour adaptation automatique

### Métriques estimées

- **Nombre de requêtes SQL** : 8 (1 stats globales + 7 graphiques)
- **Nombre de requêtes AJAX** : 8
- **Temps de chargement estimé** : < 2 secondes (28 728 friches)
- **Taille des réponses JSON** : ~10-50 KB total
- **Graphiques rendus** : 7 Chart.js instances

## Sécurité

### Contrôle d'accès
- **Authentification requise** : `AuthController::requireAuth()`
- **Pas de restriction par rôle** : Admin et User peuvent accéder
- **Session vérifiée** : Sur chaque requête

### Protection des données
- **Pas de modification** : Module en lecture seule
- **Données agrégées** : Pas d'informations personnelles exposées
- **JSON encodage** : `JSON_UNESCAPED_UNICODE` pour caractères français

### Validation
- **Type de graphique** : Switch avec valeurs autorisées uniquement
- **Paramètre limit** : Cast en `(int)` pour éviter injection
- **Colonnes SQL** : Pas de paramètres utilisateur dans les noms de colonnes

## Design et UX

### Palette de couleurs

**Cartes statistiques** :
- Bleu (#1877F2) : Friches
- Vert (#28a745) : Communes
- Orange (#ffc107) : Surface totale
- Cyan (#17a2b8) : Surface moyenne
- Rouge (#e74c3c) : Commune max
- Violet (#9b59b6) : Types
- Bleu (#3498db) : Statuts

**Graphiques** :
- Types : Dégradé bleus (cohérence avec marque)
- Statuts : Multicolore (distinction claire)
- Communes : Dégradé verts (thème géographique)
- Pollution sol : Feu tricolore (rouge/vert/orange)
- Pollution bâtiments : Cyans (thème eau/environnement)
- Propriétaires : Violets (distinction visuelle)
- Surfaces : Jaunes (thème mesure/quantité)

### Typographie
- **Titres sections** : `<h5>` avec bordure gauche bleue
- **Valeurs statistiques** : 2rem, font-weight bold, couleur primaire
- **Labels cartes** : text-muted, taille normale
- **Titres graphiques** : `<h6>` dans card-header bg-light

### Icônes
Toutes de Bootstrap Icons :
- `bi-geo-fill` : Géolocalisation
- `bi-building` : Bâtiment
- `bi-bounding-box` : Zone
- `bi-arrow-down-up` : Moyenne
- `bi-pin-map-fill` : Épingle
- `bi-tags-fill` : Étiquettes
- `bi-flag-fill` : Drapeau
- `bi-diagram-3` : Diagramme
- `bi-pie-chart` : Camembert
- `bi-bar-chart-line` : Barres
- `bi-droplet` : Goutte
- `bi-house-door` : Maison
- `bi-people` : Personnes
- `bi-graph-up` : Graphique montant

### Responsive
- **Grid Bootstrap** : col-md-3, col-md-6, col-12
- **Graphiques** : Adaptation automatique à la largeur
- **Cartes** : 4 colonnes sur desktop, 1 colonne sur mobile
- **Hauteurs fixes** : Évite les sauts de layout

## Tests recommandés

### Test de chargement
1. Se connecter (admin ou user)
2. Accéder à "Analyses & Statistiques"
3. Vérifier l'affichage du spinner
4. Attendre le chargement complet (< 2s)
5. Vérifier que les 7 cartes sont remplies
6. Vérifier que les 7 graphiques sont affichés

### Test des statistiques
1. Comparer "Friches recensées" avec :
   ```sql
   SELECT COUNT(*) FROM friches;
   ```
2. Vérifier la cohérence des surfaces
3. Identifier la commune max manuellement et comparer

### Test des graphiques
1. **Types** : Vérifier l'ordre décroissant
2. **Statuts** : Vérifier le total = 100%
3. **Communes** : Vérifier que les 10 premières correspondent
4. **Hover** : Passer la souris sur chaque graphique
5. **Responsive** : Redimensionner la fenêtre

### Test d'erreur
1. Modifier l'URL pour type inconnu :
   ```
   ?page=analytics&action=getChartData&type=invalid
   ```
2. Vérifier l'affichage de l'erreur JSON
3. Désactiver le réseau dans DevTools
4. Recharger la page, vérifier l'affichage de l'erreur

### Test cross-browser
- ✅ Chrome/Edge (Chromium)
- ✅ Firefox
- ✅ Safari

## Évolutions possibles

### Fonctionnalités avancées

1. **Filtres temporels**
   - Sélecteur de période (date début/fin)
   - Comparaison année par année
   - Évolution dans le temps (graphiques en ligne)

2. **Export des données**
   - Bouton export PNG pour chaque graphique (Chart.js toDataURL)
   - Export CSV des données agrégées
   - Génération PDF du rapport complet

3. **Graphiques supplémentaires**
   - Carte thermique (heatmap) des départements
   - Nuage de points surface vs pollution
   - Graphique en ligne : évolution du nombre de friches par année

4. **Analyses croisées**
   - Filtrer tous les graphiques par commune
   - Filtrer par type de friche
   - Comparaison entre deux types sélectionnés

5. **Dashboard personnalisable**
   - Drag & drop des graphiques
   - Choix des graphiques à afficher
   - Sauvegarde des préférences en localStorage

6. **Statistiques avancées**
   - Médiane des surfaces
   - Écart-type
   - Percentiles
   - Tendances et projections

7. **Intégration avec la carte**
   - Clic sur un graphique → zoom carte sur zone
   - Sélection zone carte → mise à jour graphiques
   - Synchronisation bidirectionnelle

8. **Indicateurs de performance**
   - Taux de friches traitées/reconverties
   - Vitesse de progression du dossier
   - Comparaison avec objectifs nationaux

### Optimisations techniques

1. **Cache côté serveur**
   - Mettre en cache les résultats agrégés (Redis/Memcached)
   - Invalidation lors de modifications de données
   - TTL de 1 heure

2. **Lazy loading**
   - Charger les graphiques au scroll (IntersectionObserver)
   - Améliore le temps de chargement initial
   - Réduit la charge serveur

3. **WebWorkers**
   - Calculs côté client dans un worker
   - Évite le blocage du thread principal
   - Meilleure réactivité UI

4. **Chart.js plugins**
   - Plugin zoom (chart.js-plugin-zoom)
   - Plugin annotation (lignes de moyenne, etc.)
   - Plugin datalabels (afficher valeurs sur graphiques)

## Dépendances externes

### CDN utilisés

**Bootstrap 5.3.2** :
```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
```

**Bootstrap Icons 1.11.1** :
```html
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
```

**Chart.js 4.4.0** :
```html
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
```

### Alternative locale
Pour éviter la dépendance aux CDN :
1. Télécharger les fichiers dans `public/vendor/`
2. Modifier les liens dans `analytics/index.php`
3. Avantages : Fonctionne hors ligne, contrôle des versions

## Résolution de problèmes

### Problème : Graphiques ne s'affichent pas
**Solution** :
1. Ouvrir la console navigateur (F12)
2. Vérifier les erreurs JavaScript
3. Vérifier que Chart.js est chargé : `typeof Chart`
4. Vérifier les requêtes AJAX dans l'onglet Network

### Problème : Statistiques à zéro
**Solution** :
1. Vérifier la connexion à la base de données
2. Exécuter manuellement les requêtes SQL
3. Vérifier les permissions de l'utilisateur MySQL

### Problème : Erreur JSON parsing
**Solution** :
1. Vérifier les réponses dans Network tab
2. S'assurer que le contrôleur retourne du JSON valide
3. Vérifier `header('Content-Type: application/json')`

### Problème : Performance lente
**Solution** :
1. Ajouter des index sur les colonnes utilisées dans GROUP BY
2. Réduire le nombre de friches (pagination future)
3. Implémenter un cache serveur

## Compatibilité

### Navigateurs supportés
- ✅ Chrome 90+ (recommandé)
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ✅ Opera 76+

### Résolutions testées
- Desktop : 1920x1080, 1366x768, 1280x720
- Tablet : 768x1024 (iPad)
- Mobile : 375x667 (iPhone), 360x640 (Android)

### Limitations connues
- Chart.js nécessite JavaScript activé
- Canvas non supporté sur navigateurs très anciens (IE)
- Graphiques non imprimables directement (utiliser export PNG)

## Conclusion

Le module **Analyses & Statistiques** offre une vue d'ensemble complète et visuellement attractive des 28 728 friches industrielles recensées. Avec 7 graphiques interactifs et 7 cartes statistiques, il permet aux utilisateurs de comprendre rapidement les tendances, distributions géographiques, états de pollution et caractéristiques des friches sans avoir besoin d'analyser les données brutes.

L'utilisation de Chart.js garantit une expérience utilisateur moderne et fluide, tandis que l'architecture MVC assure la maintenabilité et l'évolutivité du code. Le module est prêt pour la production et peut être étendu facilement avec de nouvelles analyses.
