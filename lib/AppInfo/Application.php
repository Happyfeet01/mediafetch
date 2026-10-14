<?php

namespace OCA\NCDownloader\AppInfo;

use OCA\NCDownloader\Aria2\Aria2;
use OCA\NCDownloader\Db\Settings;
use OCA\NCDownloader\Files\Aria2DownloadScanner;
use OCA\NCDownloader\Files\MediaImporter;
use OCA\NCDownloader\Http\Client;
use OCA\NCDownloader\Tools\Helper;
use OCA\NCDownloader\Ytdl\Ytdl;
use OCP\App\IAppManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

class Application extends App implements IBootstrap
{
    public const APP_ID = 'mediafetch';

    public function __construct(array $urlParams = [])
    {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void
    {
        $context->registerService(Client::class, function () {
            return Client::create(['ipv4' => true]);
        });

        $context->registerService(Crawler::class, function () {
            return new Crawler();
        });

        $context->registerService(MediaImporter::class, function (ContainerInterface $container) {
            return new MediaImporter(
                $container->get(IRootFolder::class),
                $container->get(IConfig::class)
            );
        });

        $context->registerService(Aria2DownloadScanner::class, function (ContainerInterface $container) {
            return new Aria2DownloadScanner(
                $container->get(IRootFolder::class),
                $container->get(LoggerInterface::class)
            );
        });

        $sites = Helper::getSearchSites();
        foreach ($sites as $site) {
            $className = $site['class'];
            $context->registerService($className, function (ContainerInterface $container) use ($className) {
                $crawler = $container->get(Crawler::class);
                $client = $container->get(Client::class);
                return $className::create($crawler, $client);
            });
        }
    }

    public function boot(IBootContext $context): void
    {
        $user = Helper::getUser();
        $uid = $user ? $user->getUID() : '';
        $container = $context->getAppContainer();

        $container->registerService(Aria2::class, function (ContainerInterface $c) use ($uid) {
            $settings = new Settings($uid);
            $options = [];
            $seedTime = $settings->get('ncd_seed_time');
            $seedRatio = $settings->get('ncd_seed_ratio');
            if ($seedTime !== null && (string) $seedTime !== '') {
                $options['seed-time'] = (string) $seedTime;
            }
            if ($seedRatio !== null && (string) $seedRatio !== '') {
                $options['seed-ratio'] = (string) $seedRatio;
            }

            $customSettings = $settings->getAria2();
            if (is_array($customSettings)) {
                $options = array_merge($options, $customSettings);
            }

            // Per-task aria2 RPC option. Completes torrent without further
            // seeding, but DOES NOT prevent uploads during the download.
            if ((string) $settings->get('ncd_stop_seeding', '0') === '1') {
                $options['seed-time'] = '0';
            }

            $proxy = $settings->get('ncd_download_proxy');
            if ($proxy) {
                $options['all-proxy'] = $proxy;
            }

            $config = $c->get(IConfig::class);
            $appManager = $c->get(IAppManager::class);
            $dataDir = (string) $config->getSystemValue('datadirectory');
            $appPath = (string) $appManager->getAppPath(self::APP_ID);
            $aria2Conf = Helper::getSettings('global_aria2_config', [], Settings::TYPE['SYSTEM']);
            if (!is_array($aria2Conf)) {
                $aria2Conf = [];
            }

            $aria2Conf += [
                'on-download-complete' => $appPath . '/hooks/completeHook.sh',
                'on-bt-download-complete' => $appPath . '/hooks/completeHook.sh',
                'on-download-start' => $appPath . '/hooks/startHook.sh',
            ];

            $token = Helper::getAdminSettings('ncd_aria2_rpc_token');
            $rpcHost = Helper::getAdminSettings('ncd_aria2_rpc_host');
            $rpcPort = Helper::getAdminSettings('ncd_aria2_rpc_port');
            $binary = Helper::getAdminSettings('ncd_aria2_binary');

            return new Aria2([
                'dir' => Helper::getRealDownloadDir(),
                'torrentsDir' => Helper::getRealTorrentsDir(),
                'confDir' => $dataDir . '/aria2',
                'token' => $token ?: 'ncdownloader123',
                'settings' => $options,
                'rpcHost' => $rpcHost ?: '127.0.0.1',
                'rpcPort' => $rpcPort ?: '6800',
                'binary' => $binary,
                'aria2Conf' => $aria2Conf,
            ]);
        });

        $container->registerService(Ytdl::class, function (ContainerInterface $c) use ($uid) {
            return new Ytdl(Helper::getYtdlConfig($uid));
        });

        $container->registerService(Settings::class, function (ContainerInterface $c) use ($uid) {
            return new Settings($uid);
        });

        $container->registerService('uid', function (ContainerInterface $c) use ($uid) {
            return $uid;
        });
    }
}
