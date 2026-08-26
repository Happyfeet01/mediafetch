# MediaFetch security model

MediaFetch starts external downloader processes and therefore treats all downloader configuration supplied by normal Nextcloud users as untrusted input.

## Administrator-controlled configuration

The administrator controls the installed aria2 and yt-dlp binaries (or wrapper binaries), RPC connectivity and global downloader configuration. VPN routing should be implemented through administrator-managed binaries/wrappers or system/network configuration, not through user-provided shell commands.

## Personal downloader settings

Normal users can keep personal aria2 and yt-dlp preferences, but the server filters these settings through explicit allowlists.

The following classes of options must remain administrator-only or unavailable to normal users:

- shell command hooks (`exec`, aria2 event hooks, etc.)
- executable/binary replacement and arbitrary external downloader selection
- arbitrary local file reads (cookie/config/batch files)
- RPC server configuration and credentials
- arbitrary filesystem destination paths

The personal yt-dlp `output` value is treated as a filename template only and must not contain path separators, NUL bytes or parent-directory traversal.

## Secrets

Do not commit:

- App Store tokens
- MediaFetch signing private keys
- VPN private keys
- aria2 RPC secrets
- proxy credentials

Release/signing credentials belong in protected repository secrets or administrator-managed server configuration.

## Reporting vulnerabilities

Security issues should be reported privately to the maintainer rather than disclosed in a public issue before a fix is available.
