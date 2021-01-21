<?php

namespace QApi\Config;

use QApi\Config;

class Version
{
    public string $versionDir;

    public function __construct(public float $version, public string $versionName, public string $versionDescription
    = '', public array $versionOtherData = [], public bool $force = false)
    {
        $this->versionDir = str_replace('.', '', $this->versionName);
    }

    /**
     * @param float|null $version
     * @return Version|false
     */
    public function checkVersion(float $version = null): Version|false
    {
        if (!$version) {
            $version = $this->version;
        }
        $versions = Config::versions();

        /**
         * @var Version $v
         * @var Version|bool $upgradeVersion
         */
        $upgradeVersion = false;
        foreach ($versions as $v) {
            if ($v->version > $version) {
                if (!$upgradeVersion) {
                    $upgradeVersion = $v;
                } else if ($upgradeVersion->force) {
                    $upgradeVersion = $v;
                    $upgradeVersion->force = true;
                } else {
                    $upgradeVersion = $v;
                }
            }
        }
        return $upgradeVersion;
    }
}