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

### Copie des fichiers et installation des dépendances

Copiez le dossier `site/beammp-web` dans `/var/www/beammp-web`, puis installez les dépendances PHP avec Composer :

```bash
sudo cp -r site/beammp-web /var/www/beammp-web
cd /var/www/beammp-web
sudo composer install
```

### Activation du site Apache

```bash
cd ~/BeamMP-web
sudo cp config/beammp-web.conf /etc/apache2/sites-available/
sudo a2dissite 000-default.conf (si le site par default est toujours actif)
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
sudo mysql_secure_installation (suivez les informations jusqu'a la fin)
```

### Import du schéma et droits

```bash
sudo mysql -u root -p < sql/beammp_db.sql
sudo mysql -u root -p
```

Dans le shell MariaDB :

```sql
CREATE USER 'USER'@'localhost' IDENTIFIED BY 'PASSWORD'; (modifier l'USER et le PASSWORD, gardez le ça va servir)
GRANT ALL PRIVILEGES ON beammp_db.beammp TO 'USER'@'localhost'; (remplacer USER par celui créé au dessus)
GRANT ALL PRIVILEGES ON beammp_db.beammp_users TO 'USER'@'localhost'; (remplacer USER par celui créé au dessus)
GRANT ALL PRIVILEGES ON beammp_db.users TO 'USER'@'localhost'; (remplacer USER par celui créé au dessus)
FLUSH PRIVILEGES;
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
DB_USER=xxxxxx (à remplacer par l'USER de la base de donnée)
DB_PASSWORD=xxxxxxx (à remplacer par le PASSWORD de la base de donnée)

# Chemin vers le fichier de configuration distant
CONFIG_REMOTE_PATH=/home/xxxxxxx/BeamMP-Server/bin/ServerConfig.toml (Remplacer les xxxx par votre utilisateur système)
LOG_FILE_PATH=/home/xxxxxxx/BeamMP-Server/bin/Server.log (Remplacer les xxxx par votre utilisateur système)
USER_CHANGE=www-data

# Chemins principaux
BEAMMP_FOLDER=/home/xxxxxxx/BeamMP-Server/bin/Resources/ (Remplacer les xxxx par votre utilisateur système)
PATH_RESOURCES=/home/xxxxxxx/BeamMP-Server/bin/Resources/ (Remplacer les xxxx par votre utilisateur système)
BASE_PATH=/var/www/beammp-web
SERVERCONFIG_PATH=/home/xxxxxxx/BeamMP-Server/bin/ServerConfig.toml (Remplacer les xxxx par votre utilisateur système)

# Webhooks Discord
DISCORD_WEBHOOK_MOD_UPLOAD=https://discord.com/api/webhooks/xxxx (remplacer par le lien de votre webhook pour le salon upload)
DISCORD_WEBHOOK_SERVER_RESTART=https://discord.com/api/webhooks/xxxx (remplacer par le lien de votre webhook pour le salon information serveur)

# Autres
BASE_URL=http://192.xxx.xxx.xxx (à remplacer par votre ip)
LANG_DEFAULT=fr  # ou 'en' pour anglais
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
sudo adduser www-data USER ( à remplacer par l'utilisateur système)
```

### Accès au `ServerConfig.toml`

```bash
sudo chmod g+rx /home/xxxx (Remplacer les xxxx par votre utilisateur système)
sudo chmod g+rx /home/xxxx/BeamMP-Server (Remplacer les xxxx par votre utilisateur système)
sudo chmod g+wx /home/xxxx/BeamMP-Server/bin (Remplacer les xxxx par votre utilisateur système)
sudo chmod g+rw /home/xxxx/BeamMP-Server/bin/ServerConfig.toml (Remplacer les xxxx par votre utilisateur système)
```

### Accès aux ressources mods/maps

```bash
sudo chmod -R g+w /home/xxxx/BeamMP-Server/bin/Resources/ (Remplacer les xxxx par votre utilisateur système)
sudo chgrp -R www-data /xxxx/beammp/BeamMP-Server/bin/Resources/ (Remplacer les xxxx par votre utilisateur système)
sudo chmod g+s /home/xxxx/BeamMP-Server/bin/Resources/ (Remplacer les xxxx par votre utilisateur système)
```

---

## 9️⃣ Services systemd

### Adaptation et activation

```bash
sudo cp services/*.service /etc/systemd/system/ (Modifier les 2 .services en fonction de votre utilisateur système)
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
