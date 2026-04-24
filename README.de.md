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
