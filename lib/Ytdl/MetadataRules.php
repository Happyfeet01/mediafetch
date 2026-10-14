<?php

declare(strict_types=1);

namespace OCA\NCDownloader\Ytdl;

/**
 * Minimal syntax guard for the user-configured yt-dlp --parse-metadata rule.
 * The complete expression is interpreted by yt-dlp itself; this deliberately
 * only rejects the common mistake of pasting an output filename template.
 */
final class MetadataRules
{
    public static function validParseMetadata($rule): bool
    {
        if (!is_string($rule) || trim($rule) === '') {
            return false;
        }

        // yt-dlp requires FROM:TO. The separator must have content on both
        // sides; output templates like "%(artist)s - %(title)s.%(ext)s" aren't
        // metadata parsing rules.
        $separator = strpos($rule, ':');
        return $separator !== false
            && trim(substr($rule, 0, $separator)) !== ''
            && trim(substr($rule, $separator + 1)) !== '';
    }

    public static function message(): string
    {
        return 'Invalid parse-metadata option: use FROM:TO, for example '
            . '%(title)s:(?P<artist>.+?) - (?P<title>.+). '
            . 'Filename templates belong in the output option.';
    }
}
