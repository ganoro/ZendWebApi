<?php

class ZendX_Service_ZendServer_Message
{
    const INFO    = 'info';
    const WARNING = 'warning';
    const ERROR   = 'error';
    
    protected $_level = null;
    
    protected $_message = null;
    
    public function __construct(SimpleXMLElement $messageXml)
    {
        switch ($messageXml->getName()) {
            case 'info': 
                $this->_level = self::INFO;
                break;
                
            case 'warning':
                $this->_level = self::WARNING;
                break;
                
            case 'error':
                $this->_level = self::ERROR;
                break;
                
            default:
                require_once 'ZendX/Service/ZendServer/Exception.php';
                throw new ZendX_Service_ZendServer_Exception("Unexpected XML element: {$messageXml->getName()}");
                break;
        }
        
        $this->_message = (string) $messageXml;
    }
    
    public function getLevel()
    {
        return $this->_level;
    }
    
    public function getMessage()
    {
        return $this->_message;
    }
}