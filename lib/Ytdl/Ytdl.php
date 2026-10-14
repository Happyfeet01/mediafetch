<?php

namespace OCA\NCDownloader\Ytdl;

use OCA\NCDownloader\Tools\Helper;
use OCA\NCDownloader\Ytdl\Helper as YtdHelper;
use Symfony\Component\Process\Process;

class Ytdl
{
    public $audioOnly = 0;
    public $audioFormat = 'm4a', $videoFormat = null;
    public $dbDlPath = null;
    private $format = 'bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best';
    private $options = [];
    private array $personalSettings = [];

    // Only these yt-dlp options are switches without a following argument.
    // A missing value on a value-taking option must not swallow the next
    // switch (e.g. --convert-thumbnails --parse-metadata).
    private const VALUELESS_OPTIONS = [
        'ignore-errors', 'no-abort-on-error', 'abort-on-error',
        'flat-playlist', 'no-flat-playlist', 'live-from-start', 'no-live-from-start',
        'no-colors', 'force-ipv4', 'force-ipv6', 'no-match-filter',
        'no-playlist', 'yes-playlist', 'break-on-existing', 'break-on-reject',
        'break-per-input', 'no-break-per-input', 'skip-unavailable-fragments',
        'abort-on-unavailable-fragment', 'keep-fragments', 'no-keep-fragments',
        'resize-buffer', 'no-resize-buffer', 'playlist-reverse',
        'no-playlist-reverse', 'playlist-random', 'restrict-filenames',
        'no-restrict-filenames', 'windows-filenames', 'no-windows-filenames',
        'no-overwrites', 'force-overwrites', 'no-force-overwrites',
        'continue', 'no-continue', 'part', 'no-part', 'mtime', 'no-mtime',
        'write-description', 'no-write-description', 'write-info-json',
        'no-write-info-json', 'write-playlist-metafiles', 'no-write-playlist-metafiles',
        'clean-info-json', 'no-clean-info-json', 'write-comments',
        'no-write-comments', 'write-thumbnail', 'no-write-thumbnail',
        'write-all-thumbnails', 'list-thumbnails', 'no-simulate',
        'skip-download', 'dump-json', 'dump-single-json', 'newline',
        'no-progress', 'progress', 'verbose', 'format-sort-force',
        'no-format-sort-force', 'video-multistreams', 'no-video-multistreams',
        'audio-multistreams', 'no-audio-multistreams', 'prefer-free-formats',
        'no-prefer-free-formats', 'check-formats', 'check-all-formats',
        'no-check-formats', 'list-formats', 'write-subs', 'no-write-subs',
        'write-auto-subs', 'no-write-auto-subs', 'list-subs',
        'extract-audio', 'keep-video', 'no-keep-video',
        'post-overwrites', 'no-post-overwrites', 'embed-subs', 'no-embed-subs',
        'embed-thumbnail', 'no-embed-thumbnail', 'embed-metadata',
        'no-embed-metadata', 'embed-chapters', 'no-embed-chapters',
        'embed-info-json', 'no-embed-info-json', 'split-chapters',
        'no-split-chapters', 'force-keyframes-at-cuts',
        'no-force-keyframes-at-cuts', 'no-sponsorblock', 'allow-dynamic-mpd',
        'ignore-dynamic-mpd', 'hls-split-discontinuity',
        'no-hls-split-discontinuity',
    ];
    private $downloadDir;
    private $timeout = 60 * 60 * 10;
    private $outTpl = "%(title).32s.%(ext)s";
    private $defaultDir = "/tmp/downloads";
    private $env = [];
    private $bin;
    private $cmd;
    private $completedFileHandler = null;
    private $afterMoveLog = null;
    private $afterMoveOffset = 0;
    public $helper;

    public function __construct(array $options)
    {
        $options += ['downloadDir' => '/tmp/downloads', 'settings' => []];
        $this->init($options);
    }

    public function init(array $options)
    {
        extract($options);
        if (!empty($binary)) {
            $this->bin = $binary;
        } else {
            $this->bin = __DIR__ . "/../../bin/yt-dlp";
        }
        if ($this->isInstalled() && !$this->isExecutable()) {
            chmod($this->bin, 0744);
        }
        $this->setDownloadDir($downloadDir);
        $this->personalSettings = is_array($settings) ? $settings : [];
        $this->configureDownloadOptions($this->personalSettings);
        if (empty($lang = getenv('LANG')) || strpos(strtolower($lang), 'c.utf-8') === false) {
            $lang = 'C.UTF-8';
        }
        $this->setEnv('LANG', $lang);
    }

    /**
     * Change only this download's settings. The persisted Nextcloud personal
     * options remain untouched.
     */
    public function applyDownloadPreset(string $preset): void
    {
        if (!DownloadPresets::isValid($preset)) {
            throw new \InvalidArgumentException('Unknown MediaFetch download preset.');
        }
        if ($preset === DownloadPresets::PERSONAL) {
            return;
        }

        $this->configureDownloadOptions(DownloadPresets::apply($this->personalSettings, $preset));
        $this->videoFormat = null; // Presets select/merge rather than re-encode videos.
        $this->audioOnly = DownloadPresets::isMusic($preset);
        if ($this->audioOnly) {
            $this->audioFormat = 'mp3';
        }
    }

    private function configureDownloadOptions(array $settings): void
    {
        $this->options = [];
        $this->outTpl = '%(title).32s.%(ext)s';

        foreach ($settings as $key => $value) {
            $key = ltrim((string) $key, '-');
            if ($key === 'output' && is_scalar($value) && (string) $value !== '') {
                $this->outTpl = (string) $value;
                continue;
            }
            if (in_array($key, self::VALUELESS_OPTIONS, true)) {
                // Switches use no argument regardless of a legacy empty value.
                $this->addOption($key, true);
            } elseif (is_scalar($value) && (string) $value !== '') {
                $this->setOption($key, (string) $value, true);
            }
            // Ignore an empty value-taking option rather than accidentally
            // passing the next yt-dlp switch as its parameter.
        }

        $this->addOption('--no-mtime');
        $this->addOption('--ignore-errors');
    }

    public function setEnv($key, $val)
    {
        $this->env[$key] = $val;
    }

    public function audioMode()
    {
        if (Helper::ffmpegInstalled()) {
            $this->addOption('--extract-audio');
        } else {
            $this->audioFormat = "m4a";
        }
        $this->setAudioFormat($this->audioFormat);
        return $this;
    }

    public function setAudioQuality($value = 0)
    {
        $this->setOption('--audio-quality', $value);
    }

    public function setAudioFormat($format)
    {
        $this->setOption('--audio-format', $format);
    }

    public function setVideoFormat($format)
    {
        $this->setOption('--recode-video', $format);
    }

    public function GetUrlOnly()
    {
        $this->addOption('--get-filename');
        $this->addOption('--get-url');
        return $this;
    }

    public static function create($options)
    {
        return new self($options);
    }

    public function setDownloadDir($dir)
    {
        $this->downloadDir = rtrim((string) $dir, '/');
        return $this;
    }

    public function getDownloadDir()
    {
        return $this->downloadDir;
    }

    public function setCompletedFileHandler(?callable $handler): self
    {
        $this->completedFileHandler = $handler;
        return $this;
    }

    public function prependOption(string $option)
    {
        array_unshift($this->options, $option);
    }

    public function download($url)
    {
        if ($this->audioOnly) {
            $this->audioMode();
        } else {
            // A personal --format selector (for example the GUI quality limit)
            // must not be overwritten by MediaFetch's default format selector.
            if ($this->hasOption('--format') === false) {
                $this->setOption(
                    '--format',
                    Helper::ffmpegInstalled() && $this->videoFormat
                        ? 'bestvideo+bestaudio/best'
                        : $this->format
                );
            }
            if (Helper::ffmpegInstalled() && $this->videoFormat) {
                $this->setVideoFormat($this->videoFormat);
            }
        }

        $this->helper = YtdHelper::create();
        $this->downloadDir = $this->downloadDir ?: $this->defaultDir;
        $this->setOption("--output", $this->downloadDir . "/" . $this->outTpl);
        $this->configureAfterMoveTracking();
        $this->setUrl($url);
        $this->prependOption($this->bin);

        $data = ['link' => $url, 'path' => $this->dbDlPath];
        if ($this->audioOnly) {
            $data['ext'] = $this->audioFormat;
        } else {
            $data['ext'] = $this->videoFormat;
        }
        $this->helper->start($url, $data);

        $process = new Process($this->options, null, $this->env);
        $process->setTimeout($this->timeout);

        try {
            $process->run(function ($type, $buffer) use ($data, $process) {
                $this->drainCompletedFiles();

                $extra = $data;
                $extra['pid'] = $process->getPid();
                if (Process::ERR === $type) {
                    $this->onError($buffer, $extra);
                } else {
                    $this->onOutput($buffer, $extra);
                }

                $this->drainCompletedFiles();
            });

            $this->drainCompletedFiles();
        } finally {
            $this->cleanupAfterMoveTracking();
        }

        if ($process->isSuccessful()) {
            $this->helper->updateAllStatus(Helper::STATUS['WAITING'], true);
            return ['message' => $this->helper->file ?? 'Download finished'];
        }

        $this->helper->updateAllStatus(Helper::STATUS['ERROR'], true);
        return ['error' => $process->getErrorOutput() ?: 'yt-dlp failed'];
    }

    public function markCurrentImporting(string $source): void
    {
        if ($this->helper) {
            $this->helper->markImportingFile($source);
        }
    }

    public function markCurrentImported(string $source, string $filename): void
    {
        if ($this->helper) {
            $this->helper->markImportedFile($source, $filename);
        }
    }

    public function markImported(array $imported): void
    {
        if (!$this->helper) {
            return;
        }
        $this->helper->applyImportedNames($imported);
        $this->helper->updateAllStatus(Helper::STATUS['COMPLETE'], true);
    }

    public function markImportFailed(): void
    {
        if ($this->helper) {
            $this->helper->updateAllStatus(Helper::STATUS['ERROR'], true);
        }
    }

    private function configureAfterMoveTracking(): void
    {
        if (!$this->completedFileHandler || $this->downloadDir === '') {
            return;
        }

        $this->afterMoveLog = $this->downloadDir . '/.mediafetch-after-move';
        $this->afterMoveOffset = 0;
        @unlink($this->afterMoveLog);

        $this->addOption('--print-to-file');
        $this->addOption('after_move:%(filepath)s');
        $this->addOption($this->afterMoveLog);
    }

    private function drainCompletedFiles(): void
    {
        if (!$this->completedFileHandler || !$this->afterMoveLog || !is_file($this->afterMoveLog)) {
            return;
        }

        $handle = @fopen($this->afterMoveLog, 'rb');
        if (!is_resource($handle)) {
            return;
        }

        try {
            if ($this->afterMoveOffset > 0 && fseek($handle, $this->afterMoveOffset) !== 0) {
                $this->afterMoveOffset = 0;
                rewind($handle);
            }

            while (($line = fgets($handle)) !== false) {
                $path = rtrim($line, "\r\n");
                if ($path !== '') {
                    ($this->completedFileHandler)($path);
                }
            }

            $offset = ftell($handle);
            if ($offset !== false) {
                $this->afterMoveOffset = $offset;
            }
        } finally {
            fclose($handle);
        }
    }

    private function cleanupAfterMoveTracking(): void
    {
        if ($this->afterMoveLog) {
            @unlink($this->afterMoveLog);
        }
        $this->afterMoveLog = null;
        $this->afterMoveOffset = 0;
    }

    private function onError($buffer, array $extra)
    {
        $this->helper->log($buffer);
        $this->helper->run($buffer, $extra);
    }

    public function onOutput($buffer, $extra)
    {
        $this->helper->run($buffer, $extra);
    }

    public function getDownloadUrl($url)
    {
        $this->setUrl($url);
        $this->GetUrlOnly();
        $this->buildCMD();
        exec($this->cmd, $output, $returnCode);
        if (count($output) === 1) {
            return ['url' => reset($output)];
        }
        list($url, $filename) = $output;
        $filename = Helper::cleanString($filename);
        return ['url' => $url, 'filename' => Helper::clipFilename($filename)];
    }

    public function setUrl($url)
    {
        $this->prependOption($url);
    }

    public function setOption($key, $value, $hyphens = false)
    {
        $this->addOption($key, $hyphens);
        $this->addOption($value, false);
        return $this;
    }

    public function addOption(String $option, $hyphens = false)
    {
        if ($hyphens && substr($option, 0, 2) !== '--') {
            $option = "--" . $option;
        }
        array_push($this->options, $option);
    }

    protected function hasOption($name)
    {
        return array_search($name, $this->options, true);
    }

    public function forceIPV4()
    {
        $this->addOption('force-ipv4', true);
        return $this;
    }

    private function buildCMD()
    {
        $this->cmd = $this->bin;
        foreach ($this->options as $option) {
            $this->cmd .= " " . escapeshellarg((string) $option);
        }
    }

    public function isInstalled()
    {
        return @is_file($this->bin);
    }

    public function isExecutable()
    {
        return @is_executable($this->bin);
    }

    public function isReadable()
    {
        return @is_readable($this->bin);
    }

    public function getBin()
    {
        return $this->bin;
    }

    public function install()
    {
        $url = $this->installUrl();
        $file = __DIR__ . "/../../bin/yt-dlp2";
        try {
            Helper::Download($url, $file);
            chmod($file, 0744);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
        return false;
    }

    public function installUrl()
    {
        return "https://github.com/shiningw/ncdownloader-bin/raw/master/yt-dlp";
    }

    public function version()
    {
        $process = new Process([$this->bin, '--version']);
        $process->run();
        if ($process->isSuccessful()) {
            return trim($process->getOutput());
        }
        return false;
    }

    public function check()
    {
        if ($tagName = Helper::getLatestRelease('yt-dlp', 'yt-dlp')) {
            $tagName = Helper::removeLetters($tagName);
            $version = $this->version();
            if ($version && version_compare($version, $tagName, '<')) {
                return ['status' => true, 'message' => $tagName];
            }
        }
        return ['status' => false, 'message' => 'No update available'];
    }

    public function update()
    {
        $file = __DIR__ . "/../../bin/yt-dlp";
        try {
            Helper::downloadLatestRelease('yt-dlp', 'yt-dlp', $file);
            chmod($file, 0744);
        } catch (\Exception $e) {
            return ['status' => false,'message' => $e->getMessage()];
        }
        return ['status' => true, 'message' => 'Updated to latest version','data' => $this->version()];
    }
}
