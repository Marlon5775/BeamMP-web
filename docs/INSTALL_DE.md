# 📦 Installation von BeamMP-web + Bot + Skripte (Apache + MariaDB)

Diese Anleitung führt Sie Schritt für Schritt durch die Installation des BeamMP-web-Projekts (Serververwaltungsoberfläche + Discord-Bot + Synchronisierungsskripte) auf einem Debian/Ubuntu-Server mit Apache und MariaDB.

---


## 1️⃣ Voraussetzungen

```bash
sudo apt update && sudo apt install -y \
  apache2 mariadb-server php php-mysql php-curl php-xml php-mbstring \
  unzip curl git composer
```

---

## 2️⃣ Repository klonen

```bash
git clone https://github.com/Zyphro3D/BeamMP-web.git
cd BeamMP-web
```

---

## 3️⃣ Website einrichten

### Dateien verlinken und Abhängigkeiten installieren

Erstelle einen symbolischen Link von `site/beammp-web` nach `/var/www/beammp-web` und installiere dann die PHP-Abhängigkeiten mit Composer:

```bash
sudo ln -s ~/BeamMP-web/site/beammp-web /var/www/beammp-web
cd site/beammp-web
sudo composer install
```

### Apache-Seite aktivieren

```bash
cd ~/BeamMP-web
sudo cp config/beammp-web.conf /etc/apache2/sites-available/
sudo a2dissite 000-default.conf  # Falls die Standardseite noch aktiviert ist
sudo a2ensite beammp-web.conf
sudo a2enmod rewrite
sudo systemctl reload apache2
```

### PHP-Konfiguration

Bearbeite `/etc/php/*/apache2/php.ini` und setze folgende Werte:

```ini
upload_max_filesize = 10G
post_max_size = 10G
max_input_time = 300
max_execution_time = 300
memory_limit = 256M
```

Starte dann Apache neu:

```bash
sudo systemctl restart apache2
```

---

## 4️⃣ Datenbank einrichten

### MariaDB absichern

```bash
sudo mysql_secure_installation  # Den Anweisungen folgen
```

### Schema importieren und Benutzer anlegen

```bash
sudo mysql -u root -p < sql/beammp_db.sql
sudo mysql -u root -p
```

Im MariaDB-Terminal:

```sql
CREATE USER 'BENUTZER'@'localhost' IDENTIFIED BY 'PASSWORT';  -- BENUTZER und PASSWORT ersetzen
GRANT ALL PRIVILEGES ON beammp_db.beammp TO 'BENUTZER'@'localhost';
GRANT ALL PRIVILEGES ON beammp_db.beammp_users TO 'BENUTZER'@'localhost';
GRANT ALL PRIVILEGES ON beammp_db.users TO 'BENUTZER'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 🗞️ `.env`-Datei (Anwendungskonfiguration)

Erstelle oder bearbeite `/var/www/beammp-web/.env`:

```bash
sudo nano /var/www/beammp-web/.env
```

Hier ist die Vorlage:

```dotenv
# Lokale Datenbank
DB_HOST=localhost
DB_NAME=beammp_db
DB_USER=xxxxxx  # Ersetzen durch den DB-Benutzer
DB_PASSWORD=xxxxxx  # Ersetzen durch das DB-Passwort

# Remote-Konfigurationspfade
CONFIG_REMOTE_PATH=/home/xxxxxxx/BeamMP-Server/bin/ServerConfig.toml  # Ersetze xxxxxx durch deinen Systembenutzernamen
LOG_FILE_PATH=/home/xxxxxxx/BeamMP-Server/bin/Server.log
USER_CHANGE=www-data

# Pfade
BEAMMP_FOLDER=/home/xxxxxxx/BeamMP-Server/bin/Resources/
PATH_RESOURCES=/home/xxxxxxx/BeamMP-Server/bin/Resources/
BASE_PATH=/var/www/beammp-web
SERVERCONFIG_PATH=/home/xxxxxxx/BeamMP-Server/bin/ServerConfig.toml

# Discord Webhooks
DISCORD_WEBHOOK_MOD_UPLOAD=https://discord.com/api/webhooks/xxxx  # Ersetze durch Upload-Webhook
DISCORD_WEBHOOK_SERVER_RESTART=https://discord.com/api/webhooks/xxxx  # Ersetze durch Server-Info-Webhook

# Sonstiges
BASE_URL=http://192.xxx.xxx.xxx  # Ersetze durch deine IP
LANG_DEFAULT=fr  # oder 'en' für Englisch oder 'de' für Deutsch
```

---

## 5️⃣ Discord-Bot

### Verschieben und konfigurieren

```bash
cp -R bot ~/ 
nano ~/bot/config.json
```

Konfiguriere:

* Datenbank-Zugangsdaten
* Discord-Webhook für Benutzerverfolgung

---

## 6️⃣ Skripte

### Verschieben und konfigurieren

```bash
cp -R scripts ~/ 
nano ~/scripts/config.json
```

Dann ausführen:

```bash
php ~/scripts/create_user.sh
```

---

## 7️⃣ Ordner für inaktive Mods/Maps erstellen

```bash
mkdir -p ~/BeamMP-Server/bin/Resources/inactive_maps
mkdir -p ~/BeamMP-Server/bin/Resources/inactive_mods
```

---

## 8️⃣ Unix-Berechtigungen

### Apache-Gruppenzugriff

```bash
sudo adduser www-data BENUTZER  # Ersetze BENUTZER durch deinen Systembenutzernamen
```

### Zugriff auf `ServerConfig.toml`

```bash
sudo chmod g+rx /home/xxxx  # Ersetze durch deinen Benutzernamen
sudo chmod g+rx /home/xxxx/BeamMP-Server
sudo chmod g+wx /home/xxxx/BeamMP-Server/bin
sudo chmod g+rw /home/xxxx/BeamMP-Server/bin/ServerConfig.toml
```

### Zugriff auf Mod/Map-Ressourcen

```bash
sudo chmod -R g+w /home/xxxx/BeamMP-Server/bin/Resources/
sudo chgrp -R www-data /home/xxxx/beammp/BeamMP-Server/bin/Resources/
sudo chmod g+s /home/xxxx/BeamMP-Server/bin/Resources/
```

---

## 9️⃣ systemd-Dienste

### Konfigurieren und aktivieren

```bash
sudo cp services/*.service /etc/systemd/system/  # Bearbeite die .service-Dateien für deinen Benutzernamen
sudo systemctl daemon-reexec
sudo systemctl daemon-reload
sudo systemctl enable BeamMP.service joueurs.service
sudo systemctl start BeamMP.service joueurs.service
```

### Neustart ohne Passwort erlauben

```bash
sudo visudo
```

Füge hinzu:

```
www-data ALL=NOPASSWD: /bin/systemctl restart BeamMP.service
www-data ALL=NOPASSWD: /bin/systemctl restart joueurs.service
```

---

## 🔚 Abschließende Schritte

* Website-Zugriff testen
* Dienststatus prüfen:

```bash
sudo systemctl status BeamMP.service joueurs.service
```

🎉 BeamMP-web ist jetzt einsatzbereit!
