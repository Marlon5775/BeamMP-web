# 🛠️ Installation et configuration de la base de données MariaDB (BeamMP)  
# 🛠️ MariaDB Database Setup and Configuration (BeamMP)

Ce guide décrit étape par étape comment installer, sécuriser, configurer et utiliser la base de données pour le projet BeamMP-Web + Bot Discord.  
This guide explains step-by-step how to install, secure, configure, and use the database for the BeamMP-Web + Discord Bot project.

---

## 📦 Étape 1 – Installer MariaDB Server  
## 📦 Step 1 – Install MariaDB Server

```bash
sudo apt update
sudo apt install mariadb-server -y
```

---

## 🔐 Étape 2 – Sécuriser l’installation MariaDB  
## 🔐 Step 2 – Secure MariaDB installation

Lance le script de sécurisation :  
Run the secure installation script:

```bash
sudo mysql_secure_installation
```

Réponds aux questions comme suit (recommandé) :  
Answer the questions as follows (recommended):

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

## 👤 Étape 3 – Créer un utilisateur pour l'application  
## 👤 Step 3 – Create a user for the application

Connecte-toi à MariaDB en root :  
Log into MariaDB as root:

```bash
sudo mariadb -u root -p
```

Dans le shell MariaDB, exécute :  
Inside the MariaDB shell, run (replace password with your own):

```sql
CREATE DATABASE beammp_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

CREATE USER 'beammp_web'@'localhost' IDENTIFIED BY 'motdepassefort';

GRANT ALL PRIVILEGES ON beammp_db.* TO 'beammp_web'@'localhost';

FLUSH PRIVILEGES;
EXIT;
```

⚠️ Le nom d'utilisateur (`beammp_web`) et le mot de passe (`motdepassefort`) seront utilisés dans :  
⚠️ The username (`beammp_web`) and password (`motdepassefort`) will be used in:

### 📄 Fichier `.env` du site web / Web app `.env` file:

```env
DB_HOST=localhost
DB_NAME=beammp_db
DB_USER=beammp_web
DB_PASSWORD=motdepassefort
```

### 📄 Fichier `config.json` du script Python / Python bot `config.json` file:

```json
{
  "host": "localhost",
  "user": "beammp_web",
  "password": "motdepassefort",
  "database": "beammp_db"
}
```

---

## 📥 Étape 4 – Importer la base de données fournie  
## 📥 Step 4 – Import the provided database

Assure-toi d’avoir le fichier `beammp_db.sql` dans le dossier `sql/` du projet.  
Make sure the `beammp_db.sql` file is in the project’s `sql/` directory.

```bash
mysql -u beammp_web -p beammp_db < ~/beammp-web-manager/sql/beammp_db.sql
```

---

## 📊 Structure de la base / Database structure

### 📁 Table `beammp`
Contient les mods, véhicules, maps.  
Contains mods, vehicles, and maps.

| Colonne / Column      | Type       | Description                          |
|------------------------|------------|--------------------------------------|
| id                     | INT        | Identifiant auto-incrémenté / Auto-increment ID |
| nom                    | VARCHAR    | Nom du mod / Mod name                |
| description            | TEXT       | Description du mod                   |
| type                   | ENUM       | `mod`, `vehicule`, ou `map`          |
| chemin                 | VARCHAR    | Fichier `.zip` / ZIP file name       |
| image                  | VARCHAR    | Chemin vers l’image / Image path     |
| id_map                 | VARCHAR    | ID interne de la map                 |
| mod_actif              | TINYINT    | 1 = activé, 0 = désactivé / active   |
| map_officielle         | TINYINT    | 1 = officielle / official map        |
| map_active             | TINYINT    | 1 = sélectionnée / selected          |
| vehicule_type          | VARCHAR    | `car`, `truck`, etc. (si applicable) |
| archive                | VARCHAR    | Nom d’origine de l’archive / Original ZIP name |
| link                   | VARCHAR    | Lien direct (optionnel) / Optional download link |
| date                   | DATETIME   | Date d’ajout / Added date            |

---

### 📁 Table `beammp_users`  
Gérée par le bot Python – historique des connexions.  
Managed by the Python bot – connection history.

| Colonne / Column   | Type     | Description                          |
|--------------------|----------|--------------------------------------|
| id                 | INT      | Identifiant utilisateur / User ID    |
| username           | VARCHAR  | Nom en jeu / In-game name            |
| connection_count   | INT      | Nombre de connexions / Connection count |
| last_connect       | DATETIME | Dernière connexion / Last login      |
| last_disconnect    | DATETIME | Dernière déconnexion / Last logout   |
| total_time         | INT      | Temps total en secondes / Total playtime (seconds) |

---

## ✅ Étape 5 – Vérifications  
## ✅ Step 5 – Verification

Tu peux tester la connexion manuellement :  
You can test the connection manually:

```bash
mysql -u beammp_web -p beammp_db
```

Puis, dans MariaDB / Then inside MariaDB:

```sql
SHOW TABLES;
SELECT * FROM beammp LIMIT 5;
```

---

## 🧩 Liens utiles / Useful links

- 📘 [Documentation MariaDB](https://mariadb.com/kb/en/documentation/)
- 📘 [Documentation PHP PDO](https://www.php.net/manual/fr/book.pdo.php)
- 📄 Script bot connexion/déconnexion *(README séparé)* / Login tracking bot script *(separate README)*

---

## 💡 Remarques / Notes

- Pour toute modification du mot de passe ou de l’utilisateur, **mets à jour les fichiers `.env` et `config.json`**.  
If you change the password or username, **update both `.env` and `config.json`**.

- Redémarre les services concernés si besoin :  
Restart affected services if needed:

```bash
sudo systemctl restart apache2
```
