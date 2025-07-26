#!/usr/bin/env python3

import os, sys, json, shutil, subprocess, glob

# === Chemins principaux ===
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
PARENT_DIR = os.path.abspath(os.path.join(SCRIPT_DIR, ".."))
CONFIG_PATH = os.path.join(PARENT_DIR, "install_config.json")
SITE_WEB = os.path.join(PARENT_DIR, "site", "beammp-web")
SITE_DATA = os.path.join(PARENT_DIR, "site", "DATA")
SOURCE_BOOTSTRAP = os.path.join(PARENT_DIR, "site", "bootstrap.php")
LANG_DIR = os.path.join(SCRIPT_DIR, "lang")

# === Traduction ===
def t(key):
    return LANG.get(key, f"[TRAD:{key}]")

def log(msg): print(msg)

def load_lang():
    if not os.path.exists(CONFIG_PATH):
        print("[FATAL] install_config.json introuvable."); sys.exit(1)
    with open(CONFIG_PATH, encoding="utf-8") as f:
        config = json.load(f)
    lang_code = config.get("lang", "fr")
    lang_file = os.path.join(LANG_DIR, f"{lang_code}.json")
    if not os.path.exists(lang_file):
        print(f"[FATAL] Fichier de langue manquant: {lang_file}"); sys.exit(1)
    with open(lang_file, encoding="utf-8") as f:
        return json.load(f), config

LANG, CONFIG = load_lang()

# === Templates ===
APACHE_SITES = "/etc/apache2/sites-available"
APACHE_PORTS = "/etc/apache2/ports.conf"

VHOST_TEMPLATE = """<VirtualHost *:{port}>
    ServerName beammpweb-{name}
    DocumentRoot /var/www/beammpweb-{name}/beammp-web

    <Directory /var/www/beammpweb-{name}/beammp-web>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    Alias /DATA/images /var/www/beammpweb-{name}/DATA/images
    <Directory /var/www/beammpweb-{name}/DATA/images>
        Require all granted
        Options -Indexes
    </Directory>
    Alias /DATA/descriptions /var/www/beammpweb-{name}/DATA/descriptions
    <Directory /var/www/beammpweb-{name}/DATA/descriptions>
        Require all granted
        Options -Indexes
    </Directory>

    ErrorLog  /var/log/apache2/beammpweb-{name}_error.log
    CustomLog /var/log/apache2/beammpweb-{name}_access.log combined
</VirtualHost>
"""

ENV_TEMPLATE = """#Credentials
DB_HOST=127.0.0.1
DB_NAME=beammp_db
DB_USER={db_user}
DB_PASSWORD={db_pass}
BEAMMP_TABLE=beammp_{name}
USER_CHANGE=www-data
#Path
CONFIG_REMOTE_PATH={config_remote_path}
LOG_FILE_PATH={log_file_path}
PATH_RESOURCES={path_resources}
BASE_PATH=/var/www/beammpweb-{name}
BASE_URL=http://{ip}:{port}
DATA_PATH=/var/www/beammpweb-{name}/DATA/
#Discord
DISCORD_WEBHOOK_MOD_UPLOAD=
DISCORD_WEBHOOK_SERVER_RESTART=
#Service
BEAMMP_SERVICE=beammp-{name}.service
PLAYERS_SERVICE=players-{name}.service
#Language
LANG_DEFAULT={lang}
#Instance
TITLE={name}
"""

# === Fonctions ===
def check_utf8(filepath):
    try:
        with open(filepath, encoding="utf-8") as f:
            f.read()
        return True
    except UnicodeDecodeError as e:
        log(t("ERROR_UTF8").format(file=filepath, err=e))
        return False

def load_config():
    if not check_utf8(CONFIG_PATH):
        log(t("FATAL_UTF8"))
        sys.exit(1)
    with open(CONFIG_PATH, encoding="utf-8") as f:
        return json.load(f)

def setup_instances(cfg):
    src_web = SITE_WEB
    src_data = SITE_DATA
    if not os.path.isdir(src_web): log(t("ERROR_MISSING_SITE").format(path=src_web)); sys.exit(10)
    if not os.path.isdir(src_data): log(t("ERROR_MISSING_DATA").format(path=src_data)); sys.exit(11)
    log(t("STEP_COMPOSER"))
    result = subprocess.run(["composer", "install", "--no-interaction", "--prefer-dist"], cwd=src_web)
    if result.returncode != 0:
        log(t("ERROR_COMPOSER")); sys.exit(12)
    log(t("OK_COMPOSER"))

    for instance in cfg["instances"]:
        name = instance["name"]
        dest_dir = f"/var/www/beammpweb-{name}"
        log(f"\n--- [{t('INSTANCE')} {name}] ---")
        os.makedirs(dest_dir, exist_ok=True)
        log(t("OK_DIR_CREATED").format(path=dest_dir))
        dest_web = os.path.join(dest_dir, "beammp-web")
        if os.path.islink(dest_web) or os.path.exists(dest_web):
            if os.path.islink(dest_web): os.unlink(dest_web)
            else: shutil.rmtree(dest_web)
            log(t("WARN_OLD_SYMLINK"))
        os.symlink(src_web, dest_web)
        log(t("OK_SYMLINK").format(src=src_web))
        dest_data = os.path.join(dest_dir, "DATA")
        if os.path.exists(dest_data): shutil.rmtree(dest_data)
        shutil.copytree(src_data, dest_data)
        log(t("OK_DATA_COPY"))

def ensure_port_listen(port):
    with open(APACHE_PORTS, encoding="utf-8") as f:
        lines = f.read().splitlines()
    port_line = f"Listen {port}"
    if port_line not in lines:
        log(t("INFO_LISTEN").format(port=port))
        with open(APACHE_PORTS, "a", encoding="utf-8") as f:
            f.write(f"\nListen {port}\n")
        subprocess.run(["systemctl", "reload", "apache2"])

def setup_vhosts(cfg):
    for instance in cfg["instances"]:
        name = instance["name"]
        port = instance["port"]
        conf_path = os.path.join(APACHE_SITES, f"beammpweb-{name}.conf")
        ensure_port_listen(port)
        with open(conf_path, "w", encoding="utf-8") as f:
            f.write(VHOST_TEMPLATE.format(name=name, port=port))
        subprocess.run(["a2ensite", f"beammpweb-{name}.conf"])
        log(t("OK_VHOST").format(name=name, port=port))
    subprocess.run(["a2enmod", "rewrite"])
    subprocess.run(["systemctl", "reload", "apache2"])
    log(t("OK_ALL_VHOSTS"))

def update_php_ini(filepath, changes):
    shutil.copy2(filepath, filepath + ".bak")
    found = {key: False for key in changes}
    lines_out = []
    with open(filepath, "r", encoding="utf-8", errors="replace") as f:
        for line in f:
            modified = False
            for key, value in changes.items():
                if line.lstrip().startswith(f"{key}") or line.lstrip().startswith(f";" + key):
                    lines_out.append(f"{key} = {value}\n")
                    found[key] = True
                    modified = True
                    break
            if not modified:
                lines_out.append(line)
    for key, value in changes.items():
        if not found[key]:
            lines_out.append(f"\n{key} = {value}\n")
    with open(filepath, "w", encoding="utf-8") as f:
        f.writelines(lines_out)
    log(t("OK_PATCHED").format(file=filepath))

def patch_php_ini():
    changes = {
        "upload_max_filesize": "10G",
        "post_max_size": "10G",
        "max_input_time": "300",
        "max_execution_time": "300",
        "memory_limit": "256M"
    }
    files = glob.glob("/etc/php/*/apache2/php.ini")
    if not files:
        log(t("WARN_NO_PHPINI"))
    else:
        for ini in files:
            log(t("STEP_PATCH").format(file=ini))
            update_php_ini(ini, changes)

def setup_directories(cfg):
    for inst in cfg["instances"]:
        res_dir = os.path.join(inst["root_beammp"], "bin", "Resources")
        os.makedirs(os.path.join(res_dir, "inactive_map"), exist_ok=True)
        os.makedirs(os.path.join(res_dir, "inactive_mod"), exist_ok=True)
        log(t("OK_DIR_RESOURCE").format(name=inst["name"]))

def setup_bootstrap(cfg):
    user_system = cfg.get("user_system", "www-data")
    for inst in cfg["instances"]:
        name = inst["name"]
        target = f"/var/www/beammpweb-{name}/bootstrap.php"
        try:
            shutil.copy2(SOURCE_BOOTSTRAP, target)
            os.chmod(target, 0o644)
            try:
                import pwd, grp
                uid = pwd.getpwnam("www-data").pw_uid
                gid = grp.getgrnam(user_system).gr_gid
                os.chown(target, uid, gid)
            except Exception:
                os.system(f"chown www-data:{user_system} '{target}'")
            log(t("OK_BOOTSTRAP").format(name=name))
        except Exception as e:
            log(t("ERROR_BOOTSTRAP").format(name=name, err=e))

def setup_env(cfg):
    db_user = cfg["db_user"]
    db_pass = cfg["db_pass"]
    lang = cfg.get("lang", "fr")
    ip = cfg.get("ip", "127.0.0.1")
    for inst in cfg["instances"]:
        name = inst["name"]
        port = inst.get("port", "")
        root_beammp = inst["root_beammp"]
        env_path = f"/var/www/beammpweb-{name}/.env"
        env_content = ENV_TEMPLATE.format(
            db_user=db_user,
            db_pass=db_pass,
            config_remote_path=os.path.join(root_beammp, "bin/ServerConfig.toml"),
            log_file_path=os.path.join(root_beammp, "bin/Server.log"),
            path_resources=os.path.join(root_beammp, "bin/Resources"),
            name=name,
            ip=ip,
            port=port,
            lang=lang
        )
        with open(env_path, "w", encoding="utf-8") as fenv:
            fenv.write(env_content)
        os.chmod(env_path, 0o600)
        try:
            import pwd, grp
            uid = pwd.getpwnam("www-data").pw_uid
            gid = grp.getgrnam("www-data").gr_gid
            os.chown(env_path, uid, gid)
        except Exception:
            os.system(f"chown www-data:www-data '{env_path}'")
        log(t("OK_ENV").format(name=name))
    log(t("NOTE_WEBHOOKS"))

# === MAIN ===
if __name__ == "__main__":
    if os.geteuid() != 0:
        print(t("ERROR_ROOT"))
        sys.exit(1)
    cfg = CONFIG
    print("\n===", t("STEP1"), "===")
    setup_instances(cfg)
    print("\n===", t("STEP2"), "===")
    setup_vhosts(cfg)
    print("\n===", t("STEP3"), "===")
    patch_php_ini()
    print("\n===", t("STEP4"), "===")
    setup_directories(cfg)
    print("\n===", t("STEP5"), "===")
    setup_bootstrap(cfg)
    print("\n===", t("STEP6"), "===")
    setup_env(cfg)
