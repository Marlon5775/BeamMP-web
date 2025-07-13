
# 🧑‍💻 create_user.sh

Dieses Skript ermöglicht es, **einen neuen Benutzer** in der Datenbank des BeamMP-Web-Projekts über die Kommandozeile zu erstellen.

---


## 📁 Dateistruktur

```
USER/
└── scripts/
    ├── create_user.sh
    └── config.json
```

---


## ⚙️ Voraussetzungen

- PHP im CLI-Modus (`php-cli`)
- PHP PDO-Erweiterung für MySQL (`php-mysql`)
- Zugriff auf die Datenbank

**Installation (Debian/Ubuntu):**

```bash
sudo apt update
sudo apt install php-cli php-mysql
```

---


## 🔐 Konfiguration

Die Zugangsdaten zur Datenbank sind in der Datei `config.json` gespeichert:

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

> 🔒 **Lege diese Datei außerhalb eines öffentlichen Webverzeichnisses ab**, um sensible Daten zu schützen.

---


## 🚀 Nutzung

1. Mache das Skript ausführbar:
   ```bash
   chmod +x create_user.sh
   ```

2. Starte das Skript:
   ```bash
   ./create_user.sh
   ```

3. Gib die abgefragten Informationen ein:
   ```
   Username: dein_benutzername
   Password: dein_passwort
   Role (Admin or SuperAdmin): Admin
   ```

4. Wenn alles korrekt ist:
   ```
   ✅ User 'dein_benutzername' created successfully with role 'Admin'.
   ```

---


## ❓ Was macht dieses Skript?

- Verbindung zur Datenbank (PDO)
- Interaktive Abfrage der Benutzerdaten (Name, Passwort, Rolle)
- Validierung der Rolle (`Admin` oder `SuperAdmin`)
- Sicheres Hashen des Passworts (`password_hash`)
- Einfügen in die Tabelle `users`

---


## 📌 Hinweise

- **Die Rollen sind Groß-/Kleinschreibung-sensitiv** (`Admin`, `SuperAdmin`)
- Es wird kein Benutzer erstellt, wenn ein Fehler auftritt
- Kann in Installations- oder Wartungsskripte integriert werden

---


## 🛠️ Fehlerbehebung

- `❌ Configuration file not found` → Datei `config.json` fehlt oder ist falsch platziert
- `❌ Database connection error` → falsche Konfiguration oder Datenbank nicht erreichbar
- `Permission denied` → fehlende Ausführungsrechte: `chmod +x create_user.sh`

---


## 🔐 Sicherheitshinweise

- Dieses Skript und die Datei `config.json` niemals auf einem öffentlichen Webserver ablegen
- Beschränkte Dateiberechtigungen verwenden
- Das MySQL-Benutzerpasswort regelmäßig ändern

---


## 📄 Lizenz

MIT — Freie Nutzung mit Nennung des Projekts BeamMP-Web
