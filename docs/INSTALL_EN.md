# 📦 Installation of BeamMP-web + Bot + Scripts (Apache + MariaDB)

This guide walks you step-by-step through installing the BeamMP-web project (server management interface + Discord bot + sync scripts) on a Debian/Ubuntu server using Apache and MariaDB.

---

## 1️⃣ Requirements

```bash
sudo apt update && sudo apt install -y \
  apache2 mariadb-server php php-mysql php-curl php-xml php-mbstring \
  unzip curl git composer
```

---

## 2️⃣ Clone the repository

```bash
git clone https://github.com/Zyphro3D/BeamMP-web.git
cd BeamMP-web
```

---

## 3️⃣ Website setup

### Copy files and install dependencies

Copy the `site/beammp-web` folder to `/var/www/beammp-web`, then install PHP dependencies with Composer:

```bash
sudo cp -r site/beammp-web /var/www/beammp-web
cd /var/www/beammp-web
sudo composer install
```

### Enable Apache site

```bash
cd ~/BeamMP-web
sudo cp config/beammp-web.conf /etc/apache2/sites-available/
sudo a2dissite 000-default.conf  # If the default site is still enabled
sudo a2ensite beammp-web.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

### PHP configuration

Edit `/etc/php/*/apache2/php.ini` with:

```ini
upload_max_filesize = 10G
post_max_size = 10G
max_input_time = 300
max_execution_time = 300
memory_limit = 256M
```

Then restart Apache:

```bash
sudo systemctl restart apache2
```

---

## 4️⃣ Database setup

### Secure MariaDB

```bash
sudo mysql_secure_installation  # Follow the prompts
```

### Import schema and create user

```bash
sudo mysql -u root -p < sql/beammp_db.sql
sudo mysql -u root -p
```

In the MariaDB shell:

```sql
CREATE USER 'USER'@'localhost' IDENTIFIED BY 'PASSWORD';  -- Replace USER and PASSWORD
GRANT ALL PRIVILEGES ON beammp_db.beammp TO 'USER'@'localhost';
GRANT ALL PRIVILEGES ON beammp_db.beammp_users TO 'USER'@'localhost';
GRANT ALL PRIVILEGES ON beammp_db.users TO 'USER'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 🗞️ `.env` file (application configuration)

Create or edit `/var/www/beammp-web/.env`:

```bash
sudo nano /var/www/beammp-web/.env
```

Here is the template:

```dotenv
# Local database
DB_HOST=localhost
DB_NAME=beammp_db
DB_USER=xxxxxx  # Replace with DB user
DB_PASSWORD=xxxxxx  # Replace with DB password

# Remote config paths
CONFIG_REMOTE_PATH=/home/xxxxxxx/BeamMP-Server/bin/ServerConfig.toml  # Replace xxxxxx with your system username
LOG_FILE_PATH=/home/xxxxxxx/BeamMP-Server/bin/Server.log
USER_CHANGE=www-data

# Paths
BEAMMP_FOLDER=/home/xxxxxxx/BeamMP-Server/bin/Resources/
PATH_RESOURCES=/home/xxxxxxx/BeamMP-Server/bin/Resources/
BASE_PATH=/var/www/beammp-web
SERVERCONFIG_PATH=/home/xxxxxxx/BeamMP-Server/bin/ServerConfig.toml

# Discord webhooks
DISCORD_WEBHOOK_MOD_UPLOAD=https://discord.com/api/webhooks/xxxx  # Replace with upload webhook
DISCORD_WEBHOOK_SERVER_RESTART=https://discord.com/api/webhooks/xxxx  # Replace with server info webhook

# Other
BASE_URL=http://192.xxx.xxx.xxx  # Replace with your IP
LANG_DEFAULT=fr  # or 'en' for English
```

---

## 5️⃣ Discord Bot

### Move and configure

```bash
cp -R bot ~/ 
nano ~/bot/config.json
```

Configure:

* Database credentials
* Discord webhook for user connection tracking

---

## 6️⃣ Scripts

### Move and configure

```bash
cp -R scripts ~/ 
nano ~/scripts/config.json
```

Then execute:

```bash
php ~/scripts/create_user.sh
```

---

## 7️⃣ Create folders for inactive mods/maps

```bash
mkdir -p ~/BeamMP-Server/bin/Resources/inactive_maps
mkdir -p ~/BeamMP-Server/bin/Resources/inactive_mods
```

---

## 8️⃣ Unix Permissions

### Apache group access

```bash
sudo adduser www-data USER  # Replace USER with your system username
```

### Access to `ServerConfig.toml`

```bash
sudo chmod g+rx /home/xxxx  # Replace with your username
sudo chmod g+rx /home/xxxx/BeamMP-Server
sudo chmod g+wx /home/xxxx/BeamMP-Server/bin
sudo chmod g+rw /home/xxxx/BeamMP-Server/bin/ServerConfig.toml
```

### Access to mod/map resources

```bash
sudo chmod -R g+w /home/xxxx/BeamMP-Server/bin/Resources/
sudo chgrp -R www-data /xxxx/beammp/BeamMP-Server/bin/Resources/
sudo chmod g+s /home/xxxx/BeamMP-Server/bin/Resources/
```

---

## 9️⃣ systemd Services

### Configure and enable

```bash
sudo cp services/*.service /etc/systemd/system/  # Edit the .service files for your username
sudo systemctl daemon-reexec
sudo systemctl daemon-reload
sudo systemctl enable BeamMP.service joueurs.service
sudo systemctl start BeamMP.service joueurs.service
```

### Allow restart without password

```bash
sudo visudo
```

Add:

```
www-data ALL=NOPASSWD: /bin/systemctl restart BeamMP.service
www-data ALL=NOPASSWD: /bin/systemctl restart joueurs.service
```

---

## 🔚 Final Steps

* Test website access
* Check service status:

```bash
sudo systemctl status BeamMP.service joueurs.service
```

🎉 BeamMP-web is now up and running!
