# 🧑‍💻 create_user.sh

Ce script permet de **créer un nouvel utilisateur** dans la base de données du projet BeamMP-Web via la ligne de commande.

This script allows you to **create a new user** in the BeamMP-Web database via command line.

---

## 📁 Structure des fichiers / File structure

```
USER/
└── scripts/
    ├── create_user.sh
    └── config.json
```

---

## ⚙️ Prérequis / Requirements

- PHP en mode CLI (`php-cli`)  
- Extension PHP PDO pour MySQL (`php-mysql`)  
- Accès à la base de données

**Installation (Debian/Ubuntu)** :

```bash
sudo apt update
sudo apt install php-cli php-mysql
```

---

## 🔐 Configuration

Les identifiants de connexion à la base sont stockés dans le fichier `config.json` :

All DB credentials are stored in `config.json`:

```json
{
  "db": {
    "host": "localhost",
    "name": "beammp_db",
    "user": "xxxxx", 
    "pass": "xxxxx"
  }
}
```

> 🔒 **Placez ce fichier hors d’un dossier web public** pour éviter toute fuite de données sensibles.  
> **Keep this file outside any public web directory** to prevent exposing sensitive data.

---

## 🚀 Utilisation / How to use

1. Rendre le script exécutable :
   Make the script executable:
   ```bash
   chmod +x create_user.sh
   ```

2. Lancer le script :
   ```bash
   ./create_user.sh
   ```

3. Saisir les informations demandées :
   ```
   Username: your_username
   Password: your_password
   Role (Admin or SuperAdmin): Admin
   ```

4. Si tout est correct :
   ```
   ✅ User 'your_username' created successfully with role 'Admin'.
   ```

---

## ❓ À quoi sert ce script ? / What does this script do?

- Connexion à la base de données (PDO)
- Lecture interactive des données utilisateur (nom, mot de passe, rôle)
- Validation du rôle (`Admin` ou `SuperAdmin`)
- Hachage sécurisé du mot de passe (`password_hash`)
- Insertion dans la table `users`

---

## 📌 Remarques / Notes

- **Les rôles sont sensibles à la casse** (`Admin`, `SuperAdmin`)
- Aucun utilisateur n’est créé si une erreur se produit
- Peut être intégré à des scripts d’installation ou de maintenance

---

## 🛠️ Dépannage / Troubleshooting

- `❌ Configuration file not found` → fichier `config.json` manquant ou mal placé
- `❌ Database connection error` → mauvaise configuration ou base de données inaccessible
- `Permission denied` → manque de droits d’exécution : `chmod +x create_user.sh`

---

## 🔐 Sécurité / Security Tips

- Ne jamais exposer ce script ou le fichier `config.json` sur un serveur web public
- Utiliser des permissions restreintes sur les fichiers
- Changer régulièrement le mot de passe de l’utilisateur MySQL

---

## 📄 Licence / License

MIT — Utilisation libre avec mention du projet BeamMP-Web  
MIT — Free use with credit to the BeamMP-Web project
