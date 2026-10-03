<?php
// Standalone regression test: real Aria2 class with a simulated JSON-RPC transport.
namespace OCA\NCDownloader\Aria2;

final class IpProbeTransport {
    public static array $request = [];
    public static array $calls = [];
    public static string $scenario = 'success';
    public static string $directory = '';
}
function curl_init($url) { return new \stdClass(); }
function curl_setopt_array($handle, $options) { return true; }
function curl_setopt($handle, $option, $value) {
    if ($option === CURLOPT_POSTFIELDS) {
        IpProbeTransport::$request = json_decode($value, true);
    }
    return true;
}
function curl_close($handle) {}
function curl_exec($handle) {
    $request = IpProbeTransport::$request;
    IpProbeTransport::$calls[] = $request;
    if ($request['method'] === 'aria2.addUri') {
        $options = $request['params'][2];
        foreach ($options as $value) {
            check(is_string($value), 'RPC option must be a string');
        }
        check(!isset($options['checksum'], $options['seed_time'], $options['select-file']), 'Torrent options leaked into probe');
        check($options['all-proxy'] === 'http://proxy.example:8888', 'Network proxy was lost');
        check($options['follow-torrent'] === 'false', 'Probe must not follow torrents');
        IpProbeTransport::$directory = $options['dir'];
        if (IpProbeTransport::$scenario === 'rejected') {
            return json_encode(['error' => ['code' => 1]]);
        }
        return json_encode(['result' => 'test-gid']);
    }
    if ($request['method'] === 'aria2.tellStatus') {
        if (IpProbeTransport::$scenario === 'download-error') {
            return json_encode(['result' => ['status' => 'error', 'errorCode' => '3']]);
        }
        if (IpProbeTransport::$scenario !== 'missing-file') {
            file_put_contents(IpProbeTransport::$directory . '/ip.txt', IpProbeTransport::$scenario === 'invalid' ? '<html>error</html>' : '84.17.36.51');
        }
        return json_encode(['result' => ['status' => 'complete']]);
    }
    return json_encode(['result' => 'OK']);
}
function check(bool $condition, string $message): void {
    if (!$condition) throw new \RuntimeException($message);
}
foreach (['CURLOPT_POST','CURLOPT_RETURNTRANSFER','CURLOPT_HEADER','CURLOPT_SSL_VERIFYPEER','CURLOPT_CONNECTTIMEOUT','CURLOPT_TIMEOUT','CURLOPT_POSTFIELDS'] as $index => $constant) {
    if (!defined($constant)) define($constant, $index + 1);
}
require __DIR__ . '/../lib/Aria2/Aria2.php';
$base = sys_get_temp_dir() . '/mediafetch-ip-test-' . bin2hex(random_bytes(8));
mkdir($base, 0700);
try {
    foreach (['success', 'rejected', 'download-error', 'missing-file', 'invalid'] as $scenario) {
        IpProbeTransport::$scenario = $scenario;
        IpProbeTransport::$calls = [];
        $class = new \ReflectionClass(Aria2::class);
        $aria2 = $class->newInstanceWithoutConstructor();
        foreach (['confDir' => $base, 'downloadDir' => $base, 'torrentsDir' => $base,
            'rpcUrl' => 'http://localhost/jsonrpc', 'token' => 'token:test',
            'options' => ['all-proxy' => 'http://proxy.example:8888', 'checksum' => 'sha-1=bad', 'select-file' => '2', 'seed_time' => null]] as $name => $value) {
            $property = $class->getProperty($name);
            $property->setValue($aria2, $value);
        }
        try {
            $ip = $aria2->externalIp();
            check($scenario === 'success' && $ip === '84.17.36.51', 'Unexpected successful result');
        } catch (\RuntimeException $error) {
            check($scenario !== 'success', $error->getMessage());
            $expected = ['rejected' => 'RPC-Code 1', 'download-error' => 'aria2-Code 3',
                'missing-file' => 'nicht lesbar', 'invalid' => 'keine gültige IP'];
            check(str_contains($error->getMessage(), $expected[$scenario]), 'Wrong diagnostic: ' . $error->getMessage());
        }
        check(glob($base . '/ip-check-*') === [], 'Temporary probe files were not cleaned');
        $methods = array_column(IpProbeTransport::$calls, 'method');
        check($scenario === 'rejected' || in_array('aria2.removeDownloadResult', $methods, true), 'RPC cleanup was skipped');
        echo "IP probe passed: $scenario\n";
    }
} finally { rmdir($base); }
