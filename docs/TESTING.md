# MediaFetch testing checklist

This checklist is intended for the `1.0.0-beta.1` migration test on Nextcloud 34.

## Installation

- Install the app directory as `mediafetch`.
- Confirm Nextcloud recognizes app ID `mediafetch`.
- Enable the app and open the MediaFetch navigation entry.
- Confirm the MediaFetch icon, admin settings section and personal settings section are visible.

## Migration

With the old NCDownloader configuration still present:

- Confirm the previous download folder is shown.
- Confirm the previous torrent folder is shown.
- Confirm existing personal aria2 settings are shown when allowed by the safe option list.
- Confirm existing personal yt-dlp settings are shown when allowed by the safe option list.
- Change one setting and confirm the new value persists under MediaFetch.

## Administrator settings

- Configure the aria2 binary path.
- Configure the yt-dlp binary path.
- Verify administrator-managed wrapper binaries can be used.
- Verify aria2 RPC host, port and token.
- Verify global aria2 settings can be saved and reloaded.
- Verify the option that disables personal aria2 settings for non-admin users.

## Personal settings

As a normal user:

- Select a download folder with the modern Nextcloud file picker.
- Select a torrent folder.
- Configure the download proxy if required.
- Add/remove safe aria2 options.
- Add/remove safe yt-dlp options.
- Verify unsafe command execution options are not exposed or accepted.
- Verify `output` accepts a filename template such as `%(playlist_index)02d - %(title)s.%(ext)s`.
- Verify `output` rejects path traversal or path separators.

## Downloads

- HTTP/HTTPS download.
- Magnet download.
- `.torrent` upload/download.
- Pause/resume/remove aria2 task.
- yt-dlp video download.
- yt-dlp single-song audio extraction.
- yt-dlp playlist/album download.
- Verify completed files appear in the selected Nextcloud folder.

## VPN wrapper setup

When administrator-managed VPN wrapper binaries are configured:

- Verify aria2 sees the VPN public IP.
- Verify yt-dlp sees the VPN public IP.
- Verify downloads fail closed when the VPN wrapper refuses to start.
- Verify normal Nextcloud/PHP traffic is not forced through the downloader VPN.

## Permissions

As a non-admin user:

- Confirm administrator settings cannot be changed.
- Confirm administrator-only endpoints reject access.
- Confirm arbitrary shell commands cannot be configured.
- Confirm downloader output cannot escape the configured download directory.

## Nextcloud 34 UI

- Check desktop layout.
- Check mobile/narrow layout.
- Check light and dark themes.
- Check the folder picker.
- Check toast/error messages.
- Check browser console for deprecated API errors or uncaught exceptions.
