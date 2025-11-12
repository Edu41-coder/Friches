"""
Script d'import avec pymysql
"""

import pandas as pd
import pymysql
import sys
from datetime import datetime

CSV_FILE = '../data/friches-standard-geom.csv'

print("="*70)
print("IMPORT DES DONNEES FRICHES VERS MARIADB")
print("="*70)
print(f"\nDebut : {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")

# 1. Chargement
print("Chargement du fichier CSV...")
df = pd.read_csv(CSV_FILE, sep=';', encoding='utf-8', low_memory=False)
print(f"OK - {len(df):,} lignes\n")

# 2. Nettoyage
print("Nettoyage...")
import numpy as np
df = df.replace('', None)
df = df.replace({np.nan: None})
df['longitude'] = pd.to_numeric(df['longitude'], errors='coerce')
df['latitude'] = pd.to_numeric(df['latitude'], errors='coerce')
df = df.replace({np.nan: None})  # Remplacer NaN par None après conversions
print("OK\n")

# 3. Connexion
print("Connexion a MariaDB...")
connection = pymysql.connect(
    host='localhost',
    user='root',
    password='',
    database='friches_db',
    charset='utf8mb4'
)
print("OK\n")

# 4. Vidage
cursor = connection.cursor()
cursor.execute("SELECT COUNT(*) FROM friches")
count = cursor.fetchone()[0]
if count > 0:
    print(f"Vidage de {count} lignes...")
    cursor.execute("TRUNCATE TABLE friches")
    connection.commit()
    print("OK\n")

# 5. Insertion
print(f"Insertion de {len(df)} lignes...")
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
successful = 0

for batch_num in range((len(df) + batch_size - 1) // batch_size):
    start = batch_num * batch_size
    end = min((batch_num + 1) * batch_size, len(df))
    batch = df.iloc[start:end]
    
    data = []
    for _, row in batch.iterrows():
        data.append((
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
        ))
    
    cursor.executemany(insert_query, data)
    connection.commit()
    successful += len(data)
    print(f"  Lot {batch_num + 1} : {len(data)} lignes")

print(f"\nOK - {successful} lignes inserees\n")

# 6. Verification
cursor.execute("SELECT COUNT(*) FROM friches")
print(f"Total : {cursor.fetchone()[0]}")

cursor.close()
connection.close()

print(f"\nTermine !")
print("="*70)
