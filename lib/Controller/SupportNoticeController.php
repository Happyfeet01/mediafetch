<?php

declare(strict_types=1);

namespace OCA\NCDownloader\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserSession;

final class SupportNoticeController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private IConfig $config,
        private ISession $session,
        private IUserSession $users,
    ) {
        parent::__construct($appName, $request);
    }

    #[NoAdminRequired]
    public function status(): JSONResponse {
        $uid = $this->users->getUser()?->getUID();
        $visible = $uid !== null
            && !$this->session->get($this->appName . '.support.closed.' . $uid)
            && $this->config->getUserValue($uid, $this->appName, 'support_notice_hidden', '0') !== '1';
        return new JSONResponse(['visible' => $visible], 200, ['Cache-Control' => 'no-store']);
    }

    #[NoAdminRequired]
    public function dismiss(string $duration): JSONResponse {
        $uid = $this->users->getUser()?->getUID();
        if ($uid === null) {
            return new JSONResponse([], 401);
        }
        if ($duration === 'permanent') {
            $this->config->setUserValue($uid, $this->appName, 'support_notice_hidden', '1');
        } elseif ($duration === 'session') {
            $this->session->set($this->appName . '.support.closed.' . $uid, true);
        } else {
            return new JSONResponse([], 400);
        }
        return new JSONResponse([]);
    }
}
