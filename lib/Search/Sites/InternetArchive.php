<?php

namespace OCA\NCDownloader\Search\Sites;

use OCA\NCDownloader\Tools\tableData;

/**
 * Searches the Internet Archive's official JSON API for items with Archive
 * BitTorrent derivatives. Uses canonical archive.org download links rather
 * than scraping site markup or downloading unverified torrent files.
 */
class InternetArchive extends searchBase implements searchInterface
{
    private const API_URL = 'https://archive.org/advancedsearch.php';

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

        // Treat the query as literal keywords, not arbitrary Solr clauses.
        $words = preg_split('/\s+/u', preg_replace('/[^\p{L}\p{N}\s._-]+/u', ' ', $keyword), -1, PREG_SPLIT_NO_EMPTY);
        if (!$words) {
            return tableData::create()->setError('Please enter a search term.');
        }

        $query = implode(' AND ', array_map(static fn(string $word): string => '"' . $word . '"', array_slice($words, 0, 8)))
            . ' AND format:"Archive BitTorrent"';

        try {
            $response = $this->client->request('GET', self::API_URL, [
                'query' => [
                    'q' => $query,
                    'rows' => 20,
                    'page' => 1,
                    'output' => 'json',
                    'fl[]' => ['identifier', 'title', 'date'],
                ],
                'timeout' => 15,
            ]);
            $json = $response->toArray();
            $docs = $json['response']['docs'] ?? [];
            if (!is_array($docs)) {
                throw new \RuntimeException('Invalid Internet Archive response.');
            }

            foreach ($docs as $doc) {
                if (!is_array($doc)) {
                    continue;
                }

                $id = (string) ($doc['identifier'] ?? '');
                // Internet Archive identifiers cannot contain path separators.
                if (!preg_match('/^[A-Za-z0-9._-]{1,200}$/D', $id)) {
                    continue;
                }

                $title = (string) ($doc['title'] ?? $id);
                $this->rows[] = [
                    'title' => $title,
                    'data-link' => 'https://archive.org/download/'
                        . rawurlencode($id) . '/' . rawurlencode($id . '_archive.torrent'),
                    'seeders' => '—',
                    'info' => 'Internet Archive' . (empty($doc['date']) ? '' : ' · ' . substr((string) $doc['date'], 0, 10)),
                ];
            }
        } catch (\Throwable $e) {
            return tableData::create()->setError('Internet Archive search failed: ' . $e->getMessage());
        }

        $this->addActionLinks();
        return tableData::create($this->getTableTitles(), $this->getRows());
    }

    public static function getLabel(): string
    {
        return 'Internet Archive';
    }
}
