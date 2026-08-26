<?php

declare(strict_types=1);

namespace OCA\NCDownloader\Files;

use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use RuntimeException;

final class MediaImporter
{
    public function __construct(private IRootFolder $rootFolder)
    {
    }

    public function createWorkspace(string $uid): string
    {
        if ($uid === '') {
            throw new RuntimeException('Cannot create a MediaFetch workspace without a user.');
        }

        $base = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'mediafetch'
            . DIRECTORY_SEPARATOR . hash('sha256', $uid);

        if (!is_dir($base) && !mkdir($base, 0700, true) && !is_dir($base)) {
            throw new RuntimeException('Could not create the MediaFetch workspace directory.');
        }

        $workspace = $base . DIRECTORY_SEPARATOR . bin2hex(random_bytes(12));
        if (!mkdir($workspace, 0700, false) && !is_dir($workspace)) {
            throw new RuntimeException('Could not create a MediaFetch download workspace.');
        }
        @chmod($workspace, 0700);

        return $workspace;
    }

    /**
     * Import all completed files from a private workspace through Nextcloud's
     * public Files API. This updates the file cache as part of the write and
     * avoids the old direct-datadir-write + full files scan workflow.
     *
     * @return array<int, array{source:string,name:string,path:string}>
     */
    public function importWorkspace(string $uid, string $workspace, string $targetPath): array
    {
        if ($uid === '' || !is_dir($workspace)) {
            throw new RuntimeException('The MediaFetch workspace is not available.');
        }

        $userFolder = $this->rootFolder->getUserFolder($uid);
        $targetFolder = $this->ensureFolder($userFolder, $targetPath);
        $workspace = rtrim($workspace, DIRECTORY_SEPARATOR);
        $files = $this->collectFiles($workspace);
        $imported = [];

        foreach ($files as $source) {
            $relative = ltrim(substr($source, strlen($workspace)), DIRECTORY_SEPARATOR);
            if ($relative === '' || str_contains($relative, "\0")) {
                continue;
            }

            $relativeDir = dirname($relative);
            $destinationFolder = $relativeDir === '.'
                ? $targetFolder
                : $this->ensureFolder($targetFolder, $relativeDir);

            $sourceName = basename($relative);
            $destinationName = $destinationFolder->getNonExistingName($sourceName);
            $stream = @fopen($source, 'rb');
            if (!is_resource($stream)) {
                throw new RuntimeException(sprintf('Could not read completed download "%s".', $sourceName));
            }

            try {
                $destinationFolder->newFile($destinationName, $stream);
            } finally {
                fclose($stream);
            }

            // The Nextcloud file is already committed at this point. Failure to
            // remove the temporary copy is cleanup-only and must not turn a
            // successful import into a user-visible error.
            @unlink($source);

            $relativeTarget = trim($targetPath, '/');
            if ($relativeDir !== '.') {
                $relativeTarget .= '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativeDir);
            }
            $relativeTarget = trim($relativeTarget, '/');

            $imported[] = [
                'source' => $sourceName,
                'name' => $destinationName,
                'path' => '/' . ($relativeTarget !== '' ? $relativeTarget . '/' : '') . $destinationName,
            ];
        }

        $this->removeEmptyDirectories($workspace);
        return $imported;
    }

    public function cleanupWorkspace(string $workspace): void
    {
        if ($workspace === '' || !is_dir($workspace)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($workspace, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isLink() || $item->isFile()) {
                @unlink($item->getPathname());
            } elseif ($item->isDir()) {
                @rmdir($item->getPathname());
            }
        }

        @rmdir($workspace);
    }

    private function ensureFolder(Folder $base, string $path): Folder
    {
        $path = trim(str_replace('\\', '/', $path), '/');
        if ($path === '' || $path === '.') {
            return $base;
        }

        $folder = $base;
        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.' || $part === '..' || str_contains($part, "\0")) {
                throw new RuntimeException('Invalid MediaFetch destination path.');
            }

            if (!$folder->nodeExists($part)) {
                $folder = $folder->newFolder($part);
                continue;
            }

            $node = $folder->get($part);
            if (!$node instanceof Folder) {
                throw new RuntimeException(sprintf('Destination path component "%s" is not a folder.', $part));
            }
            $folder = $node;
        }

        return $folder;
    }

    /** @return string[] */
    private function collectFiles(string $workspace): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($workspace, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $item) {
            if ($item->isLink()) {
                throw new RuntimeException('MediaFetch refuses to import symbolic links from its workspace.');
            }
            if ($item->isFile() && !str_ends_with($item->getFilename(), '.part')) {
                $files[] = $item->getPathname();
            }
        }

        sort($files, SORT_NATURAL | SORT_FLAG_CASE);
        return $files;
    }

    private function removeEmptyDirectories(string $workspace): void
    {
        if (!is_dir($workspace)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($workspace, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            }
        }
    }
}
