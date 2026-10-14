<?php

namespace OCA\NCDownloader\Search\Sites;

use OCA\NCDownloader\Tools\tableData;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;

/**
 * Searches Academic Torrents' officially published full XML catalog locally.
 *
 * Academic Torrents recommends this catalog for programmatic full-text search
 * rather than scraping browse.php or querying its website on each keystroke.
 */
class AcademicTorrents extends searchBase implements searchInterface
{
    private const CATALOG_URL = 'https://academictorrents.com/database.xml';
    private const CACHE_FOLDER = 'academic-torrents';
    private const CACHE_FILENAME = 'catalog.xml';
    private const CACHE_TTL = 86400;
    private const MAX_CATALOG_BYTES = 32 * 1024 * 1024;

    public function __construct($crawler, $client)
    {
        $this->client = $client;
    }

    public function search(string $keyword): tableData
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return tableData::create()->setError('Please enter a search term.');
        }

        if (!class_exists(\XMLReader::class)) {
            return tableData::create()->setError('The PHP XMLReader extension is required for Academic Torrents search.');
        }

        try {
            $catalog = $this->getCatalog();
            $this->parseCatalog($catalog, $keyword);
        } catch (\Throwable $e) {
            return tableData::create()->setError('Academic Torrents search failed: ' . $e->getMessage());
        }

        $this->addActionLinks();
        return tableData::create($this->getTableTitles(), $this->getRows());
    }

    private function getCatalog(): string
    {
        /** @var IAppData $appData */
        $appData = \OC::$server->get(IAppData::class);
        try {
            $folder = $appData->getFolder(self::CACHE_FOLDER);
        } catch (NotFoundException $e) {
            $folder = $appData->newFolder(self::CACHE_FOLDER);
        }

        $file = null;
        try {
            $file = $folder->getFile(self::CACHE_FILENAME);
        } catch (NotFoundException $e) {
            // Download the first snapshot.
        }

        if ($file !== null && $file->getMtime() > time() - self::CACHE_TTL) {
            return $file->getContent();
        }

        try {
            $response = $this->client->request('GET', self::CATALOG_URL, [
                'timeout' => 30,
            ]);
            $catalog = $response->getContent();
            if (strlen($catalog) > self::MAX_CATALOG_BYTES) {
                throw new \RuntimeException('The Academic Torrents catalog exceeds the supported size.');
            }
            if (stripos($catalog, '<rss') === false) {
                throw new \RuntimeException('The source did not return an XML torrent catalog.');
            }

            if ($file === null) {
                $folder->newFile(self::CACHE_FILENAME, $catalog);
            } else {
                $file->putContent($catalog);
            }

            return $catalog;
        } catch (\Throwable $e) {
            // Temporary upstream outages should not break an existing cache.
            if ($file !== null) {
                return $file->getContent();
            }
            throw $e;
        }
    }

    private function parseCatalog(string $catalog, string $keyword): void
    {
        $reader = new \XMLReader();
        if (!$reader->XML($catalog, null, LIBXML_NONET | LIBXML_NOCDATA)) {
            throw new \RuntimeException('Invalid Academic Torrents catalog.');
        }

        $terms = preg_split('/\s+/u', mb_strtolower($keyword, 'UTF-8'), -1, PREG_SPLIT_NO_EMPTY);
        try {
            while ($reader->read() && count($this->rows) < 30) {
                if ($reader->nodeType !== \XMLReader::ELEMENT || $reader->localName !== 'item') {
                    continue;
                }

                $item = @simplexml_load_string($reader->readOuterXML(), \SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOCDATA);
                if ($item === false) {
                    continue;
                }

                $title = trim((string) $item->title);
                $description = trim(strip_tags((string) $item->description));
                $haystack = mb_strtolower($title . ' ' . $description, 'UTF-8');
                if ($title === '' || !array_reduce($terms, static fn(bool $match, string $term): bool =>
                    $match && mb_strpos($haystack, $term, 0, 'UTF-8') !== false, true)) {
                    continue;
                }

                $hash = strtolower(trim((string) $item->infohash));
                if ($hash === '') {
                    $hash = strtolower(trim((string) $item->children('http://academictorrents.com')->infohash));
                }
                if (!preg_match('/^[a-f0-9]{40}$/D', $hash)) {
                    continue;
                }

                $size = (float) ((string) $item->size);
                $sizeLabel = $size > 0 ? number_format($size / (1024 ** 3), 2, '.', '') . ' GiB' : 'Size unknown';
                $this->rows[] = [
                    'title' => $title,
                    'data-link' => 'magnet:?xt=urn:btih:' . $hash . '&dn=' . rawurlencode($title),
                    'seeders' => '—',
                    'info' => 'Academic Torrents · ' . $sizeLabel,
                ];
            }
        } finally {
            $reader->close();
        }
    }

    public static function getLabel(): string
    {
        return 'Academic Torrents';
    }
}
