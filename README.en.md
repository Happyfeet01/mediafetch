## Net loader (English)

An easy-to-use web interface for Aria2 and youtube-dl/yt-dlp in Nextcloud.

- Search for torrents from multiple BT sites inside the app.
- Control Aria2 and manage download tasks via web UI.
- Use yt-dlp for downloads from many video sites.

### How to use

Net loader includes both yt-dlp and aria2c, so manual installation is usually not required.
If built-in binaries do not work in your environment, install them manually and configure paths in settings.

#### Installing aria2 and yt-dlp on Ubuntu

```bash
sudo apt install aria2
sudo curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp
sudo chmod a+rx /usr/local/bin/yt-dlp
```

### Build front-end code

Node.js 14+ and npm 7+ are required.

```bash
npm run build
composer install
```

### NPM package freshness check

A current package status report is generated in:

- `docs/npm-package-status.md`
