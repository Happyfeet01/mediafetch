<?php

declare(strict_types=1);

namespace OCA\NCDownloader\Ytdl;

/**
 * Per-download presets. These never overwrite a user's stored yt-dlp settings.
 * Settings unrelated to the selected output (e.g. proxy, rate limit and custom
 * metadata parsing) are retained.
 */
final class DownloadPresets
{
    public const PERSONAL = 'personal';
    public const VIDEO = 'youtube_video';
    public const VIDEO_PLAYLIST = 'video_playlist';
    public const TRACK = 'music_track';
    public const ALBUM = 'music_album';
    public const PLAYLIST = 'music_playlist';

    public static function isValid(string $name): bool
    {
        return in_array($name, [
            self::PERSONAL, self::VIDEO, self::VIDEO_PLAYLIST,
            self::TRACK, self::ALBUM, self::PLAYLIST,
        ], true);
    }

    public static function isMusic(string $name): bool
    {
        return in_array($name, [self::TRACK, self::ALBUM, self::PLAYLIST], true);
    }

    public static function apply(array $saved, string $name): array
    {
        if ($name === self::PERSONAL) {
            return $saved;
        }
        if (!self::isValid($name)) {
            throw new \InvalidArgumentException('Unknown download preset.');
        }

        // Remove conflicting audio/video and playlist selectors, but retain
        // metadata parsers, proxy configuration, rate limit, filename safety,
        // subtitles and other user customizations.
        foreach ([
            'extract-audio', 'audio-format', 'audio-quality', 'format',
            'merge-output-format', 'recode-video', 'remux-video', 'keep-video',
            'no-keep-video', 'yes-playlist', 'no-playlist',
            'flat-playlist', 'no-flat-playlist', 'output',
        ] as $key) {
            unset($saved[$key]);
        }

        if (self::isMusic($name)) {
            $saved['extract-audio'] = '';
            $saved['audio-format'] = 'mp3';
            $saved['audio-quality'] = '0';
            $saved['embed-metadata'] = '';
            $saved['embed-thumbnail'] = '';
            unset($saved['no-embed-metadata'], $saved['no-embed-thumbnail']);
            // Only embed available metadata. A filename does not imply
            // that every YouTube upload has artist/album ID3 information.
            if ($name === self::TRACK) {
                $saved['no-playlist'] = '';
                $saved['output'] = '%(artist,creator,uploader|Unknown Artist)s - %(title)s.%(ext)s';
            } else {
                $saved['yes-playlist'] = '';
                $saved['output'] = '%(playlist_index,track_number&{} - |)s%(artist,creator,uploader|Unknown Artist)s - %(title)s.%(ext)s';
            }
        } else {
            $saved['format'] = 'bv*[height<=1080]+ba/b[height<=1080]';
            $saved['merge-output-format'] = 'mp4';
            if ($name === self::VIDEO) {
                $saved['no-playlist'] = '';
                $saved['output'] = '%(title)s.%(ext)s';
            } else {
                $saved['yes-playlist'] = '';
                $saved['output'] = '%(playlist_index,track_number&{} - |)s%(title)s.%(ext)s';
            }
        }
        return $saved;
    }
}
