#!/usr/bin/env python3
import os
import re
import io
import glob
import json
import yaml
import shutil
import zipfile
import mysql.connector
from pathlib import Path
import pwd
import grp

# === Refuse l'exécution en root ===
if os.geteuid() == 0:
    print("❌ Ce script ne doit pas être exécuté avec sudo/root.")
    exit(1)

# === Constantes ===
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
CONFIG_PATH = os.path.join(SCRIPT_DIR, "..", "install_config.json")
LANG_FILES_DIR = os.path.join(SCRIPT_DIR, "lang")

# === Chargement config globale ===
with open(CONFIG_PATH, encoding="utf-8") as f:
    config = json.load(f)

lang = config.get("lang", "fr").lower()
db_user = config.get("db_user")
db_pass = config.get("db_pass")
db_name = "beammp_db"

# === Chargement fichier de langue ===
LANG_PATH = os.path.join(LANG_FILES_DIR, f"{lang}.json")
with open(LANG_PATH, encoding="utf-8") as f:
    MSG = json.load(f)

def msg(key, **kwargs):
    return MSG.get(key, f"[TRAD MANQUANTE: {key}]").format(**kwargs)

def demander_nom():
    while True:
        nom = input(msg("ask_nom"))
        if not nom:
            print(msg("warn_nom_requis"))
        elif not re.match(r"^[a-zA-Z0-9_. ]+$", nom):
            print(msg("warn_nom_invalid"))
        else:
            return nom

def detect_id_map(zip_path):
    try:
        with zipfile.ZipFile(zip_path, 'r') as zf:
            for entry in zf.namelist():
                if entry.startswith("levels/") and entry.count("/") >= 2:
                    return entry.split("/")[1]
    except:
        return None

def extract_map_description(zip_path, id_map):
    try:
        path = f"levels/{id_map}/info.json"
        with zipfile.ZipFile(zip_path, 'r') as zf:
            with zf.open(path) as info_file:
                info_data = json.load(info_file)
                return info_data.get("description", "")
    except:
        return ""

def get_map_active(root_beammp, id_map):
    try:
        with open(os.path.join(root_beammp, "bin/ServerConfig.json"), encoding="utf-8") as f:
            content = f.read()
            return f"/levels/{id_map}/info.json" in content
    except:
        return False

def sanitize_filename(n):
    return re.sub(r"[^a-zA-Z0-9\-_]", "_", n)

# UID/GID www-data
uid_www = pwd.getpwnam("www-data").pw_uid
gid_www = grp.getgrnam("www-data").gr_gid

print(msg("start_scan"))

conn = mysql.connector.connect(
    host="localhost",
    user=db_user,
    password=db_pass,
    database=db_name
)
cursor = conn.cursor()

for instance_conf in config["instances"]:
    name = instance_conf["name"]
    table_name = f"beammp_{name}"
    root = instance_conf["root_beammp"]
    chemin_desc = f"/var/www/beammpweb-{name}/DATA/descriptions/"
    images_attendues = []
    to_review = []
    log = []

    folders = [
        os.path.join(root, "bin/Resources/Client"),
        os.path.join(root, "bin/Resources/inactive_mod"),
        os.path.join(root, "bin/Resources/inactive_map")
    ]
    all_zips = []
    for folder in folders:
        all_zips.extend([os.path.join(folder, f) for f in os.listdir(folder) if f.endswith(".zip")])

    for zip_path in all_zips:
        filename = os.path.basename(zip_path)
        cursor.execute(f"SELECT COUNT(*) FROM `{table_name}` WHERE archive=%s", (filename,))
        if cursor.fetchone()[0] > 0:
            continue  # déjà en base

        print(msg("detecting"), filename)
        name_input = ""
        typeval = ""
        id_map = detect_id_map(zip_path)

        if id_map:
            typeval = "map"
            description = extract_map_description(zip_path, id_map)
            map_active = get_map_active(root, id_map)
            name_input = demander_nom()
            mod_actif = None
        else:
            print(msg("ask_type"))
            rep = input("> ").strip()
            if rep == "1":
                typeval = "vehicule"
            elif rep == "2":
                typeval = "mod"
            else:
                to_review.append(filename)
                continue

            name_input = demander_nom()
            map_active = None
            id_map = None
            description = input(msg("ask_desc"))
            mod_actif = input(msg("ask_actif"))

        name_sanitized = sanitize_filename(name_input)
        zip_new_name = f"{name_sanitized}.zip"
        image_path = f"images/{name_sanitized}.jpg"
        desc_json_path = os.path.join(chemin_desc, f"{name_sanitized}.json")

        try:
            os.makedirs(chemin_desc, exist_ok=True)
            desc_data = {l: "" for l in ["fr", "en", "de"]}
            desc_data[lang] = description
            with open(desc_json_path, "w", encoding="utf-8") as df:
                json.dump(desc_data, df, indent=2, ensure_ascii=False)
            os.chown(desc_json_path, uid_www, gid_www)
            log.append(msg("ok_desc", path=desc_json_path))
        except Exception as e:
            log.append(msg("error_desc", error=str(e)))
            continue

        # déplacer le zip s'il faut
        target_folder = folders[0]  # par défaut client
        if typeval == "map":
            target_folder = folders[0] if map_active else folders[2]
        elif typeval in ("mod", "vehicule"):
            target_folder = folders[0] if mod_actif == "1" else folders[1]

        dest_zip = os.path.join(target_folder, zip_new_name)
        try:
            if zip_path != dest_zip:
                shutil.move(zip_path, dest_zip)
        except Exception as e:
            log.append(msg("error_move", error=str(e)))
            continue

        try:
            sql = f"""
            INSERT INTO `{table_name}` (nom,description,type,chemin,image,id_map,map_active,map_officielle,mod_actif,link,archive)
            VALUES (%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s)
            """
            values = [
                name_input,
                f"/descriptions/{name_sanitized}.json",
                typeval,
                zip_new_name,
                image_path,
                id_map,
                1 if map_active else 0 if map_active is not None else None,
                0,
                int(mod_actif) if mod_actif else None,
                "",
                filename
            ]
            cursor.execute(sql, values)
            conn.commit()
            log.append(msg("ok_import", name=name_input))
            images_attendues.append(f"{name_sanitized}.jpg")
        except Exception as e:
            log.append(msg("error_sql", error=str(e)))

    print("\n".join(log))
    Path(f"import_result_{name}.txt").write_text("\n".join(log), encoding="utf-8")
    if to_review:
        yaml.dump({"review": to_review}, open(f"to_review_{name}.yaml", "w"))
    if images_attendues:
        print(msg("missing_images"))
        for img in images_attendues:
            print(f" - {img}")

print(msg("all_done"))
