# MediaFetch App Store release notes

MediaFetch uses the Nextcloud app ID `mediafetch`.

## Before the first App Store release

1. Test the `mediafetch-rebrand` branch on Nextcloud 34 with at least one administrator and one normal user.
2. Verify HTTP, magnet/torrent and yt-dlp downloads, folder selection, per-user settings and administrator settings.
3. Finish the remaining public API/deprecation audit.
4. Generate the MediaFetch signing key and certificate request. Keep the private key private and never commit it.
5. Request the app certificate for `mediafetch` through the Nextcloud app certificate process.
6. Create an App Store API token for the publishing account.
7. Configure the repository release secrets only after the signing certificate is issued.
8. Build the production assets and a package whose top-level directory is exactly `mediafetch`.
9. Sign and validate the package before publishing the first stable `1.0.0` release.

## Signing material

The private signing key must never be committed to this repository. The release workflow will eventually consume signing material from repository secrets.

Expected secrets for automated publishing:

- `APP_PRIVATE_KEY`
- `APP_PUBLIC_CRT`
- `APPSTORE_TOKEN`

## Compatibility target

The first MediaFetch release targets the maintained Nextcloud releases 32 through 34, with Nextcloud 34 as the primary test target.

## Migration from NCDownloader

MediaFetch uses a different app ID, so Nextcloud treats it as a separate application. The application contains a legacy settings fallback that reads existing `ncdownloader` user/app configuration and copies values into the `mediafetch` namespace when accessed.

Do not remove the old NCDownloader configuration from the database until MediaFetch has been tested successfully.
