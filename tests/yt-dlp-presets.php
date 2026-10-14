<?php

declare(strict_types=1);

require __DIR__ . '/../lib/Ytdl/DownloadPresets.php';
require __DIR__ . '/../lib/Ytdl/MetadataRules.php';
require __DIR__ . '/../lib/Ytdl/Ytdl.php';

use OCA\NCDownloader\Ytdl\DownloadPresets;
use OCA\NCDownloader\Ytdl\Ytdl;

function verify(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function argsOf(Ytdl $yt): array
{
    $prop = new ReflectionProperty(Ytdl::class, 'options');
    return $prop->getValue($yt);
}

function templateOf(Ytdl $yt): string
{
    $prop = new ReflectionProperty(Ytdl::class, 'outTpl');
    return $prop->getValue($yt);
}

// A filename template in --parse-metadata caused yt-dlp to abort before
// any YouTube Music album could be processed.
verify(\OCA\NCDownloader\Ytdl\MetadataRules::validParseMetadata('%(title)s:(?P<artist>.+?) - (?P<title>.+)'),
    'Valid yt-dlp FROM:TO parser rejected');
verify(!\OCA\NCDownloader\Ytdl\MetadataRules::validParseMetadata(
    '%(playlist_index,track_number&{} - |)s%(artist,creator,uploader|Unbekannt)s - %(title)s.%(ext)s'
), 'An output filename template was incorrectly accepted as parse-metadata');
verify(!\OCA\NCDownloader\Ytdl\MetadataRules::validParseMetadata(''),
    'An empty parse-metadata rule was incorrectly accepted');

$saved = [
    'convert-thumbnails' => '', // This used to swallow --parse-metadata.
    'parse-metadata' => '%(title)s:(?P<artist>.+?) - (?P<title>.+)',
    'audio-quality' => '0', // PHP empty('0') must not turn this into a flag!
    'yes-playlist' => '',
    'embed-metadata' => '',
    'limit-rate' => '5M',
    'output' => '%(title)s.%(ext)s',
];
$make = static fn(): Ytdl => new Ytdl([
    'binary' => '/no/such/file/yt-dlp',
    'downloadDir' => '/tmp',
    'settings' => $saved,
]);

$default = $make();
$args = argsOf($default);
verify(!in_array('--convert-thumbnails', $args, true),
    'A missing value-taking thumbnail option must be omitted');
$i = array_search('--parse-metadata', $args, true);
verify($i !== false && $args[$i + 1] === $saved['parse-metadata'],
    'A metadata parser must receive its own argument');
$i = array_search('--audio-quality', $args, true);
verify($i !== false && $args[$i + 1] === '0',
    'Zero-valued yt-dlp settings must be retained as arguments');
$i = array_search('--yes-playlist', $args, true);
verify($i !== false && str_starts_with($args[$i + 1], '--'),
    'Valueless playlist switches must not consume the next argument');
verify(templateOf($default) === $saved['output'],
    'Personal filename template was changed unexpectedly');

foreach ([
    DownloadPresets::TRACK, DownloadPresets::ALBUM, DownloadPresets::PLAYLIST,
    DownloadPresets::VIDEO, DownloadPresets::VIDEO_PLAYLIST,
] as $preset) {
    $yt = $make();
    $yt->applyDownloadPreset($preset);
    $options = argsOf($yt);
    $music = DownloadPresets::isMusic($preset);
    verify($yt->audioOnly === $music, "Wrong audio mode: $preset");
    verify($yt->videoFormat === null, "Unexpected recoding: $preset");
    verify(in_array($music ? '--extract-audio' : '--format', $options, true),
        "Preset missing core format option: $preset");
    verify(in_array(
        in_array($preset, [DownloadPresets::TRACK, DownloadPresets::VIDEO], true)
            ? '--no-playlist' : '--yes-playlist',
        $options, true
    ), "Wrong playlist selection: $preset");
    verify(str_contains(templateOf($yt), '%(title)s.%(ext)s'),
        "Preset filename is missing title/ext: $preset");
    verify(in_array('--parse-metadata', $options, true),
        "Preset removed user's metadata parser: $preset");
    verify(in_array('--limit-rate', $options, true),
        "Preset removed user's rate limit: $preset");

    if ($music) {
        $i = array_search('--audio-format', $options, true);
        verify($i !== false && $options[$i + 1] === 'mp3', 'Music preset must use MP3');
        $i = array_search('--audio-quality', $options, true);
        verify($i !== false && $options[$i + 1] === '0', 'Music preset must use quality 0');
        verify(in_array('--embed-thumbnail', $options, true), 'Music preset must embed covers');
    }
}

$yt = $make();
$yt->applyDownloadPreset(DownloadPresets::PERSONAL);
verify(templateOf($yt) === $saved['output'],
    'Personal profile must keep saved filename');
try {
    $yt->applyDownloadPreset('custom;rm -rf /');
    throw new RuntimeException('Unknown profile accepted');
} catch (InvalidArgumentException $expected) {
    // Good: only server-provided presets are allowed.
}
verify($saved['output'] === '%(title)s.%(ext)s',
    'Preset must never overwrite stored personal options');

echo "yt-dlp per-download presets and missing argument regressions passed.\n";
