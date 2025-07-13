
# 🧑‍💻 create_user.sh

This script allows you to **create a new user** in the BeamMP-Web database via command line.

---


## 📁 File structure

```
USER/
└── scripts/
    ├── create_user.sh
    └── config.json
```

---


## ⚙️ Requirements

- PHP in CLI mode (`php-cli`)
- PHP PDO extension for MySQL (`php-mysql`)
- Database access

**Installation (Debian/Ubuntu):**

```bash
sudo apt update
sudo apt install php-cli php-mysql
```

---


## 🔐 Configuration

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

> 🔒 **Keep this file outside any public web directory** to prevent exposing sensitive data.

---


## 🚀 How to use

1. Make the script executable:
   ```bash
   chmod +x create_user.sh
   ```

2. Run the script:
   ```bash
   ./create_user.sh
   ```

3. Enter the requested information:
   ```
   Username: your_username
   Password: your_password
   Role (Admin or SuperAdmin): Admin
   ```

4. If everything is correct:
   ```
   ✅ User 'your_username' created successfully with role 'Admin'.
   ```

---


## ❓ What does this script do?

- Connects to the database (PDO)
- Reads user data interactively (name, password, role)
- Validates the role (`Admin` or `SuperAdmin`)
- Securely hashes the password (`password_hash`)
- Inserts into the `users` table

---


## 📌 Notes

- **Roles are case-sensitive** (`Admin`, `SuperAdmin`)
- No user is created if an error occurs
- Can be integrated into installation or maintenance scripts

---


## 🛠️ Troubleshooting

- `❌ Configuration file not found` → `config.json` file missing or misplaced
- `❌ Database connection error` → wrong configuration or database inaccessible
- `Permission denied` → missing execution rights: `chmod +x create_user.sh`

---


## 🔐 Security Tips

- Never expose this script or the `config.json` file on a public web server
- Use restricted permissions on the files
- Change the MySQL user password regularly

---


## 📄 License

MIT — Free use with credit to the BeamMP-Web project
