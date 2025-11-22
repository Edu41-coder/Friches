/**
 * Gestion des analyses et statistiques
 * Fichier : public/js/analytics.js
 */

const Analytics = {
    charts: {},
    colors: {
        primary: ['#1877F2', '#4A90E2', '#7FB3D5', '#B5D5F5', '#E0F0FF'],
        success: ['#28a745', '#48c774', '#71d98f', '#9aebaa', '#c3f7c5'],
        danger: ['#dc3545', '#e55b68', '#ee818b', '#f7a7ae', '#ffcdd1'],
        warning: ['#ffc107', '#ffcd39', '#ffd96b', '#ffe59d', '#fff1cf'],
        info: ['#17a2b8', '#3eb5c9', '#65c8da', '#8cdbeb', '#b3eefc'],
        purple: ['#9b59b6', '#a873c1', '#b58dcc', '#c2a7d7', '#cfc1e2'],
        mixed: ['#1877F2', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#9b59b6', '#fd7e14', '#6610f2']
    },
    currentFilters: {
        commune: null,
        codeInsee: null
    },
    
    /**
     * Initialisation
     */
    async init() {
        try {
            this.initFilters();
            
            // Afficher la section AVANT de charger les graphiques pour que les canvas soient visibles
            document.getElementById('loadingStats').style.display = 'none';
            document.getElementById('globalStatsSection').style.display = 'block';
            
            await this.loadGlobalStats();
            await this.loadAllCharts();
        } catch (error) {
            console.error('Erreur lors de l\'initialisation:', error);
            this.showError('Erreur lors du chargement des statistiques');
        }
    },
    
    /**
     * Initialise les filtres avec Select2
     */
    initFilters() {
        const $filterCommune = $('#filterCommune');
        const $filterCodeInsee = $('#filterCodeInsee');
        
        // Configuration Select2 commune (identique à friches/index.php)
        $filterCommune.select2({
            theme: 'bootstrap-5',
            placeholder: 'Rechercher une commune...',
            allowClear: true,
            width: '100%',
            language: {
                noResults: function() {
                    return "Aucune commune trouvée";
                },
                searching: function() {
                    return "Recherche en cours...";
                }
            },
            matcher: function(params, data) {
                // Si pas de recherche, afficher tout
                if ($.trim(params.term) === '') {
                    return data;
                }
                
                // Recherche uniquement au début du texte (insensible à la casse)
                if (data.text.toUpperCase().indexOf(params.term.toUpperCase()) === 0) {
                    return data;
                }
                
                return null;
            }
        });
        
        // Configuration Select2 code INSEE (identique à friches/index.php)
        $filterCodeInsee.select2({
            theme: 'bootstrap-5',
            placeholder: 'Rechercher un code INSEE ou département...',
            allowClear: true,
            width: '100%',
            language: {
                noResults: function() {
                    return "Aucun code trouvé";
                },
                searching: function() {
                    return "Recherche en cours...";
                }
            },
            matcher: function(params, data) {
                // Si pas de recherche, afficher tout
                if ($.trim(params.term) === '') {
                    return data;
                }
                
                // Si c'est un groupe (optgroup)
                if (data.children) {
                    // Filtrer les enfants du groupe
                    var filteredChildren = [];
                    $.each(data.children, function(idx, child) {
                        // Pour l'option "Tout le département", chercher dans l'ID (dept:75)
                        if (child.id && child.id.indexOf('dept:') === 0) {
                            var deptCode = child.id.substring(5);
                            // Chercher UNIQUEMENT au début du code département
                            if (deptCode.indexOf(params.term) === 0) {
                                filteredChildren.push(child);
                            }
                        }
                        // Pour les codes INSEE normaux, chercher UNIQUEMENT au début du code
                        else if (child.id && child.id.indexOf(params.term) === 0) {
                            filteredChildren.push(child);
                        }
                    });
                    
                    // Si des enfants correspondent, retourner le groupe avec ces enfants
                    if (filteredChildren.length > 0) {
                        var modifiedData = $.extend({}, data, true);
                        modifiedData.children = filteredChildren;
                        return modifiedData;
                    }
                    return null;
                }
                
                // Si c'est une option simple, recherche UNIQUEMENT au début de l'ID (value)
                if (data.id && data.id.indexOf(params.term) === 0) {
                    return data;
                }
                
                return null;
            }
        });
        
        // Mutual exclusivity : commune sélectionnée -> désactiver code INSEE
        $filterCommune.on('select2:select', () => {
            $filterCodeInsee.val(null).trigger('change').prop('disabled', true);
            this.currentFilters.commune = $filterCommune.val();
            this.currentFilters.codeInsee = null;
            this.updateActiveFilters();
            this.reloadAllData();
        });
        
        // Commune cleared -> réactiver code INSEE
        $filterCommune.on('select2:clear', () => {
            $filterCodeInsee.prop('disabled', false);
            this.currentFilters.commune = null;
            this.updateActiveFilters();
            this.reloadAllData();
        });
        
        // Mutual exclusivity : code INSEE sélectionné -> désactiver commune
        $filterCodeInsee.on('select2:select', () => {
            $filterCommune.val(null).trigger('change').prop('disabled', true);
            this.currentFilters.codeInsee = $filterCodeInsee.val();
            this.currentFilters.commune = null;
            this.updateActiveFilters();
            this.reloadAllData();
        });
        
        // Code INSEE cleared -> réactiver commune
        $filterCodeInsee.on('select2:clear', () => {
            $filterCommune.prop('disabled', false);
            this.currentFilters.codeInsee = null;
            this.updateActiveFilters();
            this.reloadAllData();
        });
        
        // Bouton reset
        document.getElementById('resetAnalyticsFilters').addEventListener('click', () => {
            this.resetFilters();
        });
    },
    
    /**
     * Met à jour l'affichage des filtres actifs
     */
    updateActiveFilters() {
        const $activeFilters = $('#activeFiltersAnalytics');
        
        if (!this.currentFilters.commune && !this.currentFilters.codeInsee) {
            $activeFilters.html('<em class="text-muted">Aucun filtre actif</em>');
            return;
        }
        
        let html = '<strong>Filtres actifs :</strong> ';
        const badges = [];
        
        if (this.currentFilters.commune) {
            const communeText = $('#filterCommune option:selected').text();
            badges.push(`<span class="badge bg-primary me-2">Commune: ${communeText}</span>`);
        }
        
        if (this.currentFilters.codeInsee) {
            const inseeText = $('#filterCodeInsee option:selected').text();
            badges.push(`<span class="badge bg-info me-2">Code INSEE: ${inseeText}</span>`);
        }
        
        html += badges.join('');
        $activeFilters.html(html);
    },
    
    /**
     * Réinitialise les filtres
     */
    resetFilters() {
        $('#filterCommune').val(null).trigger('change').prop('disabled', false);
        $('#filterCodeInsee').val(null).trigger('change').prop('disabled', false);
        this.currentFilters.commune = null;
        this.currentFilters.codeInsee = null;
        this.updateActiveFilters();
        this.reloadAllData();
    },
    
    /**
     * Recharge toutes les données (stats + graphiques)
     */
    async reloadAllData() {
        try {
            await this.loadGlobalStats();
            await this.reloadAllCharts();
        } catch (error) {
            console.error('Erreur lors du rechargement des données:', error);
            this.showError('Erreur lors du rechargement des statistiques');
        }
    },
    
    /**
     * Recharge tous les graphiques
     */
    async reloadAllCharts() {
        // Détruire les graphiques existants de manière sécurisée
        for (const key in this.charts) {
            if (this.charts[key] && typeof this.charts[key].destroy === 'function') {
                try {
                    this.charts[key].destroy();
                } catch (e) {
                    console.warn(`Erreur lors de la destruction du graphique ${key}:`, e);
                }
            }
        }
        this.charts = {};
        
        // Recharger tous les graphiques avec filtres - chaque erreur est gérée individuellement
        const chartPromises = [
            this.createTypeChart().catch(e => console.error('Erreur Types:', e)),
            this.createStatusChart().catch(e => console.error('Erreur Statuts:', e)),
            this.createCommunesChart().catch(e => console.error('Erreur Communes:', e)),
            this.createSoilPollutionChart().catch(e => console.error('Erreur Pollution Sol:', e)),
            this.createBuildingPollutionChart().catch(e => console.error('Erreur Pollution Bâtiments:', e)),
            this.createOwnerTypesChart().catch(e => console.error('Erreur Propriétaires:', e)),
            this.createSurfacesChart().catch(e => console.error('Erreur Surfaces:', e))
        ];
        
        await Promise.all(chartPromises);
    },
    
    /**
     * Charge les statistiques globales
     */
    async loadGlobalStats() {
        const params = new URLSearchParams();
        if (this.currentFilters.commune) {
            params.append('comm_nom', this.currentFilters.commune);
        }
        if (this.currentFilters.codeInsee) {
            params.append('comm_insee', this.currentFilters.codeInsee);
        }
        
        const queryString = params.toString();
        const url = `/Friches/public/index.php?page=analytics&action=getStats${queryString ? '&' + queryString : ''}`;
        const data = await ajaxFetch(url);
        
        if (!data) return; // Redirection en cours
        
        if (data.success) {
            const stats = data.data;
            
            // Formater les nombres avec séparateurs de milliers
            document.getElementById('statTotalFriches').textContent = 
                new Intl.NumberFormat('fr-FR').format(stats.total_friches);
            document.getElementById('statTotalCommunes').textContent = 
                new Intl.NumberFormat('fr-FR').format(stats.total_communes);
            document.getElementById('statSurfaceTotale').textContent = 
                new Intl.NumberFormat('fr-FR').format(stats.surface_totale);
            document.getElementById('statSurfaceMoyenne').textContent = 
                new Intl.NumberFormat('fr-FR').format(stats.surface_moyenne);
            document.getElementById('statTotalTypes').textContent = 
                new Intl.NumberFormat('fr-FR').format(stats.total_types);
            document.getElementById('statTotalStatuts').textContent = 
                new Intl.NumberFormat('fr-FR').format(stats.total_statuts);
            document.getElementById('statCommuneMax').textContent = stats.commune_max;
            document.getElementById('statCommuneMaxCount').textContent = 
                stats.commune_max_count + ' friches';
        } else {
            throw new Error(data.error || 'Erreur inconnue');
        }
    },
    
    /**
     * Charge tous les graphiques
     */
    async loadAllCharts() {
        // Détruire les graphiques existants avant de les créer
        for (const key in this.charts) {
            if (this.charts[key] && typeof this.charts[key].destroy === 'function') {
                try {
                    this.charts[key].destroy();
                } catch (e) {
                    console.warn(`Erreur lors de la destruction du graphique ${key}:`, e);
                }
            }
        }
        this.charts = {};
        
        // Charger tous les graphiques - chaque erreur est gérée individuellement
        const chartPromises = [
            this.createTypeChart().catch(e => console.error('Erreur Types:', e)),
            this.createStatusChart().catch(e => console.error('Erreur Statuts:', e)),
            this.createCommunesChart().catch(e => console.error('Erreur Communes:', e)),
            this.createSoilPollutionChart().catch(e => console.error('Erreur Pollution Sol:', e)),
            this.createBuildingPollutionChart().catch(e => console.error('Erreur Pollution Bâtiments:', e)),
            this.createOwnerTypesChart().catch(e => console.error('Erreur Propriétaires:', e)),
            this.createSurfacesChart().catch(e => console.error('Erreur Surfaces:', e))
        ];
        
        await Promise.all(chartPromises);
    },
    
    /**
     * Récupère les données d'un graphique
     */
    async fetchChartData(type, params = {}, applyFilters = true) {
        const allParams = {type, ...params};
        
        // Ajouter les filtres seulement si applyFilters est true (pas pour types et statuts)
        if (applyFilters) {
            if (this.currentFilters.commune) {
                allParams.comm_nom = this.currentFilters.commune;
            }
            if (this.currentFilters.codeInsee) {
                allParams.comm_insee = this.currentFilters.codeInsee;
            }
        }
        
        const queryString = new URLSearchParams(allParams).toString();
        const data = await ajaxFetch(`/Friches/public/index.php?page=analytics&action=getChartData&${queryString}`);
        
        if (!data) return null; // Redirection en cours
        
        if (data.success) {
            return data.data;
        } else {
            throw new Error(data.message || data.error || 'Erreur inconnue');
        }
    },
    
    /**
     * Crée le graphique des types de friches (barres horizontales)
     */
    async createTypeChart() {
        const data = await this.fetchChartData('types', {}, true); // Appliquer les filtres
        if (!data) return; // Redirection en cours
        
        const canvasElement = document.getElementById('chartTypes');
        if (!canvasElement) {
            console.error('Canvas chartTypes introuvable');
            return;
        }
        
        const ctx = canvasElement.getContext('2d');
        
        this.charts.types = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(item => item.label || 'Non renseigné'),
                datasets: [{
                    label: 'Nombre de friches',
                    data: data.map(item => item.count),
                    backgroundColor: this.colors.primary,
                    borderColor: '#1877F2',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });
    },
    
    /**
     * Crée le graphique des statuts (camembert)
     */
    async createStatusChart() {
        const data = await this.fetchChartData('statuts', {}, true); // Appliquer les filtres
        if (!data) return; // Redirection en cours
        const ctx = document.getElementById('chartStatuts').getContext('2d');
        
        this.charts.statuts = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: data.map(item => item.label || 'Non renseigné'),
                datasets: [{
                    data: data.map(item => item.count),
                    backgroundColor: this.colors.mixed,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    },
    
    /**
     * Crée le graphique des top communes (barres)
     */
    async createCommunesChart() {
        const data = await this.fetchChartData('communes', {limit: 10});
        if (!data) return; // Redirection en cours
        const ctx = document.getElementById('chartCommunes').getContext('2d');
        
        this.charts.communes = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(item => item.label),
                datasets: [{
                    label: 'Nombre de friches',
                    data: data.map(item => item.count),
                    backgroundColor: this.colors.success,
                    borderColor: '#28a745',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    },
    
    /**
     * Crée le graphique de pollution du sol (donut)
     */
    async createSoilPollutionChart() {
        const data = await this.fetchChartData('soil_pollution');
        if (!data) return; // Redirection en cours
        const ctx = document.getElementById('chartSoilPollution').getContext('2d');
        
        this.charts.soilPollution = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(item => item.label),
                datasets: [{
                    data: data.map(item => item.count),
                    backgroundColor: [this.colors.danger[0], this.colors.success[0], this.colors.warning[0]],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    },
    
    /**
     * Crée le graphique de pollution des bâtiments (donut)
     */
    async createBuildingPollutionChart() {
        const data = await this.fetchChartData('building_pollution');
        if (!data) return; // Redirection en cours
        const ctx = document.getElementById('chartBuildingPollution').getContext('2d');
        
        this.charts.buildingPollution = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(item => item.label || 'Non renseigné'),
                datasets: [{
                    data: data.map(item => item.count),
                    backgroundColor: this.colors.info,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    },
    
    /**
     * Crée le graphique des types de propriétaires (camembert)
     */
    async createOwnerTypesChart() {
        const data = await this.fetchChartData('owner_types');
        if (!data) return; // Redirection en cours
        const ctx = document.getElementById('chartOwnerTypes').getContext('2d');
        
        this.charts.ownerTypes = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: data.map(item => item.label || 'Non renseigné'),
                datasets: [{
                    data: data.map(item => item.count),
                    backgroundColor: this.colors.purple,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    },
    
    /**
     * Crée le graphique de distribution des surfaces (barres)
     */
    async createSurfacesChart() {
        const data = await this.fetchChartData('surfaces');
        if (!data) return; // Redirection en cours
        const ctx = document.getElementById('chartSurfaces').getContext('2d');
        
        this.charts.surfaces = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(item => item.label + ' m²'),
                datasets: [{
                    label: 'Nombre de friches',
                    data: data.map(item => item.count),
                    backgroundColor: this.colors.warning,
                    borderColor: '#ffc107',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    },
    
    /**
     * Affiche une erreur
     */
    showError(message) {
        document.getElementById('loadingStats').innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i> ${message}
            </div>
        `;
    }
};

// Initialiser au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    Analytics.init();
});
