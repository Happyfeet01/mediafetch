# MediaFetch

MediaFetch is a download manager for Nextcloud built around **aria2** and **yt-dlp**.

It is a continuation and rebranding of the NCDownloader / Net loader codebase, with a focus on current Nextcloud releases, per-user download settings and a maintainable App Store release process.

## Features

- HTTP/HTTPS downloads through aria2
- BitTorrent and magnet downloads through aria2
- Media downloads through yt-dlp
- Per-user download and torrent folders
- Per-user aria2 options
- Per-user yt-dlp options
- Administrator-controlled binary paths and aria2 RPC settings
- Existing NCDownloader user/app settings are imported lazily when MediaFetch first reads them

## Requirements

- Nextcloud 28–34 during the migration/beta phase
- PHP 8.1–8.4
- aria2
- yt-dlp
- ffmpeg for media conversion/extraction features

The first public MediaFetch release is being prepared and tested against Nextcloud 34. The compatibility range will be tightened before the final App Store release if testing shows that older versions are no longer appropriate.

## External binaries and VPN wrappers

Administrators can configure the aria2 and yt-dlp binary paths. This also makes it possible to point MediaFetch at administrator-managed wrapper scripts, for example when downloads must run in a dedicated VPN network namespace.

Users do not need direct access to the VPN configuration itself.

## Security

MediaFetch treats downloader options as untrusted user input. The App Store release will only expose options that are safe for unprivileged users. Options capable of executing arbitrary commands, loading arbitrary local files or replacing the configured downloader are not intended to be available to normal users.

## Development

```bash
npm install
npm run build
```

PHP dependencies:

```bash
composer install
```

The active development branch for the rebrand and Nextcloud 34 work is `mediafetch-rebrand`.

## License and attribution

MediaFetch is licensed under the **GNU Affero General Public License v3.0 or later (AGPL-3.0-or-later)**.

It is based on the original NCDownloader / Net loader project by Jiaxin Huang and subsequent contributors. The existing copyright and license history is retained.

### Download
https://apps.nextcloud.com/apps/mediafetch
