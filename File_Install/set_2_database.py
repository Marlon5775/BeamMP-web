#!/usr/bin/env python3

import os
import sys
import json
import pymysql

# --- Gestion des chemins relatifs ---
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PARENT_DIR = os.path.abspath(os.path.join(SCRIPT_DIR, ".."))

CONFIG_PATH = os.path.join(PARENT_DIR, "install_config.json")
SQL_PATH = os.path.join(PARENT_DIR, "sql")
LANG_DIR = os.path.join(SCRIPT_DIR, "lang")
DB_NAME = "beammp_db"

def log(msg):
    print(msg)

def t(key, **kwargs):
    text = LANG.get(key, f"[TRAD:{key}]")
    return text.format(**kwargs)

def read_config():
    if not os.path.exists(CONFIG_PATH):
        log(t("ERROR_CONFIG_MISSING", path=CONFIG_PATH))
        sys.exit(1)
    with open(CONFIG_PATH, "r", encoding="utf-8") as f:
        return json.load(f)

def read_lang(config):
    lang_code = config.get("lang", "fr")
    lang_path = os.path.join(LANG_DIR, f"{lang_code}.json")
    if not os.path.exists(lang_path):
        print(f"[FATAL] Fichier de langue manquant: {lang_path}")
        sys.exit(1)
    with open(lang_path, "r", encoding="utf-8") as f:
        return json.load(f)

def read_sql_file(path):
    with open(path, "r", encoding="utf-8") as f:
        return f.read()

def main():
    global LANG
    config = read_config()
    LANG = read_lang(config)
    db_user = config["db_user"]
    db_pass = config["db_pass"]
    instances = config["instances"]

    try:
        log(t("INFO_DB_CONNECT", user=db_user))
        conn = pymysql.connect(
            host="localhost",
            user=db_user,
            password=db_pass,
            charset="utf8mb4",
            autocommit=True
        )
        cursor = conn.cursor()
    except Exception as e:
        log(t("ERROR_DB_CONN", err=e))
        sys.exit(2)

    # Création base + users
    try:
        sql_init = read_sql_file(os.path.join(SQL_PATH, "beammp_db.sql"))
        for statement in sql_init.split(';'):
            s = statement.strip()
            if s:
                cursor.execute(s)
        cursor.execute(f"USE {DB_NAME};")
        log(t("OK_DB_INIT"))
    except Exception as e:
        log(t("ERROR_DB_INIT", err=e))
        sys.exit(3)

    # Création des tables par instance
    for inst in instances:
        try:
            name = inst["name"]
            log(t("INFO_INSTANCE", name=name))

            sql_beammp = read_sql_file(os.path.join(SQL_PATH, "beammp.sql"))
            table_beammp = f"beammp_{name}"
            cursor.execute(f"CREATE TABLE IF NOT EXISTS `{table_beammp}` (\n{sql_beammp}\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;")

            sql_users = read_sql_file(os.path.join(SQL_PATH, "beammp_users.sql"))
            table_users = f"beammp_users_{name}"
            cursor.execute(f"CREATE TABLE IF NOT EXISTS `{table_users}` (\n{sql_users}\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;")

            data_json_path = os.path.join(SQL_PATH, "data_sql.json")
            if os.path.exists(data_json_path):
                with open(data_json_path, "r", encoding="utf-8") as f:
                    data = json.load(f)
                for row in data:
                    fields = ",".join(row.keys())
                    placeholders = ",".join(["%s"] * len(row))
                    values = tuple(row.values())
                    sql = f"INSERT IGNORE INTO `{table_beammp}` ({fields}) VALUES ({placeholders});"
                    cursor.execute(sql, values)

            log(t("OK_INSTANCE", name=name))
        except Exception as e:
            log(t("ERROR_INSTANCE", name=name, err=e))
            sys.exit(4)

    cursor.close()
    conn.close()
    log(t("OK_ALL_DONE"))

if __name__ == "__main__":
    main()
