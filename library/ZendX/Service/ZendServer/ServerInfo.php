<?php

require_once 'ZendX/Service/ZendServer/Message.php';

class ZendX_Service_ZendServer_ServerInfo
{
    /**
     * Server status constants
     */
    const OK                 = 'OK';
    const SHUTTING_DOWN      = 'shuttingDown';
    const STARTING_UP        = 'startingUp';
    const PENDING_RESTART    = 'pendingRestart'; 
    const RESTARTING         = 'restarting';
    const MISCONFIGURED      = 'misconfigured';
    const EXTENSION_MISMATCH = 'extensionMismatch';
    const DAEMON_MISMATCH    = 'daemonMismatch';
    const NOT_RESPONDING     = 'notResponding';
    const DISABLED           = 'disabled';
    const REMOVED            = 'removed';
    const UNKNOWN            = 'unknown';
    
    /**
     * Server ID
     * 
     * @var integer
     */
    protected $_id       = null;
    
    /**
     * Server name
     * 
     * @var string
     */
    protected $_name     = null;
    
    /**
     * Server address
     * 
     * @var string
     */
    protected $_address  = null;
    
    /**
     * Server status
     * 
     * @var string
     */
    protected $_status   = null;
    
    /**
     * Server messages
     * 
     * @var array of ZendX_Service_ZendServer_Messages
     */
    protected $_messages = array();
    
    /**
     * Create a new serverInfo object from a response XML element
     * 
     * @param SimpleXMLElement $responseXml
     */
    public function __construct(SimpleXMLElement $xml)
    {
        if ($xml->getName() !== 'serverInfo') {
            require_once 'ZendX/Service/ZendServer/Exception.php'; 
            throw new ZendX_Service_ZendServer_Exception("Unexpected base XML element: {$xml->getName()}");
        }
        
        if (!empty($xml->id)) $this->_id = (int) $xml->id; // as to avoid silent casting of an empty value to 0
        $this->_name    = (string) $xml->name;
        $this->_address = (string) $xml->address;
        $this->_status  = (string) $xml->status;
        
        /* NOT VALIDTING these fields, as when dealing with single server, they're not populated
        if (! $this->_id) { 
            require_once 'ZendX/Service/ZendServer/Exception.php';
            throw new ZendX_Service_ZendServer_Exception("Missing server ID in serverInfo XML");
        }
        
        if (! $this->_name) {
            require_once 'ZendX/Service/ZendServer/Exception.php';
            throw new ZendX_Service_ZendServer_Exception("Missing server name in serverInfo XML");
        }
        
        if (! $this->_address) { 
            require_once 'ZendX/Service/ZendServer/Exception.php';
            throw new ZendX_Service_ZendServer_Exception("Missing server address in serverInfo XML");
        }*/
        
        if (! $this->_status) { 
            require_once 'ZendX/Service/ZendServer/Exception.php';
            throw new ZendX_Service_ZendServer_Exception("Missing server status in serverInfo XML");
        }
        
        foreach($xml->messageList->children() as $message) { 
            $this->_messages[] = new ZendX_Service_ZendServer_Message($message);
        }
    }
    
    /**
     * 
     * Enter description here ...
     */
    public function getId()
    {
        return $this->_id;
    }
    
    /**
     * 
     * Enter description here ...
     */
    public function getName()
    {
        return $this->_name;
    }
    
    /**
     * 
     * Enter description here ...
     */
    public function getAddress()
    {
        return $this->_address;
    }
    
    /**
     * 
     * Enter description here ...
     */
    public function getStatus()
    {
        return $this->_status;
    }
    
    /**
     * 
     * Enter description here ...
     */
    public function getMessages()
    {
        return $this->_messages;
    }
}