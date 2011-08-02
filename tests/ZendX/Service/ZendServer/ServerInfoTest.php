<?php

require_once dirname(__FILE__) . '/../../../TestHelper.php';

require_once 'Zend/Http/Response.php';

require_once 'ZendX/Service/ZendServer/ServerInfo.php';

/**
 * ZendX_Service_ZendServer test case.
 */
class ZendX_Service_ZendServer_ServerInfoTest extends PHPUnit_Framework_TestCase
{
    public function testSimpleGetId()
    {
        $xml = $this->_getXmlElementFromFile('response-clusterAddServer-ok-001.http');
        $info = new ZendX_Service_ZendServer_ServerInfo($xml->responseData->serverInfo);
        
        $this->assertEquals(25, $info->getId());
    }

    public function testSimpleGetName()
    {
        $xml = $this->_getXmlElementFromFile('response-clusterAddServer-ok-001.http');
        $info = new ZendX_Service_ZendServer_ServerInfo($xml->responseData->serverInfo);
        
        $this->assertEquals('www-05', $info->getName());
    }
    
    public function testSimpleGetAddress()
    {
        $xml = $this->_getXmlElementFromFile('response-clusterAddServer-ok-001.http');
        $info = new ZendX_Service_ZendServer_ServerInfo($xml->responseData->serverInfo);
        
        $this->assertEquals('https://www-05.local:10082/ZendServer', $info->getAddress());
    }
    
    public function testSimpleGetStatus()
    {
        $xml = $this->_getXmlElementFromFile('response-clusterAddServer-ok-001.http');
        $info = new ZendX_Service_ZendServer_ServerInfo($xml->responseData->serverInfo);
        
        $this->assertEquals(ZendX_Service_ZendServer_ServerInfo::OK, $info->getStatus());
    }
    
    public function testSimpleGetMessagesNoMessages()
    {
        $xml = $this->_getXmlElementFromFile('response-clusterAddServer-ok-001.http');
        $info = new ZendX_Service_ZendServer_ServerInfo($xml->responseData->serverInfo);
        
        $this->assertEquals(array(), $info->getMessages());
    }
    
    public function testSimpleGetMessagesTwoMessages()
    {
        $xml = $this->_getXmlElementFromFile('response-clusterAddServer-ok-002.http');
        $info = new ZendX_Service_ZendServer_ServerInfo($xml->responseData->serverInfo);
        
        $messages = $info->getMessages();
        $this->assertEquals(ZendX_Service_ZendServer_Message::WARNING, $messages[0]->getLevel());
        $this->assertEquals(ZendX_Service_ZendServer_Message::ERROR, $messages[1]->getLevel());
        $this->assertEquals("This server is waiting a PHP restart", $messages[0]->getMessage());
        $this->assertEquals("Job Queue daemon is not running on this server", $messages[1]->getMessage());
    }
    
    /**
     * Provate helper method to set the next response from file contents
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
