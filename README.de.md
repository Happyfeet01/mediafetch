## Net loader (Deutsch)

Eine einfach zu bedienende Weboberfläche für Aria2 und youtube-dl/yt-dlp in Nextcloud.

- Torrents direkt in der App über mehrere BT-Seiten suchen.
- Aria2 steuern und Download-Aufgaben per Weboberfläche verwalten.
- yt-dlp für Downloads von vielen Video-Plattformen nutzen.

### Verwendung

Net loader bringt yt-dlp und aria2c bereits mit, daher ist eine manuelle Installation meist nicht nötig.
Wenn die mitgelieferten Binärdateien in deiner Umgebung nicht funktionieren, installiere sie manuell und hinterlege die Pfade in den Einstellungen.

#### aria2 und yt-dlp unter Ubuntu installieren

```bash
sudo apt install aria2
sudo curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp
sudo chmod a+rx /usr/local/bin/yt-dlp
```

### Frontend bauen

Node.js 14+ und npm 7+ werden benötigt.

```bash
npm run build
composer install
```

### Prüfung der NPM-Paket-Aktualität

Ein aktueller Statusbericht liegt hier:

- `docs/npm-package-status.md`

### Externe Downloader-IP

Mit „Downloader-IP prüfen“ lässt sich die aktuelle externe IP anzeigen. Die Prüfung lädt eine kleine IP-Antwort von `https://api.ipify.org` über den laufenden aria2-Daemon und dessen HTTP-Proxy-Einstellungen. Bei einem über Gluetun gerouteten aria2-Daemon wird entsprechend dessen VPN-Ausgang verwendet. Die Prüfung startet aria2 nicht automatisch und fällt bei Fehlern nicht auf den Netzwerkweg von PHP oder dem Browser zurück. Die Anzeige ist eine Momentaufnahme des HTTP-Netzwerkwegs und bestätigt weder einen aktiven VPN noch die IP aller BitTorrent-Verbindungen (etwa bei getrennten Proxy-/IPv6-Regeln). Der IP-Dienst sieht die anfragende Ausgangs-IP.

Die temporäre Prüfdatei wird im aria2-Konfigurationsverzeichnis angelegt und nach der Prüfung entfernt. Dieses Verzeichnis muss für PHP und aria2 zugänglich sein. Ein separater Remote-Daemon ohne gemeinsam zugängliches Verzeichnis liefert „IP unbekannt“.
