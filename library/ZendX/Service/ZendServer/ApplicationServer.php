<?php

class ZendX_Service_ZendServer_ApplicationServer
{
    /**
     * Server ID
     * 
     * @var integer
     */
    protected $_id = null;
    
    /**
     * Current deployed version
     * 
     * @var string
     */
    protected $_deployedVersion = null;
    
    /**
     * Application Status
     * 
     * @var string
     */
    protected $_status = null;
    
    public function __construct(SimpleXMLElement $xml)
    {
        if ($xml->getName() != 'applicationServer') {
            require_once 'ZendX/Service/ZendServer/Exception.php'; 
            throw new ZendX_Service_ZendServer_Exception("Unexpected base XML element: {$xml->getName()}");
        }
        
        if (strlen((string) $xml->id)) { // <id> may be integer 0, this is valid
            $this->_id = (int) $xml->id;
        } else {
            require_once 'ZendX/Service/ZendServer/Exception.php';
            throw new ZendX_Service_ZendServer_Exception("Missing required applicationServer element: id");
        }
        
        // Set all required string elements
        $elements = array(
            'deployedVersion', 
            'status', 
        );
        
        foreach($elements as $element) { 
            $this->{"_$element"} = (string) $xml->{$element};
            if (! $this->{"_$element"}) { 
                require_once 'ZendX/Service/ZendServer/Exception.php';
                throw new ZendX_Service_ZendServer_Exception("Missing required applicationInfo element: $element");
            }
        }
    }
    
	/**
	 * Get the server ID
	 * 
     * @return integer
     */
    public function getId()
    {
        return $this->_id;
    }

	/**
	 * Get the current deployed version
	 * 
     * @return string
     */
    public function getDeployedVersion()
    {
        return $this->_deployedVersion;
    }

	/**
	 * Get the application's status on this particular server
	 * 
	 * This is one of the status constants defined in ZendX_Service_ZendServer_ApplicationInfo
	 * 
     * @return string
     */
    public function getStatus()
    {
        return $this->_status;
    }

}