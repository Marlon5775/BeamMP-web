#!/bin/bash

# === Script global d'installation BeamMP-Web (multilangue) ===
# À lancer en root depuis la racine du projet (sudo ./install.sh)
set -e
set -x  # <-- Affiche chaque commande exécutée

INSTALL_DIR="$(pwd)/File_Install"
VENV_DIR="$(pwd)/.venv"
CONFIG_FILE="./install_config.json"

# Vérifie jq installé
if ! command -v jq &>/dev/null; then
  echo "[ERREUR] jq est requis. Lance : sudo apt install jq" >&2
  exit 1
fi

# Récupère la langue depuis install_config.json
LANG=$(jq -r '.lang' "$CONFIG_FILE")
LANG_FILE="$INSTALL_DIR/lang/$LANG.json"

# Vérifie présence fichier de langue
if [ ! -f "$LANG_FILE" ]; then
  echo "[ERREUR] Fichier de langue introuvable : $LANG_FILE" >&2
  exit 1
fi

# Fonction pour charger une clé traduite
t() {
  jq -r --arg key "$1" '.[$key] // ("[TRAD MANQUANTE: " + $key + "]")' "$LANG_FILE"
}

step() {
  echo -e "\n\033[1;33m=== $(t "$1") ===\033[0m"
}

ok() {
  echo -e "\033[1;32m[OK]\033[0m $(t "$1")"
}

err() {
  echo -e "\033[1;31m[ERREUR]\033[0m $(t "$1")" >&2
  exit 1
}

# Vérif root
if [ "$(id -u)" -ne 0 ]; then
  err "ERROR_NEED_ROOT"
fi

# Vérif dépendances système
step "STEP_CHECK_PREREQUIS"
for pkg in python3 python3-venv python3-pip; do
  if ! dpkg -l | grep -qw "$pkg"; then
    err "ERROR_MISSING_PACKAGE"
  fi
done

# Crée le venv si absent
if [ ! -d "$VENV_DIR" ]; then
  step "STEP_CREATE_VENV"
  python3 -m venv "$VENV_DIR"
  ok "OK_VENV_CREATED"
fi

# Active le venv
source "$VENV_DIR/bin/activate"

# Vérifie presence requirements.txt
if [ ! -f requirements.txt ]; then
  err "ERROR_MISSING_REQUIREMENTS"
fi

# Installe les dépendances Python
step "STEP_INSTALL_DEP"
pip install --upgrade pip
pip install -r requirements.txt
ok "OK_DEP_INSTALLED"

# Vérif scripts présents
for f in set_1_base.py set_2_database.py set_3_services.py set_4_permissions.py; do
  if [ ! -f "$INSTALL_DIR/$f" ]; then
    err "ERROR_MISSING_SCRIPT"
  fi
done

cd "$INSTALL_DIR"

step "STEP_1"
python set_1_base.py || err "ERROR_SCRIPT1"
ok "OK_STEP1"

step "STEP_2"
python set_2_database.py || err "ERROR_SCRIPT2"
ok "OK_STEP2"

if [ ! -f "$INSTALL_DIR/../bot/players.out" ]; then
    echo -e "\033[1;33m[ACTION]\033[0m Compilation bot Discord avec PyInstaller..."

    BOT_DIR="$INSTALL_DIR/../bot"

    if [ ! -f "$BOT_DIR/players.py" ]; then
        echo -e "\033[1;31m[ERREUR]\033[0m players.py introuvable dans $BOT_DIR"
        exit 10
    fi

    # Compilation en tant qu'utilisateur courant, même en sudo
    sudo -u $(logname) "$VENV_DIR/bin/python" -m PyInstaller \
        --onefile --name players.out \
        --distpath "$BOT_DIR" \
        --workpath "$BOT_DIR/build" \
        --specpath "$BOT_DIR/build" \
        --clean "$BOT_DIR/players.py" || {
            echo -e "\033[1;31m[ERREUR]\033[0m Échec compilation bot"
            exit 11
        }

    echo -e "\033[1;32m[OK]\033[0m Bot Discord compilé"
else
    echo -e "\033[1;36m[INFO]\033[0m Bot déjà compilé"
fi

step "STEP_3"
python set_3_services.py || err "ERROR_SCRIPT3"
ok "OK_STEP3"

step "STEP_4"
python set_4_permissions.py || err "ERROR_SCRIPT4"
ok "OK_STEP4"
step "RESTART_APACHE"
systemctl restart apache2
ok "OK_APACHE_RESTARTED"

echo -e "\n\033[1;32m--- $(t "INSTALL_DONE") ---\033[0m"
echo "$(t "INSTALL_NOTE")"
