<?php

require_once dirname(__FILE__) . '/../../../TestHelper.php';

require_once 'ZendX/Service/ZendServer.php';

/**
 * ZendX_Service_ZendServer test case.
 */
class ZendX_Service_ZendServer_ZendServerTest extends PHPUnit_Framework_TestCase
{
    const DEFAULT_URL     = 'http://localhost:10081/ZendServer';
    const DEFAULT_KEYNAME = 'test';
    const DEFAULT_KEY     = 'cc14b445ad6ed9041d936b7f363a8e5a525275d3960dbb373f35e97e2abcdab2'; 

    /**
     * @var ZendX_Service_ZendServer
     */
    private $_client;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->_client = new ZendX_Service_ZendServer(
            self::DEFAULT_URL,
            self::DEFAULT_KEYNAME,
            self::DEFAULT_KEY
        );
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        $this->_client = null;
        parent::tearDown();
    }

    /**
     * Test that the constructor throws exceptions on invalid URI types
     * 
     * @param mixed $uri
     * @dataProvider invalidServerUriTypes
     * @expectedException ZendX_Service_ZendServer_Exception
     */
    public function testConstructorInvalidUriType($uri)
    {
        $client = new ZendX_Service_ZendServer($uri, self::DEFAULT_KEYNAME, self::DEFAULT_KEY);
    }
    
    /**
     * Test that the constructor throws exceptions on invalid URI strings 
     * 
     * @param string $uri
     * @dataProvider      invalidServerUriStrings
     * @expectedException Zend_Uri_Exception
     */
    public function testConstructorInvalidUriString($uri)
    {
        $client = new ZendX_Service_ZendServer($uri, self::DEFAULT_KEYNAME, self::DEFAULT_KEY);
    }

    /**
     * Test that we can set the HTTP client and then access it
     * 
     */
    public function testSetGetHttpClient()
    {
        $client = new Zend_Http_Client();
        $this->_client->setHttpClient($client);
        $this->assertSame($client, $this->_client->getHttpClient());
    }

    /**
     * Test that if no HTTP client is set, a default one is used
     * 
     */
    public function testGetDefaultHttpClient()
    {
        $client = $this->_client->getHttpClient();
        $this->assertTrue($client instanceof Zend_Http_Client);
    }

    /**
     * Tests that calling clusterGetServerStatus will trigger an exception in 
     * case of authentication error
     * 
     */
    public function testGetServerStatusThrowsExceptionOnErrorResponse()
    {
        $this->_setNextResponseFromFile('error-response-auth-error.http');
        
        try {
            $this->_client->clusterGetServerStatus();
            $this->fail("Expected exception not thrown");
        } catch (ZendX_Service_ZendServer_Exception $ex) {
            $this->assertEquals('authError', $ex->getZendServerErrorCode(), "Unexpected exception: $ex");
            
        } catch (Exception $ex) {
            $this->fail("Unexpected exception: $ex");
        }
    }
    
    public function testApplicationGetStatusValidResponse()
    {
        $this->_setNextResponseFromFile('response-applicationGetStatus-ok-001.http');
        $apps = $this->_client->applicationGetStatus();
        $this->assertEquals(2, count($apps));
        $this->assertEquals('/usr/local/somewhere', $apps->byId(2)->getInstalledLocation());
    }

    /**
     * Private helper method to set a test adapter on our client and return it
     * 
     * @return Zend_Http_Client_Adapter_Test
     */
    private function _getTestAdapter()
    {
        require_once 'Zend/Http/Client/Adapter/Test.php';
        $adapter = new Zend_Http_Client_Adapter_Test();
        $this->_client->getHttpClient()->setAdapter($adapter);
        
        return $adapter;
    }
    
    /**
     * Provate helper method to set the next response from file contents
     * 
     */
    private function _setNextResponseFromFile($file)
    {
        $adapter = $this->_getTestAdapter();
        $response = file_get_contents(dirname(__FILE__) . '/_files/' . $file);
        $adapter->setResponse($response);
    }
    
    /**
     * Data Providers
     */
    
    static public function invalidServerUriTypes()
    {
        return array(
            array(null),
            array(false),
            array(true),
            array(12),
            array(new ArrayObject(array())),
            array(array('http://foo.bar/'))
        );
    }
    
    static public function invalidServerUriStrings()
    {
        return array(
            array('www.zend.com'),
            array('file:///usr/locaol/zend/gui'),
            array('http://my host;10081/Zend+Server'),
            array('ftp://localhost:10081/ZendServer'),
            array('somestring'),
            array('')
        );
    }
}
