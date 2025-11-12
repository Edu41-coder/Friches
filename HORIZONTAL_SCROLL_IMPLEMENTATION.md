# Implémentation du Double Scroll Horizontal

## Vue d'ensemble
Le tableau des friches dispose maintenant d'un **système de double barre de défilement horizontal** synchronisée pour faciliter la navigation lorsque de nombreuses colonnes sont sélectionnées.

## Fonctionnalités

### 1. Double Scrollbar
- **Scrollbar supérieure** : Située juste au-dessus du tableau
- **Scrollbar inférieure** : Scrollbar native du tableau
- Les deux scrollbars sont **synchronisées** : faire défiler l'une entraîne le défilement de l'autre

### 2. Synchronisation Automatique
- La largeur de la scrollbar supérieure s'adapte automatiquement à la largeur du tableau
- Mise à jour après chaque chargement de données
- Mise à jour après chaque changement de colonnes visibles
- Mise à jour après redimensionnement de la fenêtre

## Architecture Technique

### HTML Structure (`app/views/friches/index.php`)
```html
<!-- Scrollbar supérieure -->
<div id="scrollTopWrapper" class="scroll-top-wrapper">
    <div id="scrollTopInner" class="scroll-top-inner"></div>
</div>

<!-- Tableau avec scrollbar inférieure -->
<div id="tableScrollWrapper" class="table-scroll-wrapper">
    <table id="frichesTable" class="table table-hover">
        <!-- Contenu du tableau -->
    </table>
</div>
```

### CSS (`app/views/friches/index.php` - styles inline)
```css
/* Wrapper pour le scroll du haut */
.scroll-top-wrapper {
    overflow-x: auto;
    overflow-y: hidden;
    height: 16px;
    margin-bottom: 0;
}

/* Div interne pour définir la largeur scrollable */
.scroll-top-inner {
    height: 1px;
}

/* Wrapper du tableau avec scroll */
.table-scroll-wrapper {
    overflow-x: auto;
}

/* Style des scrollbars */
.scroll-top-wrapper::-webkit-scrollbar,
.table-scroll-wrapper::-webkit-scrollbar {
    height: 12px;
}

.scroll-top-wrapper::-webkit-scrollbar-track,
.table-scroll-wrapper::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.scroll-top-wrapper::-webkit-scrollbar-thumb,
.table-scroll-wrapper::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 6px;
}

.scroll-top-wrapper::-webkit-scrollbar-thumb:hover,
.table-scroll-wrapper::-webkit-scrollbar-thumb:hover {
    background: #555;
}
```

### JavaScript (`public/js/friches-table.js`)
```javascript
setupHorizontalScroll() {
    const scrollTopWrapper = document.getElementById('scrollTopWrapper');
    const scrollTopInner = document.getElementById('scrollTopInner');
    const tableScrollWrapper = document.getElementById('tableScrollWrapper');
    
    // Fonction pour synchroniser la largeur du scroll supérieur
    const syncScrollWidth = () => {
        const table = document.getElementById('frichesTable');
        if (table) {
            scrollTopInner.style.width = table.scrollWidth + 'px';
        }
    };
    
    // Synchroniser le scroll du haut avec celui du bas
    scrollTopWrapper.addEventListener('scroll', () => {
        tableScrollWrapper.scrollLeft = scrollTopWrapper.scrollLeft;
    });
    
    // Synchroniser le scroll du bas avec celui du haut
    tableScrollWrapper.addEventListener('scroll', () => {
        scrollTopWrapper.scrollLeft = tableScrollWrapper.scrollLeft;
    });
    
    // Initialiser la largeur après un court délai
    setTimeout(syncScrollWidth, 100);
    
    // Mettre à jour la largeur lors du redimensionnement
    window.addEventListener('resize', syncScrollWidth);
    
    // Exposer la fonction pour l'appeler après le chargement des données
    this.syncScrollWidth = syncScrollWidth;
}

// Appel dans loadData() après le rendu
if (this.syncScrollWidth) {
    setTimeout(() => this.syncScrollWidth(), 100);
}
```

## Points Clés d'Implémentation

### 1. Synchronisation Bidirectionnelle
- Événement `scroll` sur `scrollTopWrapper` → met à jour `tableScrollWrapper.scrollLeft`
- Événement `scroll` sur `tableScrollWrapper` → met à jour `scrollTopWrapper.scrollLeft`
- Résultat : les deux scrollbars bougent ensemble en temps réel

### 2. Adaptation Dynamique de la Largeur
- `scrollTopInner.style.width = table.scrollWidth + 'px'`
- Permet à `scrollTopWrapper` d'avoir la même zone scrollable que le tableau
- Déclenchée :
  - Au chargement initial (timeout 100ms)
  - Après chaque chargement de données (timeout 100ms)
  - Au redimensionnement de la fenêtre (event listener)

### 3. Timing avec setTimeout
Les `setTimeout(..., 100)` sont essentiels car :
- Le DOM doit être complètement rendu avant de mesurer `table.scrollWidth`
- Évite les mesures incorrectes sur un tableau partiellement rendu
- 100ms est suffisant pour la plupart des navigateurs

## Cas d'Usage

### Scénario 1 : Peu de colonnes
- Tableau s'affiche entièrement dans la largeur disponible
- Les scrollbars sont invisibles (rien à faire défiler)
- Pas d'impact sur l'UX

### Scénario 2 : Nombreuses colonnes
- Tableau déborde horizontalement
- **Scrollbar supérieure** : immédiatement visible, permet de naviguer sans descendre
- **Scrollbar inférieure** : accessible après le tableau, navigation traditionnelle
- L'utilisateur peut utiliser l'une ou l'autre selon sa position sur la page

### Scénario 3 : Changement de colonnes
1. Utilisateur ouvre le modal "Sélection des colonnes"
2. Coche/décoche des colonnes
3. Clique sur "Sauvegarder"
4. `saveColumnSelection()` → `loadData()` → `syncScrollWidth()`
5. Les scrollbars s'adaptent automatiquement à la nouvelle largeur

## Avantages de cette Approche

1. **UX Simplifiée** : Pas de boutons ou messages, juste les scrollbars natives
2. **Intuitive** : Comportement familier (comme Excel/Google Sheets)
3. **Performance** : Événements natifs du navigateur, pas de calculs lourds
4. **Responsive** : S'adapte automatiquement au redimensionnement
5. **Accessible** : Compatible clavier (Tab + flèches) et souris/trackpad
6. **Cross-browser** : Styles webkit pour Chrome/Safari/Edge, fallback pour Firefox

## Tests Recommandés

1. **Test de synchronisation** :
   - Faire défiler la scrollbar supérieure → vérifier que le tableau suit
   - Faire défiler la scrollbar inférieure → vérifier que la supérieure suit

2. **Test de largeur dynamique** :
   - Sélectionner 5 colonnes → scrollbars invisibles
   - Sélectionner 47 colonnes → scrollbars actives et proportionnelles
   - Redimensionner la fenêtre → vérifier l'adaptation

3. **Test de performance** :
   - Charger 100 lignes avec 47 colonnes
   - Vérifier que le scroll reste fluide
   - Pas de lag ni de saccades

4. **Test cross-browser** :
   - Chrome/Edge (Chromium) : styles webkit appliqués
   - Firefox : scrollbar native
   - Safari : styles webkit appliqués

## Évolutions Possibles

1. **Sticky header** : Fixer l'en-tête lors du scroll vertical
2. **Virtual scrolling** : Ne rendre que les colonnes visibles (pour > 100 colonnes)
3. **Touch gestures** : Support du swipe sur mobile/tablette
4. **Scroll to column** : Bouton pour centrer sur une colonne spécifique
5. **Keyboard shortcuts** : Home/End pour aller au début/fin

## Compatibilité

- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ✅ Opera 76+

**Note** : Les styles `::-webkit-scrollbar` ne fonctionnent pas sur Firefox, mais la fonctionnalité reste active avec la scrollbar native.

## Résolution de Problèmes

### Problème : Scrollbars non synchronisées
**Solution** : Vérifier que les IDs `scrollTopWrapper` et `tableScrollWrapper` sont corrects dans le HTML

### Problème : Scrollbar supérieure trop courte/longue
**Solution** : S'assurer que `syncScrollWidth()` est appelée après le rendu complet du tableau

### Problème : Scrollbars invisibles malgré le débordement
**Solution** : Vérifier les styles CSS `overflow-x: auto` sur les wrappers

### Problème : Lag lors du scroll
**Solution** : Réduire le nombre de lignes affichées ou implémenter la pagination côté client

## Conclusion

Cette implémentation offre une expérience utilisateur propre et intuitive pour la navigation horizontale dans un tableau avec de nombreuses colonnes, sans encombrer l'interface avec des boutons ou indicateurs supplémentaires.
