# 📦 Installation of BeamMP-web + Bot + Scripts (Apache + MariaDB)

This tutorial guides you step by step to install the BeamMP-web project (server management interface + Discord bot + synchronization scripts) on a Debian/Ubuntu server with Apache and MariaDB.

---

## 1️⃣ Prerequisites

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

## 3️⃣ Web site setup

### Create symbolic link and install PHP dependencies

Create a symbolic link between the folder `site/beammp-web` and `/var/www/beammp-web`, then install PHP dependencies with Composer:

```bash
sudo ln -s ~/BeamMP-web/site/beammp-web /var/www/beammp-web
cd site/beammp-web/
sudo composer install
```

### Apache site activation

```bash
cd ~/BeamMP-web
sudo cp config/beammp-web.conf /etc/apache2/sites-available/
# (Optional) Disable the default Apache site if still active
sudo a2dissite 000-default.conf
sudo a2ensite beammp-web.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

### PHP configuration

Edit `/etc/php/*/apache2/php.ini` and modify:

```ini
upload_max_filesize = 10G
post_max_size = 10G
max_input_time = 300
max_execution_time = 300
memory_limit = 256M
```

Restart Apache:

```bash
sudo systemctl restart apache2
```

---

## 4️⃣ Database

### MariaDB hardening

```bash
sudo mysql_secure_installation
```

### Import schema and assign rights

```bash
sudo mysql -u root -p < sql/beammp_db.sql
sudo mysql -u root -p
```

In the MariaDB shell:

```sql
-- Create the user (replace USER and PASSWORD with your own)
CREATE USER 'USER'@'localhost' IDENTIFIED BY 'PASSWORD';

-- Grant privileges (replace USER accordingly)
GRANT ALL PRIVILEGES ON beammp_db.beammp TO 'USER'@'localhost';
GRANT ALL PRIVILEGES ON beammp_db.beammp_users TO 'USER'@'localhost';
GRANT ALL PRIVILEGES ON beammp_db.users TO 'USER'@'localhost';

-- Apply changes
FLUSH PRIVILEGES;

-- Exit MariaDB
EXIT;
```

---

## 🗞️ `.env` File (App Configuration)

Create or edit the file `/var/www/beammp-web/.env`:

```bash
sudo nano /var/www/beammp-web/.env
```

Here's the template:

```dotenv
# Local database
DB_HOST=localhost
DB_NAME=beammp_db
DB_USER=USER         # Replace USER with your MariaDB user
DB_PASSWORD=PASSWORD # Replace with the user's password

# BeamMP server paths
CONFIG_REMOTE_PATH=/home/USER/BeamMP-Server/bin/ServerConfig.toml  # Replace USER with your Linux username
LOG_FILE_PATH=/home/USER/BeamMP-Server/bin/Server.log               # Same
USER_CHANGE=www-data

# Main paths
BEAMMP_FOLDER=/home/USER/BeamMP-Server/bin/Resources/
PATH_RESOURCES=/home/USER/BeamMP-Server/bin/Resources/
BASE_PATH=/var/www/beammp-web
SERVERCONFIG_PATH=/home/USER/BeamMP-Server/bin/ServerConfig.toml

# Discord Webhooks
DISCORD_WEBHOOK_MOD_UPLOAD=https://discord.com/api/webhooks/xxxx   # Replace with webhook for upload channel
DISCORD_WEBHOOK_SERVER_RESTART=https://discord.com/api/webhooks/xxx # Replace with webhook for server status

# Other
BASE_URL=http://192.xxx.xxx.xxx   # Local IP or domain
LANG_DEFAULT=en                   # Default language: fr, en, de
```

---

## 5️⃣ Discord Bot

### Move and configure

```bash
cp -R bot ~/
nano ~/bot/config.json
```

Fill in:

* database credentials
* webhook URL for connection management

---

## 6️⃣ Scripts

### Move and configure

```bash
cp -R scripts ~/
nano ~/scripts/config.json
```

Then run:

```bash
php ~/scripts/create_user.sh
```

---

## 7️⃣ Create folders for inactive mods/maps

```bash
mkdir -p ~/BeamMP-Server/bin/Resources/inactive_map
mkdir -p ~/BeamMP-Server/bin/Resources/inactive_mod
```

---

## 8️⃣ Unix Permissions

### Add Apache to your user group

```bash
# Replace USER with your Linux username
sudo adduser www-data USER
```

### Access to `ServerConfig.toml`

```bash
# Allow www-data to access your home directory
sudo chmod g+rx /home/USER

# Allow read access to BeamMP-Server folder
sudo chmod g+rx /home/USER/BeamMP-Server

# Allow write access to /bin folder
sudo chmod g+wx /home/USER/BeamMP-Server/bin

# Allow read/write on ServerConfig.toml
sudo chmod g+rw /home/USER/BeamMP-Server/bin/ServerConfig.toml
```

### Access to Resources folder

```bash
# Allow write access to Resources content
sudo chmod -R g+w /home/USER/BeamMP-Server/bin/Resources/

# Set group ownership to www-data
sudo chgrp -R www-data /home/USER/BeamMP-Server/bin/Resources/

# Set setgid bit so new files inherit www-data group
sudo chmod g+s /home/USER/BeamMP-Server/bin/Resources/
```

---

## 9️⃣ systemd Services

### Copy, edit, and activate

```bash
# Edit both .service files to match your Linux user
sudo cp services/*.service /etc/systemd/system/
sudo systemctl daemon-reexec
sudo systemctl daemon-reload
sudo systemctl enable BeamMP.service joueurs.service
sudo systemctl start BeamMP.service joueurs.service
```

### Allow Apache to restart services

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

* Test web access
* Check service statuses:

```bash
sudo systemctl status BeamMP.service joueurs.service
```

🎉 BeamMP-web is now up and running!
