<?php

namespace OCA\NCDownloader\Controller;

use OCA\NCDownloader\Aria2\Aria2;
use OCA\NCDownloader\Tools\Counters;
use OCA\NCDownloader\Db\Helper as DbHelper;
use OCA\NCDownloader\Tools\Helper;
use OCA\NCDownloader\Ytdl\Ytdl;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Util;

class MainController extends Controller
{
    private $l10n;
    private $urlGenerator;
    private $uid;
    private $isAdmin;
    private $hideError;
    private $disableBtNonAdmin;
    private $aria2;
    private $dbconn;
    private $counters;
    private $ytdl;
    private $accessDenied;

    public function __construct(
        $appName,
        IRequest $request,
        $UserId,
        IL10N $IL10N,
        Aria2 $aria2,
        Ytdl $ytdl,
        IURLGenerator $urlGenerator,
        IGroupManager $groupManager
    ) {
        parent::__construct($appName, $request);
        $this->appName = $appName;
        $this->uid = $UserId;
        $this->l10n = $IL10N;
        $this->aria2 = $aria2;
        $this->aria2->init();
        $this->urlGenerator = $urlGenerator;
        $this->dbconn = new DbHelper();
        $this->counters = new Counters($aria2, $this->dbconn, $UserId);
        $this->ytdl = $ytdl;
        $this->isAdmin = $groupManager->isAdmin($this->uid);
        $this->hideError = Helper::getSettings('ncd_hide_errors', false);
        $this->disableBtNonAdmin = Helper::getAdminSettings('ncd_disable_bt');
        $this->accessDenied = $this->l10n->t('Sorry, only admin users can download files via BitTorrent.');
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function Index()
    {
        Util::addScript($this->appName, 'app');
        Util::addStyle($this->appName, 'app');

        return new TemplateResponse($this->appName, 'Index', $this->buildParams());
    }

    private function buildParams(): array
    {
        $params = [];
        $params['aria2_running'] = $this->aria2->isRunning();
        $params['aria2_installed'] = $aria2Installed = $this->aria2->isInstalled();
        $params['aria2_bin'] = $aria2Bin = $this->aria2->getBin();
        $params['aria2_executable'] = $aria2Executable = $this->aria2->isExecutable();
        $params['ytdlinstalled'] = $ytdlInstalled = $this->ytdl->isInstalled();
        $params['ytdlbin'] = $ytdlBin = $this->ytdl->getBin();
        $params['ytdlexecutable'] = $ytdlExecutable = $this->ytdl->isExecutable();
        $params['ncd_hide_errors'] = $this->hideError;
        $params['counter'] = $this->counters->getCounters();
        $params['python_installed'] = Helper::pythonInstalled();
        $params['ffmpeg_installed'] = Helper::ffmpegInstalled();
        $params['is_admin'] = $this->isAdmin;

        $sites = [];
        foreach (Helper::getSearchSites() as $site) {
            $label = $site['class']::getLabel();
            $sites[] = ['name' => $site['name'], 'label' => strtoupper($label)];
        }
        $params['search_sites'] = json_encode($sites);

        $errors = [];
        if ($aria2Installed) {
            if (!$aria2Executable) {
                $errors[] = sprintf('aria2 is installed but is not executable. Please check permissions for %s', $aria2Bin);
            }
            if (!$params['aria2_running']) {
                $this->aria2->start();
            }
        }

        if ($ytdlInstalled && (!$ytdlExecutable || !@is_readable($ytdlBin))) {
            $errors[] = sprintf('yt-dlp is installed but is not executable/readable. Please check permissions for %s', $ytdlBin);
        }

        foreach ($params as $key => $value) {
            if (strpos($key, '_') === false) {
                continue;
            }
            [$name, $suffix] = explode('_', $key, 2);
            if ($suffix === 'installed' && !$value) {
                $errors[] = $this->l10n->t(sprintf('%s is not installed', $name));
            }
        }

        $params['errors'] = $errors;
        $params['settings'] = json_encode([
            'is_admin' => $this->isAdmin,
            'admin_url' => $this->urlGenerator->linkToRoute('settings.AdminSettings.index', ['section' => 'mediafetch']),
            'personal_url' => $this->urlGenerator->linkToRoute('settings.PersonalSettings.index', ['section' => 'mediafetch']),
            'ncd_hide_errors' => $this->hideError,
            'ncd_disable_bt' => $this->disableBtNonAdmin,
            'ncd_downloader_dir' => Helper::getSettings('ncd_downloader_dir'),
            'disallow_aria2_settings' => Helper::getAdminSettings('disallow_aria2_settings'),
        ]);

        return $params;
    }

    /**
     * @NoAdminRequired
     */
    public function Download(string $url)
    {
        $dlDir = $this->aria2->getDownloadDir();
        if (!is_writable($dlDir)) {
            return new JSONResponse(['error' => sprintf('%s is not writable', $dlDir)]);
        }

        if (Helper::isMagnet($url) && $this->disableBtNonAdmin && !$this->isAdmin) {
            return new JSONResponse(['error' => $this->accessDenied]);
        }

        return new JSONResponse($this->downloadUrl($url));
    }

    private function downloadUrl($url)
    {
        $filename = Helper::getFileName($url);
        if ($filename) {
            $this->aria2->setFileName($filename);
        }

        $result = $this->aria2->download($url);
        if (!$result) {
            return ['error' => 'Failed to download the file.'];
        }

        if (isset($result['error'])) {
            return $result;
        }

        $data = [
            'uid' => $this->uid,
            'gid' => $result,
            'type' => Helper::DOWNLOADTYPE['ARIA2'],
            'filename' => $filename ?: 'unknown',
            'timestamp' => time(),
            'data' => serialize(['link' => $url, 'path' => Helper::getDownloadDir()]),
        ];
        $this->dbconn->save($data);

        return ['message' => $filename, 'result' => $result, 'file' => $filename];
    }

    /**
     * @NoAdminRequired
     */
    public function Upload()
    {
        if ($this->disableBtNonAdmin && !$this->isAdmin) {
            return new JSONResponse(['error' => $this->accessDenied]);
        }

        if (!isset($_FILES['torrentfile']['tmp_name']) || !is_uploaded_file($_FILES['torrentfile']['tmp_name'])) {
            return new JSONResponse(['error' => 'No valid torrent file was uploaded.'], 400);
        }

        $file = $this->aria2->getTorrentsDir() . '/' . Helper::cleanString($_FILES['torrentfile']['name']);
        move_uploaded_file($_FILES['torrentfile']['tmp_name'], $file);

        $result = $this->aria2->btDownload($file);
        if (!$result) {
            return new JSONResponse(['error' => 'Failed to download the torrent.']);
        }
        if (isset($result['error'])) {
            return new JSONResponse($result);
        }

        $data = [
            'uid' => $this->uid,
            'gid' => $result['gid'],
            'type' => Helper::DOWNLOADTYPE['ARIA2'],
            'filename' => $result['filename'] ?? 'unknown',
            'timestamp' => time(),
        ];
        $this->dbconn->save($data);

        return new JSONResponse([
            'message' => $result['filename'] ?? 'unknown',
            'result' => $result['gid'],
            'file' => $result['filename'] ?? 'unknown',
        ]);
    }

    /**
     * @NoAdminRequired
     */
    public function getCounters(): JSONResponse
    {
        return new JSONResponse(['counter' => $this->counters->getCounters()]);
    }

    public function ytdlCheck()
    {
        return new JSONResponse($this->ytdl->check());
    }

    public function ytdlUpdate()
    {
        return new JSONResponse($this->ytdl->update());
    }
}
