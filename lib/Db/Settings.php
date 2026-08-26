<?php

namespace OCA\NCDownloader\Db;

class Settings
{
    private $appConfig;
    private $sysConfig;
    private $user;
    private $appName;
    private $legacyAppName;
    private $type;

    public const TYPE = ['SYSTEM' => 1, 'USER' => 2, 'APP' => 3];

    public function __construct($user = null)
    {
        $this->appConfig = \OC::$server->get(\OCP\IConfig::class);
        $this->sysConfig = $this->appConfig;
        $this->appName = 'mediafetch';
        $this->legacyAppName = 'ncdownloader';
        $this->type = self::TYPE['USER'];
        $this->user = $user;
    }

    public static function create($user = null)
    {
        // Settings are user-scoped. Do not share one static instance across users.
        return new static($user);
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function get($key, $default = null)
    {
        if ($this->type == self::TYPE['USER'] && isset($this->user)) {
            return $this->getUserValueWithLegacyFallback($key, $default);
        }

        if ($this->type == self::TYPE['SYSTEM']) {
            return $this->appConfig->getSystemValue($key, $default);
        }

        return $this->getAppValueWithLegacyFallback($key, $default);
    }

    private function getUserValueWithLegacyFallback($key, $default = null)
    {
        $newKeys = $this->appConfig->getUserKeys($this->user, $this->appName);
        if (in_array($key, $newKeys, true)) {
            return $this->appConfig->getUserValue($this->user, $this->appName, $key, $default);
        }

        $legacyKeys = $this->appConfig->getUserKeys($this->user, $this->legacyAppName);
        if (in_array($key, $legacyKeys, true)) {
            $value = $this->appConfig->getUserValue($this->user, $this->legacyAppName, $key, $default);
            $this->appConfig->setUserValue($this->user, $this->appName, $key, $value);
            return $value;
        }

        return $default;
    }

    private function getAppValueWithLegacyFallback($key, $default = null)
    {
        $newKeys = $this->appConfig->getAppKeys($this->appName);
        if (in_array($key, $newKeys, true)) {
            return $this->appConfig->getAppValue($this->appName, $key, $default);
        }

        $legacyKeys = $this->appConfig->getAppKeys($this->legacyAppName);
        if (in_array($key, $legacyKeys, true)) {
            $value = $this->appConfig->getAppValue($this->legacyAppName, $key, $default);
            $this->appConfig->setAppValue($this->appName, $key, $value);
            return $value;
        }

        return $default;
    }

    public function getAria2()
    {
        $settings = $this->get('custom_aria2_settings', '');
        return json_decode($settings, true);
    }

    public function getYtdl()
    {
        $settings = $this->get('custom_ytdl_settings', '');
        return json_decode($settings, true);
    }

    public function getAll()
    {
        if ($this->type === self::TYPE['APP']) {
            return $this->getAllAppValues();
        }

        return $this->getAllUserSettings();
    }

    public function save($key, $value)
    {
        try {
            if ($this->type == self::TYPE['USER'] && isset($this->user)) {
                $this->appConfig->setUserValue($this->user, $this->appName, $key, $value);
            } elseif ($this->type == self::TYPE['SYSTEM']) {
                $this->appConfig->setSystemValue($key, $value);
            } else {
                $this->appConfig->setAppValue($this->appName, $key, $value);
            }
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }

        return ['message' => 'Saved!'];
    }

    public function getAllAppValues()
    {
        $keys = array_unique(array_merge(
            $this->appConfig->getAppKeys($this->legacyAppName),
            $this->appConfig->getAppKeys($this->appName)
        ));

        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->getAppValueWithLegacyFallback($key);
        }
        return $values;
    }

    public function getAllKeys()
    {
        return array_unique(array_merge(
            $this->appConfig->getAppKeys($this->legacyAppName),
            $this->appConfig->getAppKeys($this->appName)
        ));
    }

    public function getAllUserSettings()
    {
        if (!isset($this->user)) {
            return [];
        }

        $keys = array_unique(array_merge(
            $this->appConfig->getUserKeys($this->user, $this->legacyAppName),
            $this->appConfig->getUserKeys($this->user, $this->appName)
        ));

        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->getUserValueWithLegacyFallback($key);
        }
        return $values;
    }
}
