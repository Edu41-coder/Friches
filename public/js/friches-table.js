/**
 * Script pour gérer le tableau interactif des friches
 * Fichier : public/js/friches-table.js
 */

// Configuration globale
const FrichesTable = {
    currentPage: 1,
    perPage: 25,
    sortColumn: 'id',
    sortDirection: 'ASC',
    filters: {},
    visibleColumns: ['site_nom', 'site_type', 'comm_nom', 'site_statut', 'unite_fonciere_surface'],
    
    /**
     * Initialisation du tableau
     */
    init() {
        this.loadColumnsFromStorage();
        this.bindEvents();
        this.loadData();
    },
    
    /**
     * Charge les préférences de colonnes depuis localStorage
     */
    loadColumnsFromStorage() {
        const saved = localStorage.getItem('friches_visible_columns');
        if (saved) {
            try {
                this.visibleColumns = JSON.parse(saved);
            } catch (e) {
                console.error('Erreur lors du chargement des colonnes', e);
            }
        }
        this.updateColumnCheckboxes();
    },
    
    /**
     * Sauvegarde les préférences de colonnes
     */
    saveColumnsToStorage() {
        localStorage.setItem('friches_visible_columns', JSON.stringify(this.visibleColumns));
    },
    
    /**
     * Met à jour l'état des checkboxes des colonnes
     */
    updateColumnCheckboxes() {
        document.querySelectorAll('.column-checkbox').forEach(checkbox => {
            checkbox.checked = this.visibleColumns.includes(checkbox.value);
        });
    },
    
    /**
     * Attache tous les event listeners
     */
    bindEvents() {
        // Gestion du défilement horizontal
        this.setupHorizontalScroll();
        
        // Recherche globale
        const searchInput = document.getElementById('searchInput');
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.filters.search = e.target.value;
                this.currentPage = 1;
                this.loadData();
            }, 500);
        });
        
        // Éléments par page
        document.getElementById('perPageSelect').addEventListener('change', (e) => {
            this.perPage = parseInt(e.target.value);
            this.currentPage = 1;
            this.loadData();
        });
        
        // Bouton réinitialiser
        document.getElementById('resetBtn').addEventListener('click', () => {
            this.resetAll();
        });
        
        // Appliquer les filtres
        document.getElementById('applyFiltersBtn').addEventListener('click', () => {
            this.applyFilters();
            bootstrap.Modal.getInstance(document.getElementById('filterModal')).hide();
        });
        
        // Effacer les filtres
        document.getElementById('clearFiltersBtn').addEventListener('click', () => {
            this.clearFilters();
        });
        
        // Sauvegarder les colonnes
        document.getElementById('saveColumnsBtn').addEventListener('click', () => {
            this.saveColumnSelection();
            bootstrap.Modal.getInstance(document.getElementById('columnModal')).hide();
        });
    },
    
    /**
     * Charge les données depuis le serveur
     */
    async loadData() {
        this.showLoading(true);
        
        const params = new URLSearchParams({
            pageNum: this.currentPage,
            per_page: this.perPage,
            sort_column: this.sortColumn,
            sort_direction: this.sortDirection,
            ...this.filters
        });
        
        try {
            const data = await ajaxFetch(`/Friches/public/index.php?page=friches&action=getData&${params}`);
            
            if (!data) return; // Redirection en cours
            
            if (data.success) {
                this.renderTable(data.data);
                this.renderPagination(data.pagination);
                
                // Synchroniser la largeur du scroll après le rendu
                if (this.syncScrollWidth) {
                    setTimeout(() => this.syncScrollWidth(), 100);
                }
            } else {
                this.showError(data.message || 'Erreur lors du chargement des données');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.showError('Erreur lors de la récupération des données');
        } finally {
            this.showLoading(false);
        }
    },
    
    /**
     * Affiche/masque le loader
     */
    showLoading(show) {
        const overlay = document.getElementById('loadingOverlay');
        if (show) {
            overlay.classList.add('active');
        } else {
            overlay.classList.remove('active');
        }
    },
    
    /**
     * Affiche une erreur
     */
    showError(message) {
        alert(message); // TODO: Utiliser un toast Bootstrap
    },
    
    /**
     * Génère les en-têtes du tableau
     */
    renderTableHeader() {
        const header = document.getElementById('tableHeader');
        header.innerHTML = '';
        
        this.visibleColumns.forEach((column, index) => {
            const th = document.createElement('th');
            th.innerHTML = `
                ${availableColumns[column]}
                <i class="bi bi-arrow-down-up sort-icon ${this.sortColumn === column ? 'active' : ''}"></i>
                <div class="resize-handle"></div>
            `;
            th.dataset.column = column;
            th.dataset.columnIndex = index;
            
            // Restaurer la largeur sauvegardée
            const savedWidth = localStorage.getItem(`friches_col_width_${column}`);
            if (savedWidth) {
                th.style.width = savedWidth + 'px';
                th.style.minWidth = savedWidth + 'px';
            }
            
            // Événement de tri (éviter le resize handle)
            th.addEventListener('click', (e) => {
                if (!e.target.classList.contains('resize-handle')) {
                    this.toggleSort(column);
                }
            });
            
            // Événement de redimensionnement
            const resizeHandle = th.querySelector('.resize-handle');
            this.makeResizable(th, resizeHandle, column);
            
            header.appendChild(th);
        });
        
        // Ajouter colonne Actions (pour tous les utilisateurs)
        const thActions = document.createElement('th');
        thActions.textContent = 'Actions';
        // Largeur variable selon le rôle (plus large pour admin avec 3 boutons)
        const actionsWidth = (typeof userRole !== 'undefined' && userRole === 'admin') ? '160px' : '80px';
        thActions.style.width = actionsWidth;
        thActions.style.minWidth = actionsWidth;
        thActions.style.textAlign = 'center';
        header.appendChild(thActions);
    },
    
    /**
     * Rend une colonne redimensionnable
     */
    makeResizable(th, handle, column) {
        let startX = 0;
        let startWidth = 0;
        let isDragging = false;
        
        // Double-clic pour réinitialiser la largeur
        handle.addEventListener('dblclick', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            // Supprimer la largeur sauvegardée
            localStorage.removeItem(`friches_col_width_${column}`);
            
            // Réinitialiser les styles
            th.style.width = '';
            th.style.minWidth = '100px';
            
            // Réinitialiser les cellules de cette colonne
            const columnIndex = parseInt(th.dataset.columnIndex);
            const tbody = document.getElementById('tableBody');
            const rows = tbody.querySelectorAll('tr');
            
            rows.forEach(row => {
                const cell = row.cells[columnIndex];
                if (cell) {
                    cell.style.width = '';
                    cell.style.minWidth = '';
                    cell.style.maxWidth = '';
                }
            });
            
            // Mettre à jour le scroll horizontal
            if (this.syncScrollWidth) {
                setTimeout(() => this.syncScrollWidth(), 50);
            }
        });
        
        handle.addEventListener('mousedown', (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            isDragging = true;
            startX = e.pageX;
            startWidth = th.offsetWidth;
            
            th.classList.add('resizing');
            handle.classList.add('resizing');
            
            // Désactiver la sélection de texte pendant le drag
            document.body.style.userSelect = 'none';
            document.body.style.cursor = 'col-resize';
        });
        
        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            
            const diff = e.pageX - startX;
            const newWidth = Math.max(100, startWidth + diff); // Largeur minimum de 100px
            
            th.style.width = newWidth + 'px';
            th.style.minWidth = newWidth + 'px';
            
            // Appliquer la même largeur aux cellules de cette colonne
            const columnIndex = parseInt(th.dataset.columnIndex);
            const tbody = document.getElementById('tableBody');
            const rows = tbody.querySelectorAll('tr');
            
            rows.forEach(row => {
                const cell = row.cells[columnIndex];
                if (cell) {
                    cell.style.width = newWidth + 'px';
                    cell.style.minWidth = newWidth + 'px';
                    cell.style.maxWidth = newWidth + 'px';
                }
            });
        });
        
        document.addEventListener('mouseup', () => {
            if (!isDragging) return;
            
            isDragging = false;
            th.classList.remove('resizing');
            handle.classList.remove('resizing');
            
            document.body.style.userSelect = '';
            document.body.style.cursor = '';
            
            // Sauvegarder la largeur dans localStorage
            const finalWidth = th.offsetWidth;
            localStorage.setItem(`friches_col_width_${column}`, finalWidth);
            
            // Mettre à jour le scroll horizontal
            if (this.syncScrollWidth) {
                setTimeout(() => this.syncScrollWidth(), 50);
            }
        });
    },
    
    /**
     * Rend le contenu du tableau
     */
    renderTable(data) {
        this.renderTableHeader();
        
        const tbody = document.getElementById('tableBody');
        tbody.innerHTML = '';
        
        if (data.length === 0) {
            // +1 pour la colonne Actions (toujours présente maintenant)
            const colspan = this.visibleColumns.length + 1;
            tbody.innerHTML = `
                <tr>
                    <td colspan="${colspan}" class="text-center text-muted py-4">
                        <i class="bi bi-inbox" style="font-size: 2rem;"></i><br>
                        Aucune donnée trouvée
                    </td>
                </tr>
            `;
            return;
        }
        
        data.forEach(row => {
            const tr = document.createElement('tr');
            
            this.visibleColumns.forEach((column, index) => {
                const td = document.createElement('td');
                let value = row[column];
                
                // Formatage spécial pour certaines colonnes
                if (column === 'unite_fonciere_surface' && value) {
                    value = new Intl.NumberFormat('fr-FR').format(value) + ' m²';
                } else if ((column === 'longitude' || column === 'latitude') && value) {
                    value = parseFloat(value).toFixed(6);
                } else if (value === null || value === '') {
                    value = '<span class="text-muted">-</span>';
                }
                
                td.innerHTML = value;
                
                // Appliquer la largeur sauvegardée
                const savedWidth = localStorage.getItem(`friches_col_width_${column}`);
                if (savedWidth) {
                    td.style.width = savedWidth + 'px';
                    td.style.minWidth = savedWidth + 'px';
                    td.style.maxWidth = savedWidth + 'px';
                }
                
                tr.appendChild(td);
            });
            
            // Ajouter colonne Actions (pour tous les utilisateurs)
            const tdActions = document.createElement('td');
            tdActions.style.textAlign = 'center';
            tdActions.style.whiteSpace = 'nowrap';
            
            let actionsHtml = `
                <a href="index.php?page=map&friche_id=${row.id}" 
                   class="btn btn-sm btn-outline-info me-1" 
                   title="Voir sur la carte">
                    <i class="bi bi-geo-alt-fill"></i>
                </a>
            `;
            
            // Ajouter les boutons admin seulement si l'utilisateur est admin
            if (typeof userRole !== 'undefined' && userRole === 'admin') {
                actionsHtml += `
                    <a href="index.php?page=friches&action=edit&id=${row.id}" 
                       class="btn btn-sm btn-outline-primary me-1" 
                       title="Modifier">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <button class="btn btn-sm btn-outline-danger" 
                            onclick="FrichesTable.confirmDelete(${row.id}, '${this.escapeHtml(row.site_nom || 'Friche sans nom')}', '${this.escapeHtml(row.comm_nom || '')}')" 
                            title="Supprimer">
                        <i class="bi bi-trash"></i>
                    </button>
                `;
            }
            
            tdActions.innerHTML = actionsHtml;
            tr.appendChild(tdActions);
            
            tbody.appendChild(tr);
        });
    },
    
    /**
     * Rend les contrôles de pagination
     */
    renderPagination(pagination) {
        // Info de pagination
        const info = document.getElementById('paginationInfo');
        info.textContent = `Affichage de ${pagination.from} à ${pagination.to} sur ${pagination.total_items} résultats`;
        
        // Contrôles de pagination
        const controls = document.getElementById('paginationControls');
        controls.innerHTML = '';
        
        // Bouton Première page
        this.addPaginationButton(controls, '«', 1, pagination.current_page === 1);
        
        // Bouton Page précédente
        this.addPaginationButton(controls, '‹', pagination.current_page - 1, pagination.current_page === 1);
        
        // Numéros de pages
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            this.addPaginationButton(controls, i, i, false, i === pagination.current_page);
        }
        
        // Bouton Page suivante
        this.addPaginationButton(controls, '›', pagination.current_page + 1, pagination.current_page === pagination.total_pages);
        
        // Bouton Dernière page
        this.addPaginationButton(controls, '»', pagination.total_pages, pagination.current_page === pagination.total_pages);
    },
    
    /**
     * Ajoute un bouton de pagination
     */
    addPaginationButton(container, label, page, disabled, active = false) {
        const li = document.createElement('li');
        li.className = `page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}`;
        
        const link = document.createElement('a');
        link.className = 'page-link';
        link.href = '#';
        link.textContent = label;
        
        if (!disabled) {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.currentPage = page;
                this.loadData();
            });
        }
        
        li.appendChild(link);
        container.appendChild(li);
    },
    
    /**
     * Change le tri
     */
    toggleSort(column) {
        if (this.sortColumn === column) {
            this.sortDirection = this.sortDirection === 'ASC' ? 'DESC' : 'ASC';
        } else {
            this.sortColumn = column;
            this.sortDirection = 'ASC';
        }
        
        this.currentPage = 1;
        this.loadData();
    },
    
    /**
     * Applique les filtres avancés
     */
    applyFilters() {
        this.filters = {};
        
        const filterType = document.getElementById('filterType').value;
        if (filterType) this.filters.site_type = filterType;
        
        const filterStatut = document.getElementById('filterStatut').value;
        if (filterStatut) this.filters.site_statut = filterStatut;
        
        const filterCommune = document.getElementById('filterCommune').value;
        if (filterCommune) this.filters.comm_nom = filterCommune;
        
        const filterPollution = document.getElementById('filterPollution').value;
        if (filterPollution) this.filters.sol_pollution_existe = filterPollution;
        
        const filterSurfaceMin = document.getElementById('filterSurfaceMin').value;
        if (filterSurfaceMin) this.filters.surface_min = filterSurfaceMin;
        
        const filterSurfaceMax = document.getElementById('filterSurfaceMax').value;
        if (filterSurfaceMax) this.filters.surface_max = filterSurfaceMax;
        
        const filterDateMin = document.getElementById('filterDateMin').value;
        if (filterDateMin) this.filters.date_min = filterDateMin;
        
        const filterDateMax = document.getElementById('filterDateMax').value;
        if (filterDateMax) this.filters.date_max = filterDateMax;
        
        // Garder la recherche globale
        const search = document.getElementById('searchInput').value;
        if (search) this.filters.search = search;
        
        this.currentPage = 1;
        this.showActiveFilters();
        this.loadData();
    },
    
    /**
     * Affiche les filtres actifs
     */
    showActiveFilters() {
        const container = document.getElementById('activeFilters');
        container.innerHTML = '';
        
        const filterCount = Object.keys(this.filters).filter(k => k !== 'search').length;
        
        if (filterCount > 0) {
            const badge = document.createElement('div');
            badge.className = 'alert alert-info d-inline-flex align-items-center';
            badge.innerHTML = `
                <i class="bi bi-funnel-fill me-2"></i>
                ${filterCount} filtre(s) actif(s)
                <button type="button" class="btn btn-sm btn-link ms-3" id="clearAllFilters">
                    Tout effacer
                </button>
            `;
            container.appendChild(badge);
            
            document.getElementById('clearAllFilters').addEventListener('click', () => {
                this.clearFilters();
                this.applyFilters();
            });
        }
    },
    
    /**
     * Efface tous les filtres
     */
    clearFilters() {
        document.getElementById('filterType').value = '';
        document.getElementById('filterStatut').value = '';
        document.getElementById('filterCommune').value = '';
        document.getElementById('filterPollution').value = '';
        document.getElementById('filterSurfaceMin').value = '';
        document.getElementById('filterSurfaceMax').value = '';
        document.getElementById('filterDateMin').value = '';
        document.getElementById('filterDateMax').value = '';
    },
    
    /**
     * Sauvegarde la sélection des colonnes
     */
    saveColumnSelection() {
        this.visibleColumns = [];
        document.querySelectorAll('.column-checkbox:checked').forEach(checkbox => {
            this.visibleColumns.push(checkbox.value);
        });
        
        if (this.visibleColumns.length === 0) {
            alert('Veuillez sélectionner au moins une colonne');
            return;
        }
        
        this.saveColumnsToStorage();
        this.loadData();
    },
    
    /**
     * Réinitialise tout
     */
    resetAll() {
        this.currentPage = 1;
        this.perPage = 25;
        this.sortColumn = 'id';
        this.sortDirection = 'ASC';
        this.filters = {};
        
        document.getElementById('searchInput').value = '';
        document.getElementById('perPageSelect').value = '25';
        this.clearFilters();
        document.getElementById('activeFilters').innerHTML = '';
        
        this.loadData();
    },
    
    /**
     * Configure le défilement horizontal du tableau avec double scroll
     */
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
    },
    
    /**
     * Échappe les caractères HTML pour éviter les injections XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
    
    /**
     * Affiche le modal de confirmation de suppression
     */
    confirmDelete(id, name, commune) {
        this.deleteId = id;
        
        document.getElementById('deleteFricheName').textContent = name;
        document.getElementById('deleteFricheDetails').textContent = commune ? `Commune : ${commune}` : '';
        
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
        
        // Attacher l'événement de confirmation
        document.getElementById('confirmDeleteBtn').onclick = () => {
            this.performDelete();
            deleteModal.hide();
        };
    },
    
    /**
     * Effectue la suppression via AJAX
     */
    async performDelete() {
        if (!this.deleteId) return;
        
        try {
            const result = await ajaxFetch('index.php?page=friches&action=delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `id=${this.deleteId}&csrf_token=${document.querySelector('meta[name="csrf-token"]')?.content || ''}`
            });
            
            if (!result) return; // Redirection en cours
            
            if (result.success) {
                // Recharger les données
                this.loadData();
                
                // Afficher un message de succès (TODO: utiliser toast Bootstrap)
                alert('Friche supprimée avec succès');
            } else {
                alert('Erreur : ' + (result.message || result.error || 'Impossible de supprimer la friche'));
            }
        } catch (error) {
            console.error('Erreur lors de la suppression:', error);
            alert('Erreur lors de la suppression de la friche');
        }
        
        this.deleteId = null;
    }
};

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    FrichesTable.init();
});
