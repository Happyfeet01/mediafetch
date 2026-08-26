<?php

namespace OCA\NCDownloader\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Settings\ISettings;
use OCA\NCDownloader\Tools\Helper;

class Admin implements ISettings
{
    private $connection;
    private $timeFactory;
    private $config;

    public function __construct(
        IDBConnection $connection,
        ITimeFactory $timeFactory,
        IConfig $config
    ) {
        $this->connection = $connection;
        $this->timeFactory = $timeFactory;
        $this->config = $config;
    }

    public function getForm()
    {
        $aria2Version = null;
        $ytdlVersion = null;

        try {
            $aria2Version = Helper::getAria2Version();
        } catch (\Throwable $e) {
            Helper::debug('Unable to read aria2 version: ' . $e->getMessage());
        }

        try {
            $ytdlVersion = Helper::getYtdlVersion();
        } catch (\Throwable $e) {
            Helper::debug('Unable to read yt-dlp version: ' . $e->getMessage());
        }

        $settings = Helper::getAllAdminSettings();
        $settings += [
            'path' => '/apps/mediafetch/admin/save',
            'aria2_version' => $aria2Version,
            'ytdl_version' => $ytdlVersion,
        ];

        $parameters = [
            'settings' => $settings,
            'options' => Helper::getAdminOptions($settings),
        ];

        return new TemplateResponse('mediafetch', 'settings/Admin', $parameters, '');
    }

    public function getSection(): string
    {
        return 'mediafetch';
    }

    public function getPriority(): int
    {
        return 0;
    }
}
