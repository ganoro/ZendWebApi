<?php

require_once 'PHPUnit\Framework\TestSuite.php';
require_once 'tests\ZendX\Service\ZendServer\ApplicationInfoTest.php';
require_once 'tests\ZendX\Service\ZendServer\ApplicationsListTest.php';
require_once 'tests\ZendX\Service\ZendServer\ServerInfoTest.php';
require_once 'tests\ZendX\Service\ZendServer\ServersListTest.php';
require_once 'tests\ZendX\Service\ZendServer\ZendServerTest.php';

/**
 * Static test suite.
 */
class ZendXSuite extends PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     */
    public function __construct ()
    {
        $this->setName('ZendXSuite');
        $this->addTestSuite('ZendX_Service_ZendServer_ApplicationInfoTest');
        $this->addTestSuite('ZendX_Service_ZendServer_ApplicationsListTest');
        $this->addTestSuite('ZendX_Service_ZendServer_ServerInfoTest');
        $this->addTestSuite('ZendX_Service_ZendServer_ServersListTest');
        $this->addTestSuite('ZendX_Service_ZendServer_ZendServerTest');
    }
    /**
     * Creates the suite.
     */
    public static function suite ()
    {
        return new self();
    }
}

