<?php

namespace OCA\NCDownloader\Db;

class Settings
{
    //@config OC\AppConfig
    private $appConfig;

    //@OC\SystemConfig
    private $sysConfig;

    private $user;
    private $appName;
    //type of settings (system = 1 or app =2)
    private $type;
    private static $instance = null;
    public const TYPE = ['SYSTEM' => 1, 'USER' => 2, 'APP' => 3];
    public function __construct($user = null)
    {
        $this->appConfig = \OC::$server->get(\OCP\IConfig::class);
        $this->sysConfig = \OC::$server->get(\OCP\IConfig::class);
        $this->appName = 'ncdownloader';
        $this->type = self::TYPE['USER'];
        $this->user = $user;
        //$this->connAdapter = \OC::$server->getDatabaseConnection();
        //$this->conn = $this->connAdapter->getInner();
    }
    public static function create($user = null)
    {

        if (!self::$instance) {
            self::$instance = new static($user);
        }
        return self::$instance;
    }
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
    public function get($key, $default = null)
    {
        if ($this->type == self::TYPE['USER'] && isset($this->user)) {
            return $this->appConfig->getUserValue($this->user, $this->appName, $key, $default);
        } else if ($this->type == self::TYPE['SYSTEM']) {
            return $this->appConfig->getSystemValue($key, $default);
        } else {
            return $this->appConfig->getAppValue($this->appName, $key, $default);
        }
    }
    public function getAria2()
    {
        $settings = $this->appConfig->getUserValue($this->user, $this->appName, "custom_aria2_settings", '');
        return json_decode($settings, 1);
    }

    public function getYtdl()
    {
        $settings = $this->get("custom_ytdl_settings");
        return json_decode($settings, 1);
    }
    public function getAll()
    {
        if ($this->type === self::TYPE['APP']) {
            return $this->getAllAppValues();
        } else {
            $data = $this->getAllUserSettings();
            return $data;
        }

    }
    public function save($key, $value)
    {
        try {
            if ($this->type == self::TYPE['USER'] && isset($this->user)) {
                $this->appConfig->setUserValue($this->user, $this->appName, $key, $value);
            } else if ($this->type == self::TYPE['SYSTEM']) {
                $this->appConfig->setSystemValue($key, $value);
            } else {
                $this->appConfig->setAppValue($this->appName, $key, $value);
            }
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
        return ['message' => "Saved!"];

    }
    public function getAllAppValues()
    {
        $keys = $this->getAllKeys();
        $value = [];
        foreach ($keys as $key) {
            $value[$key] = $this->appConfig->getAppValue($this->appName, $key);
        }
        return $value;
    }
    public function getAllKeys()
    {
        return $this->appConfig->getAppKeys($this->appName);
    }

    public function getAllUserSettings()
    {
        $keys = $this->appConfig->getUserKeys($this->user, $this->appName);
        $value = [];
        foreach ($keys as $key) {
            $value[$key] = $this->appConfig->getUserValue($this->user, $this->appName, $key);
        }
        return $value;
    }
}