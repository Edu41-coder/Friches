"""
Script d'import des données CSV vers MariaDB
Fichier : import_csv_to_mariadb.py
Date : 2025-11-08

Ce script charge le fichier friches-standard-geom.csv et l'importe dans MariaDB.
"""

import pandas as pd
import mysql.connector
from mysql.connector import Error
import sys
from datetime import datetime

# ============================================================================
# Configuration de la connexion à la base de données
# ============================================================================
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',  # Pas de mot de passe
    'database': 'friches_db',
    'charset': 'utf8mb4'
}

# ============================================================================
# Chemin du fichier CSV
# ============================================================================
CSV_FILE = '../data/friches-standard-geom.csv'

# ============================================================================
# Fonction de connexion à la base de données
# ============================================================================
def connect_to_database():
    """Établit une connexion à la base de données MariaDB"""
    print(f"DEBUG: Tentative de connexion avec {DB_CONFIG}")
    connection = mysql.connector.connect(**DB_CONFIG)
    print("DEBUG: Connexion créée")
    if connection.is_connected():
        db_info = connection.get_server_info()
        print(f"✅ Connecté à MariaDB Server version {db_info}")
        return connection
    else:
        print("❌ La connexion n'est pas établie")
        raise Exception("Connexion non établie")

# ============================================================================
# Fonction de nettoyage des données
# ============================================================================
def clean_data(df):
    """Nettoie les données avant l'insertion"""
    print("\n📊 Nettoyage des données...")
    
    # Remplacer les valeurs vides par None (NULL en SQL)
    df = df.replace('', None)
    df = df.replace('NA', None)
    df = df.replace('nan', None)
    
    # Conversion des dates
    date_columns = ['site_identif_date', 'site_actu_date']
    for col in date_columns:
        if col in df.columns:
            df[col] = pd.to_datetime(df[col], errors='coerce')
    
    # S'assurer que les coordonnées sont bien des floats
    if 'longitude' in df.columns:
        df['longitude'] = pd.to_numeric(df['longitude'], errors='coerce')
    if 'latitude' in df.columns:
        df['latitude'] = pd.to_numeric(df['latitude'], errors='coerce')
    
    # Colonnes numériques
    numeric_columns = ['bati_nombre', 'local_ancien_annee', 'local_recent_annee', 
                      'unite_fonciere_surface']
    for col in numeric_columns:
        if col in df.columns:
            df[col] = pd.to_numeric(df[col], errors='coerce')
    
    print(f"✅ Nettoyage terminé")
    return df

# ============================================================================
# Fonction d'insertion des données
# ============================================================================
def insert_data(connection, df):
    """Insère les données du DataFrame dans la base de données"""
    cursor = connection.cursor()
    
    # Requête d'insertion préparée
    insert_query = """
    INSERT INTO friches (
        site_id, longitude, latitude, site_nom, site_type,
        site_identif_date, site_actu_date, site_url, site_securite,
        site_occupation, site_statut, activite_libelle,
        comm_nom, comm_insee, bati_type, bati_nombre,
        bati_pollution, bati_vacance, bati_patrimoine, bati_etat,
        local_ancien_annee, local_recent_annee, proprio_type,
        proprio_personne, proprio_nom, sol_pollution_existe,
        sol_pollution_origine, unite_fonciere_surface,
        unite_fonciere_refcad, urba_zone_type, urba_zone_lib,
        urba_doc_type, source_nom, source_url, source_producteur, geompoint
    ) VALUES (
        %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,
        %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,
        %s, %s, %s, %s, %s, %s, %s, %s, %s, %s,
        %s, %s, %s, %s, %s, %s
    )
    """
    
    print(f"\n📥 Insertion de {len(df)} lignes dans la base de données...")
    
    successful_inserts = 0
    failed_inserts = 0
    
    # Insertion par lot pour de meilleures performances
    batch_size = 1000
    total_batches = (len(df) + batch_size - 1) // batch_size
    
    for batch_num in range(total_batches):
        start_idx = batch_num * batch_size
        end_idx = min((batch_num + 1) * batch_size, len(df))
        batch_df = df.iloc[start_idx:end_idx]
        
        batch_data = []
        for _, row in batch_df.iterrows():
            try:
                # Préparer les données pour l'insertion
                data = (
                    row.get('site_id'),
                    row.get('longitude'),
                    row.get('latitude'),
                    row.get('site_nom'),
                    row.get('site_type'),
                    row.get('site_identif_date'),
                    row.get('site_actu_date'),
                    row.get('site_url'),
                    row.get('site_securite'),
                    row.get('site_occupation'),
                    row.get('site_statut'),
                    row.get('activite_libelle'),
                    row.get('comm_nom'),
                    row.get('comm_insee'),
                    row.get('bati_type'),
                    row.get('bati_nombre'),
                    row.get('bati_pollution'),
                    row.get('bati_vacance'),
                    row.get('bati_patrimoine'),
                    row.get('bati_etat'),
                    row.get('local_ancien_annee'),
                    row.get('local_recent_annee'),
                    row.get('proprio_type'),
                    row.get('proprio_personne'),
                    row.get('proprio_nom'),
                    row.get('sol_pollution_existe'),
                    row.get('sol_pollution_origine'),
                    row.get('unite_fonciere_surface'),
                    row.get('unite_fonciere_refcad'),
                    row.get('urba_zone_type'),
                    row.get('urba_zone_lib'),
                    row.get('urba_doc_type'),
                    row.get('source_nom'),
                    row.get('source_url'),
                    row.get('source_producteur'),
                    row.get('geompoint')
                )
                batch_data.append(data)
            except Exception as e:
                print(f"⚠️  Erreur ligne {start_idx + len(batch_data)}: {e}")
                failed_inserts += 1
        
        # Insertion du lot
        try:
            cursor.executemany(insert_query, batch_data)
            connection.commit()
            successful_inserts += len(batch_data)
            print(f"   Lot {batch_num + 1}/{total_batches} : {len(batch_data)} lignes insérées")
        except Error as e:
            print(f"❌ Erreur lors de l'insertion du lot {batch_num + 1}: {e}")
            connection.rollback()
            failed_inserts += len(batch_data)
    
    cursor.close()
    
    print(f"\n✅ Insertion terminée :")
    print(f"   - Réussies : {successful_inserts:,}")
    print(f"   - Échecs : {failed_inserts:,}")
    
    return successful_inserts, failed_inserts

# ============================================================================
# Fonction principale
# ============================================================================
def main():
    """Fonction principale d'exécution"""
    try:
        print("="*70)
        print("   IMPORT DES DONNÉES FRICHES VERS MARIADB")
        print("="*70)
        print(f"\nDébut : {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
        
        # 1. Chargement du fichier CSV
        print(f"\n📂 Chargement du fichier CSV : {CSV_FILE}")
        df = pd.read_csv(CSV_FILE, sep=';', encoding='utf-8', low_memory=False)
        print(f"✅ Fichier chargé : {len(df):,} lignes, {len(df.columns)} colonnes")
    
        # 2. Nettoyage des données
        df = clean_data(df)
        print("DEBUG: Après clean_data")
        
        # 3. Connexion à la base de données
        print("DEBUG: Avant connect_to_database")
        connection = connect_to_database()
        print("DEBUG: Après connect_to_database")
    
        # 4. Vérification que la table existe
        cursor = connection.cursor()
        cursor.execute("SHOW TABLES LIKE 'friches'")
        result = cursor.fetchone()
        if not result:
            print("❌ La table 'friches' n'existe pas. Exécutez d'abord create_database.sql")
            connection.close()
            sys.exit(1)
    
        # 5. Vider la table si elle contient déjà des données (optionnel)
        cursor.execute("SELECT COUNT(*) FROM friches")
        count = cursor.fetchone()[0]
        if count > 0:
            print(f"\n⚠️  La table contient déjà {count:,} lignes - vidage automatique...")
            cursor.execute("TRUNCATE TABLE friches")
            connection.commit()
            print("✅ Table vidée")
        cursor.close()
    
        # 6. Insertion des données
        successful, failed = insert_data(connection, df)
        
        # 7. Vérification finale
        cursor = connection.cursor()
        cursor.execute("SELECT COUNT(*) FROM friches")
        final_count = cursor.fetchone()[0]
        print(f"\n📊 Nombre total de lignes dans la table : {final_count:,}")
    
        # Statistiques
        cursor.execute("""
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN longitude IS NOT NULL AND latitude IS NOT NULL THEN 1 END) as avec_coords,
                COUNT(CASE WHEN longitude IS NULL OR latitude IS NULL THEN 1 END) as sans_coords
            FROM friches
        """)
        stats = cursor.fetchone()
        print(f"\n📍 Statistiques géographiques :")
        print(f"   - Total : {stats[0]:,}")
        print(f"   - Avec coordonnées : {stats[1]:,} ({stats[1]/stats[0]*100:.1f}%)")
        print(f"   - Sans coordonnées : {stats[2]:,} ({stats[2]/stats[0]*100:.1f}%)")
    
        cursor.close()
        connection.close()
        
        print(f"\n✅ Import terminé avec succès !")
        print(f"Fin : {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
        print("="*70)
    except Exception as e:
        print(f"\n❌ ERREUR : {e}")
        import traceback
        traceback.print_exc()
        sys.exit(1)

# ============================================================================
# Point d'entrée du script
# ============================================================================
if __name__ == "__main__":
    main()
