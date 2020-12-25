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
     * @param float $version
     * @return Version|false
     */
    public function checkVersion(float $version): Version|false
    {
        $versions = Config::versions();

        /**
         * @var Version $lastVersion
         */
        $lastVersion = end($versions);
        if ($version->$version > $version) {
            return $lastVersion;
        }

        return false;
    }
}