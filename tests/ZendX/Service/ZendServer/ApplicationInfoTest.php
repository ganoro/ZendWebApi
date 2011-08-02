<?php

require_once dirname(__FILE__) . '/../../../TestHelper.php';

require_once 'Zend/Http/Response.php';

require_once 'ZendX/Service/ZendServer/ApplicationInfo.php';

class ZendX_Service_ZendServer_ApplicationInfoTest extends PHPUnit_Framework_TestCase
{
    public function testSimpleGetId()
    {
        $xml = $this->_getXmlElementFromFile('response-applicationDeploy-ok-001.http');
        $info = new ZendX_Service_ZendServer_ApplicationInfo($xml->responseData->applicationInfo);
        
        $this->assertEquals(2, $info->getId());
    }

    public function testSimpleGetBaseUrl()
    {
        $xml = $this->_getXmlElementFromFile('response-applicationDeploy-ok-001.http');
        $info = new ZendX_Service_ZendServer_ApplicationInfo($xml->responseData->applicationInfo);
        
        $this->assertEquals('http://oapp.example.com:8080/', $info->getBaseUrl());
    }
    
    public function testSimpleGetMessagesNoMessages()
    {
        $xml = $this->_getXmlElementFromFile('response-applicationDeploy-ok-001.http');
        $info = new ZendX_Service_ZendServer_ApplicationInfo($xml->responseData->applicationInfo);
        
        $this->assertEquals(array(), $info->getMessages());
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
