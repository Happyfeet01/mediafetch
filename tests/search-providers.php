<?php

declare(strict_types=1);

// Standalone regression tests for the bundled search provider integrations.
// No Nextcloud instance or internet access is required.
require_once __DIR__ . '/../lib/Tools/tableData.php';
require_once __DIR__ . '/../lib/Search/Sites/searchBase.php';
require_once __DIR__ . '/../lib/Search/Sites/searchInterface.php';
require_once __DIR__ . '/../lib/Search/Sites/AcademicTorrents.php';
require_once __DIR__ . '/../lib/Search/Sites/InternetArchive.php';

final class OC
{
    public static object $server;
}

final class SearchTestAppDataFactory
{
    public string $appId = '';

    public function get(string $appId): SearchTestAppData
    {
        $this->appId = $appId;
        return new SearchTestAppData();
    }
}

final class SearchTestAppData
{
    public function getFolder(string $folder): SearchTestFolder
    {
        if ($folder !== 'academic-torrents') {
            throw new \RuntimeException('Unexpected cache folder');
        }
        return new SearchTestFolder();
    }
}

final class SearchTestFolder
{
    public function getFile(string $name): SearchTestFile
    {
        if ($name !== 'catalog.xml') {
            throw new \RuntimeException('Unexpected cache filename');
        }
        return new SearchTestFile();
    }
}

final class SearchTestFile
{
    public function getMtime(): int
    {
        return time();
    }

    public function getContent(): string
    {
        return '<rss version="2.0"><channel><item>'
            . '<title>Small research dataset</title>'
            . '<infohash>b2bbaccd349e8e2954a438ced6fc01adae4ea1f1</infohash>'
            . '<description>A reproducible research test dataset</description>'
            . '<size>1000</size></item></channel></rss>';
    }
}

final class SearchTestServer
{
    public SearchTestAppDataFactory $factory;

    public function __construct()
    {
        $this->factory = new SearchTestAppDataFactory();
    }

    public function get(string $name): object
    {
        if ($name === 'OCP\Files\AppData\IAppDataFactory') {
            return $this->factory;
        }

        if ($name === 'OCP\IURLGenerator') {
            return new class {
                public function linkToRoute(string $route): string
                {
                    if ($route !== 'mediafetch.main.download') {
                        throw new \RuntimeException('Unexpected route');
                    }
                    return '/apps/mediafetch/new';
                }
            };
        }

        throw new \RuntimeException('Unexpected Nextcloud service requested: ' . $name);
    }
}

final class SearchTestArchiveHttpResponse
{
    public function toArray(): array
    {
        return [
            'response' => [
                'docs' => [
                    [
                        'identifier' => 'prelinger-test',
                        'title' => 'A public historical film',
                        'date' => '1960-01-01',
                    ],
                ],
            ],
        ];
    }
}

final class SearchTestArchiveHttpClient
{
    public string $lastUrl = '';

    public function request(string $method, string $url, array $options): SearchTestArchiveHttpResponse
    {
        if ($method !== 'GET') {
            throw new \RuntimeException('Expected GET');
        }
        $this->lastUrl = $url;
        return new SearchTestArchiveHttpResponse();
    }
}

function searchAssert(bool $ok, string $message): void
{
    if (!$ok) {
        throw new \RuntimeException($message);
    }
}

\OC::$server = new SearchTestServer();

if (class_exists(\XMLReader::class) && function_exists('simplexml_load_string') && function_exists('mb_strtolower')) {
    $provider = new \OCA\NCDownloader\Search\Sites\AcademicTorrents(null, new \stdClass());
    $result = $provider->search('research');
    searchAssert(!$result->hasError(), 'Academic Torrents cached search returned an error');
    searchAssert(\OC::$server->factory->appId === 'mediafetch', 'Expected per-app Nextcloud AppDataFactory');
    $items = $result->getData()['row'];
    searchAssert(count($items) === 1, 'Expected cached Academic Torrents dataset');
    searchAssert(str_starts_with($items[0]['data-link'], 'magnet:?xt=urn:btih:b2bbaccd'),
        'Expected a magnet URI');
}

$http = new SearchTestArchiveHttpClient();
$archive = new \OCA\NCDownloader\Search\Sites\InternetArchive(null, $http);
$result = $archive->search('Prelinger');
searchAssert(!$result->hasError(), 'Internet Archive test search returned an error');
searchAssert(count($result->getData()['row']) === 1, 'Expected Internet Archive result');
searchAssert(str_contains($http->lastUrl, 'fl%5B%5D=identifier&fl%5B%5D=title'),
    'Expected repeated Internet Archive fl[] fields');
searchAssert(!str_contains($http->lastUrl, 'fl%5B%5D%5B0%5D'),
    'Found invalid nested fl[][] fields');
searchAssert($result->getData()['row'][0]['data-link'] ===
    'https://archive.org/download/prelinger-test/prelinger-test_archive.torrent',
    'Unexpected Archive BitTorrent download URL');

echo "Academic Torrents AppDataFactory and Internet Archive request regressions passed.\n";
