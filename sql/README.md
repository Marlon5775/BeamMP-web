# 🛠️ Installation et configuration de la base de données MariaDB (BeamMP)  
# 🛠️ MariaDB Database Setup and Configuration (BeamMP)
# 🛠️ Installation und Konfiguration der MariaDB-Datenbank (BeamMP)

Ce guide décrit étape par étape comment installer, sécuriser, configurer et utiliser la base de données pour le projet BeamMP-Web + Bot Discord.  
This guide explains step-by-step how to install, secure, configure, and use the database for the BeamMP-Web + Discord Bot project.
Diese Anleitung beschreibt Schritt für Schritt, wie man die Datenbank für das BeamMP-Web + Discord Bot-Projekt installiert, sichert, konfiguriert und verwendet.

---

## 📦 – Installer MariaDB Server / Install MariaDB Server / Installieren des MariaDB-Servers

```bash
sudo apt update
sudo apt install mariadb-server -y
```

---

## 🔐 – Sécuriser l’installation MariaDB / Secure MariaDB installation / MariaDB-Installation sichern

Lance le script de sécurisation :  
Run the secure installation script:
Starten Sie das Sicherungsskript:

```bash
sudo mysql_secure_installation
```

Réponds aux questions comme suit (recommandé) :  
Answer the questions as follows (recommended):
Beantworten Sie die Fragen wie folgt (empfohlen):

```
Enter current password for root (enter for none): [Enter]
Set root password? [Y/n]: Y
New password: ********
Remove anonymous users? [Y/n]: Y
Disallow root login remotely? [Y/n]: Y
Remove test database and access to it? [Y/n]: Y
Reload privilege tables now? [Y/n]: Y
```

---

## 👤 – Créer un utilisateur pour l'application / Create a user for the application / Einen Benutzer für die Anwendung erstellen

Connecte-toi à MariaDB en root :  
Log into MariaDB as root:
Loggen Sie sich bei MariaDB als root ein:

```bash
sudo mariadb -u root -p
```

Dans le shell MariaDB, exécute :  
Inside the MariaDB shell, run (replace password with your own):
Im Shell von MariaDB führen Sie Folgendes aus (ersetzen Sie das Passwort durch Ihr eigenes):

```sql
CREATE DATABASE beammp_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

CREATE USER 'beammp_web'@'localhost' IDENTIFIED BY 'motdepassefort';

GRANT ALL PRIVILEGES ON beammp_db.* TO 'beammp_web'@'localhost';

FLUSH PRIVILEGES;
EXIT;
```

⚠️ Le nom d'utilisateur (`beammp_web`) et le mot de passe (`motdepassefort`) seront utilisés dans :  
⚠️ The username (`beammp_web`) and password (`motdepassefort`) will be used in:
⚠️ Der Benutzername (`beammp_web`) und das Passwort (`motdepassefort`) werden in folgenden Dateien verwendet:

### 📄 Fichier `.env` du site web / Web app `.env` file / Web-App `.env`-Datei:

```env
DB_HOST=localhost
DB_NAME=beammp_db
DB_USER=beammp_web
DB_PASSWORD=motdepassefort
```

### 📄 Fichier `config.json` du script Python / Python bot `config.json` file / Python-Bot `config.json`-Datei:

```json
{
  "host": "localhost",
  "user": "beammp_web",
  "password": "motdepassefort",
  "database": "beammp_db"
}
```

---

## 📥 – Importer la base de données fournie / Import the provided database / Die bereitgestellte Datenbank importieren

Assure-toi d’avoir le fichier `beammp_db.sql` dans le dossier `sql/` du projet.  
Make sure the `beammp_db.sql` file is in the project’s `sql/` directory.
Gehe sicher, dass sich die Datei `beammp_db.sql` im Ordner `sql/` des Projekts befindet.

```bash
mysql -u beammp_web -p beammp_db < ~/beammp-web-manager/sql/beammp_db.sql
```

---

## 📊 Structure de la base / Database structure / Datenbankstruktur

### 📁 Table `beammp`
Contient les mods, véhicules, maps.  
Contains mods, vehicles, and maps.
Beinhaltet Mods, Fahrzeuge und Maps.

| Colonne / Column       | Type       | Description                                                                 |
|------------------------|------------|-----------------------------------------------------------------------------|
| id                     | INT        | Identifiant auto-incrémenté / Auto-increment ID / Auto-Inkrement-ID         |
| nom                    | VARCHAR    | Nom du mod / Mod name / Mod-Name                                            |
| description            | TEXT       | Description du mod / Mod description / Mod-Beschreibung                     |
| type                   | ENUM       | `mod`, `vehicule`, ou `map`                                                 |
| chemin                 | VARCHAR    | Fichier `.zip` / ZIP file name / ZIP-Dateiname                              |
| image                  | VARCHAR    | Chemin vers l’image / Image path / Bildpfad                                 |
| id_map                 | VARCHAR    | ID interne de la map / Internal map ID / Interne Map-ID                     |
| mod_actif              | TINYINT    | 1 = activé, 0 = désactivé / active inactive / aktiv inaktiv                 |
| map_officielle         | TINYINT    | 1 = officielle / official map / offizielle Karte                            |
| map_active             | TINYINT    | 1 = sélectionnée / selected / ausgewählt                                    |
| vehicule_type          | VARCHAR    | `car`, `truck`, etc. (si applicable) / (if applicable)                      |
| archive                | VARCHAR    | Nom d’origine de l’archive / Original ZIP name / Ursprünglicher ZIP-Name    |
| link                   | VARCHAR    | Lien direct (optionnel) / Optional download link / Direkter Link (optional) |
| date                   | DATETIME   | Date d’ajout / Added date / Hinzugefügt am                                  |

---

### 📁 Table `beammp_users`  
Gérée par le bot Python – historique des connexions.  
Managed by the Python bot – connection history.
Vom Python-Bot verwaltet – Verbindungsverlauf.

| Colonne / Column   | Type     | Description                                                                     |
|--------------------|----------|---------------------------------------------------------------------------------|
| id                 | INT      | Identifiant utilisateur / User ID / Benutzer-ID                                 |
| username           | VARCHAR  | Nom en jeu / In-game name / Benutzername                                        |
| connection_count   | INT      | Nombre de connexions / Connection count / Verbindungsanzahl                     |
| last_connect       | DATETIME | Dernière connexion / Last login / Letzter Login                                 |
| last_disconnect    | DATETIME | Dernière déconnexion / Last logout / Letzter Logout                             |
| total_time         | INT      | Temps total en secondes / Total playtime (seconds) / Gesamtspielzeit (Sekunden) |

---

## ✅ Étape 5 – Vérifications / Verification / Überprüfungen

Tu peux tester la connexion manuellement :  
You can test the connection manually:
Sie können die Verbindung manuell testen:

```bash
mysql -u beammp_web -p beammp_db
```

Puis, dans MariaDB / Then inside MariaDB / Dann in MariaDB:

```sql
SHOW TABLES;
SELECT * FROM beammp LIMIT 5;
```

---

## 💡 Remarques / Notes / Hinweise

- Pour toute modification du mot de passe ou de l’utilisateur, **mets à jour les fichiers `.env` et `config.json`**.  
If you change the password or username, **update both `.env` and `config.json`**.
Wenn Sie das Passwort oder den Benutzernamen ändern, **aktualisieren Sie sowohl `.env` als auch `config.json`**.

- Redémarre les services concernés si besoin :  
Restart affected services if needed:
Starten Sie die betroffenen Dienste bei Bedarf neu:

```bash
sudo systemctl restart apache2
```
