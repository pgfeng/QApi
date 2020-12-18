<?php

namespace QApi\Config;

class Version
{
    public string $versionDir;

    public function __construct(public float $version, public string $versionName, public string $versionDescription
    = '', public array $versionOtherData = [])
    {
        $this->versionDir = str_replace('.', '', $this->versionName);
    }

    /**
     * 验证版本
     */
    public function checkVersion(float $version): Version
    {

    }
}