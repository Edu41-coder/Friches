"""
Script pour générer des instructions SQL INSERT à partir du CSV
Ce script crée un fichier SQL que vous pouvez importer directement dans phpMyAdmin
"""

import csv
import sys

# Chemins des fichiers
CSV_FILE = '../data/friches-standard-geom.csv'
OUTPUT_SQL = 'insert_data.sql'

def escape_sql_string(value):
    """Échappe les caractères spéciaux pour SQL"""
    if value is None or value == '':
        return 'NULL'
    # Remplacer les apostrophes par double apostrophe
    value = str(value).replace("'", "''")
    # Remplacer les backslash
    value = value.replace("\\", "\\\\")
    return f"'{value}'"

def main():
    print("="*70)
    print("   GÉNÉRATION DU FICHIER SQL D'INSERTION")
    print("="*70)
    
    with open(CSV_FILE, 'r', encoding='utf-8') as csv_file:
        with open(OUTPUT_SQL, 'w', encoding='utf-8') as sql_file:
            # Écrire l'en-tête
            sql_file.write("-- Fichier d'insertion généré automatiquement\n")
            sql_file.write("-- Source : friches-standard-geom.csv\n\n")
            sql_file.write("USE friches_db;\n\n")
            sql_file.write("-- Désactiver les vérifications temporairement pour accélérer\n")
            sql_file.write("SET FOREIGN_KEY_CHECKS=0;\n")
            sql_file.write("SET UNIQUE_CHECKS=0;\n")
            sql_file.write("SET AUTOCOMMIT=0;\n\n")
            
            reader = csv.DictReader(csv_file, delimiter=';')
            
            count = 0
            batch_size = 1000
            
            sql_file.write("START TRANSACTION;\n\n")
            
            for row in reader:
                count += 1
                
                # Construire l'instruction INSERT
                sql = f"""INSERT INTO friches (
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
    {escape_sql_string(row.get('site_id'))},
    {escape_sql_string(row.get('longitude'))},
    {escape_sql_string(row.get('latitude'))},
    {escape_sql_string(row.get('site_nom'))},
    {escape_sql_string(row.get('site_type'))},
    {escape_sql_string(row.get('site_identif_date'))},
    {escape_sql_string(row.get('site_actu_date'))},
    {escape_sql_string(row.get('site_url'))},
    {escape_sql_string(row.get('site_securite'))},
    {escape_sql_string(row.get('site_occupation'))},
    {escape_sql_string(row.get('site_statut'))},
    {escape_sql_string(row.get('activite_libelle'))},
    {escape_sql_string(row.get('comm_nom'))},
    {escape_sql_string(row.get('comm_insee'))},
    {escape_sql_string(row.get('bati_type'))},
    {escape_sql_string(row.get('bati_nombre'))},
    {escape_sql_string(row.get('bati_pollution'))},
    {escape_sql_string(row.get('bati_vacance'))},
    {escape_sql_string(row.get('bati_patrimoine'))},
    {escape_sql_string(row.get('bati_etat'))},
    {escape_sql_string(row.get('local_ancien_annee'))},
    {escape_sql_string(row.get('local_recent_annee'))},
    {escape_sql_string(row.get('proprio_type'))},
    {escape_sql_string(row.get('proprio_personne'))},
    {escape_sql_string(row.get('proprio_nom'))},
    {escape_sql_string(row.get('sol_pollution_existe'))},
    {escape_sql_string(row.get('sol_pollution_origine'))},
    {escape_sql_string(row.get('unite_fonciere_surface'))},
    {escape_sql_string(row.get('unite_fonciere_refcad'))},
    {escape_sql_string(row.get('urba_zone_type'))},
    {escape_sql_string(row.get('urba_zone_lib'))},
    {escape_sql_string(row.get('urba_doc_type'))},
    {escape_sql_string(row.get('source_nom'))},
    {escape_sql_string(row.get('source_url'))},
    {escape_sql_string(row.get('source_producteur'))},
    {escape_sql_string(row.get('geompoint'))}
);\n"""
                
                sql_file.write(sql)
                
                # Commit tous les 1000 enregistrements
                if count % batch_size == 0:
                    sql_file.write("\nCOMMIT;\n")
                    sql_file.write("START TRANSACTION;\n\n")
                    print(f"✅ {count:,} lignes générées...")
            
            # Commit final
            sql_file.write("\nCOMMIT;\n\n")
            sql_file.write("-- Réactiver les vérifications\n")
            sql_file.write("SET FOREIGN_KEY_CHECKS=1;\n")
            sql_file.write("SET UNIQUE_CHECKS=1;\n")
            sql_file.write("SET AUTOCOMMIT=1;\n\n")
            sql_file.write(f"-- Total : {count:,} lignes insérées\n")
    
    print(f"\n✅ Fichier SQL généré : {OUTPUT_SQL}")
    print(f"📊 Total : {count:,} instructions INSERT")
    print(f"\n📋 Instructions :")
    print(f"   1. Ouvrez phpMyAdmin")
    print(f"   2. Sélectionnez la base 'friches_db'")
    print(f"   3. Onglet SQL")
    print(f"   4. Importez le fichier '{OUTPUT_SQL}'")
    print("="*70)

if __name__ == "__main__":
    main()
