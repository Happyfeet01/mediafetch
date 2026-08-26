# Changelog

All notable changes to MediaFetch will be documented in this file.

## [1.0.0] - 2026-08-26

### Added

- Rebranded the application as **MediaFetch** with the new app ID `mediafetch`.
- Added compatibility metadata for Nextcloud 32, 33 and 34.
- Added a MediaFetch navigation/settings icon.
- Added GitHub Actions checks for PHP syntax, app metadata and frontend builds.
- Added support for administrator-managed downloader wrapper binaries, including VPN wrapper setups.
- Added safe per-user allowlists for aria2 and yt-dlp options.
- Added a Nextcloud 34 compatible mobile navigation drawer.
- Added visible yt-dlp lifecycle states for preparing, downloading, importing and completed downloads.

### Changed

- Updated frontend routes and translation domains from `ncdownloader` to `mediafetch`.
- Updated the folder selector to use the maintained `@nextcloud/dialogs` file picker.
- Updated Nextcloud frontend libraries for current Nextcloud releases.
- Renamed user-facing youtube-dl references to yt-dlp where applicable.
- Restricted the supported Nextcloud range to currently maintained releases (32–34) for the first MediaFetch release.
- Updated bundled PHP dependencies for PHP 8.2–8.4 and Nextcloud 34 compatibility.
- yt-dlp now downloads into a private temporary workspace and imports completed files through Nextcloud's public Files API.
- Removed the old forced folder scan after yt-dlp downloads and before opening the destination folder.

### Migration

- Existing NCDownloader app and user settings are read through a legacy fallback and copied to the `mediafetch` configuration namespace when accessed.
- Existing per-user download folders, torrent folders, aria2 settings and yt-dlp settings remain available after migration.
- Existing NCDownloader download records are imported into the MediaFetch download table without deleting the legacy table during the first release.

### Security

- Removed user-configurable VPN start/stop shell commands. VPN routing should instead be implemented by an administrator-controlled downloader binary or wrapper.
- Removed arbitrary command-execution options from personal yt-dlp settings.
- Removed unsafe filesystem, credential and RPC-related options from personal aria2 settings.
- Restricted personal yt-dlp output templates to filenames so users cannot escape the configured download directory.
- Removed the legacy direct use of Nextcloud's internal file scanner from the download workflow.

### Tested

- Runtime tested successfully on Nextcloud 34.0.2 with PHP 8.4.
- Main app view, mobile navigation, admin settings, personal settings, aria2 and yt-dlp workflows verified on the target installation.
