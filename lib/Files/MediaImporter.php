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
     * Import one completed yt-dlp file while the surrounding playlist job may
     * still be running. By default the temporary source is kept so yt-dlp can
     * finish any remaining after_video/playlist stages safely.
     *
     * @return array{source:string,name:string,path:string}
     */
    public function importFile(
        string $uid,
        string $workspace,
        string $source,
        string $targetPath,
        bool $removeSource = false
    ): array {
        if ($uid === '' || !is_dir($workspace)) {
            throw new RuntimeException('The MediaFetch workspace is not available.');
        }

        if (is_link($source)) {
            throw new RuntimeException('MediaFetch refuses to import symbolic links from its workspace.');
        }

        $workspaceReal = realpath($workspace);
        $sourceReal = realpath($source);
        if ($workspaceReal === false || $sourceReal === false || !is_file($sourceReal)) {
            throw new RuntimeException('The completed MediaFetch download is not available.');
        }

        $workspacePrefix = rtrim($workspaceReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($sourceReal, $workspacePrefix)) {
            throw new RuntimeException('MediaFetch refused to import a file outside its private workspace.');
        }

        $relative = ltrim(substr($sourceReal, strlen($workspaceReal)), DIRECTORY_SEPARATOR);
        if ($relative === '' || str_contains($relative, "\0")) {
            throw new RuntimeException('Invalid MediaFetch source path.');
        }

        $userFolder = $this->rootFolder->getUserFolder($uid);
        $targetFolder = $this->ensureFolder($userFolder, $targetPath);

        $relativeDir = dirname($relative);
        $destinationFolder = $relativeDir === '.'
            ? $targetFolder
            : $this->ensureFolder($targetFolder, $relativeDir);

        $sourceName = basename($relative);
        $destinationName = $destinationFolder->getNonExistingName($sourceName);
        $stream = @fopen($sourceReal, 'rb');
        if (!is_resource($stream)) {
            throw new RuntimeException(sprintf('Could not read completed download "%s".', $sourceName));
        }

        try {
            $destinationFolder->newFile($destinationName, $stream);
        } finally {
            fclose($stream);
        }

        if ($removeSource) {
            @unlink($sourceReal);
        }

        $relativeTarget = trim($targetPath, '/');
        if ($relativeDir !== '.') {
            $relativeTarget .= '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativeDir);
        }
        $relativeTarget = trim($relativeTarget, '/');

        return [
            'source' => $sourceName,
            'name' => $destinationName,
            'path' => '/' . ($relativeTarget !== '' ? $relativeTarget . '/' : '') . $destinationName,
        ];
    }

    /**
     * Import all completed files from a private workspace through Nextcloud's
     * public Files API. Files that were already imported during a running
     * playlist can be skipped while their temporary copies are kept until
     * yt-dlp exits.
     *
     * @param string[] $skipSources Absolute source paths already imported.
     * @return array<int, array{source:string,name:string,path:string}>
     */
    public function importWorkspace(
        string $uid,
        string $workspace,
        string $targetPath,
        array $skipSources = []
    ): array {
        if ($uid === '' || !is_dir($workspace)) {
            throw new RuntimeException('The MediaFetch workspace is not available.');
        }

        $workspace = rtrim($workspace, DIRECTORY_SEPARATOR);
        $files = $this->collectFiles($workspace);
        $skip = [];

        foreach ($skipSources as $source) {
            $real = realpath((string) $source);
            if ($real !== false) {
                $skip[$real] = true;
            }
        }

        $imported = [];
        foreach ($files as $source) {
            $real = realpath($source);
            if ($real !== false && isset($skip[$real])) {
                continue;
            }

            $imported[] = $this->importFile($uid, $workspace, $source, $targetPath, true);
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
