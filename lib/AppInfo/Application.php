<?php

namespace OCA\NCDownloader\AppInfo;

use OCA\NCDownloader\Aria2\Aria2;
use OCA\NCDownloader\Http\Client;
use OCA\NCDownloader\Tools\Helper;
use OCA\NCDownloader\Db\Settings;
use OCA\NCDownloader\Ytdl\Ytdl;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use Symfony\Component\DomCrawler\Crawler;
use Psr\Container\ContainerInterface;

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

    public function boot(IBootContext $c): void
    {
        $user = Helper::getUser();
        $uid = $user ? $user->getUID() : '';
        $container = $c->getAppContainer();

        $container->registerService(Aria2::class, function (ContainerInterface $c) use ($uid) {
            return new Aria2(Helper::getAria2Config($uid));
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
