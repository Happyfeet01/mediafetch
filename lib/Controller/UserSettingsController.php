<?php
namespace OCA\NCDownloader\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IConfig;
use OCP\IUserSession;

class UserSettingsController extends Controller {
    public function __construct(string $appName, IRequest $request, private IConfig $config, private IUserSession $userSession) {
        parent::__construct($appName, $request);
    }

    /** @NoAdminRequired */
    public function getPersonal(): DataResponse {
        $uid = $this->userSession->getUser()->getUID();
        $keys = ['vpnStartCmd','vpnStopCmd','downloadProxy'];
        $data = [];
        foreach ($keys as $k) {
            $data[$k] = (string) $this->config->getUserValue($uid, 'ncdownloader', $k, '');
        }
        $map = ['vpnStartCmd'=>'vpn_start_cmd','vpnStopCmd'=>'vpn_stop_cmd','downloadProxy'=>'download_proxy'];
        foreach ($map as $new => $old) {
            if ($data[$new] === '') {
                $v = $this->config->getUserValue($uid, 'ncdownloader', $old, '');
                if ($v !== '') {
                    $data[$new] = $v;
                    $this->config->setUserValue($uid, 'ncdownloader', $new, $v);
                }
            }
        }
        return new DataResponse($data);
    }

    /** @NoAdminRequired @CSRFRequired */
    public function savePersonal(string $vpnStartCmd = '', string $vpnStopCmd = '', string $downloadProxy = ''): DataResponse {
        $uid = $this->userSession->getUser()->getUID();
        $this->config->setUserValue($uid, 'ncdownloader', 'vpnStartCmd', trim($vpnStartCmd));
        $this->config->setUserValue($uid, 'ncdownloader', 'vpnStopCmd', trim($vpnStopCmd));
        $this->config->setUserValue($uid, 'ncdownloader', 'downloadProxy', trim($downloadProxy));
        return new DataResponse(['status' => 'ok']);
    }
}
