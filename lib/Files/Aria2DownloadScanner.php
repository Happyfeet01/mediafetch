<?php

declare(strict_types=1);

namespace OCA\Vapor\Files;

use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Storage\ILocalStorage;
use Psr\Log\LoggerInterface;
use RuntimeException;

final class Aria2DownloadScanner
{
    public function __construct(
        private IRootFolder $rootFolder,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Check whether an aria2 hook path belongs to a user's configured
     * download folder. This is used for followed BitTorrent downloads whose
     * GID differs from the original magnet GID stored by Vapor.
     */
    public function contains(string $uid, string $targetPath, string $completedPath): bool
    {
        try {
            [, $localRoot] = $this->resolveTargetFolder($uid, $targetPath);
            return $this->getRelativePath($localRoot, $completedPath) !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Add a completed aria2 download to Nextcloud's file cache.
     *
     * Single-file downloads are scanned individually. For multi-file
     * torrents, the torrent's top-level directory is scanned recursively. If
     * aria2 stores multiple files directly in the destination, the configured
     * destination itself is used as the safe fallback.
     */
    public function scan(
        string $uid,
        string $targetPath,
        string $completedPath,
        int $numberOfFiles
    ): bool {
        try {
            [$targetFolder, $localRoot] = $this->resolveTargetFolder($uid, $targetPath);
            $relativePath = $this->getRelativePath($localRoot, $completedPath);
            if ($relativePath === null) {
                throw new RuntimeException('The completed path is outside the configured download folder.');
            }

            [$scanRelativePath, $recursive] = $this->selectScanTarget(
                $localRoot,
                $relativePath,
                $numberOfFiles
            );

            $internalRoot = trim(str_replace('\\', '/', $targetFolder->getInternalPath()), '/');
            $scanPath = $internalRoot;
            if ($scanRelativePath !== '') {
                $scanPath .= ($scanPath !== '' ? '/' : '') . $scanRelativePath;
            }

            $targetFolder->getStorage()->getScanner()->scan($scanPath, $recursive);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error(
                'Vapor could not scan a completed aria2 download: ' . $e->getMessage(),
                [
                    'app' => 'vapor',
                    'user' => $uid,
                    'target' => $targetPath,
                    'path' => $completedPath,
                ]
            );
            return false;
        }
    }

    /** @return array{0:Folder,1:string} */
    private function resolveTargetFolder(string $uid, string $targetPath): array
    {
        if ($uid === '') {
            throw new RuntimeException('The download has no associated Nextcloud user.');
        }

        $targetPath = $this->normaliseTargetPath($targetPath);
        $userFolder = $this->rootFolder->getUserFolder($uid);

        if ($targetPath === '') {
            $targetFolder = $userFolder;
        } else {
            if (!$userFolder->nodeExists($targetPath)) {
                throw new RuntimeException('The configured download folder does not exist in Nextcloud.');
            }

            $targetFolder = $userFolder->get($targetPath);
            if (!$targetFolder instanceof Folder) {
                throw new RuntimeException('The configured download destination is not a folder.');
            }
        }

        $storage = $targetFolder->getStorage();
        $internalPath = $targetFolder->getInternalPath();
        if ($storage instanceof ILocalStorage) {
            $localRoot = $storage->getSourcePath($internalPath);
        } elseif (method_exists($storage, 'getLocalFile')) {
            $localRoot = $storage->getLocalFile($internalPath);
        } else {
            throw new RuntimeException('The configured download storage has no local path for aria2.');
        }

        if (!is_string($localRoot) || $localRoot === '') {
            throw new RuntimeException('The local download folder could not be resolved.');
        }

        $localRoot = realpath($localRoot);
        if ($localRoot === false || !is_dir($localRoot)) {
            throw new RuntimeException('The local download folder is unavailable.');
        }

        return [$targetFolder, rtrim($localRoot, DIRECTORY_SEPARATOR)];
    }

    private function normaliseTargetPath(string $targetPath): string
    {
        $targetPath = trim(str_replace('\\', '/', $targetPath), '/');
        if ($targetPath === '') {
            return '';
        }

        foreach (explode('/', $targetPath) as $part) {
            if ($part === '' || $part === '.' || $part === '..' || str_contains($part, "\0")) {
                throw new RuntimeException('The configured download folder is invalid.');
            }
        }

        return $targetPath;
    }

    private function getRelativePath(string $localRoot, string $completedPath): ?string
    {
        if ($completedPath === '') {
            return null;
        }

        $completedPath = realpath($completedPath);
        if ($completedPath === false) {
            return null;
        }

        if ($completedPath === $localRoot) {
            return '';
        }

        $prefix = $localRoot . DIRECTORY_SEPARATOR;
        if (!str_starts_with($completedPath, $prefix)) {
            return null;
        }

        return str_replace(DIRECTORY_SEPARATOR, '/', substr($completedPath, strlen($prefix)));
    }

    /** @return array{0:string,1:bool} */
    private function selectScanTarget(string $localRoot, string $relativePath, int $numberOfFiles): array
    {
        if ($relativePath === '') {
            return ['', true];
        }

        if ($numberOfFiles <= 1) {
            return [$relativePath, is_dir($localRoot . DIRECTORY_SEPARATOR . $relativePath)];
        }

        $topLevelName = explode('/', $relativePath, 2)[0];
        $topLevelPath = $localRoot . DIRECTORY_SEPARATOR . $topLevelName;
        if (is_dir($topLevelPath)) {
            return [$topLevelName, true];
        }

        return ['', true];
    }
}
