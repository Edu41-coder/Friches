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
    
    /**
     * Initialisation
     */
    async init() {
        try {
            await this.loadGlobalStats();
            await this.loadAllCharts();
            
            document.getElementById('loadingStats').style.display = 'none';
            document.getElementById('globalStatsSection').style.display = 'block';
        } catch (error) {
            console.error('Erreur lors de l\'initialisation:', error);
            this.showError('Erreur lors du chargement des statistiques');
        }
    },
    
    /**
     * Charge les statistiques globales
     */
    async loadGlobalStats() {
        const data = await ajaxFetch('/Friches/public/index.php?page=analytics&action=getStats');
        
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
        await Promise.all([
            this.createTypeChart(),
            this.createStatusChart(),
            this.createCommunesChart(),
            this.createSoilPollutionChart(),
            this.createBuildingPollutionChart(),
            this.createOwnerTypesChart(),
            this.createSurfacesChart()
        ]);
    },
    
    /**
     * Récupère les données d'un graphique
     */
    async fetchChartData(type, params = {}) {
        const queryString = new URLSearchParams({type, ...params}).toString();
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
        const data = await this.fetchChartData('types');
        if (!data) return; // Redirection en cours
        const ctx = document.getElementById('chartTypes').getContext('2d');
        
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
        const data = await this.fetchChartData('statuts');
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
