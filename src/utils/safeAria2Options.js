// aria2 options that unprivileged users may customize.
// Command hooks, filesystem paths, credentials and RPC options are excluded.
const safeAria2Options = [
  "allow-overwrite", "allow-piece-length-change", "always-resume", "async-dns",
  "auto-file-renaming", "bt-enable-lpd", "bt-exclude-tracker", "bt-force-encryption",
  "bt-hash-check-seed", "bt-load-saved-metadata", "bt-max-peers", "bt-metadata-only",
  "bt-min-crypto-level", "bt-prioritize-piece", "bt-remove-unselected-file",
  "bt-request-peer-speed-limit", "bt-require-crypto", "bt-save-metadata",
  "bt-seed-unverified", "bt-stop-timeout", "bt-tracker", "bt-tracker-connect-timeout",
  "bt-tracker-interval", "bt-tracker-timeout", "check-integrity", "checksum",
  "conditional-get", "connect-timeout", "continue", "dry-run", "enable-http-keep-alive",
  "enable-http-pipelining", "enable-mmap", "enable-peer-exchange", "file-allocation",
  "follow-metalink", "follow-torrent", "force-save", "hash-check-only",
  "http-accept-gzip", "http-auth-challenge", "http-no-cache", "lowest-speed-limit",
  "max-connection-per-server", "max-download-limit", "max-file-not-found",
  "max-mmap-limit", "max-resume-failure-tries", "max-tries", "max-upload-limit",
  "min-split-size", "no-file-allocation-limit", "no-netrc", "pause", "piece-length",
  "realtime-chunk-checksum", "remote-time", "remove-control-file", "retry-wait",
  "reuse-uri", "seed-ratio", "seed-time", "select-file", "split",
  "stream-piece-selector", "timeout", "uri-selector", "use-head", "user-agent",
  "max-overall-download-limit", "max-overall-upload-limit"
];

export default safeAria2Options;
