<?php
declare(strict_types=1);

namespace OCA\NCDownloader\Migration;

use Closure;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version20240915 extends SimpleMigrationStep {
    public function __construct(private IConfig $config, private IUserManager $userManager) {
    }

    public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
        $map = ['vpnStartCmd'=>'vpn_start_cmd','vpnStopCmd'=>'vpn_stop_cmd','downloadProxy'=>'download_proxy'];
        $users = $this->userManager->search('');
        foreach ($users as $user) {
            $uid = $user->getUID();
            foreach ($map as $new => $old) {
                $newVal = $this->config->getUserValue($uid, 'ncdownloader', $new, '');
                if ($newVal === '') {
                    $oldVal = $this->config->getUserValue($uid, 'ncdownloader', $old, '');
                    if ($oldVal !== '') {
                        $this->config->setUserValue($uid, 'ncdownloader', $new, $oldVal);
                    }
                }
            }
        }
    }
}
