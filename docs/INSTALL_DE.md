# 🚀 Automatische Installation von BeamMP-Web (Multi-Instance)

> Installieren Sie BeamMP-Web, die Weboberfläche, den Discord-Bot und die Multi-Server-Verwaltung in wenigen Minuten auf einer Linux-Umgebung.
>
> ✅ Kompatibel (getestet): Debian 12 AMD64.

> **Nativ integrierte Multi-Instance-Unterstützung** (zentrale Verwaltung, Admin-Konten, Sicherheit).

---

## 1️⃣ Systemvoraussetzungen

Verwenden Sie einen dedizierten Systembenutzer für BeamMP-Server:

```bash
sudo usermod -aG sudo Ihr_Benutzer
```

Installieren Sie alle Abhängigkeiten:

```bash
apache2 mariadb-server php php-mysql php-curl php-xml php-mbstring python3 python3-venv python3-pip unzip curl git composer jq
```

---

## 2️⃣ SQL-Benutzer erstellen

Verbinden Sie sich mit MariaDB:

```bash
sudo mysql
```

Geben Sie dann Folgendes ein (passen Sie `user_db` und `password_db` an):

```sql
CREATE USER 'user_db'@'localhost' IDENTIFIED BY 'password_db';
GRANT CREATE ON *.* TO 'user_db'@'localhost';
GRANT ALL PRIVILEGES ON beammp_db.* TO 'user_db'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 3️⃣ Projekt klonen und konfigurieren

Klonen Sie das Repository und bearbeiten Sie die Konfiguration:

```bash
git clone https://github.com/Zyphro3D/BeamMP-web.git
cd BeamMP-web
```

Bearbeiten Sie die Datei `install_config.json` und tragen Sie Ihre Werte ein:
```bash
nano install_config.json
```

**Beispiel für eine `install_config.json`:**

```json
{
  "db_user": "user_db",
  "db_pass": "password_db",
  "user_system": "Ihr_Benutzer",
  "lang": "de",
  "ip": "192.168.XX.XXX",
  "instances": [
    {
      "name": "Instanz 1",
      "port": "8081",
      "root_beammp": "/pfad/zu/BeamMP-Server1"
    },
    {
      "name": "Instanz 2",
      "port": "8082",
      "root_beammp": "/pfad/zu/BeamMP-Server2"
    }
  ]
}
```

---

## 4️⃣ Automatisches Installationsskript starten

```bash
chmod +x Install.sh
sudo ./Install.sh
```

**Während der Installation:**
- Sie werden gefragt, ob Sie die Rechte automatisch setzen möchten oder lieber manuell:
  > *Sicherheit: Im professionellen/empfindlichen Umfeld lieber manuell, für VM/Tests schnell automatisch.*
- Benutzername, Passwort und Rolle:
  - **Admin**: alles außer `ServerConfig.toml`
  - **SuperAdmin**: Vollzugriff

---

## 5️⃣ Zugriff, Status & Wartung

- **Dienste prüfen:**
  ```bash
  sudo systemctl status beammp-[name].service players-[name].service
  ```
- **Webzugriff:**
  ```
  http://192.168.X.XXX:8081
  http://192.168.X.XXX:8082
  ```
- **Logs:**
  ```bash
  sudo journalctl -u beammp-[name].service
  sudo journalctl -u players-[name].service
  ```
- **Wichtig:**
  - Tragen Sie die Discord Webhooks in `.env` und `config.json` jeder Instanz ein.

---

Für Fragen oder Probleme:  
[https://github.com/Zyphro3D/BeamMP-web/issues](https://github.com/Zyphro3D/BeamMP-web/issues)
