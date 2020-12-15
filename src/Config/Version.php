<?php

namespace QApi\Config;

class Version
{
    public function __construct(public float $version, public string $versionName, public string $versionDescription
    = '', public array $versionOtherData = [])
    {
    }

    /**
     * 验证版本
     */
    public function checkVersion(float $version): Version
    {

    }
}