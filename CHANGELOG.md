# Changelog

All notable changes to MediaFetch will be documented in this file.

## [Unreleased]

## [2.0.0] - 2026-10-14

### Added

- Graphical yt-dlp settings assistant for audio and video quality, MP3 conversion, embedded metadata and thumbnails, subtitles, playlists, rate limits and filename templates.
- Per-download profiles: personal settings, YouTube video, video playlist, music track, music album and music playlist. Profiles do not overwrite saved personal settings.
- Internet Archive and Academic Torrents as optional torrent search sources alongside existing providers; neutral third-party-content disclaimer.
- Personal aria2 setting to stop seeding after a torrent finishes (`seed-time=0`); uploads while downloading may still occur.
- German and English interface coverage for navigation, search, download views, admin/personal settings and new controls.
- Regression tests for search-provider data passing, localized UI, yt-dlp profiles and metadata rule validation.

### Changed

- Mobile-friendly Nextcloud settings and simplified download form: link → destination folder → optional preset → Start download.
- The advanced yt-dlp option editor is collapsible and only shows options absent from the graphical assistant; saved options are preserved when switching views.
- Artist/title parsing (`parse-metadata`) has its own explained field, separate from the output filename template.
- Legacy aria2 seed options now use the valid `seed-time` and `seed-ratio` keys.

### Fixed

- Resolved blank main page caused by improperly encoded search-provider JSON in the HTML template.
- Corrected Academic Torrents AppDataFactory resolution and Internet Archive query-field encoding.
- Prevented empty value-taking yt-dlp flags from swallowing subsequent arguments and kept `audio-quality=0` intact.
- Validated `parse-metadata` syntax before saving/starting downloads with actionable errors.
- Fixed Safari iPhone input auto-zoom by using at least 16px text on media URL fields.
- Preserved selected per-download profiles for redownloads and saved personal choices when no override is selected.

### Compatibility

- Nextcloud 32–35 and PHP 8.2–8.5 (subject to each Nextcloud release's own PHP requirements).
- Requires aria2 and yt-dlp; ffmpeg is needed for audio/video post-processing.
- Academic Torrents search additionally requires PHP XMLReader.

## [1.1.2] - 2026-10-03

### Added

- Added a public downloader IP check through the running aria2 daemon, including a timestamp and specific failure diagnostics.
- Added regression tests for torrent search actions and the IP probe, including CI execution.

### Fixed

- Torrent search download buttons now use the MediaFetch route and correctly submit the selected result's link.
- Nested button clicks, clipboard actions and HTTP failures are handled correctly.
- IP checks retain relevant proxy settings while excluding unrelated torrent/file options, and use bounded RPC timeouts.

## [1.1.1] - 2026-09-18

### Changed

- Added Nextcloud 35 to the supported App Store compatibility range while retaining Nextcloud 32–34 support.
- Added PHP 8.5 to the declared compatibility range while retaining PHP 8.2 support for older supported Nextcloud releases.
- Expanded CI PHP checks to PHP 8.2, 8.3, 8.4 and 8.5.
- Verified the existing MediaFetch occ command already uses the Symfony 7-compatible `execute(...): int` signature required by Nextcloud 35.
- This is a compatibility release; downloader behavior is unchanged from 1.1.0.

## [1.1.0] - 2026-09-07

### Changed

- Completed yt-dlp playlist items are imported into Nextcloud immediately after yt-dlp's `after_move` stage instead of waiting for the entire playlist job to finish.
- yt-dlp staging now defaults to `/var/tmp/mediafetch` instead of `/tmp/mediafetch`; administrators can override the work directory with the `mediafetch` app setting `work_directory`.
- Completed and failed yt-dlp items now appear together with aria2/HTTP/magnet history in the existing Complete Downloads and Failed Downloads views.
- The yt-dlp Downloads view now contains only running/importing jobs; completed and failed rows leave the live queue automatically.

### Added

- Completed yt-dlp items briefly show `✅` after `Adding to Nextcloud…` before leaving the live queue.
- Running yt-dlp jobs can be cancelled once their process ID is known.
- Active aria2/HTTP/magnet downloads now expose a cancel action in addition to pause.
- Added `Stop all downloads & reset` to stop the current user's tracked yt-dlp/ffmpeg processes, remove active/waiting aria2 jobs and clear failed history while preserving completed history.
- Playlist entries that fail before a media file is created are recorded as failed yt-dlp items, including extractor errors when available.

### Fixed

- Removed the deprecated yt-dlp `--prefer-ffmpeg` option; audio extraction continues through yt-dlp's normal ffmpeg post-processing flow.
- Already imported playlist items keep their completed state while later entries continue downloading or fail.
- A final workspace pass still imports sidecar files and recovers completed files if an immediate per-item import was not possible.
- Existing yt-dlp destination files are skipped during the Nextcloud import instead of being renamed into duplicate copies such as `(1)`, `(2)` and so on.
- `Stop all downloads & reset` now keeps a live yt-dlp row visible when its process tree could not actually be terminated instead of falsely showing an empty queue.
- Reset correctly handles privileged VPN wrappers by terminating the www-data-owned yt-dlp/ffmpeg descendants first and allowing the root sudo/unshare/nsenter wrapper chain to unwind naturally.
- Corrected the remaining aria2 action route to use the `mediafetch` route namespace.

## [1.0.1] - 2026-09-03

### Changed

- yt-dlp downloads are now imported through Nextcloud's Files API instead of relying on internal file scanning.
- Added visible preparing, downloading, importing and completed states for yt-dlp jobs.
- Updated project links to point to the MediaFetch repository and issue tracker.

### Fixed

- Fixed yt-dlp jobs appearing inactive immediately after submitting a download.
- Avoided false import errors while cleaning up temporary yt-dlp workspaces.
- Made completed HTTP, magnet and torrent downloads appear in Nextcloud without requiring a manual `occ files:scan`.
