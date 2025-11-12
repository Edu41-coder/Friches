/**
 * Script pour la carte interactive des friches
 * Fichier : public/js/map.js
 */

const FrichesMap = {
    map: null,
    markersLayer: null,
    allFriches: [],
    clusterRadius: 80,
    
    /**
     * Initialisation de la carte
     */
    init() {
        // Charger le rayon de clustering sauvegardé
        const savedRadius = localStorage.getItem('friches_cluster_radius');
        if (savedRadius) {
            this.clusterRadius = parseInt(savedRadius);
            document.getElementById('clusterRadius').value = this.clusterRadius;
            document.getElementById('clusterRadiusValue').textContent = this.clusterRadius + 'm';
        }
        
        // Initialiser la carte Leaflet
        this.map = L.map('map').setView([46.603354, 1.888334], 6); // Centre sur la France
        
        // Ajouter le fond de carte OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            maxZoom: 19
        }).addTo(this.map);
        
        // Bind events
        this.bindEvents();
        
        // Charger les données
        this.loadFriches();
    },
    
    /**
     * Attache les event listeners
     */
    bindEvents() {
        // Slider de rayon de clustering
        document.getElementById('clusterRadius').addEventListener('input', (e) => {
            this.clusterRadius = parseInt(e.target.value);
            document.getElementById('clusterRadiusValue').textContent = this.clusterRadius + 'm';
            
            // Sauvegarder dans localStorage
            localStorage.setItem('friches_cluster_radius', this.clusterRadius);
            
            // Recharger les marqueurs avec le nouveau rayon
            this.renderMarkers();
        });
    },
    
    /**
     * Charge les données des friches depuis le serveur
     */
    async loadFriches() {
        try {
            const geojson = await ajaxFetch('/Friches/public/index.php?page=map&action=getMapData');
            
            if (!geojson) return; // Redirection en cours
            
            if (geojson.error) {
                throw new Error(geojson.error);
            }
            
            this.allFriches = geojson.features;
            
            // Afficher le nombre total
            document.getElementById('totalFrichesCount').textContent = this.allFriches.length.toLocaleString('fr-FR');
            
            // Rendre les marqueurs
            this.renderMarkers();
            
            // Centrer sur une friche spécifique si demandé
            if (specificFricheId) {
                this.focusOnFriche(specificFricheId);
            }
            
            // Masquer le loader
            document.getElementById('loadingMap').style.display = 'none';
            
        } catch (error) {
            console.error('Erreur lors du chargement des friches:', error);
            document.getElementById('loadingMap').innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    Erreur lors du chargement des données : ${error.message}
                </div>
            `;
        }
    },
    
    /**
     * Rend les marqueurs sur la carte avec clustering
     */
    renderMarkers() {
        // Supprimer l'ancienne couche si elle existe
        if (this.markersLayer) {
            this.map.removeLayer(this.markersLayer);
        }
        
        // Créer un groupe de clustering avec le rayon défini
        this.markersLayer = L.markerClusterGroup({
            maxClusterRadius: this.clusterRadius,
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false,
            zoomToBoundsOnClick: true,
            iconCreateFunction: (cluster) => {
                const count = cluster.getChildCount();
                let size = 'small';
                
                if (count > 100) size = 'large';
                else if (count > 10) size = 'medium';
                
                return L.divIcon({
                    html: '<div><span>' + count + '</span></div>',
                    className: 'marker-cluster marker-cluster-' + size,
                    iconSize: L.point(40, 40)
                });
            }
        });
        
        // Ajouter les marqueurs
        this.allFriches.forEach(feature => {
            const coords = feature.geometry.coordinates;
            const props = feature.properties;
            
            // Créer le marqueur
            const marker = L.marker([coords[1], coords[0]], {
                icon: this.createCustomIcon(),
                title: props.site_nom
            });
            
            // Créer le popup
            const popupContent = this.createPopupContent(props);
            marker.bindPopup(popupContent);
            
            // Stocker l'ID pour la recherche
            marker.fricheId = props.id;
            
            // Ajouter au cluster
            this.markersLayer.addLayer(marker);
        });
        
        // Ajouter le cluster à la carte
        this.map.addLayer(this.markersLayer);
    },
    
    /**
     * Crée une icône personnalisée pour les marqueurs
     */
    createCustomIcon() {
        return L.divIcon({
            className: 'custom-marker',
            html: `
                <div style="
                    width: 25px;
                    height: 25px;
                    background-color: #1877F2;
                    border: 3px solid white;
                    border-radius: 50%;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
                "></div>
            `,
            iconSize: [25, 25],
            iconAnchor: [12, 12],
            popupAnchor: [0, -15]
        });
    },
    
    /**
     * Crée le contenu HTML du popup
     */
    createPopupContent(props) {
        return `
            <div>
                <h6><i class="bi bi-geo-alt-fill"></i> ${this.escapeHtml(props.site_nom)}</h6>
                
                <div class="info-row">
                    <span class="info-label">Commune :</span>
                    <span class="info-value">${this.escapeHtml(props.comm_nom)}</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Type :</span>
                    <span class="info-value">${this.escapeHtml(props.site_type)}</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Statut :</span>
                    <span class="info-value">${this.escapeHtml(props.site_statut)}</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Surface :</span>
                    <span class="info-value">${props.surface} m²</span>
                </div>
                
                ${props.activite !== 'Non spécifié' ? `
                <div class="info-row">
                    <span class="info-label">Activité :</span>
                    <span class="info-value">${this.escapeHtml(props.activite)}</span>
                </div>
                ` : ''}
                
                ${props.dept_nom ? `
                <div class="info-row">
                    <span class="info-label">Département :</span>
                    <span class="info-value">${this.escapeHtml(props.dept_nom)}</span>
                </div>
                ` : ''}
                
                <hr style="margin: 0.5rem 0;">
                
                <div class="text-center mt-2">
                    <a href="index.php?page=friches&highlight=${props.id}" class="btn btn-sm btn-primary">
                        <i class="bi bi-table"></i> Voir dans le tableau
                    </a>
                </div>
            </div>
        `;
    },
    
    /**
     * Centre la carte sur une friche spécifique et ouvre son popup
     */
    focusOnFriche(fricheId) {
        // Attendre que les marqueurs soient chargés
        setTimeout(() => {
            let targetMarker = null;
            
            // Parcourir tous les marqueurs du cluster
            this.markersLayer.eachLayer((layer) => {
                if (layer.fricheId === parseInt(fricheId)) {
                    targetMarker = layer;
                    return false; // Arrêter la boucle
                }
            });
            
            if (targetMarker) {
                // Zoomer sur le marqueur
                this.map.setView(targetMarker.getLatLng(), 15);
                
                // Ouvrir le popup
                setTimeout(() => {
                    targetMarker.openPopup();
                }, 500);
            }
        }, 1000);
    },
    
    /**
     * Échappe les caractères HTML
     */
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    FrichesMap.init();
});
