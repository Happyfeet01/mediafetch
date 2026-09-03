<?php

namespace OCA\Vapor\Command;

use OCA\Vapor\Db\Helper as DbHelper;
use OCA\Vapor\Db\Settings;
use OCA\Vapor\Files\Aria2DownloadScanner;
use OCA\Vapor\Tools\Helper;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command as Base;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Aria2Command extends Base
{
    private DbHelper $dbconn;

    public function __construct(
        IDBConnection $connection,
        private Aria2DownloadScanner $downloadScanner,
        private LoggerInterface $logger
    ) {
        $this->dbconn = new DbHelper($connection);
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('aria2')
            ->setDescription('Aria2 hooks')
            ->addArgument(
                'action',
                InputArgument::OPTIONAL,
                'Aria2 hook names: start, complete, error'
            )
            ->addArgument(
                'gid',
                InputArgument::OPTIONAL,
                'Aria2 GID'
            )
            ->addArgument(
                'numFiles',
                InputArgument::OPTIONAL,
                'Number of files',
                1
            )
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Downloaded file path'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $action = strtolower((string) ($input->getArgument('action') ?: 'start'));
        $gid = $input->getArgument('gid');
        if (!is_string($gid) || $gid === '') {
            return self::SUCCESS;
        }

        $path = $input->getArgument('path');
        $path = is_string($path) ? $path : '';
        $numberOfFiles = max(1, (int) $input->getArgument('numFiles'));
        $row = $this->dbconn->getByGid($gid);

        if ($action === 'error') {
            if ($row) {
                $this->dbconn->updateStatus($gid, Helper::STATUS['ERROR']);
            }
            return self::SUCCESS;
        }

        if ($action === 'complete') {
            if ($row) {
                $this->dbconn->updateStatus($gid, Helper::STATUS['COMPLETE']);
            }

            $context = $row ? $this->getScanContext($row) : $this->findScanContext($path);
            if ($context !== null && $path !== '') {
                $this->downloadScanner->scan(
                    $context['uid'],
                    $context['target'],
                    $path,
                    $numberOfFiles
                );
            } else {
                $this->logger->debug(
                    'Vapor ignored an untracked aria2 completion hook.',
                    ['app' => 'vapor', 'gid' => $gid, 'path' => $path]
                );
            }

            return self::SUCCESS;
        }

        if ($action === 'start' && $row && $path !== '') {
            $this->dbconn->updateFilename($gid, basename($path));
        }

        return self::SUCCESS;
    }

    /** @return array{uid:string,target:string}|null */
    private function getScanContext(array $row): ?array
    {
        $uid = isset($row['uid']) ? (string) $row['uid'] : '';
        if ($uid === '') {
            return null;
        }

        $target = '';
        if (isset($row['data']) && $row['data'] !== null && $row['data'] !== '') {
            try {
                $extra = $this->dbconn->getExtra($row['data']);
                if (is_array($extra) && isset($extra['path'])) {
                    $target = (string) $extra['path'];
                }
            } catch (\Throwable $e) {
                $this->logger->warning(
                    'Vapor could not read the stored aria2 destination.',
                    ['app' => 'vapor', 'gid' => $row['gid'] ?? null]
                );
            }
        }

        if ($target === '') {
            $settings = new Settings($uid);
            $target = (string) $settings->get('ncd_downloader_dir', '/Downloads');
        }

        return ['uid' => $uid, 'target' => $target];
    }

    /** @return array{uid:string,target:string}|null */
    private function findScanContext(string $completedPath): ?array
    {
        if ($completedPath === '') {
            return null;
        }

        $seen = [];
        foreach ($this->dbconn->getAria2Rows() as $row) {
            $context = $this->getScanContext($row);
            if ($context === null) {
                continue;
            }

            $key = $context['uid'] . "\0" . $context['target'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            if ($this->downloadScanner->contains(
                $context['uid'],
                $context['target'],
                $completedPath
            )) {
                return $context;
            }
        }

        return null;
    }
}
