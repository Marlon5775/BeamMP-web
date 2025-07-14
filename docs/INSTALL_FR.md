# 📦 Installation de BeamMP-web + Bot + Scripts (Apache + MariaDB)

Ce tutoriel vous guide pas à pas pour installer le projet BeamMP-web (interface de gestion serveur + bot Discord + scripts de synchronisation) sur un serveur Debian/Ubuntu avec Apache et MariaDB.

---

## 1️⃣ Prérequis

```bash
sudo apt update && sudo apt install -y \
  apache2 mariadb-server php php-mysql php-curl php-xml php-mbstring \
  unzip curl git composer
```

---

## 2️⃣ Clonage du dépôt

```bash
git clone https://github.com/Zyphro3D/BeamMP-web.git
cd BeamMP-web
```

---

## 3️⃣ Installation du site web

### Création lien symbolique des fichiers et installation des dépendances

Créer un lien symbolique entre le dossier `site/beammp-web` et `/var/www/beammp-web`, puis installez les dépendances PHP avec Composer :

```bash
sudo ln -s ~/BeamMP-web/site/beammp-web /var/www/beammp-web
cd site/beammp-web/
sudo composer install
```

### Activation du site Apache

```bash
cd ~/BeamMP-web
sudo cp config/beammp-web.conf /etc/apache2/sites-available/
# (Optionnel) Désactiver le site Apache par défaut si encore actif
sudo a2dissite 000-default.conf
sudo a2ensite beammp-web.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

### Configuration PHP

Modifiez `/etc/php/*/apache2/php.ini` avec :

```ini
upload_max_filesize = 10G
post_max_size = 10G
max_input_time = 300
max_execution_time = 300
memory_limit = 256M
```

Redémarrage d'Apache :

```bash
sudo systemctl restart apache2
```

---

## 4️⃣ Base de données

### Sécurisation de MariaDB

```bash
sudo mysql_secure_installation
```

### Import du schéma et droits

```bash
sudo mysql -u root -p < sql/beammp_db.sql
sudo mysql -u root -p
```

Dans le shell MariaDB :

```sql
-- Créer l'utilisateur (remplacer USER et PASSWORD par les vôtres)
CREATE USER 'USER'@'localhost' IDENTIFIED BY 'PASSWORD';

-- Donner les droits sur les tables nécessaires (remplacer USER par le vôtre)
GRANT ALL PRIVILEGES ON beammp_db.beammp TO 'USER'@'localhost';
GRANT ALL PRIVILEGES ON beammp_db.beammp_users TO 'USER'@'localhost';
GRANT ALL PRIVILEGES ON beammp_db.users TO 'USER'@'localhost';

-- Appliquer les changements
FLUSH PRIVILEGES;

-- Quitter le shell MariaDB
EXIT;
```

---

## 🗞️ Fichier `.env` (configuration de l'application)

Créez ou modifiez le fichier `/var/www/beammp-web/.env` :

```bash
sudo nano /var/www/beammp-web/.env
```

Voici le **modèle à utiliser** :

```dotenv
# Base de données locale
DB_HOST=localhost
DB_NAME=beammp_db
DB_USER=USER         # Remplacer USER par le nom de l'utilisateur MariaDB
DB_PASSWORD=PASSWORD # Remplacer PASSWORD par le mot de passe de l'utilisateur

# Chemins vers les fichiers du serveur BeamMP
CONFIG_REMOTE_PATH=/home/USER/BeamMP-Server/bin/ServerConfig.toml  # Remplacer USER par votre nom d'utilisateur système
LOG_FILE_PATH=/home/USER/BeamMP-Server/bin/Server.log               # Idem
USER_CHANGE=www-data

# Chemins principaux
BEAMMP_FOLDER=/home/USER/BeamMP-Server/bin/Resources/               # Répertoire racine des mods
PATH_RESOURCES=/home/USER/BeamMP-Server/bin/Resources/              # identique à BEAMMP_FOLDER
BASE_PATH=/var/www/beammp-web                                       # Chemin d’installation du site web
SERVERCONFIG_PATH=/home/USER/BeamMP-Server/bin/ServerConfig.toml    # Chemin vers la config serveur

# Webhooks Discord
DISCORD_WEBHOOK_MOD_UPLOAD=https://discord.com/api/webhooks/xxxx   # Lien vers le webhook du salon d’upload
DISCORD_WEBHOOK_SERVER_RESTART=https://discord.com/api/webhooks/xxx # Lien vers le webhook pour le statut serveur

# Autres paramètres
BASE_URL=http://192.xxx.xxx.xxx     # IP locale ou domaine
LANG_DEFAULT=fr                     # Langue par défaut : fr, en, de
```

---

## 5️⃣ Bot Discord

### Déplacement et configuration

```bash
cp -R bot ~/ 
nano ~/bot/config.json
```

Renseignez :

* identifiants base de donnée
* lien du webhook Discord pour la gestion des connexions

---

## 6️⃣ Scripts

### Déplacement et configuration

```bash
cp -R scripts ~/ 
nano ~/scripts/config.json
```

Puis exécution :

```bash
php ~/scripts/create_user.sh
```

---

## 7️⃣ Dossiers à créer pour mods/maps inactifs

```bash
mkdir -p ~/BeamMP-Server/bin/Resources/inactive_maps
mkdir -p ~/BeamMP-Server/bin/Resources/inactive_mods
```

---

## 8️⃣ Droits Unix

### Groupe et accès Apache

```bash
#Remplacer USER par le votre
sudo adduser www-data USER 
```

### Accès au `ServerConfig.toml`

```bash
# Autoriser www-data à lire le dossier personnel de l'utilisateur
sudo chmod g+rx /home/USER        # Remplacer USER par votre nom d’utilisateur système

# Autoriser www-data à lire le dossier BeamMP-Server
sudo chmod g+rx /home/USER/BeamMP-Server

# Autoriser www-data à écrire dans le dossier /bin (pour les fichiers générés/modifiés)
sudo chmod g+wx /home/USER/BeamMP-Server/bin

# Autoriser www-data à lire et écrire le fichier ServerConfig.toml
sudo chmod g+rw /home/USER/BeamMP-Server/bin/ServerConfig.toml
```

### Accès aux ressources mods/maps

```bash
# Autoriser le groupe (www-data) à écrire dans tous les fichiers/dossiers de Resources
sudo chmod -R g+w /home/USER/BeamMP-Server/bin/Resources/  # Remplacer USER par votre nom d’utilisateur système

# Changer le groupe propriétaire en www-data pour tous les fichiers/dossiers
sudo chgrp -R www-data /home/USER/BeamMP-Server/bin/Resources/

# Activer le bit "setgid" pour que les nouveaux fichiers héritent du groupe www-data
sudo chmod g+s /home/USER/BeamMP-Server/bin/Resources/
```

---

## 9️⃣ Services systemd

### Adaptation et activation

```bash
#Modifier les deux .services en fonction de votre utilisateur système
sudo cp services/*.service /etc/systemd/system/
sudo systemctl daemon-reexec
sudo systemctl daemon-reload
sudo systemctl enable BeamMP.service joueurs.service
sudo systemctl start BeamMP.service joueurs.service
```

### Autorisation restart Apache

```bash
sudo visudo
```

Ajouter :

```
www-data ALL=NOPASSWD: /bin/systemctl restart BeamMP.service
www-data ALL=NOPASSWD: /bin/systemctl restart joueurs.service
```

---

## 🔚 Finalisation

* Test de l'accès web
* Contrôle du status des services :

```bash
sudo systemctl status BeamMP.service joueurs.service
```

🎉 BeamMP-web est maintenant fonctionnel !
