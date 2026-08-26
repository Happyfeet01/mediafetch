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
        $settings = Helper::getAllAdminSettings();
        $settings += [
            'path' => '/apps/mediafetch/admin/save',
            'aria2_version' => Helper::getAria2Version(),
            'ytdl_version' => Helper::getYtdlVersion(),
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
