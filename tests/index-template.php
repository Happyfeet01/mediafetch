<?php
/**
 * Regression for broken app settings-data markup:
 * Provider labels with spaces used to truncate an unquoted HTML attribute,
 * breaking JSON.parse() and leaving the whole MediaFetch frontend blank.
 */
declare(strict_types=1);

namespace OCP {
    class Util {
        public static function addHeader(string $type, array $attributes): void {}
    }
}

namespace {
    final class MediaFetchTemplateHarness
    {
        public function inc(string $template): string
        {
            // The nav and content are unrelated to the JSON attribute contract.
            return '';
        }

        public function render(string $searchSites, string $settings): string
        {
            $_ = ['search_sites' => $searchSites, 'settings' => $settings];
            ob_start();
            include __DIR__ . '/../templates/Index.php';
            return (string) ob_get_clean();
        }
    }

    $sites = [
        ['name' => 'TPB', 'label' => 'THEPIRATEBAY'],
        ['name' => 'InternetArchive', 'label' => 'INTERNET ARCHIVE'],
        ['name' => 'AcademicTorrents', 'label' => 'Academic Torrents'],
        ['name' => 'example', 'label' => 'Quote " and apostrophe \' & <test>'],
    ];
    $settings = [
        'ncd_downloader_dir' => '/Downloads/Music "test" & <covers>',
        'is_admin' => true,
    ];

    $rendered = (new MediaFetchTemplateHarness())->render(
        (string) json_encode($sites, JSON_THROW_ON_ERROR),
        (string) json_encode($settings, JSON_THROW_ON_ERROR)
    );

    $doc = new \DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $parsed = $doc->loadHTML($rendered);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    if (!$parsed) {
        throw new \RuntimeException('MediaFetch Index.php could not be parsed as HTML.');
    }

    $node = $doc->getElementById('app-settings-data');
    if (!$node instanceof \DOMElement) {
        throw new \RuntimeException('MediaFetch settings data container is missing.');
    }

    foreach (['data-search-sites' => $sites, 'data-settings' => $settings] as $attr => $expected) {
        if (!$node->hasAttribute($attr)) {
            throw new \RuntimeException("Missing attribute $attr");
        }

        $actual = json_decode($node->getAttribute($attr), true, 512, JSON_THROW_ON_ERROR);
        if ($actual !== $expected) {
            throw new \RuntimeException("Broken JSON HTML attribute $attr (names with spaces, quotes or symbols)");
        }
    }

    echo "MediaFetch settings-data JSON attributes round-trip safely.\n";
}
