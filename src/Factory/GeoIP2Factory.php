<?php

namespace Tourze\TmdTopBundle\Factory;

use Composer\InstalledVersions;
use GeoIp2\Database\Reader;

class GeoIP2Factory
{
    public function create(): Reader
    {
        $dbPath = InstalledVersions::getInstallPath('leo108/geolite2-db') . '/City.mmdb';
        return new Reader($dbPath);
    }
}
