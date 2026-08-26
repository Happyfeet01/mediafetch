# Changelog

All notable changes to MediaFetch will be documented in this file.

## [1.0.0-beta.1] - Unreleased

### Added

- Rebranded the application as **MediaFetch** with the new app ID `mediafetch`.
- Added compatibility metadata for Nextcloud 32, 33 and 34.
- Added a MediaFetch navigation/settings icon.
- Added GitHub Actions checks for PHP syntax, app metadata and frontend builds.
- Added support for administrator-managed downloader wrapper binaries, including VPN wrapper setups.
- Added safe per-user allowlists for aria2 and yt-dlp options.

### Changed

- Updated frontend routes and translation domains from `ncdownloader` to `mediafetch`.
- Updated the folder selector to use the maintained `@nextcloud/dialogs` file picker.
- Updated Nextcloud frontend libraries for current Nextcloud releases.
- Renamed user-facing youtube-dl references to yt-dlp where applicable.
- Restricted the supported Nextcloud range to currently maintained releases (32–34) for the first MediaFetch release.

### Migration

- Existing NCDownloader app and user settings are read through a legacy fallback and copied to the `mediafetch` configuration namespace when accessed.
- Existing per-user download folders, torrent folders, aria2 settings and yt-dlp settings remain available after migration.

### Security

- Removed user-configurable VPN start/stop shell commands. VPN routing should instead be implemented by an administrator-controlled downloader binary or wrapper.
- Removed arbitrary command-execution options from personal yt-dlp settings.
- Removed unsafe filesystem, credential and RPC-related options from personal aria2 settings.
- Restricted personal yt-dlp output templates to filenames so users cannot escape the configured download directory.

### Pending before 1.0.0

- Full runtime test on Nextcloud 34.
- Remaining public-API/deprecation audit.
- App package and signing workflow.
- Nextcloud App Store certificate and release credentials.
- Final screenshots, translations and App Store metadata.
