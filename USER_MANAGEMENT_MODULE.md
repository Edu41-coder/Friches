# Module de Gestion des Utilisateurs

## Vue d'ensemble

Le module de gestion des utilisateurs permet aux **administrateurs** de gérer les comptes utilisateurs de l'application. Ce module offre toutes les fonctionnalités CRUD (Create, Read, Update, Delete) avec une interface similaire au tableau des friches.

## Accès

- **URL** : `http://localhost/Friches/public/index.php?page=users`
- **Restriction** : Réservé aux utilisateurs avec le rôle `admin`
- **Point d'entrée** : Bouton "Gérer Utilisateurs" dans le dashboard (visible uniquement pour les admins)

## Fonctionnalités

### 1. Liste des utilisateurs (Tableau interactif)

#### Affichage des colonnes
- **ID** : Identifiant unique de l'utilisateur
- **Nom d'utilisateur** : Login de l'utilisateur (en gras)
- **Email** : Adresse email
- **Prénom** : Prénom de l'utilisateur
- **Nom** : Nom de famille
- **Rôle** : Badge coloré (Rouge pour Admin, Gris pour Utilisateur)
- **Statut** : Icône + texte (Actif/Inactif)
- **Dernière connexion** : Date et heure formatées (ou "Jamais")
- **Date de création** : Date et heure formatées
- **Actions** : Icônes Modifier et Supprimer

#### Pagination
- **Options** : 10, 25, 50, 100 utilisateurs par page
- **Par défaut** : 25 par page
- **Navigation** : Première, Précédente, Numéros de pages, Suivante, Dernière
- **Informations** : Affichage de X à Y sur Z résultats
- **Implémentation** : Côté serveur (SQL LIMIT/OFFSET)

#### Tri
- **Colonnes triables** : Toutes sauf "Actions"
- **Méthode** : Clic sur l'en-tête de colonne
- **Indicateur** : Icône flèche active
- **Directions** : ASC / DESC
- **Par défaut** : Date de création décroissant (les plus récents d'abord)

#### Recherche et filtres
- **Recherche globale** : Barre de recherche en temps réel (500ms debounce)
  - Recherche dans : username, email, first_name, last_name
  
- **Filtres avancés** (Modal) :
  - **Rôle** : Tous / Administrateur / Utilisateur
  - **Statut** : Tous / Actif / Inactif
  
- **Bouton Réinitialiser** : Efface tous les filtres et recherches

#### Actions sur les utilisateurs
- **Modifier** : Icône crayon bleu → Page d'édition
- **Supprimer** : Icône poubelle rouge → Modal de confirmation

### 2. Création d'un utilisateur

#### Accès
- Bouton vert "Créer un utilisateur" dans la liste
- URL : `index.php?page=users&action=create`

#### Formulaire
**Champs obligatoires (*)** :
- **Nom d'utilisateur*** : 3-50 caractères, alphanumérique + underscore
- **Email*** : Format email valide, max 100 caractères
- **Mot de passe*** : Minimum 6 caractères
- **Confirmer le mot de passe*** : Doit correspondre
- **Rôle*** : Utilisateur ou Administrateur

**Champs optionnels** :
- **Prénom** : Max 50 caractères
- **Nom** : Max 50 caractères
- **Compte actif** : Switch (coché par défaut)

#### Validations

**Côté client (JavaScript)** :
- Vérification que les mots de passe correspondent
- Validation HTML5 (required, minlength, maxlength, type="email")

**Côté serveur (PHP)** :
- Username requis et unique
- Email requis, valide et unique
- Mot de passe minimum 6 caractères
- Confirmation mot de passe

**En cas d'erreur** :
- Affichage d'un message d'erreur en haut du formulaire
- Les données saisies sont conservées (sauf mots de passe)

**En cas de succès** :
- Redirection vers la liste des utilisateurs
- Message de succès affiché

### 3. Modification d'un utilisateur

#### Accès
- Clic sur l'icône crayon dans le tableau
- URL : `index.php?page=users&action=edit&id=X`

#### Formulaire
Identique à la création avec ces différences :
- Les champs sont **pré-remplis** avec les données actuelles
- Le mot de passe est **optionnel** : laisser vide pour ne pas modifier
- Affichage en lecture seule de :
  - Date de création
  - Dernière connexion

#### Validations
Identiques à la création, avec :
- Vérification d'unicité username/email **en excluant l'utilisateur actuel**
- Mot de passe optionnel, mais si saisi, minimum 6 caractères + confirmation

### 4. Suppression d'un utilisateur

#### Processus
1. Clic sur l'icône poubelle rouge
2. Ouverture d'un **modal de confirmation** :
   - Titre : "Confirmer la suppression"
   - Affichage du nom d'utilisateur
   - Message d'avertissement : "Cette action est irréversible"
3. Boutons : Annuler (gris) / Supprimer (rouge)
4. Suppression via **AJAX** (pas de rechargement de page)
5. Fermeture du modal et **rechargement automatique** du tableau

#### Protections
- **Impossible de supprimer son propre compte**
- **Impossible de supprimer le dernier administrateur**
- Vérifications côté serveur (UsersController et User model)

#### Retour
- **Succès** : Message "Utilisateur supprimé avec succès" + tableau rechargé
- **Erreur** : Message d'erreur avec détails

## Architecture technique

### Fichiers créés/modifiés

#### Contrôleur
- **`app/controllers/UsersController.php`** (nouveau) :
  - `__construct()` : Vérification admin obligatoire
  - `index()` : Affiche la liste
  - `getUsersJson()` : Endpoint AJAX pour les données
  - `create()` / `handleCreate()` : Création d'utilisateur
  - `edit()` / `handleEdit()` : Modification d'utilisateur
  - `delete()` : Suppression via AJAX

#### Modèle
- **`app/models/User.php`** (modifié) :
  - `findAll($filters, $sortColumn, $sortDirection, $limit, $offset)` : Liste paginée et triée
  - `count($filters)` : Nombre total avec filtres
  - `delete($id)` : Suppression avec protection admin

#### Vues
- **`app/views/users/index.php`** (nouveau) : Liste avec tableau interactif
- **`app/views/users/create.php`** (nouveau) : Formulaire de création
- **`app/views/users/edit.php`** (nouveau) : Formulaire d'édition
- **`app/views/dashboard.php`** (modifié) : Ajout du bouton "Gérer Utilisateurs" (admin uniquement)

#### JavaScript
- **`public/js/users-table.js`** (nouveau) :
  - Gestion AJAX du tableau
  - Pagination, tri, filtres
  - Modal de confirmation de suppression
  - Double scrollbar synchronisé

#### Routeur
- **`public/index.php`** (modifié) :
  - Ajout du case `users` avec actions : index, getData, create, edit, delete

### Flux de données

#### Chargement de la liste
```
users-table.js (loadData) 
  → AJAX GET index.php?page=users&action=getData&pageNum=1&per_page=25&...
  → UsersController::getUsersJson()
  → User::findAll() + User::count()
  → SQL SELECT avec WHERE, ORDER BY, LIMIT, OFFSET
  → JSON response
  → users-table.js (renderTable + renderPagination)
```

#### Suppression
```
users-table.js (confirmDelete → performDelete)
  → AJAX POST index.php?page=users&action=delete
  → UsersController::delete()
  → User::delete() avec vérifications
  → SQL DELETE
  → JSON response
  → Fermeture modal + rechargement tableau
```

## Sécurité

### Contrôle d'accès
1. **Authentification** : Vérifiée dans `AuthController::requireAuth()`
2. **Autorisation** : `UsersController::requireAdmin()` vérifie `$_SESSION['role'] === 'admin'`
3. **Si accès refusé** : Redirection vers dashboard avec message d'erreur

### Protection des données
- **Mots de passe** : Hashés avec `password_hash()` bcrypt
- **Requêtes SQL** : Préparées avec PDO pour éviter les injections
- **Sorties HTML** : `htmlspecialchars()` pour éviter XSS
- **Validation** : Double validation (client + serveur)

### Règles métier
- Un utilisateur ne peut pas se supprimer lui-même
- Le dernier admin ne peut pas être supprimé
- Vérification d'unicité username/email

## Styles et UX

### Design
- **Couleurs** :
  - Admin badge : Rouge (#dc3545)
  - User badge : Gris (#6c757d)
  - Bouton Créer : Vert (#28a745)
  - Bouton Modifier : Bleu (#1877F2)
  - Bouton Supprimer : Rouge (#dc3545)
  
- **Icônes Bootstrap Icons** :
  - Créer : `bi-plus-circle`
  - Modifier : `bi-pencil`
  - Supprimer : `bi-trash`
  - Actif : `bi-check-circle-fill`
  - Inactif : `bi-x-circle-fill`

### Responsive
- **Tableau** : Double scrollbar horizontale synchronisée
- **Formulaires** : Grid Bootstrap (col-md-6)
- **Mobile-friendly** : Boutons adaptés aux petits écrans

### Feedback utilisateur
- **Messages de succès** : Vert avec icône check
- **Messages d'erreur** : Rouge avec détails
- **Loading** : Spinner pendant les requêtes AJAX
- **Confirmations** : Modal pour les actions destructives

## Tests recommandés

### Test du tableau
1. Se connecter en tant qu'admin
2. Accéder à "Gérer Utilisateurs"
3. Vérifier l'affichage des 2 utilisateurs de test
4. Tester la recherche par username/email
5. Tester le tri par différentes colonnes
6. Tester les filtres (rôle, statut)
7. Changer le nombre d'éléments par page

### Test de création
1. Cliquer sur "Créer un utilisateur"
2. Tester les validations (champs vides, email invalide, mots de passe différents)
3. Créer un utilisateur valide
4. Vérifier l'apparition dans la liste

### Test de modification
1. Cliquer sur l'icône crayon d'un utilisateur
2. Modifier des champs (sans toucher au mot de passe)
3. Enregistrer et vérifier les modifications
4. Re-modifier en changeant le mot de passe
5. Tester la connexion avec le nouveau mot de passe

### Test de suppression
1. Tenter de supprimer son propre compte → Erreur attendue
2. Tenter de supprimer le dernier admin → Erreur attendue
3. Créer un 2e admin, puis supprimer le 1er → Succès
4. Supprimer un utilisateur standard → Succès

### Test des restrictions
1. Se connecter en tant qu'utilisateur standard
2. Vérifier que le bouton "Gérer Utilisateurs" n'apparaît pas
3. Tenter d'accéder directement à `index.php?page=users` → Redirection + erreur

## Évolutions possibles

1. **Export** : Export CSV/Excel de la liste des utilisateurs
2. **Import** : Import en masse de comptes utilisateurs
3. **Historique** : Log des actions d'administration (audit trail)
4. **Permissions fines** : Plus de rôles (lecteur, contributeur, modérateur, admin)
5. **Email** : Envoi d'email de bienvenue / réinitialisation mot de passe
6. **Avatar** : Upload et affichage d'une photo de profil
7. **Sessions actives** : Voir et révoquer les sessions actives d'un utilisateur
8. **Désactivation temporaire** : Suspendre un compte sans le supprimer
9. **Statistiques** : Nombre de connexions, dernière activité, etc.
10. **Filtres avancés** : Date de création, dernière connexion, etc.

## Conclusion

Le module de gestion des utilisateurs offre une interface complète et sécurisée pour administrer les comptes de l'application. Il suit les mêmes patterns et conventions que le module de gestion des friches, assurant une cohérence dans l'expérience utilisateur et la maintenabilité du code.
