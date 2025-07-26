#!/usr/bin/env python3

import os
import sys
import json
import shutil
import subprocess

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PARENT_DIR = os.path.abspath(os.path.join(SCRIPT_DIR, ".."))
CONFIG_PATH = os.path.join(PARENT_DIR, "install_config.json")
LANG_DIR = os.path.join(SCRIPT_DIR, "lang")

def log(msg):
    print(msg)

def t(key, **kwargs):
    msg = LANG.get(key, f"[TRAD:{key}]")
    return msg.format(**kwargs)

def safe_symlink(src, dest):
    try:
        if os.path.islink(dest) or os.path.exists(dest):
            os.remove(dest)
        os.symlink(src, dest)
    except Exception as e:
        log(t("ERROR_SYMLINK", dest=dest, err=e))
        sys.exit(2)

def load_config_and_lang():
    if not os.path.exists(CONFIG_PATH):
        log(t("ERROR_CONFIG_MISSING", path=CONFIG_PATH))
        sys.exit(1)
    with open(CONFIG_PATH, encoding="utf-8") as f:
        cfg = json.load(f)
    lang_code = cfg.get("lang", "fr")
    lang_path = os.path.join(LANG_DIR, f"{lang_code}.json")
    if not os.path.exists(lang_path):
        log(f"[FATAL] Fichier de langue manquant: {lang_path}")
        sys.exit(1)
    with open(lang_path, encoding="utf-8") as f:
        lang = json.load(f)
    return cfg, lang

def main():
    global LANG
    if os.geteuid() != 0:
        print(t("ERROR_ROOT"))
        sys.exit(1)

    config, LANG = load_config_and_lang()
    user = config.get("user_system", "beammp")
    lang = config.get("lang", "fr")
    db_user = config.get("db_user", "")
    db_pass = config.get("db_pass", "")
    db_name = config.get("db_name", "")
    db_host = "localhost"

    parent_of_project = os.path.abspath(os.path.join(SCRIPT_DIR, "..", ".."))
    source_bot = os.path.join(PARENT_DIR, "bot")
    source_players_out = os.path.join(source_bot, "players.out")
    source_messages = os.path.join(source_bot, "messages.json")

    # Vérification des fichiers
    if not os.path.exists(source_players_out):
        log(t("ERROR_MISSING_PLAYERS", path=source_players_out))
        sys.exit(3)
    if not os.path.exists(source_messages):
        log(t("ERROR_MISSING_MESSAGES", path=source_messages))
        sys.exit(4)

    for inst in config["instances"]:
        name = inst["name"]
        root_beammp = inst["root_beammp"]
        instance_dir = os.path.join(parent_of_project, name)
        os.makedirs(instance_dir, exist_ok=True)

        dst_bin = os.path.join(instance_dir, "players.out")
        dst_messages = os.path.join(instance_dir, "messages.json")
        safe_symlink(source_players_out, dst_bin)
        shutil.copy2(source_messages, dst_messages)

        config_json = {
            "webhook_url": "A renseigner apres installation (voir README).",
            "logfile_path": os.path.join(root_beammp, "bin/Server.log"),
            "lang": lang,
            "server_name": name,
            "table_name": f"beammp_users_{name}",
            "db": {
                "user": db_user,
                "password": db_pass
            }
        }
        config_json_path = os.path.join(instance_dir, "config.json")
        with open(config_json_path, "w") as f:
            json.dump(config_json, f, indent=2, ensure_ascii=False)
        log(t("OK_INSTANCE_READY", path=instance_dir))

        # Service players
        players_service_path = f"/etc/systemd/system/players-{name}.service"
        with open(players_service_path, "w") as f:
            f.write(f"""[Unit]
Description=Suivi des connexions BeamMP instance {name}
After=network.target

[Service]
Type=simple
User={user}
WorkingDirectory={instance_dir}
ExecStart={dst_bin}

Restart=on-failure
RestartSec=3
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
""")
        os.chmod(players_service_path, 0o644)
        subprocess.run(["systemctl", "daemon-reload"])
        subprocess.run(["systemctl", "enable", "--now", f"players-{name}.service"])
        log(t("OK_SERVICE_PLAYERS", name=name))

        # Service BeamMP
        beammp_exec = os.path.join(root_beammp, "bin/BeamMP-Server")
        beammp_workdir = os.path.join(root_beammp, "bin/")
        beammp_log = f"/var/log/BeamMP-{name}.log"
        beammp_service_path = f"/etc/systemd/system/beammp-{name}.service"
        with open(beammp_service_path, "w") as f:
            f.write(f"""[Unit]
Description=BeamMP-Server instance {name}
After=network.target

[Service]
Type=simple
ExecStart={beammp_exec}
WorkingDirectory={beammp_workdir}
User={user}
Restart=on-failure
RestartSec=5
StandardOutput=append:{beammp_log}
StandardError=append:{beammp_log}
Environment="SUDO_ASKPASS=/bin/true"

[Install]
WantedBy=multi-user.target
""")
        os.chmod(beammp_service_path, 0o644)
        subprocess.run(["systemctl", "daemon-reload"])
        subprocess.run(["systemctl", "enable", "--now", f"beammp-{name}.service"])
        log(t("OK_SERVICE_BEAMMP", name=name))

        log(t("TODO_WEBHOOK", path=config_json_path))

if __name__ == "__main__":
    main()
