#!/usr/bin/env python3

import os
import sys
import json
import stat
import grp
import pwd
import tempfile
import subprocess
import getpass
import pymysql
import bcrypt
from pathlib import Path

# === Constantes ===
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PARENT_DIR = os.path.abspath(os.path.join(SCRIPT_DIR, ".."))
CONFIG_PATH = os.path.join(PARENT_DIR, "install_config.json")
LANG_DIR = os.path.join(SCRIPT_DIR, "lang")
SUDOERS_FILE = "/etc/sudoers.d/beammp-services"
SYSTEMCTL_PATH = "/bin/systemctl"
WWW_DATA = "www-data"

# === Chargement de la config et de la langue ===
def load_config():
    if not os.path.exists(CONFIG_PATH):
        print("[ERREUR] Fichier config introuvable:", CONFIG_PATH)
        sys.exit(1)
    with open(CONFIG_PATH, encoding="utf-8") as f:
        return json.load(f)

def load_lang(lang_code):
    lang_file = os.path.join(LANG_DIR, f"{lang_code}.json")
    if not os.path.exists(lang_file):
        print(f"[ERREUR] Fichier de langue introuvable: {lang_file}")
        sys.exit(1)
    with open(lang_file, encoding="utf-8") as f:
        return json.load(f)

# Placeholder, rempli juste après
LANG = {}

def tr(key, **kwargs):
    msg = LANG.get(key, f"[TRAD_MISSING:{key}]")
    if kwargs:
        try:
            return msg.format(**kwargs)
        except Exception:
            return msg + " [ERREUR_FORMAT]"
    return msg

def log(msg):
    print(msg)

# === SUDOERS (services restart par www-data) ===
def sudoers_action(config):
    players_services = []
    beammp_services = []
    for inst in config["instances"]:
        name = inst["name"]
        players_services.append(f"{SYSTEMCTL_PATH} restart players-{name}.service")
        beammp_services.append(f"{SYSTEMCTL_PATH} restart beammp-{name}.service")

    sudoers_line = (
        "www-data ALL=NOPASSWD: " +
        ", ".join(players_services + beammp_services)
    ) + "\n"

    log(tr("INFO_SUDOERS_LINE"))
    log(sudoers_line)

    if os.path.exists(SUDOERS_FILE):
        resp = input(tr("WARN_SUDOERS_EXISTS", file=SUDOERS_FILE)).strip().lower()
        if resp != "o":
            log(tr("MSG_ABORT"))
            return

    # Test syntaxe avant installation
    with tempfile.NamedTemporaryFile("w", delete=False) as tmpf:
        tmpf.write(sudoers_line)
        tmp_path = tmpf.name

    try:
        res = subprocess.run(["visudo", "-cf", tmp_path], capture_output=True)
        if res.returncode != 0:
            print(tr("ERROR_SUDOERS_SYNTAX") + ":\n" + res.stderr.decode())
            os.unlink(tmp_path)
            return
        # Installe
        with open(tmp_path, "r") as src, open(SUDOERS_FILE, "w") as dst:
            dst.write(src.read())
        os.chmod(SUDOERS_FILE, 0o440)
        log(tr("OK_SUDOERS_INSTALLED", file=SUDOERS_FILE))
    finally:
        os.unlink(tmp_path)

# === DROITS DE PASSAGE ET GROUPES ===
def get_www_data_groups():
    """Groupes dont www-data fait partie."""
    groups = [g.gr_name for g in grp.getgrall() if WWW_DATA in g.gr_mem]
    try:
        pw = pwd.getpwnam(WWW_DATA)
        primary_group = grp.getgrgid(pw.pw_gid).gr_name
        if primary_group not in groups:
            groups.append(primary_group)
    except KeyError:
        print(tr("ERROR_USER_NOT_FOUND", user=WWW_DATA))
        exit(1)
    return groups

def get_all_check_paths(config):
    """Chemins à vérifier : toutes les instances + site web."""
    paths = set()
    for inst in config.get("instances", []):
        rb = inst["root_beammp"]
        paths.update([
            rb,
            os.path.join(rb, "bin"),
            os.path.join(rb, "bin", "Resources"),
            os.path.join(rb, "bin", "Resources", "Client"),
            os.path.join(rb, "bin", "Resources", "inactive_map"),
            os.path.join(rb, "bin", "Resources", "inactive_mod"),
            os.path.join(rb, "bin", "ServerConfig.toml"),
            os.path.join(rb, "bin", "Server.log")
        ])
    # Ajoute le dossier du site web (cible du symlink)
    web_dir = Path(PARENT_DIR) / "site" / "beammp-web"
    paths.add(str(web_dir))
    return list(paths)

def get_parents(path):
    """Liste tous les dossiers parents de path (de / à path inclus)."""
    p = Path(path).resolve()
    if p.is_file():
        p = p.parent
    return [str(parent) for parent in p.parents[::-1]] + [str(p)]

def check_access(path, www_groups):
    if not os.path.exists(path):
        return None
    st = os.stat(path)
    mode = stat.S_IMODE(st.st_mode)
    owner = pwd.getpwuid(st.st_uid).pw_name
    group = grp.getgrgid(st.st_gid).gr_name

    # Cas ok : propriétaire www-data et u+x, ou www-data groupe et g+x, ou o+x
    if owner == WWW_DATA and (mode & stat.S_IXUSR):
        return None
    if group in www_groups and (mode & stat.S_IXGRP):
        return None
    if (mode & stat.S_IXOTH):
        return None

    details = {
        "path": str(path),
        "owner": owner,
        "group": group,
        "mode": oct(mode),
        "actions": []
    }
    if group in www_groups:
        details["actions"].append({
            "type": "chmod",
            "desc": tr("FIX_ADD_GX", path=path, group=group),
            "cmd": f"chmod g+x '{path}'"
        })
    else:
        details["actions"].append({
            "type": "usermod",
            "desc": tr("FIX_ADD_WWWDATA_GROUP", group=group),
            "cmd": f"usermod -aG {group} {WWW_DATA}"
        })
        if not (mode & stat.S_IXGRP):
            details["actions"].append({
                "type": "chmod",
                "desc": tr("FIX_ADD_GX", path=path, group=group),
                "cmd": f"chmod g+x '{path}'"
            })
    return details

def passage_action(config):
    www_groups = get_www_data_groups()
    to_fix = []
    paths = get_all_check_paths(config)
    seen = set()
    for p in paths:
        for par in get_parents(p):
            if par not in seen and os.path.exists(par):
                seen.add(par)
                pb = check_access(par, www_groups)
                if pb:
                    to_fix.append(pb)

    if not to_fix:
        log(tr("OK_ALL_PATHS_ACCESSIBLE"))
        return

    print(tr("ERROR_PATHS", n=len(to_fix)))
    for pb in to_fix:
        print(f"\n--- {pb['path']} ---")
        print(tr("PROP", owner=pb['owner'], group=pb['group'], mode=pb['mode']))
        for action in pb["actions"]:
            print(f"    > {action['desc']}")
            print(f"      {action['cmd']}")

    print("\n" + tr("ACTIONS_AVAILABLE"))
    print(tr("ACTION_AUTO"))
    print(tr("ACTION_TXT"))
    print(tr("ACTION_QUIT") + "\n")
    choix = input(tr("CHOICE")).strip()

    if choix == "1":
        print("\n" + tr("WARN_APPLY_MODIF"))
        confirm = input(tr("CONFIRM_EXEC_AUTO")).strip().lower()
        if confirm != "o":
            print(tr("MSG_ABORT"))
            return
        for pb in to_fix:
            for action in pb["actions"]:
                print(f"> {action['cmd']}")
                os.system(action["cmd"])
        print("\n" + tr("OK_MODIFS_APPLIED"))
    elif choix == "2":
        out = "droits_beammp_a_corriger.txt"
        with open(out, "w") as f:
            for pb in to_fix:
                f.write(f"\n--- {pb['path']} ---\n")
                f.write(tr("PROP", owner=pb['owner'], group=pb['group'], mode=pb['mode']) + "\n")
                for action in pb["actions"]:
                    f.write(f"    > {action['desc']}\n")
                    f.write(f"      {action['cmd']}\n")
        print("\n" + tr("OK_TXT_GENERATED", out=out))
    else:
        print(tr("MSG_NO_CHANGE"))

# === CHOWN/CHMOD RACINE SITES WEB ===
def web_dirs_permissions(config):
    user_system = config["user_system"]
    log(tr("INFO_EXPECTED_OWNER", user="www-data", group=user_system))
    for inst in config["instances"]:
        name = inst["name"]
        site_path = f"/var/www/beammpweb-{name}"
        if not os.path.exists(site_path):
            log(tr("WARN_DIR_MISSING", path=site_path))
            continue
        log(tr("ACTION_CORRECT_OWNER", path=site_path))
        os.system(f"chown -R www-data:{user_system} '{site_path}'")
        log(tr("ACTION_CORRECT_RIGHTS", path=site_path))
        os.system(f"chmod -R 755 '{site_path}'")


# === Correction des droits sur site/ et bot/
def chown_site_and_bot(config):
    site_dir = os.path.join(PARENT_DIR, "site")
    bot_dir = os.path.join(PARENT_DIR, "..", "bot")
    user_system = config.get("user_system", "beammp")

    if os.path.isdir(site_dir):
        print(f"[ACTION] chown {user_system}:www-data -R {site_dir}")
        os.system(f"chown -R {user_system}:www-data '{site_dir}'")

    # Correction des droits sur tous les dossiers [instance]
    for inst in config.get("instances", []):
        name = inst["name"]
        instance_dir = os.path.join(PARENT_DIR, name)
        if os.path.isdir(instance_dir):
            print(f"[ACTION] chown {user_system}:www-data -R {instance_dir}")
            os.system(f"chown -R {user_system}:www-data '{instance_dir}'")

# === CHMOD 770 SUR /site GLOBAL ===
def chmod_site_770():
    site_dir = os.path.join(PARENT_DIR, "site")
    if os.path.isdir(site_dir):
        print(tr("ACTION_CHMOD_770", dir=site_dir))
        os.system(f"chmod -R 770 '{site_dir}'")
        print(tr("OK_CHMOD_770", dir=site_dir))
    else:
        print(tr("WARN_SITE_DIR_MISSING", dir=site_dir))

# === G+W SI NECESSAIRE POUR www-data (uploads/cache/Client/inactive/...) ===
def group_write_permissions(config):
    www_groups = get_www_data_groups()
    actions_done = []
    for inst in config.get("instances", []):
        rb = inst["root_beammp"]
        # bin/
        bin_path = os.path.join(rb, "bin")
        if os.path.isdir(bin_path):
            st = os.stat(bin_path)
            group = grp.getgrgid(st.st_gid).gr_name
            mode = stat.S_IMODE(st.st_mode)
            if group in www_groups and not (mode & stat.S_IWGRP):
                os.system(f"chmod g+w '{bin_path}'")
                actions_done.append(f"chmod g+w '{bin_path}'")
        # ServerConfig.toml
        sc_path = os.path.join(rb, "bin", "ServerConfig.toml")
        if os.path.isfile(sc_path):
            st = os.stat(sc_path)
            group = grp.getgrgid(st.st_gid).gr_name
            mode = stat.S_IMODE(st.st_mode)
            if group in www_groups and not (mode & stat.S_IWGRP):
                os.system(f"chmod g+w '{sc_path}'")
                actions_done.append(f"chmod g+w '{sc_path}'")
        # dossiers DATA/Client/inactive_map/inactive_mod
        for subdir in ("Client", "inactive_map", "inactive_mod"):
            res_path = os.path.join(rb, "bin", "Resources", subdir)
            if os.path.isdir(res_path):
                st = os.stat(res_path)
                group = grp.getgrgid(st.st_gid).gr_name
                mode = stat.S_IMODE(st.st_mode)
                if group in www_groups and not (mode & stat.S_IWGRP):
                    os.system(f"chmod -R g+w '{res_path}'")
                    actions_done.append(f"chmod -R g+w '{res_path}'")
    if actions_done:
        print(tr("OK_GROUP_W_APPLIED"))
        for a in actions_done:
            print(f"  > {a}")

# === AJOUT UTILISATEUR ADMIN / SUPERADMIN DANS LA DB ===
def admin_user_creation(config):
    db_user = config.get("db_user")
    db_pass = config.get("db_pass")
    db_name = config.get("db_name", "beammp_db")
    db_host = "localhost"
    # Prompt login/mdp/role
    pseudo = input(tr("ENTER_USERNAME")).strip()
    while not pseudo:
        pseudo = input(tr("REQ_USERNAME")).strip()
    while True:
        pwd1 = getpass.getpass(tr("ENTER_PASSWORD"))
        pwd2 = getpass.getpass(tr("CONFIRM_PASSWORD"))
        if pwd1 != pwd2:
            print(tr("PASS_NO_MATCH"))
        elif not pwd1:
            print(tr("PASS_EMPTY"))
        else:
            break
    print(tr("CHOOSE_ACCOUNT_TYPE").replace("\\n", "\n"))
    while True:
        type_raw = input(tr("ENTER_ACCOUNT_TYPE")).strip()
        if type_raw == "1":
            user_type = "Admin"
            break
        elif type_raw == "2":
            user_type = "SuperAdmin"
            break
        else:
            print(tr("INVALID_CHOICE"))

    # Connexion DB
    try:
        conn = pymysql.connect(
            host=db_host,
            user=db_user,
            password=db_pass,
            database=db_name,
            charset="utf8mb4"
        )
    except Exception as e:
        print(tr("DB_CONN_FAILED", error=e))
        sys.exit(2)

    # Hash du mot de passe
    pwd_hash = bcrypt.hashpw(pwd1.encode(), bcrypt.gensalt()).decode()

    # Vérifie si l'utilisateur existe déjà
    try:
        with conn.cursor() as cur:
            sql = f"SELECT COUNT(*) FROM users WHERE username = %s"
            cur.execute(sql, (pseudo,))
            count = cur.fetchone()[0]
            if count > 0:
                print(tr("ERR_USER_EXISTS", pseudo=pseudo))
                sys.exit(4)
            sql = f"INSERT INTO users (username, password_hash, role) VALUES (%s, %s, %s)"
            cur.execute(sql, (pseudo, pwd_hash, user_type))
        conn.commit()
    except Exception as e:
        print(tr("DB_INSERT_FAILED", error=e))
        sys.exit(3)
    finally:
        conn.close()

    print("\n" + tr("OK_USER_ADDED"))
    # Affiche les adresses des sites web pour chaque instance
    print(tr("WEB_ACCESS"))
    for inst in config.get("instances", []):
        name = inst["name"]
        ip = config.get("ip", "127.0.0.1")
        port = inst.get("port", "80")
        print(tr("WEB_INSTANCE", ip=ip, port=port, name=name))
    print(tr("CREDENTIALS", pseudo=pseudo))
    voir = input(tr("SHOW_PASS")).strip()
    if voir == "1":
        print(tr("YOUR_PASSWORD", pwd=pwd1))

# === Modification droit et propriaitaire inactive_map et invactive_mod ===
def fix_inactive_dirs_permissions(config):
    """Corrige les droits sur inactive_map et inactive_mod pour chaque instance."""
    user_system = config.get("user_system", "beammp")
    for inst in config.get("instances", []):
        rb = inst["root_beammp"]
        for subdir in ("inactive_map", "inactive_mod"):
            path = os.path.join(rb, "bin", "Resources", subdir)
            if os.path.isdir(path):
                print(tr("ACTION_CHMOD_770", dir=path))
                os.system(f"chmod -R 770 '{path}'")
                print(tr("ACTION_CHOWN", dir=path, user=user_system))
                os.system(f"chown -R {user_system}:www-data '{path}'")


def fix_data_dir_permissions(config):
    """Ajoute les droits g+w sur les dossiers DATA des sites web."""
    for inst in config.get("instances", []):
        name = inst["name"]
        data_path = f"/var/www/beammpweb-{name}/DATA"
        if os.path.isdir(data_path):
            print(tr("ACTION_CHMOD_GW", dir=data_path))
            os.system(f"chmod -R g+w '{data_path}'")
        else:
            print(tr("WARN_DIR_MISSING", path=data_path))

# === MAIN ===
if __name__ == "__main__":
    if os.geteuid() != 0:
        print("[ERREUR] Ce script doit etre lance en root (sudo).")
        exit(1)
    config = load_config()
    lang_code = config.get("lang", "fr")
    LANG.update(load_lang(lang_code))

    print("\n=== " + tr("TITLE_PERMS_CONFIG") + " ===\n")
    # 1. sudoers
    sudoers_action(config)
    # 2. droit de passage
    passage_action(config)
    # 3. chown/chmod site web
    web_dirs_permissions(config)
    # 4. chown/chmod site_source and bot
    chown_site_and_bot(config)
    # 5. group+w DATA/bin/Client/inactive...
    group_write_permissions(config)
    # 6. Creation user admin
    admin_user_creation(config)
    # 7. Modification droit inactive_map inactive_mod
    fix_inactive_dirs_permissions(config)
    # 8. Ajout droits g+w sur DATA/
    fix_data_dir_permissions(config)
