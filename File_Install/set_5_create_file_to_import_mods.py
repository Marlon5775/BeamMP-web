#!/usr/bin/env python3

import os
import sys
import json
import yaml
import mysql.connector

# === Chemins ===
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PARENT_DIR = os.path.abspath(os.path.join(SCRIPT_DIR, ".."))
CONFIG_PATH = os.path.join(PARENT_DIR, "install_config.json")
LANG_DIR = os.path.join(SCRIPT_DIR, "lang")

# === Chargement configuration globale ===
if not os.path.exists(CONFIG_PATH):
    print("[ERREUR] Fichier install_config.json introuvable")
    sys.exit(1)

with open(CONFIG_PATH, encoding="utf-8") as f:
    config = json.load(f)

lang_code = config.get("lang", "en")
lang_file = os.path.join(LANG_DIR, f"{lang_code}.json")

if not os.path.exists(lang_file):
    print(f"[ERREUR] Fichier de langue manquant : {lang_file}")
    sys.exit(2)

with open(lang_file, encoding="utf-8") as f:
    MSG = json.load(f)

def msg(key, **kwargs):
    return MSG.get(key, f"[TRAD:{key}]").format(**kwargs)

print(msg("start_scan"))

db_user = config["db_user"]
db_pass = config["db_pass"]

for instance in config.get("instances", []):
    name = instance["name"]
    root = instance["root_beammp"]
    db_name = "beammp_db"
    table = f"beammp_{name}"

    # ⚠️ Respect strict de la casse des dossiers
    path_active = os.path.join(root, "bin", "Resources", "Client")
    path_inactive_mod = os.path.join(root, "bin", "Resources", "inactive_mod")
    path_inactive_map = os.path.join(root, "bin", "Resources", "inactive_map")
    fichiers = []

    for chemin in [path_active, path_inactive_mod, path_inactive_map]:
        if not os.path.isdir(chemin):
            print(msg("skip_missing_dir", dir=chemin))
            continue
        for fichier in os.listdir(chemin):
            if fichier.endswith(".zip"):
                fichiers.append((fichier, chemin))

    try:
        conn = mysql.connector.connect(
            host="localhost",
            user=db_user,
            password=db_pass,
            database=db_name
        )
        cursor = conn.cursor()
    except Exception as e:
        print(msg("db_error", name=name, error=str(e)))
        continue

    fichiers_db = set()
    try:
        cursor.execute(f"SELECT chemin FROM " + table)
        for (row,) in cursor.fetchall():
            fichiers_db.add(row)
    except Exception as e:
        print(msg("db_error", name=name, error=f"Requête SQL échouée : {e}"))
        cursor.close()
        conn.close()
        continue

    orphelins = []
    for nom, chemin in fichiers:
        if nom not in fichiers_db:
            mod_actif = 1 if "Client" in chemin else 0
            orphelins.append({
                "nom": os.path.splitext(nom)[0],
                "description": "",
                "type": "mod",
                "chemin": nom,
                "image": "",
                "id_map": "",
                "map_active": "",
                "mod_actif": mod_actif,
                "link": ""
            })

    if orphelins:
        yaml_path = os.path.join(PARENT_DIR, f"to_import_{name}.yaml")
        with open(yaml_path, "w", encoding="utf-8") as out:
            out.write("# NOTICE:\n")
            out.write("# - Remplir les descriptions et champs requis.\n")
            out.write("# - type: map / mod / vehicule\n")
            out.write("# - nom: lettres, chiffres, -, _ uniquement.\n")
            yaml.dump({"imports": orphelins}, out, allow_unicode=True, default_flow_style=False)
        print(msg("yaml_created", name=name, count=len(orphelins)))
    else:
        print(msg("no_orphan_found", name=name))

    cursor.close()
    conn.close()

print(msg("end_scan"))
