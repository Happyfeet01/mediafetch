<?php

namespace OCA\NCDownloader\Controller;

use OCA\NCDownloader\Db\Settings;
use OCA\NCDownloader\Tools\Helper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IRequest;

class SettingsController extends Controller
{
    private const PERSONAL_KEYS = [
        'ncd_downloader_dir',
        'ncd_torrents_dir',
        'ncd_seed_ratio',
        'ncd_seed_time_unit',
        'ncd_seed_time',
        'ncd_download_proxy',
        'ncd_hide_errors',
    ];

    private const SAFE_USER_ARIA2_OPTIONS = [
        'allow-overwrite', 'allow-piece-length-change', 'always-resume', 'async-dns',
        'auto-file-renaming', 'bt-enable-lpd', 'bt-exclude-tracker', 'bt-force-encryption',
        'bt-hash-check-seed', 'bt-load-saved-metadata', 'bt-max-peers', 'bt-metadata-only',
        'bt-min-crypto-level', 'bt-prioritize-piece', 'bt-remove-unselected-file',
        'bt-request-peer-speed-limit', 'bt-require-crypto', 'bt-save-metadata',
        'bt-seed-unverified', 'bt-stop-timeout', 'bt-tracker', 'bt-tracker-connect-timeout',
        'bt-tracker-interval', 'bt-tracker-timeout', 'check-integrity', 'checksum',
        'conditional-get', 'connect-timeout', 'continue', 'dry-run', 'enable-http-keep-alive',
        'enable-http-pipelining', 'enable-mmap', 'enable-peer-exchange', 'file-allocation',
        'follow-metalink', 'follow-torrent', 'force-save', 'hash-check-only',
        'http-accept-gzip', 'http-auth-challenge', 'http-no-cache', 'lowest-speed-limit',
        'max-connection-per-server', 'max-download-limit', 'max-file-not-found',
        'max-mmap-limit', 'max-resume-failure-tries', 'max-tries', 'max-upload-limit',
        'min-split-size', 'no-file-allocation-limit', 'no-netrc', 'pause', 'piece-length',
        'realtime-chunk-checksum', 'remote-time', 'remove-control-file', 'retry-wait',
        'reuse-uri', 'seed-ratio', 'seed-time', 'select-file', 'split',
        'stream-piece-selector', 'timeout', 'uri-selector', 'use-head', 'user-agent',
        'max-overall-download-limit', 'max-overall-upload-limit'
    ];

    private $uid;
    private $settings;
    private $groupManager;

    public function __construct($AppName, IRequest $Request, $uid, IGroupManager $groupManager)
    {
        parent::__construct($AppName, $Request);
        $this->uid = $uid;
        $this->settings = new Settings($uid);
        $this->groupManager = $groupManager;
    }

    /**
     * @NoAdminRequired
     */
    public function getSettings()
    {
        $name = $this->request->getParam('name');
        $type = $this->request->getParam('type') ?? Settings::TYPE['USER'];
        $default = $this->request->getParam('default') ?? null;

        // Normal users may only query their own settings. System/app settings
        // stay behind the admin endpoints.
        if (!$this->groupManager->isAdmin($this->uid)) {
            $type = Settings::TYPE['USER'];
        }

        return new JSONResponse(Helper::getSettings($name, $default, (int) $type));
    }

    /**
     * @NoAdminRequired
     */
    public function saveCustom()
    {
        $params = array_intersect_key($this->request->getParams(), array_flip(self::PERSONAL_KEYS));
        $resp = ['message' => 'Nothing to save', 'status' => true];
        foreach ($params as $key => $value) {
            $resp = $this->save($key, $value);
        }
        return new JSONResponse($resp);
    }

    /**
     * @NoAdminRequired
     */
    public function getCustomAria2()
    {
        $data = $this->settings->getAria2();
        if (!is_array($data)) {
            $data = [];
        }
        $data = array_intersect_key($data, array_flip(self::SAFE_USER_ARIA2_OPTIONS));
        return new JSONResponse($data);
    }

    public function saveAdmin()
    {
        $params = $this->request->getParams();
        $data = $this->settings->setType(Settings::TYPE['SYSTEM'])->get('ncd_admin_settings', []);
        if (!is_array($data)) {
            $data = [];
        }

        foreach ($params as $key => $value) {
            if (str_starts_with((string) $key, '_')) {
                continue;
            }
            $data[$key] = $value;
        }

        return new JSONResponse($this->save('ncd_admin_settings', $data, Settings::TYPE['SYSTEM']));
    }

    public function saveGlobalAria2()
    {
        $params = $this->request->getParams();
        $data = Helper::filterData($params, Helper::aria2Options());
        return new JSONResponse($this->save('global_aria2_config', $data, Settings::TYPE['SYSTEM']));
    }

    public function getGlobalAria2()
    {
        return new JSONResponse(Helper::getSettings('global_aria2_config', '', Settings::TYPE['SYSTEM']));
    }

    /**
     * @NoAdminRequired
     */
    public function saveCustomAria2()
    {
        $noAria2Settings = (bool) Helper::getAdminSettings('disallow_aria2_settings');
        if ($noAria2Settings && !$this->groupManager->isAdmin($this->uid)) {
            return new JSONResponse(['error' => 'forbidden', 'status' => false], 403);
        }

        $params = $this->request->getParams();
        $data = array_intersect_key($params, array_flip(self::SAFE_USER_ARIA2_OPTIONS));
        return new JSONResponse($this->settings->save('custom_aria2_settings', json_encode($data)));
    }

    /**
     * @NoAdminRequired
     */
    public function deleteCustomAria2()
    {
        $saved = $this->settings->getAria2();
        if (!is_array($saved)) {
            $saved = [];
        }

        foreach (array_keys($this->request->getParams()) as $key) {
            if (in_array($key, self::SAFE_USER_ARIA2_OPTIONS, true)) {
                unset($saved[$key]);
            }
        }

        return new JSONResponse($this->settings->save('custom_aria2_settings', json_encode($saved)));
    }

    /**
     * @NoAdminRequired
     */
    public function getYtdl()
    {
        return new JSONResponse($this->settings->getYtdl());
    }

    /**
     * @NoAdminRequired
     */
    public function saveYtdl()
    {
        $params = $this->request->getParams();
        $allowed = array_flip(Settings::safeYtdlOptions());
        $data = array_intersect_key($params, $allowed);

        if (isset($data['output'])) {
            $output = (string) $data['output'];
            if ($output === '' || str_contains($output, '..') || str_contains($output, '/') || str_contains($output, '\\') || str_contains($output, "\0")) {
                return new JSONResponse([
                    'error' => 'The output option may only contain a filename template, not a path.',
                    'status' => false,
                ], 400);
            }
        }

        return new JSONResponse($this->settings->save('custom_ytdl_settings', json_encode($data)));
    }

    /**
     * @NoAdminRequired
     */
    public function deleteYtdl()
    {
        $saved = $this->settings->getYtdl();
        foreach (array_keys($this->request->getParams()) as $key) {
            if (in_array($key, Settings::safeYtdlOptions(), true)) {
                unset($saved[$key]);
            }
        }
        return new JSONResponse($this->settings->save('custom_ytdl_settings', json_encode($saved)));
    }

    private function cleanValue($value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->cleanValue($item);
            }
            return $value;
        }

        if (!is_scalar($value) && $value !== null) {
            return '';
        }

        return str_replace("\0", '', (string) $value);
    }

    public function save($key, $value, $type = Settings::TYPE['USER'])
    {
        if (str_starts_with((string) $key, '_')) {
            return ['error' => 'Invalid setting key', 'status' => false];
        }

        $key = preg_replace('/[^a-zA-Z0-9_.-]/', '', (string) $key);
        $value = $this->cleanValue($value);

        try {
            $this->settings->setType($type);
            $this->settings->save($key, $value);
        } catch (\Exception $e) {
            return ['error' => $e->getMessage(), 'status' => false];
        }

        return ['message' => 'Saved!', 'status' => true];
    }
}
