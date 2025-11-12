/**
 * Gestion du tableau des utilisateurs
 * Fichier : public/js/users-table.js
 */

const UsersTable = {
    currentPage: 1,
    perPage: 25,
    sortColumn: 'created_at',
    sortDirection: 'DESC',
    filters: {},
    deleteUserId: null,
    
    /**
     * Initialisation
     */
    init() {
        this.setupEventListeners();
        this.setupHorizontalScroll();
        this.loadData();
    },
    
    /**
     * Configure les écouteurs d'événements
     */
    setupEventListeners() {
        // Recherche
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', (e) => {
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
        
        // Confirmation suppression
        document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
            this.performDelete();
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
            const data = await ajaxFetch(`/Friches/public/index.php?page=users&action=getData&${params}`);
            
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
        overlay.style.display = show ? 'block' : 'none';
    },
    
    /**
     * Affiche une erreur
     */
    showError(message) {
        alert(message);
    },
    
    /**
     * Génère les en-têtes du tableau
     */
    renderTableHeader() {
        const columns = {
            id: 'ID',
            username: 'Nom d\'utilisateur',
            email: 'Email',
            first_name: 'Prénom',
            last_name: 'Nom',
            role: 'Rôle',
            is_active: 'Statut',
            last_login_formatted: 'Dernière connexion',
            created_at_formatted: 'Date de création',
            actions: 'Actions'
        };
        
        const header = document.getElementById('tableHeader');
        header.innerHTML = '';
        
        Object.keys(columns).forEach(column => {
            const th = document.createElement('th');
            
            if (column === 'actions') {
                th.textContent = columns[column];
                th.style.width = '120px';
                th.className = 'text-center';
            } else {
                th.innerHTML = `
                    ${columns[column]}
                    <i class="bi bi-arrow-down-up sort-icon ${this.sortColumn === column ? 'active' : ''}"></i>
                `;
                th.dataset.column = column;
                th.style.cursor = 'pointer';
                th.addEventListener('click', () => this.toggleSort(column));
            }
            
            header.appendChild(th);
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
            tbody.innerHTML = `
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">
                        <i class="bi bi-inbox" style="font-size: 2rem;"></i><br>
                        Aucun utilisateur trouvé
                    </td>
                </tr>
            `;
            return;
        }
        
        data.forEach(user => {
            const tr = document.createElement('tr');
            
            // ID
            tr.innerHTML += `<td>${user.id}</td>`;
            
            // Username
            tr.innerHTML += `<td><strong>${this.escapeHtml(user.username)}</strong></td>`;
            
            // Email
            tr.innerHTML += `<td>${this.escapeHtml(user.email)}</td>`;
            
            // Prénom
            tr.innerHTML += `<td>${user.first_name || '<span class="text-muted">-</span>'}</td>`;
            
            // Nom
            tr.innerHTML += `<td>${user.last_name || '<span class="text-muted">-</span>'}</td>`;
            
            // Rôle
            const roleClass = user.role === 'admin' ? 'role-admin' : 'role-user';
            tr.innerHTML += `<td><span class="role-badge ${roleClass}">${user.role_fr}</span></td>`;
            
            // Statut
            const statusClass = user.is_active ? 'status-active' : 'status-inactive';
            const statusIcon = user.is_active ? 'check-circle-fill' : 'x-circle-fill';
            tr.innerHTML += `<td class="${statusClass}"><i class="bi bi-${statusIcon}"></i> ${user.is_active_text}</td>`;
            
            // Dernière connexion
            tr.innerHTML += `<td>${user.last_login_formatted}</td>`;
            
            // Date de création
            tr.innerHTML += `<td>${user.created_at_formatted}</td>`;
            
            // Actions
            const actionsCell = document.createElement('td');
            actionsCell.className = 'text-center';
            actionsCell.innerHTML = `
                <a href="index.php?page=users&action=edit&id=${user.id}" 
                   class="btn btn-sm btn-outline-primary me-1" 
                   title="Modifier">
                    <i class="bi bi-pencil"></i>
                </a>
                <button class="btn btn-sm btn-outline-danger" 
                        onclick="UsersTable.confirmDelete(${user.id}, '${this.escapeHtml(user.username)}')"
                        title="Supprimer">
                    <i class="bi bi-trash"></i>
                </button>
            `;
            tr.appendChild(actionsCell);
            
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
    addPaginationButton(container, label, page, disabled = false, active = false) {
        const li = document.createElement('li');
        li.className = `page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}`;
        
        const a = document.createElement('a');
        a.className = 'page-link';
        a.href = '#';
        a.textContent = label;
        
        if (!disabled && !active) {
            a.addEventListener('click', (e) => {
                e.preventDefault();
                this.currentPage = page;
                this.loadData();
            });
        }
        
        li.appendChild(a);
        container.appendChild(li);
    },
    
    /**
     * Bascule le tri
     */
    toggleSort(column) {
        if (this.sortColumn === column) {
            this.sortDirection = this.sortDirection === 'ASC' ? 'DESC' : 'ASC';
        } else {
            this.sortColumn = column;
            this.sortDirection = 'ASC';
        }
        
        this.loadData();
    },
    
    /**
     * Applique les filtres
     */
    applyFilters() {
        const role = document.getElementById('filterRole').value;
        const status = document.getElementById('filterStatus').value;
        
        this.filters = {
            ...this.filters,
            role: role,
            is_active: status
        };
        
        this.currentPage = 1;
        this.loadData();
    },
    
    /**
     * Efface les filtres
     */
    clearFilters() {
        this.filters = {};
        document.getElementById('searchInput').value = '';
        document.getElementById('filterRole').value = '';
        document.getElementById('filterStatus').value = '';
        this.currentPage = 1;
        this.loadData();
    },
    
    /**
     * Réinitialise tout
     */
    resetAll() {
        this.currentPage = 1;
        this.perPage = 25;
        this.sortColumn = 'created_at';
        this.sortDirection = 'DESC';
        this.filters = {};
        
        document.getElementById('searchInput').value = '';
        document.getElementById('perPageSelect').value = '25';
        document.getElementById('filterRole').value = '';
        document.getElementById('filterStatus').value = '';
        
        this.loadData();
    },
    
    /**
     * Affiche le modal de confirmation de suppression
     */
    confirmDelete(userId, username) {
        this.deleteUserId = userId;
        document.getElementById('deleteUserName').textContent = username;
        
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
        deleteModal.show();
    },
    
    /**
     * Effectue la suppression
     */
    async performDelete() {
        if (!this.deleteUserId) return;
        
        try {
            const formData = new FormData();
            formData.append('id', this.deleteUserId);
            
            const data = await ajaxFetch('/Friches/public/index.php?page=users&action=delete', {
                method: 'POST',
                body: formData
            });
            
            if (!data) return; // Redirection en cours
            
            if (data.success) {
                // Fermer le modal
                const deleteModal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
                deleteModal.hide();
                
                // Recharger les données
                this.loadData();
                
                // Afficher un message de succès
                alert('Utilisateur supprimé avec succès');
            } else {
                alert('Erreur : ' + (data.message || data.error || 'Impossible de supprimer l\'utilisateur'));
            }
        } catch (error) {
            console.error('Erreur:', error);
            alert('Erreur lors de la suppression de l\'utilisateur');
        }
        
        this.deleteUserId = null;
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
            const table = document.getElementById('usersTable');
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
     * Échappe les caractères HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Initialiser au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    UsersTable.init();
});
