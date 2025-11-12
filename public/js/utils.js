/**
 * Utilitaires pour les requêtes AJAX
 * Fichier : public/js/utils.js
 */

/**
 * Effectue une requête fetch avec les en-têtes nécessaires
 * Gère automatiquement l'expiration de session et les erreurs HTTP
 * @param {string} url - URL de la requête
 * @param {object} options - Options pour fetch
 * @returns {Promise<object>} - Réponse JSON (ou null si redirection)
 */
async function ajaxFetch(url, options = {}) {
    // Ajouter les en-têtes par défaut
    const defaultHeaders = {
        'X-Requested-With': 'XMLHttpRequest'
    };
    
    // Fusionner les en-têtes
    options.headers = {
        ...defaultHeaders,
        ...(options.headers || {})
    };
    
    try {
        const response = await fetch(url, options);
        
        // Gérer les erreurs HTTP (500, 404, etc.)
        if (!response.ok) {
            throw new Error(`Erreur HTTP ${response.status}: ${response.statusText}`);
        }
        
        // Vérifier si la réponse est du JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Réponse invalide du serveur (pas de JSON)');
        }
        
        const data = await response.json();
        
        // Gérer la redirection d'authentification (session expirée)
        if (!data.success && data.redirect) {
            console.warn('Session expirée, redirection vers login...');
            window.location.href = data.redirect;
            return null;
        }
        
        return data;
    } catch (error) {
        console.error('Erreur AJAX:', error);
        throw error;
    }
}
