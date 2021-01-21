<?php

namespace QApi\Config;

use QApi\Config;

class Version
{
    public string $versionDir;

    public function __construct(public float $version, public string $versionName, public string $versionDescription
    = '', public array $versionOtherData = [])
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
         * @var Version $lastVersion
         */
        $lastVersion = end($versions);
        if ($lastVersion->version > $version) {
            return $lastVersion;
        }

        return false;
    }
}