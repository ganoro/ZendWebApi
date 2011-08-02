<?php

require_once dirname(__FILE__) . '/../../../TestHelper.php';

require_once 'Zend/Http/Response.php';

require_once 'ZendX/Service/ZendServer/ServersList.php';

/**
 * ZendX_Service_ZendServer test case.
 */
class ZendX_Service_ZendServer_ServersListTest extends PHPUnit_Framework_TestCase
{
    public function testCountableInterface()
    {
        $xml = $this->_getXmlElementFromFile('response-clusterGetServerStatus-ok-001.http');
        $list = new ZendX_Service_ZendServer_ServersList($xml->responseData->serversList);
        
        $this->assertEquals(2, count($list));
    }

    public function testIteratorInterface()
    {
        $xml = $this->_getXmlElementFromFile('response-clusterGetServerStatus-ok-001.http');
        $list = new ZendX_Service_ZendServer_ServersList($xml->responseData->serversList);
        
        $i = 0;
        foreach($list as $server) { 
            $this->assertTrue($server instanceof ZendX_Service_ZendServer_ServerInfo);
            $i++;
        }
        
        $this->assertEquals(2, $i);
    }
    
    /**
     * Private helper method to set the next response from file contents
     * 
     */
    private function _getXmlElementFromFile($file)
    {
        $xmlStr = Zend_Http_Response::extractBody(
            file_get_contents(dirname(__FILE__) . '/_files/' . $file)
        );
        
        return simplexml_load_string($xmlStr);
    }
}
