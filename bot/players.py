#!/usr/bin/env python3

import sys
import time
import re
import json
import requests
import pymysql
from pymysql.cursors import DictCursor
from datetime import datetime
import os

# --- CHARGEMENT DE CONFIGURATION ---
try:
    with open("config.json", "r") as f:
        config = json.load(f)
except Exception as e:
    print(f"❌ Impossible de charger config.json : {e}")
    sys.exit(1)

# Vérifications de base
required_keys = ["webhook_url", "logfile_path", "lang", "server_name", "table_name", "db"]
for key in required_keys:
    if key not in config:
        print(f"❌ Clé manquante dans config.json : {key}")
        sys.exit(1)

if "user" not in config["db"] or "password" not in config["db"]:
    print("❌ Les identifiants de connexion à la base sont incomplets.")
    sys.exit(1)

# --- CHARGEMENT DES MESSAGES MULTILANGUE ---
try:
    with open("messages.json", "r") as f:
        messages_config = json.load(f)
except Exception as e:
    print(f"❌ Impossible de charger messages.json : {e}")
    sys.exit(1)

lang = config["lang"]
if lang not in messages_config["static"]:
    print(f"❌ Langue '{lang}' non prise en charge dans messages.json.")
    sys.exit(1)

T = messages_config["static"][lang]
CONN_MSG = messages_config["connection_count"].get(lang, {})
DURATION_MSG = messages_config["session_duration"].get(lang, {})

WEBHOOK_URL = config["webhook_url"]
LOGFILE_PATH = config["logfile_path"]
TABLE_NAME = config["table_name"]
SERVER_NAME = config["server_name"]
DB_USER = config["db"]["user"]
DB_PASSWORD = config["db"]["password"]

# --- VARIABLES ET REGEX ---
active_sessions = {}
pattern_connect = re.compile(r"\[INFO\] (.+?) : Connected")
pattern_disconnect = re.compile(r"\[INFO\] (.+?) Connection Terminated")

# --- UTILS ---
def send_discord_notification(message):
    data = {"content": message}
    try:
        response = requests.post(WEBHOOK_URL, json=data)
        if response.status_code != 204:
            print(T["discord_error"].format(status=response.status_code))
        else:
            print(T["discord_sent"].format(message=message))
    except Exception as e:
        print(T["discord_fail"].format(error=e))

def parse_time(val):
    if val.endswith("m"):
        return int(val[:-1]) * 60
    elif val.endswith("h"):
        return int(val[:-1]) * 3600
    elif val.endswith("d"):
        return int(val[:-1]) * 86400
    else:
        return int(val)

def select_filtered_message(messages_dict, value, username=""):
    for key, templates in messages_dict.items():
        if "-" in key:
            min_val, max_val = key.split("-")
            if parse_time(min_val) <= value < parse_time(max_val):
                return templates[0].format(username=username, value=value)
        elif "+" in key:
            if value >= parse_time(key.replace("+", "")):
                return templates[0].format(username=username, value=value)
    return ""

def get_db_connection():
    return pymysql.connect(
        host="localhost",
        user=DB_USER,
        password=DB_PASSWORD,
        database="beammp_db",
        cursorclass=DictCursor
    )

# --- HANDLERS ---
def handle_connection(username):
    now = datetime.now()
    conn = get_db_connection()
    cursor = conn.cursor()

    cursor.execute(f"SELECT * FROM `{TABLE_NAME}` WHERE username = %s", (username,))
    user = cursor.fetchone()

    if user:
        connection_count = user["connection_count"] + 1
        cursor.execute(f"""
            UPDATE `{TABLE_NAME}`
            SET connection_count = connection_count + 1, last_connect = %s
            WHERE username = %s
        """, (now, username))
        msg = select_filtered_message(CONN_MSG, connection_count, username)
        message = T["user_connected"].format(username=username, server_name=SERVER_NAME) + " " + msg
    else:
        cursor.execute(f"""
            INSERT INTO `{TABLE_NAME}` (username, connection_count, last_connect, total_time)
            VALUES (%s, 1, %s, 0)
        """, (username, now))
        message = T["user_connected"].format(username=username, server_name=SERVER_NAME)

    conn.commit()
    cursor.close()
    conn.close()

    active_sessions[username] = now
    send_discord_notification(message.strip())

def handle_disconnection(username):
    now = datetime.now()
    conn = get_db_connection()
    cursor = conn.cursor()

    start = active_sessions.pop(username, None)
    session_seconds = 0

    if start:
        duration = now - start
        session_seconds = int(duration.total_seconds())

        minutes = session_seconds // 60
        hours = minutes // 60
        minutes = minutes % 60
        duration_str = f"{hours}h {minutes}min" if hours > 0 else f"{minutes}min"

        cursor.execute(f"SELECT total_time FROM `{TABLE_NAME}` WHERE username = %s", (username,))
        user = cursor.fetchone()
        total_time = user["total_time"] if user else 0
        new_total = total_time + session_seconds

        cursor.execute(f"""
            UPDATE `{TABLE_NAME}`
            SET last_disconnect = %s, total_time = %s
            WHERE username = %s
        """, (now, new_total, username))

        msg = ""
        if session_seconds < 120:
            msg = select_filtered_message(DURATION_MSG, session_seconds, username)

        total_hours = new_total // 3600
        total_minutes = (new_total % 3600) // 60
        total_str = f"{total_hours}h {total_minutes}min"
        message = T["session_summary"].format(username=username, session=duration_str, total=total_str, msg=msg)
    else:
        cursor.execute(f"UPDATE `{TABLE_NAME}` SET last_disconnect = %s WHERE username = %s", (now, username))
        message = T["user_disconnected"].format(username=username, server_name=SERVER_NAME)

    conn.commit()
    cursor.close()
    conn.close()
    send_discord_notification(message.strip())

# --- LECTURE DU LOG ---
try:
    with open(LOGFILE_PATH, "r") as logfile:
        logfile.seek(0, os.SEEK_END)
        print(T["db_ok"])
        print(T["watching_log"].format(path=LOGFILE_PATH))
        while True:
            line = logfile.readline()
            if not line:
                time.sleep(0.5)
                continue

            match_conn = pattern_connect.search(line)
            match_disc = pattern_disconnect.search(line)

            if match_conn:
                username = match_conn.group(1).strip()
                print(T["log_connection"].format(username=username))
                handle_connection(username)

            elif match_disc:
                username = match_disc.group(1).strip()
                print(T["log_disconnection"].format(username=username))
                handle_disconnection(username)
except KeyboardInterrupt:
    print("\n🛑 Arrêt du script demandé.")
    sys.exit(0)
except Exception as e:
    print(f"❌ Erreur fatale : {e}")
    sys.exit(1)
