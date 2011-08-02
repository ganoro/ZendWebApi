<?php

require_once 'ZendX/Service/ZendServer/LicenseInfo.php';

class ZendX_Service_ZendServer_SystemInfo
{
    const OK              = 'OK';
    const NOT_LICENSED    = 'notLicensed';
    const PENDING_RESTART = 'pendingRestart';
    
    const EDITION_ZS   = 'ZendServer';
    const EDITION_ZSCM = 'ZendServerClusterManager';
    const EDITION_ZSCE = 'ZendServerCommunityEdition';
    
    protected $_status = null;
    
    protected $_edition = null;
    
    protected $_zendServerVersion = null;
    
    protected $_phpVersion = null;
    
    protected $_operatingSystem = null;
    
    protected $_supportedApiVersions = array();
    
    protected $_serverLicenseInfo = null;
    
    protected $_managerLicenseInfo = null;
    
    public function __construct(SimpleXMLElement $xml)
    {
        if ($xml->getName() != 'systemInfo') {
            require_once 'ZendX/Service/ZendServer/Exception.php'; 
            throw new ZendX_Service_ZendServer_Exception("Unexpected base XML element: {$xml->getName()}");
        }
        
        $this->_status            = (string) $xml->status;
        $this->_edition           = (string) $xml->edition;
        $this->_zendServerVersion = (string) $xml->zendServerVersion;
        $this->_phpVersion        = (string) $xml->phpVersion;
        $this->_operatingSystem   = (string) $xml->operatingSystem;
        
        foreach(explode(',', $xml->supportedApiVersions) as $version ) {
            $this->_supportedApiVersions[] = trim($version);
        }
        
        $this->_serverLicenseInfo  = new ZendX_Service_ZendServer_LicenseInfo($xml->serverLicenseInfo);
        $this->_managerLicenseInfo = new ZendX_Service_ZendServer_LicenseInfo($xml->managerLicenseInfo);
        
        if (! $this->_status) { 
            require_once 'ZendX/Service/ZendServer/Exception.php';
            throw new ZendX_Service_ZendServer_Exception("Missing server status in systemInfo XML");
        }
        
        if (! $this->_edition) { 
            require_once 'ZendX/Service/ZendServer/Exception.php';
            throw new ZendX_Service_ZendServer_Exception("Missing Zend Server edition in systemInfo XML");
        }
        
        /**
         * @todo: Validate other XML elements?
         */
    }
    
    public function getStatus()
    {
        return $this->_status;
    }
    
    public function getEdition()
    {
        return $this->_edition;
    }
    
    public function getZendServerVersion()
    {
        return $this->_zendServerVersion;
    }
    
    public function getSupportedApiVersions()
    {
        return $this->_supportedApiVersions;
    }
    
    public function getPHPVersion()
    {
        return $this->_phpVersion;
    }
    
    public function getOperatingSystem()
    {
        return $this->_operatingSystem;
    }
    
    public function getServerLicenseInfo()
    {
        return $this->_serverLicenseInfo;
    }
    
    public function getManagerLicenseInfo()
    {
        return $this->_managerLicenseInfo;
    }
}