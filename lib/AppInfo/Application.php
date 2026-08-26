<?php

namespace OCA\NCDownloader\AppInfo;

use OCA\NCDownloader\Aria2\Aria2;
use OCA\NCDownloader\Db\Settings;
use OCA\NCDownloader\Http\Client;
use OCA\NCDownloader\Tools\Helper;
use OCA\NCDownloader\Ytdl\Ytdl;
use OCP\App\IAppManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\IConfig;
use Psr\Container\ContainerInterface;
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
            $options['seed_time'] = $settings->get('ncd_seed_time');
            $options['seed_ratio'] = $settings->get('ncd_seed_ratio');

            $customSettings = $settings->getAria2();
            if (is_array($customSettings)) {
                $options = array_merge($customSettings, $options);
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

            // Supplying the hooks here prevents the legacy Aria2 class defaults
            // from falling back to /apps/ncdownloader after the rebrand.
            $aria2Conf += [
                'on-download-complete' => $appPath . '/hooks/completeHook.sh',
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
