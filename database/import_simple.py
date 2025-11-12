"""
Script d'import simplifié des données CSV vers MariaDB
"""

import pandas as pd
import mysql.connector
from mysql.connector import Error
import sys
from datetime import datetime

# Configuration
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'friches_db',
    'charset': 'utf8mb4'
}

CSV_FILE = '../data/friches-standard-geom.csv'

print("="*70)
print("IMPORT DES DONNEES FRICHES VERS MARIADB")
print("="*70)
print(f"\nDebut : {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")

# 1. Chargement du CSV
print("Chargement du fichier CSV...")
df = pd.read_csv(CSV_FILE, sep=';', encoding='utf-8', low_memory=False)
print(f"OK - {len(df):,} lignes, {len(df.columns)} colonnes\n")

# 2. Nettoyage
print("Nettoyage des donnees...")
df = df.replace('', None)
df = df.replace('NA', None)
df['site_identif_date'] = pd.to_datetime(df['site_identif_date'], errors='coerce')
df['site_actu_date'] = pd.to_datetime(df['site_actu_date'], errors='coerce')
df['longitude'] = pd.to_numeric(df['longitude'], errors='coerce')
df['latitude'] = pd.to_numeric(df['latitude'], errors='coerce')
print("OK\n")

# 3. Connexion
print("Connexion a MariaDB...")
try:
    connection = mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='friches_db'
    )
    print(f"OK - Version {connection.get_server_info()}\n")
except Exception as e:
    print(f"ERREUR: {e}")
    import traceback
    traceback.print_exc()
    sys.exit(1)

# 4. Vidage de la table
cursor = connection.cursor()
cursor.execute("SELECT COUNT(*) FROM friches")
count = cursor.fetchone()[0]
if count > 0:
    print(f"Vidage de {count:,} lignes existantes...")
    cursor.execute("TRUNCATE TABLE friches")
    connection.commit()
    print("OK\n")

# 5. Insertion
print(f"Insertion de {len(df):,} lignes...")
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

batch_size = 1000
total_batches = (len(df) + batch_size - 1) // batch_size
successful = 0

for batch_num in range(total_batches):
    start_idx = batch_num * batch_size
    end_idx = min((batch_num + 1) * batch_size, len(df))
    batch_df = df.iloc[start_idx:end_idx]
    
    batch_data = []
    for _, row in batch_df.iterrows():
        data = (
            row.get('site_id'), row.get('longitude'), row.get('latitude'),
            row.get('site_nom'), row.get('site_type'), row.get('site_identif_date'),
            row.get('site_actu_date'), row.get('site_url'), row.get('site_securite'),
            row.get('site_occupation'), row.get('site_statut'), row.get('activite_libelle'),
            row.get('comm_nom'), row.get('comm_insee'), row.get('bati_type'),
            row.get('bati_nombre'), row.get('bati_pollution'), row.get('bati_vacance'),
            row.get('bati_patrimoine'), row.get('bati_etat'), row.get('local_ancien_annee'),
            row.get('local_recent_annee'), row.get('proprio_type'), row.get('proprio_personne'),
            row.get('proprio_nom'), row.get('sol_pollution_existe'), row.get('sol_pollution_origine'),
            row.get('unite_fonciere_surface'), row.get('unite_fonciere_refcad'),
            row.get('urba_zone_type'), row.get('urba_zone_lib'), row.get('urba_doc_type'),
            row.get('source_nom'), row.get('source_url'), row.get('source_producteur'),
            row.get('geompoint')
        )
        batch_data.append(data)
    
    cursor.executemany(insert_query, batch_data)
    connection.commit()
    successful += len(batch_data)
    print(f"  Lot {batch_num + 1}/{total_batches} : {len(batch_data)} lignes inserees")

print(f"\nOK - {successful:,} lignes inserees\n")

# 6. Vérification
cursor.execute("SELECT COUNT(*) FROM friches")
final_count = cursor.fetchone()[0]
print(f"Total dans la table : {final_count:,}")

cursor.execute("""
    SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN longitude IS NOT NULL AND latitude IS NOT NULL THEN 1 END) as avec_coords
    FROM friches
""")
stats = cursor.fetchone()
print(f"Avec coordonnees : {stats[1]:,} ({stats[1]/stats[0]*100:.1f}%)")

cursor.close()
connection.close()

print(f"\nImport termine avec succes !")
print(f"Fin : {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
print("="*70)
