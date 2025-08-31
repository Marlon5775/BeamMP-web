
# 🚀 Installation automatique BeamMP-Web (multi-instances)

> Déployez BeamMP-Web, son interface web, son bot Discord et la gestion multi-serveurs en quelques minutes sur un environnement Linux.
> 
> ✅ Compatible (testé) : Debian 12 AMD64.

> **Support natif multi-instances BeamMP** (gestion centralisée, comptes admin, sécurité).

---

## 1️⃣ Prérequis système

Utiliser l'utilisateur système dédié à BeamMP-Server :

```bash
sudo usermod -aG sudo votre_utilisateur
```

Installer toutes les dépendances :

```bash
apache2 mariadb-server php php-mysql php-curl php-xml php-mbstring python3 python3-venv python3-pip unzip curl git composer jq php-gd libwebp-dev
```

---

## 2️⃣ Création de l’utilisateur SQL

Connectez-vous à MariaDB :

```bash
sudo mysql
```

Puis entrez (adaptez `user_db` et `password_db`) :

```sql
CREATE USER 'user_db'@'localhost' IDENTIFIED BY 'password_db';
GRANT CREATE ON *.* TO 'user_db'@'localhost';
GRANT ALL PRIVILEGES ON beammp_db.* TO 'user_db'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 3️⃣ Clonage et configuration du projet

Clonez le dépôt puis éditez la config :

```bash
git clone https://github.com/Zyphro3D/BeamMP-web.git
cd BeamMP-web
```

Modifiez le fichier `install_config.json` pour y renseigner vos propres valeurs :
```bash
nano install_config.json
```

**Exemple de fichier `install_config.json`** :

```json
{
  "db_user": "user_db",          // Nom d'utilisateur MariaDB/MySQL créé plus haut
  "db_pass": "password_db",      // Mot de passe associé à l'utilisateur
  "user_system": "votre_user",   // Utilisateur système identique à BeamMP-Server
  "lang": "fr",                  // liste des langue disponible dans File_Install/lang/
  "ip": "192.168.XX.XXX",        // Adresse IP locale du serveur web
  "instances": [
    {
      "name": "Instance 1",      // Nom affiché pour l’instance web
      "port": "8081",            // Port HTTP à utiliser
      "root_beammp": "/chemin/vers/BeamMP-Server1"   // Dossier racine du serveur BeamMP (complet)
    },
    {
      "name": "Instance 2",
      "port": "8082",
      "root_beammp": "/chemin/vers/BeamMP-Server2"
    }
    // Ajoutez ou supprimez des blocs selon le nombre de serveurs BeamMP à gérer
  ]
}
```

---

## 4️⃣ Lancement de l’installation automatisée

```bash
chmod +x Install.sh
sudo ./Install.sh
```

**Pendant l’installation :**
- Il vous sera demandé **si vous souhaitez appliquer automatiquement les droits Linux** (chmod/chown/usermod) ou générer un fichier pour les exécuter à la main :  
  > *Sécurité : privilégiez le mode manuel en environnement pro/sensible, auto pour une VM ou une install locale rapide.*
- **Vous devrez choisir un identifiant, un mot de passe et le rôle** de votre compte web principal :
  - **Admin** : gestion courante, mais ne peut PAS modifier le `ServerConfig.toml`
  - **SuperAdmin** : accès total à toutes les fonctionnalités

*Chaque instance web sera accessible à la fin avec le compte créé.*

---

## 5️⃣ Accès, statut & maintenance

- **Vérifier les services :**
  ```bash
  sudo systemctl status beammp-[name].service players-[name].service
  ```
- **Accès web aux interfaces :**
  ```
  http://192.168.X.XXX:8081
  http://192.168.X.XXX:8082
  ```
- **Consulter les logs. [name] = nom de l'instance:**
  ```bash
  sudo journalctl -u beammp-[name].service
  sudo journalctl -u players-[name].service
  ```
- **Important :**
  - **Complétez les webhooks Discord** dans chaque fichier `.env` du site web (`/var/www/beammpweb-[instance]/`)  
    et dans le `config.json` de chaque instance pour le bot players.out (`[Dossier_Parent_du_clone]/[instance]/`).

---

- Pour toute question/signalement :  
  [https://github.com/Zyphro3D/BeamMP-web/issues](https://github.com/Zyphro3D/BeamMP-web/issues)


