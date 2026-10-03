<?php

namespace OCA\NCDownloader\Search\Sites;

abstract class searchBase
{
    protected $query = null;
    protected $tableTitles = [];
    protected $rows = [];
    protected $errors = [];
    protected $searchUrl;
    protected $crawler;
    protected $client;
    protected $actionLinks = [["name" => 'download', 'path' => '/apps/mediafetch/new'], ['name' => 'clipboard']];

    public function getTableTitles(): array
    {
        if (empty($this->tableTitles)) {
            return ['title', 'seeders', 'info', 'actions'];
        }
        return $this->tableTitles;
    }

    public static function create($crawler, $client)
    {

        return new static($crawler, $client);

    }

    public function setTableTitles(array $titles)
    {
        $this->tableTitles = $titles;
        return $this;
    }

    protected function addActionLinks(?array $links = null)
    {
        $links = $links ?? $this->actionLinks;
        foreach ($this->rows as $key => &$value) {
            if (!$value) {
                continue;
            }
            // Generate paths for installations in a subdirectory as well.
            $value['actions'] = array_map(static function ($link) {
                if (!empty($link['path'])) {
                    $link['path'] = \OC::$server->get(\OCP\IURLGenerator::class)->linkToRoute('mediafetch.main.download');
                }
                return $link;
            }, $links);
        }
    }
    public function getRows(): array
    {
        return $this->rows;
    }

    public function hasErrors(): bool
    {
        return (bool) (count($this->errors) > 0);
    }

    public function getErrors(): string
    {
        return implode(",", $this->errors);
    }

}
