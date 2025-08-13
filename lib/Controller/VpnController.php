<?php
namespace OCA\NCDownloader\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IConfig;
use Symfony\Component\Process\Process;

class VpnController extends Controller
{
    /** @var IConfig */
    private $config;
    private $uid;

    public function __construct(string $appName, IRequest $request, IConfig $config, $UserId)
    {
        parent::__construct($appName, $request);
        $this->config = $config;
        $this->uid = $UserId;
    }

    /**
     * @NoAdminRequired
     */
    public function start(): JSONResponse
    {
        $cmd = trim($this->config->getUserValue($this->uid, $this->appName, 'vpnStartCmd', ''));
        if ($cmd === '') {
            return new JSONResponse(['error' => 'No VPN start command configured']);
        }
        return $this->runCommand($cmd);
    }

    /**
     * @NoAdminRequired
     */
    public function stop(): JSONResponse
    {
        $cmd = trim($this->config->getUserValue($this->uid, $this->appName, 'vpnStopCmd', ''));
        if ($cmd === '') {
            return new JSONResponse(['error' => 'No VPN stop command configured']);
        }
        return $this->runCommand($cmd);
    }

    private function runCommand(string $cmd): JSONResponse
    {
        try {
            $process = Process::fromShellCommandline($cmd);
            $process->run();
            if ($process->isSuccessful()) {
                $output = trim($process->getOutput());
                return new JSONResponse(['status' => true, 'message' => $output !== '' ? $output : 'Command executed']);
            }
            $error = trim($process->getErrorOutput());
            return new JSONResponse(['status' => false, 'error' => $error !== '' ? $error : 'Command failed']);
        } catch (\Throwable $e) {
            return new JSONResponse(['status' => false, 'error' => $e->getMessage()]);
        }
    }
}
