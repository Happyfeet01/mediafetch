<?php

namespace OCA\NCDownloader\Db;

class Settings
{
    private $appConfig;
    private $sysConfig;
    private $user;
    private $appName;
    private $legacyAppName;
    private $type;

    public const TYPE = ['SYSTEM' => 1, 'USER' => 2, 'APP' => 3];

    /**
     * yt-dlp options that are safe for unprivileged Nextcloud users.
     * Options that execute commands, read arbitrary local files, select an
     * external downloader, or redirect output to arbitrary paths are omitted.
     */
    private const SAFE_YTDL_OPTIONS = [
        'ignore-errors', 'no-abort-on-error', 'abort-on-error',
        'flat-playlist', 'no-flat-playlist', 'live-from-start', 'no-live-from-start',
        'wait-for-video', 'no-wait-for-video', 'no-colors', 'socket-timeout',
        'force-ipv4', 'force-ipv6', 'playlist-start', 'playlist-end', 'playlist-items',
        'min-filesize', 'max-filesize', 'date', 'datebefore', 'dateafter',
        'match-filters', 'no-match-filter', 'no-playlist', 'yes-playlist', 'age-limit',
        'max-downloads', 'break-on-existing', 'break-on-reject', 'break-per-input',
        'no-break-per-input', 'skip-playlist-after-errors', 'concurrent-fragments',
        'limit-rate', 'throttled-rate', 'retries', 'file-access-retries',
        'fragment-retries', 'skip-unavailable-fragments', 'abort-on-unavailable-fragment',
        'keep-fragments', 'no-keep-fragments', 'buffer-size', 'resize-buffer',
        'no-resize-buffer', 'http-chunk-size', 'playlist-reverse', 'no-playlist-reverse',
        'playlist-random', 'output', 'output-na-placeholder', 'restrict-filenames',
        'no-restrict-filenames', 'windows-filenames', 'no-windows-filenames',
        'trim-filenames', 'no-overwrites', 'force-overwrites', 'no-force-overwrites',
        'continue', 'no-continue', 'part', 'no-part', 'mtime', 'no-mtime',
        'write-description', 'no-write-description', 'write-info-json', 'no-write-info-json',
        'write-playlist-metafiles', 'no-write-playlist-metafiles', 'clean-info-json',
        'no-clean-info-json', 'write-comments', 'no-write-comments', 'write-thumbnail',
        'no-write-thumbnail', 'write-all-thumbnails', 'list-thumbnails', 'no-simulate',
        'skip-download', 'dump-json', 'dump-single-json', 'newline', 'no-progress',
        'progress', 'progress-template', 'verbose', 'encoding', 'sleep-requests',
        'sleep-interval', 'max-sleep-interval', 'sleep-subtitles', 'format', 'format-sort',
        'format-sort-force', 'no-format-sort-force', 'video-multistreams',
        'no-video-multistreams', 'audio-multistreams', 'no-audio-multistreams',
        'prefer-free-formats', 'no-prefer-free-formats', 'check-formats',
        'check-all-formats', 'no-check-formats', 'list-formats', 'merge-output-format',
        'write-subs', 'no-write-subs', 'write-auto-subs', 'no-write-auto-subs',
        'list-subs', 'sub-format', 'sub-langs', 'extract-audio', 'audio-format',
        'audio-quality', 'remux-video', 'recode-video', 'keep-video', 'no-keep-video',
        'post-overwrites', 'no-post-overwrites', 'embed-subs', 'no-embed-subs',
        'embed-thumbnail', 'no-embed-thumbnail', 'embed-metadata', 'no-embed-metadata',
        'embed-chapters', 'no-embed-chapters', 'embed-info-json', 'no-embed-info-json',
        'parse-metadata', 'replace-in-metadata', 'concat-playlist', 'fixup',
        'convert-subs', 'convert-thumbnails', 'split-chapters', 'no-split-chapters',
        'remove-chapters', 'no-remove-chapters', 'force-keyframes-at-cuts',
        'no-force-keyframes-at-cuts', 'sponsorblock-mark', 'sponsorblock-remove',
        'sponsorblock-chapter-title', 'no-sponsorblock', 'extractor-retries',
        'allow-dynamic-mpd', 'ignore-dynamic-mpd', 'hls-split-discontinuity',
        'no-hls-split-discontinuity', 'extractor-args'
    ];

    public function __construct($user = null)
    {
        $this->appConfig = \OC::$server->get(\OCP\IConfig::class);
        $this->sysConfig = $this->appConfig;
        $this->appName = 'mediafetch';
        $this->legacyAppName = 'ncdownloader';
        $this->type = self::TYPE['USER'];
        $this->user = $user;
    }

    public static function create($user = null)
    {
        return new static($user);
    }

    public static function safeYtdlOptions(): array
    {
        return self::SAFE_YTDL_OPTIONS;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function get($key, $default = null)
    {
        if ($this->type == self::TYPE['USER'] && isset($this->user)) {
            return $this->getUserValueWithLegacyFallback($key, $default);
        }

        if ($this->type == self::TYPE['SYSTEM']) {
            return $this->appConfig->getSystemValue($key, $default);
        }

        return $this->getAppValueWithLegacyFallback($key, $default);
    }

    private function getUserValueWithLegacyFallback($key, $default = null)
    {
        $newKeys = $this->appConfig->getUserKeys($this->user, $this->appName);
        if (in_array($key, $newKeys, true)) {
            return $this->appConfig->getUserValue($this->user, $this->appName, $key, $default);
        }

        $legacyKeys = $this->appConfig->getUserKeys($this->user, $this->legacyAppName);
        if (in_array($key, $legacyKeys, true)) {
            $value = $this->appConfig->getUserValue($this->user, $this->legacyAppName, $key, $default);
            $this->appConfig->setUserValue($this->user, $this->appName, $key, $value);
            return $value;
        }

        return $default;
    }

    private function getAppValueWithLegacyFallback($key, $default = null)
    {
        $newKeys = $this->appConfig->getAppKeys($this->appName);
        if (in_array($key, $newKeys, true)) {
            return $this->appConfig->getAppValue($this->appName, $key, $default);
        }

        $legacyKeys = $this->appConfig->getAppKeys($this->legacyAppName);
        if (in_array($key, $legacyKeys, true)) {
            $value = $this->appConfig->getAppValue($this->legacyAppName, $key, $default);
            $this->appConfig->setAppValue($this->appName, $key, $value);
            return $value;
        }

        return $default;
    }

    public function getAria2()
    {
        $settings = $this->get('custom_aria2_settings', '');
        return json_decode($settings, true);
    }

    public function getYtdl(): array
    {
        $settings = json_decode($this->get('custom_ytdl_settings', ''), true);
        if (!is_array($settings)) {
            return [];
        }

        $allowed = array_flip(self::SAFE_YTDL_OPTIONS);
        $settings = array_intersect_key($settings, $allowed);

        // MediaFetch owns the destination directory. The user may customize
        // the filename template, but must not escape that directory.
        if (isset($settings['output'])) {
            $output = (string) $settings['output'];
            if ($output === '' || str_contains($output, '..') || str_contains($output, '/') || str_contains($output, '\\') || str_contains($output, "\0")) {
                unset($settings['output']);
            }
        }

        return $settings;
    }

    public function getAll()
    {
        if ($this->type === self::TYPE['APP']) {
            return $this->getAllAppValues();
        }

        return $this->getAllUserSettings();
    }

    public function save($key, $value)
    {
        try {
            if ($this->type == self::TYPE['USER'] && isset($this->user)) {
                $this->appConfig->setUserValue($this->user, $this->appName, $key, $value);
            } elseif ($this->type == self::TYPE['SYSTEM']) {
                $this->appConfig->setSystemValue($key, $value);
            } else {
                $this->appConfig->setAppValue($this->appName, $key, $value);
            }
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }

        return ['message' => 'Saved!'];
    }

    public function getAllAppValues()
    {
        $keys = array_unique(array_merge(
            $this->appConfig->getAppKeys($this->legacyAppName),
            $this->appConfig->getAppKeys($this->appName)
        ));

        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->getAppValueWithLegacyFallback($key);
        }
        return $values;
    }

    public function getAllKeys()
    {
        return array_unique(array_merge(
            $this->appConfig->getAppKeys($this->legacyAppName),
            $this->appConfig->getAppKeys($this->appName)
        ));
    }

    public function getAllUserSettings()
    {
        if (!isset($this->user)) {
            return [];
        }

        $keys = array_unique(array_merge(
            $this->appConfig->getUserKeys($this->user, $this->legacyAppName),
            $this->appConfig->getUserKeys($this->user, $this->appName)
        ));

        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->getUserValueWithLegacyFallback($key);
        }
        return $values;
    }
}
