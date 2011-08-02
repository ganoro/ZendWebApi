<?php

require_once 'ZendX/Service/ZendServer/ApplicationServer.php';
require_once 'ZendX/Service/ZendServer/Message.php';

class ZendX_Service_ZendServer_ApplicationInfo
{
    /**
     * Application Status Codes
     */
    const OK               = 'deployed';
    const STAGING          = 'staging';
    const ACTIVATING       = 'activating';
    const DEACTIVATING     = 'deactivating';
    const UNSTAGING        = 'unstaging';
    const UNKNOWN          = 'unknown';
    const INCONSISTENT     = 'inconsistent';
    const UPLOAD_ERROR     = 'uploadError';
    const STAGE_ERROR      = 'stageError';
    const ACTIVAE_ERROR    = 'activeError';
    const DEACTIVATE_ERROR = 'deactivateError';
    const UNSTAGE_ERROR    = 'unstageError';
    const NOT_EXISTS       = 'notExists';

    /**
     * Application ID
     *
     * @var integer
     */
    protected $_id = null;

    /**
     * Application base URL
     *
     * @var string
     */
    protected $_baseUrl = null;

    /**
     * Application name
     *
     * @var string
     */
    protected $_appName = null;

    /**
     * Application name as defined by the user
     *
     * @var string
     */
    protected $_userAppName = null;

    /**
     * Installed Location
     *
     * @var string
     */
    protected $_installedLocation = null;

    /**
     * Application Status. Will be equal to one of ths status constants.
     *
     * @var string
     */
    protected $_status = null;

    /**
     * List of servers and their status
     *
     * @var array of ApplicationServer objects
     */
    protected $_servers = array();

    /**
     * Deployed versions
     *
     * @var array of deployed versions
     */
    protected $_deployedVersions = array();

    /**
     * Array of messages
     *
     * @var array of Message objects
     */
    protected $_messages = array();

	/**
     * Create a new applicationInfo object from a response XML element
     *
     * @param SimpleXMLElement $responseXml
     */
    public function __construct(SimpleXMLElement $xml)
    {
        if ($xml->getName() != 'applicationInfo') {
            require_once 'ZendX/Service/ZendServer/Exception.php';
            throw new ZendX_Service_ZendServer_Exception("Unexpected base XML element: {$xml->getName()}");
        }

        $this->_id = (int) $xml->id;
        if (! $this->_id) {
            require_once 'ZendX/Service/ZendServer/Exception.php';
            throw new ZendX_Service_ZendServer_Exception("Missing required applicationInfo element: id");
        }

        // Set all required string elements
        $elements = array(
            'baseUrl',
            'appName',
            'userAppName',
            'status',
        );

        foreach($elements as $element) {
            $this->{"_$element"} = (string) $xml->{$element};
            if (! $this->{"_$element"}) {
                require_once 'ZendX/Service/ZendServer/Exception.php';
                throw new ZendX_Service_ZendServer_Exception("Missing required applicationInfo element: $element");
            }
        }

        // Installed location may be empty for some responses (e.g. applicationDeploy)
        $this->_installedLocation = (string) $xml->installedLocation;

        foreach($xml->servers->children() as $server) {
            $this->_servers[] = new ZendX_Service_ZendServer_ApplicationServer($server);
        }

        foreach($xml->deployedVersions->deployedVersion as $version) {
            $this->_deployedVersions[] = (string) $version;
        }

        foreach($xml->messageList->children() as $message) {
            $this->_messages[] = new ZendX_Service_ZendServer_Message($message);
        }
    }

	/**
	 * Get the application ID
	 *
     * @return integer
     */
    public function getId()
    {
        return $this->_id;
    }

	/**
	 * Get the application's installed base URL
	 *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->_baseUrl;
    }

	/**
	 * Get the application name as defined in the app package
	 *
     * @return string
     */
    public function getAppName()
    {
        return $this->_appName;
    }

	/**
	 * Get the name given to this application by the user
	 *
     * @return string
     */
    public function getUserAppName()
    {
        return $this->_userAppName;
    }

	/**
     * Get the location on which this app is installed
     *
     * @return string
     */
    public function getInstalledLocation()
    {
        return $this->_installedLocation;
    }

	/**
     * Get the application Status
     *
     * This will be equal to one of the status constants
     *
	 * @return string
     */
    public function getStatus()
    {
        return $this->_status;
    }

	/**
     * Get the list of servers on which the app is deployed
     *
     * @return array
     */
    public function getServers()
    {
        return $this->_servers;
    }

	/**
	 * Get the list of deployed versions
	 *
     * @return array
     */
    public function getDeployedVersions()
    {
        return $this->_deployedVersions;
    }

    /**
     * Get the list of messages, if any
     *
     * @return array Array of Message objects
     */
    public function getMessages()
    {
        return $this->_messages;
    }
}