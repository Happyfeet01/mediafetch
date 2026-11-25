# Signierte MediaFetch-Releases

## Einmalig einrichten

Das bestehende Nextcloud-App-Zertifikat und den zugehörigen privaten PEM-Schlüssel im GitHub-Repository als Actions-Secrets hinterlegen. Auf dem Rechner mit den Zertifikatsdateien und einer angemeldeten GitHub CLI ausführen:

```bash
gh secret set APP_PRIVATE_KEY --repo Happyfeet01/mediafetch < /var/www/nextcloud-signing/certificates/mediafetch.key
gh secret set APP_PUBLIC_CRT --repo Happyfeet01/mediafetch < /var/www/nextcloud-signing/certificates/mediafetch.crt
```

Damit erhält GitHub Actions den Schlüssel für Release-Läufe auf `master`. Änderungen am Release-Workflow und am Signierskript vor dem Merge prüfen. Pull Requests bauen und prüfen das Paket ohne Zugriff auf die Signiergeheimnisse.

## Neue Version

1. Die Version in `appinfo/info.xml` und `package.json` erhöhen, den Changelog ergänzen und `docs/releases/<Version>.md` anlegen.
2. Den geprüften Pull Request nach `master` mergen. Der Workflow übernimmt Version und Commit automatisch aus diesem Stand. Ein manueller Start über **Actions → Build and sign MediaFetch release → Run workflow → master** ist ebenfalls möglich.
3. Der Workflow baut JavaScript, CSS und PHP-Abhängigkeiten. In einer temporären Nextcloud signiert und prüft `occ` das App-Verzeichnis. Anschließend wird das endgültige Archiv gepackt, mit SHA-512 signiert und die Signatur gegen den öffentlichen Schlüssel des App-Zertifikats geprüft.
4. Das GitHub-Release enthält `mediafetch-<Version>.tar.gz`, `mediafetch-<Version>.signature` und die SHA-256-Prüfsumme. Erst wenn alle drei Dateien hochgeladen sind, wird der Entwurf veröffentlicht. Bereits veröffentlichte Assets werden nicht ersetzt. Bei fehlenden Secrets diese hinterlegen und den fehlgeschlagenen Job erneut ausführen.
5. Für die Freigabe im Nextcloud App Store die öffentliche Download-URL genau dieses Archivs und den Base64-Inhalt der zugehörigen `.signature` verwenden. Der automatische Ablauf endet mit dem GitHub-Release.

Die App-ID lautet `mediafetch`. Für diesen Ablauf werden weder Zugriff auf die produktive Nextcloud noch ein App-Store-Token benötigt.
