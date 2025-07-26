# 🚀 Automatic Installation of BeamMP-Web (Multi-Instance)

> Deploy BeamMP-Web, its web interface, Discord bot, and multi-server management in just a few minutes on any Linux environment.
>
> ✅ Compatible (tested): Debian 12 AMD64.

> **Native multi-instance BeamMP support** (centralized management, admin accounts, security).

---

## 1️⃣ System Prerequisites

Use a dedicated system user for BeamMP-Server:

```bash
sudo usermod -aG sudo your_user
```

Install all dependencies:

```bash
apache2 mariadb-server php php-mysql php-curl php-xml php-mbstring python3 python3-venv python3-pip unzip curl git composer jq
```

---

## 2️⃣ Create the SQL User

Connect to MariaDB:

```bash
sudo mysql
```

Then enter (adapt `user_db` and `password_db`):

```sql
CREATE USER 'user_db'@'localhost' IDENTIFIED BY 'password_db';
GRANT CREATE ON *.* TO 'user_db'@'localhost';
GRANT ALL PRIVILEGES ON beammp_db.* TO 'user_db'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 3️⃣ Clone and Configure the Project

Clone the repo and edit the config:

```bash
git clone https://github.com/Zyphro3D/BeamMP-web.git
cd BeamMP-web
```

Edit the file `install_config.json` with your own values:
```bash
nano install_config.json
```

**Sample `install_config.json` file:**

```json
{
  "db_user": "user_db",
  "db_pass": "password_db",
  "user_system": "your_user",
  "lang": "en",
  "ip": "192.168.XX.XXX",
  "instances": [
    {
      "name": "Instance 1",
      "port": "8081",
      "root_beammp": "/path/to/BeamMP-Server1"
    },
    {
      "name": "Instance 2",
      "port": "8082",
      "root_beammp": "/path/to/BeamMP-Server2"
    }
  ]
}
```

---

## 4️⃣ Launch the Automatic Installer

```bash
chmod +x Install.sh
sudo ./Install.sh
```

**During installation:**
- You will be asked whether you want to automatically set Linux rights or generate a manual script:
  > *Security: Choose manual mode for professional/sensitive setups, auto for VM/local quick installs.*
- Choose a username, password, and role:
  - **Admin**: manage everything except `ServerConfig.toml`
  - **SuperAdmin**: full access

---

## 5️⃣ Access, Status & Maintenance

- **Check services:**
  ```bash
  sudo systemctl status beammp-[name].service players-[name].service
  ```
- **Web access:**
  ```
  http://192.168.X.XXX:8081
  http://192.168.X.XXX:8082
  ```
- **Logs:**
  ```bash
  sudo journalctl -u beammp-[name].service
  sudo journalctl -u players-[name].service
  ```
- **Important:**
  - Fill in Discord webhooks in `.env` and `config.json` for each instance.

---

For help or issues:  
[https://github.com/Zyphro3D/BeamMP-web/issues](https://github.com/Zyphro3D/BeamMP-web/issues)
