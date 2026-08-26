# NCDownloader → MediaFetch rebrand notes

## Public identity

- Display name: `MediaFetch`
- App ID: `mediafetch`
- First release line: `1.0.0`
- Development/beta version: `1.0.0-beta.1`

## Internal compatibility

The historical PHP namespace `OCA\\NCDownloader` is intentionally retained for the initial MediaFetch release. The public Nextcloud app ID and routes use `mediafetch`, while keeping the PHP namespace stable reduces migration risk and avoids a large unrelated class rename during the Nextcloud 34 compatibility update.

Legacy DOM/CSS identifiers that contain `ncdownloader` may also remain internally where they do not affect the public app identity.

## Configuration migration

Existing settings stored under the old `ncdownloader` app configuration namespace are read as a fallback. When MediaFetch reads a legacy value it writes it to the new `mediafetch` namespace.

This migration strategy is intentionally conservative: the old configuration is not deleted automatically.

## User settings

MediaFetch retains separate administrator and personal settings. Personal aria2 and yt-dlp settings remain a supported feature, with security allowlists applied before options reach external downloader processes.
