<?php
namespace Pitech\MssqlBundle\DBAL\Driver\PDODblib;

use Pitech\MssqlBundle\DBAL\Platforms\DblibPlatform;

class Driver extends \MediaMonks\MssqlBundle\Doctrine\DBAL\Driver\PDODblib\Driver
{
    /**
     * @return DblibPlatform
     */
    public function getDatabasePlatform()
    {
        return new DblibPlatform();
    }
}
