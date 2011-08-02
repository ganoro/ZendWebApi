<?php

require_once 'Zend/Uri.php';
require_once 'Zend/Http/Client.php';
require_once 'ZendX/Service/ZendServer/Response.php';

class ZendX_Service_ZendServer
{
	const ZSAPI_XMLNS			 = 'http://www.zend.com/server/api/1.1';
	const ZSAPI_MEDIATYPE		 = 'application/vnd.zend.serverapi+xml';
	const ZSAPI_VERSION		   = '1.1';
	const ZSAPI_CFGFILE_MEDIATYPE = 'application/vnd.zend.serverconfig';
	const ZSAPI_PKGFILE_MEDIATYPE = 'application/vnd.zend.applicationpackage';
	
	/**
	 * HTTP client instance
	 * 
	 * @var Zend_Http_Client
	 */
	protected $_httpClient = null;
	
	/**
	 * Server URL
	 * 
	 * @var Zend_Uri_Http
	 */
	protected $_serverUrl  = null;
	
	/**
	 * API key name
	 * 
	 * @var string
	 */
	protected $_apiKeyName = null;
	
	/**
	 * API key
	 * 
	 * @var string
	 */
	protected $_apiKey	 = null;
	
	/**
	 * User agent string
	 * 
	 * @var string
	 */
	protected $_userAgent  = null;
	
	/**
	 * Number of retries to perform if ZSCM is locked
	 * 
	 * @var integer | boolean
	 */
	protected $_lockedRetries = 0;
	
	/**
	 * Number of seconds to wait between retries
	 * 
	 * @var integer
	 */
	protected $_lockedWait = 5;
	
	/**
	 * Create a new ZendServer API client
	 * 
	 * @param Zend_Uri_Http | string $serverUrl
	 * @param string				 $apiKeyName
	 * @param string				 $apiKey
	 */
	public function __construct($serverUrl, $apiKeyName, $apiKey)
	{
		if (is_string($serverUrl)) { 
			$serverUrl = Zend_Uri::factory($serverUrl);
		}
		
		if (! $serverUrl instanceof Zend_Uri_Http) {
			require_once 'ZendX/Service/ZendServer/Exception.php';
			throw new ZendX_Service_ZendServer_Exception("\$serverUrl is expected to be an HTTP URL, got " . gettype($serverUrl));
		}
		
		$this->_serverUrl = $serverUrl;
		
		$this->_apiKeyName = (string) $apiKeyName;
		$this->_apiKey	 = (string) $apiKey;
		
		$this->_userAgent = __CLASS__ . '/ZendFramework-1.11/PHP-' . PHP_VERSION;
	}
	
	/**
	 * Set the HTTP client to be used to connect to Zend Server
	 * 
	 * @param  Zend_Http_Client $client
	 * @return ZendX_Service_ZendServer
	 */
	public function setHttpClient(Zend_Http_Client $client)
	{
		$this->_httpClient = $client;
		return $this;
	}
	
	/**
	 * Get the HTTP client which will be used to connect to Zend Server
	 * 
	 * If no HTTP client was set, will return the default one
	 * 
	 * @return Zend_Http_Client
	 */
	public function getHttpClient()
	{
		if (! $this->_httpClient) {
			$this->_httpClient = $this->_getDefaultHttpClient();
		}
		
		return $this->_httpClient;
	}
	
	/**
	 * Run the getSystemInfo API call, returning information about the Zend 
	 * Server installation we are connected to
	 * 
	 * @return ZendX_Service_ZendServer_SystemInfo
	 */
	public function getSystemInfo()
	{
		require_once 'ZendX/Service/ZendServer/SystemInfo.php';
		
		$response = $this->_sendRequestCheckResponse('GET', 'getSystemInfo');
		return new ZendX_Service_ZendServer_SystemInfo(
			$response->getXmlBody()->responseData->systemInfo
		);
	}
	
	/**
	 * Run the clusterGetServerInfo API call, returning information about one 
	 * or more servers in a cluster
	 * 
	 * @param  array $servers
	 * @return ZendX_Service_ZendServer_ServersList
	 */
	public function clusterGetServerStatus(array $servers = array())
	{
		$params = array();
		if (! empty($servers)) { 
			$params['servers'] = array_values($servers);
		}
		
		$response = $this->_sendRequestCheckResponse('GET', 'clusterGetServerStatus', $params);
		
		return new ZendX_Service_ZendServer_ServersList($response->getXmlBody()->responseData->serversList);
	}
	
	/**
	 * Add a new server to a ZSCM cluster
	 * 
	 * @param string				 $serverName
	 * @param Zend_Uri_Http | string $serverUrl
	 * @param string				 $guiPassword
	 * @param boolean				$propagate
	 * @param boolean				$restart
	 * 
	 * @return ZendX_Service_ZendServer_ServerInfo
	 */
	public function clusterAddServer($serverName, $serverUrl, $guiPassword, $propagate = false, $restart = false)
	{
		if (is_string($serverUrl)) { 
			$serverUrl = Zend_Uri_Http::factory($serverUrl);
		}
		
		if ($serverUrl instanceof Zend_Uri_Http) {
			if (! $serverUrl->valid()) {
				throw new ZendX_Service_ZendServer_Exception("Server URL is not a valid HTTP URL: $serverUrl");
			}
			
		} else {
			throw new ZendX_Service_ZendServer_Exception("Unexpected value for \$serverUrl, expecting a Zend_Uri_Http object or valid URL string");
		}
		
		$params = array(
			'serverName'		=> (string) $serverName,
			'serverUrl'		 => (string) $serverUrl,
			'guiPassword'	   => (string) $guiPassword,
			'propagateSettings' => ($propagate ? 'TRUE' : 'FALSE'),
			'doRestart'		 => ($restart ? 'TRUE' : 'FALSE')
		);
		
		$response = $this->_sendRequestHandleLockedServer('POST', 'clusterAddServer', $params);
		
		return new ZendX_Service_ZendServer_ServerInfo($response->getXmlBody()->responseData->serverInfo);
	}
	
	/**
	 * Remove a server from a cluster
	 * 
	 * @todo  Allow users to retry in case of locked cluster ?
	 * 
	 * @param integer $serverId
	 * @return ZendX_Service_ZendServer_ServerInfo
	 */
	public function clusterRemoveServer($serverId)
	{
		$serverId = (int) $serverId;
		if ($serverId < 1) { 
			throw new ZendX_Service_ZendServer_Exception('serverId is expected to be a positive number');
		}
		
		$params = array(
			'serverId' => $serverId
		);
		
		$response = $this->_sendRequestHandleLockedServer('POST', 'clusterRemoveServer', $params);
		
		return new ZendX_Service_ZendServer_ServerInfo($response->getXmlBody()->responseData->serverInfo);
	}
	
	/**
	 * Enable a disabled cluster member
	 * 
	 * @param  integer $serverId
	 * @return ZendX_Service_ZendServer_ServerInfo
	 */
	public function clusterEnableServer($serverId)
	{
		$serverId = (int) $serverId;
		if ($serverId < 1) { 
			throw new ZendX_Service_ZendServer_Exception('serverId is expected to be a positive number');
		}
		
		$params = array(
			'serverId' => $serverId
		);
		
		$response = $this->_sendRequestHandleLockedServer('POST', 'clusterEnableServer', $params);
		
		return new ZendX_Service_ZendServer_ServerInfo($response->getXmlBody()->responseData->serverInfo);
	}
	
	/**
	 * Disable a cluster member
	 * 
	 * @param integer $serverId
	 * @return ZendX_Service_ZendServer_ServerInfo
	 */
	public function clusterDisableServer($serverId)
	{
		$serverId = (int) $serverId;
		if ($serverId < 1) { 
			throw new ZendX_Service_ZendServer_Exception('serverId is expected to be a positive number');
		}
		
		$params = array(
			'serverId' => $serverId
		);
		
		$response = $this->_sendRequestHandleLockedServer('POST', 'clusterDisableServer', $params);
		
		return new ZendX_Service_ZendServer_ServerInfo($response->getXmlBody()->responseData->serverInfo);
	}
	
	/**
	 * Reconfigure a cluster member to match the cluster's profile
	 * 
	 * @param  integer $serverId
	 * @param  boolean $doRestart - Should the reconfigured server be restarted after the reconfigure action.
	 * @return ZendX_Service_ZendServer_ServerInfo
	 */
	public function clusterReconfigureServer($serverId, $doRestart=false)
	{
		$serverId = (int) $serverId;
		if ($serverId < 1) { 
			throw new ZendX_Service_ZendServer_Exception('serverId is expected to be a positive number');
		}
		
		$params = array(
			'serverId' => $serverId,
			'doRestart' => ($doRestart ? 'TRUE' : 'FALSE'),	
		);
		
		$response = $this->_sendRequestHandleLockedServer('POST', __FUNCTION__, $params);
		
		return new ZendX_Service_ZendServer_ServerInfo($response->getXmlBody()->responseData->serverInfo);
	}
		
	/**
	 * Run the restartPhp API call, restarting PHP on one or more servers
	 * 
	 * @param  array   $servers
	 * @param  boolean $parallel
	 * @return ZendX_Service_ZendServer_ServersList
	 */
	public function restartPhp(array $servers = array(), $parallel = false)
	{
		$params = array();
		if (!empty($servers)) { 
			$params['servers'] = array_values($servers);
		}		
	
		if ($parallel) {
			$params['parallelRestart'] = 'TRUE';
		}
		
		$response = $this->_sendRequestCheckResponse('POST', 'restartPhp', $params);
		
		return new ZendX_Service_ZendServer_ServersList($response->getXmlBody()->responseData->serversList);
	}
	
	/**
	 * Export configuration to a file 
	 * 
	 * Configuration will be written to the specified file. If a directory is 
	 * specified, configuration will be written to a server-provided file name
	 * in that directory. If no output path is specified, configuration will be 
	 * written to a file in the system's temporary directory.
	 * 
	 * The final file name to which configuration has been written will be 
	 * returned.
	 *  
	 * @param  string $outFile
	 * @return string 
	 */
	public function configurationExport($outFile = null)
	{
		$response = $this->_sendRequestCheckResponse('GET', 'configurationExport');
		$responseContentParts = explode(';', $response->getContentType()); // content type may appear as: application/vnd.zend.serverconfig;application/vnd.zend.serverapi+xml;version=1.0

		if ($responseContentParts[0] != self::ZSAPI_CFGFILE_MEDIATYPE) {
			throw new ZendX_Service_ZendServer_Exception("unexpected response content-type: {$response->getContentType()}");
		}
		
		if (! $outFile) {
			$outFile = realpath(sys_get_temp_dir());
		}
		
		if (is_dir($outFile)) {
			$contentDisp = $response->getHeader('content-disposition');
			if (preg_match('/filename="([^\/\\"]+)"/', $contentDisp, $match)) { 
				$fileName = trim($match[1]);
			} else {
				$fileName = 'ZendServerConfig-' . date('Ymd') . '.zcfg';
			}
			
			$outFile = rtrim($outFile, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;
		}
		
		if (! file_put_contents($outFile, $response->getData())) {
			throw new ZendX_Service_ZendServer_Exception("failed writing configuration snapshot to $outFile");
		}
		
		return $outFile;
	}
	
	/**
	 * Import Zend Server configuration from a snapshot file
	 * 
	 * @param string $inFile
	 * @param force  $force
	 */
	public function configurationImport($inFile, $force = false)
	{
		if (! ($data = file_get_contents($inFile))) {
			throw new ZendX_Service_ZendServer_Exception("failed reading configuration snapshot from $inFile");
		}
		
		$params = array('ignoreSystemMismatch' => ($force ? 'TRUE' : 'FALSE'));
		
		$response = $this->_sendRequestCheckResponse('POST', 'configurationImport', $params, array(
			'configFile' => array(
				'data'  => $data,
				'ctype' => self::ZSAPI_CFGFILE_MEDIATYPE
			)
		));
		
		return new ZendX_Service_ZendServer_ServersList($response->getXmlBody()->responseData->serversList);
	}
	
	/**
	 * Get the status of all or some of the currently installed apps
	 * 
	 * If $apps is empty, status of all apps is returned. Otherwise, $apps can
	 * be an array of application IDs, and only the status of specified
	 * applications will be returned
	 * 
	 * @param array $apps
	 */
	public function applicationGetStatus($apps = array())
	{
		$params = array();
		if (! empty($apps)) { 
			$params['applications'] = array_values($apps);
		}
		
		$response = $this->_sendRequestCheckResponse('GET', __FUNCTION__, $params);
		
		require_once 'ZendX/Service/ZendServer/ApplicationsList.php';
		return new ZendX_Service_ZendServer_ApplicationsList($response->getXmlBody()->responseData->applicationsList);
	}
	
	/**
	 * Deploy an application using the web API
	 * 
	 * @param  string  $pkgFile		Path or URL of ZPK package to deploy.
	 *								 URL schemes supported by PHP's stream wrappers can be used.
	 * @param  string  $baseUrl		Base URL to deploy app on. 
	 *								 If only path is given, will deploy on the default vhost.
	 * @param  string  $appName		Application name as given by the user
	 * @param  array   $userParams	 Associative array of user parameters
	 * @param  boolean $createVhost	Create a new virtual host if not exists?
	 * @param  boolean $ignoreFailures Ignore failures if some hosts failed?
	 * @return ZendX_Service_ZendServer_ApplicationInfo
	 */
	public function applicationDeploy($pkgFile, $baseUrl, $appName=null, array $userParams=array(), $createVhost=false, $ignoreFailures=false)
	{
		if (!$pkgFile) { 
			require_once 'ZendX/Service/ZendServer/Exception.php';
			throw new ZendX_Service_ZendServer_Exception("package file must point to a valid package file or URL");
		}
		
		// Check if base URL is an absolute URL or not. 
		// If it is just a path, we assume 'defaultServer = true'
		if (Zend_Uri_Http::check($baseUrl)) { 
			$defaultVhost = false;
		} else {
			// Assume URL is a valid path (TODO: validate?)
			$defaultVhost = true;
			$baseUrl = 'http://localhost/' . (ltrim($baseUrl, '/'));
		}
		
		$params = array(
			'baseUrl'		=> $baseUrl,
			'userAppName'	=> $appName,
			'userParams'	 => $userParams,
			'ignoreFailures' => ($ignoreFailures ? 'TRUE' : 'FALSE'),
			'createVhost'	=> ($createVhost ? 'TRUE' : 'FALSE'),
			'defaultServer'  => ($defaultVhost ? 'TRUE' : 'FALSE')
		);
		
		$files = array('appPackage' => array(
			'path'  => $pkgFile,
			'ctype' => self::ZSAPI_PKGFILE_MEDIATYPE
		));
		
		$response = $this->_sendRequestCheckResponse('POST', __FUNCTION__, $params, $files);
		
		require_once 'ZendX/Service/ZendServer/ApplicationInfo.php';
		return new ZendX_Service_ZendServer_ApplicationInfo($response->getXmlBody()->responseData->applicationInfo);
	}
	
	/**
	 * Remove an application using the web API
	 * 
	 * @param  integer  $appId		the application ID to remove
	 * @return ZendX_Service_ZendServer_ApplicationInfo
	 */
	public function applicationRemove($appId)
	{
		if (!is_numeric($appId)) { 
			require_once 'ZendX/Service/ZendServer/Exception.php';
			throw new ZendX_Service_ZendServer_Exception("appId to remove must be an integer");
		}
		
		$params = array(
			'appId'		=> $appId,
		);
		
		$response = $this->_sendRequestCheckResponse('POST', __FUNCTION__, $params);
		
		require_once 'ZendX/Service/ZendServer/ApplicationInfo.php';
		return new ZendX_Service_ZendServer_ApplicationInfo($response->getXmlBody()->responseData->applicationInfo);
	}
	
	/**
	 * Redeploy an application using the web API
	 * 
	 * @param  integer	$appId		the application ID to redeploy
	 * @param  array	$servers
	 * @return ZendX_Service_ZendServer_ApplicationInfo
	 */
	public function applicationRedeploy($appId, array $servers=array())
	{
		if (!is_numeric($appId)) { 
			require_once 'ZendX/Service/ZendServer/Exception.php';
			throw new ZendX_Service_ZendServer_Exception("appId to redeploy must be an integer");
		}
		
		$params['appId'] = $appId;

		if (!empty($servers)) { 
			$params['servers'] = array_values($servers);
		}
		
		$response = $this->_sendRequestCheckResponse('POST', __FUNCTION__, $params);
		
		require_once 'ZendX/Service/ZendServer/ApplicationInfo.php';
		return new ZendX_Service_ZendServer_ApplicationInfo($response->getXmlBody()->responseData->applicationInfo);
	}
	
	/**
	 * Update an application using the web API
	 * 
	 * @param  integer $appId		  the application ID to update
	 * @param  string  $pkgFile		Path or URL of ZPK package to deploy.
	 *								 URL schemes supported by PHP's stream wrappers can be used.
	 * @param  array   $userParams	 Associative array of user parameters
	 * @param  boolean $ignoreFailures Ignore failures if some hosts failed?
	 * @return ZendX_Service_ZendServer_ApplicationInfo
	 */
	public function applicationUpdate($appId, $pkgFile, array $userParams=array(), $ignoreFailures=false)
	{
		if (!is_numeric($appId)) { 
			require_once 'ZendX/Service/ZendServer/Exception.php';
			throw new ZendX_Service_ZendServer_Exception("appId to update must be an integer");
		}
		
		if (!$pkgFile) { 
			require_once 'ZendX/Service/ZendServer/Exception.php';
			throw new ZendX_Service_ZendServer_Exception("package file must point to a valid package file or URL");
		}		
		
		$params = array(
			'appId'		=> $appId,
			'userParams'	 => $userParams,
			'ignoreFailures' => ($ignoreFailures ? 'TRUE' : 'FALSE'),
		);		
		
		$files = array('appPackage' => array(
			'path'  => $pkgFile,
			'ctype' => self::ZSAPI_PKGFILE_MEDIATYPE
		));

		
		$response = $this->_sendRequestCheckResponse('POST', __FUNCTION__, $params, $files);
		
		require_once 'ZendX/Service/ZendServer/ApplicationInfo.php';
		return new ZendX_Service_ZendServer_ApplicationInfo($response->getXmlBody()->responseData->applicationInfo);
	}	
	
	/**
	 * Rollback an application using the web API
	 * 
	 * @param  integer  $appId		the application ID to rollback
	 * @return ZendX_Service_ZendServer_ApplicationInfo
	 */
	public function applicationRollback($appId)
	{
		if (!is_numeric($appId)) { 
			require_once 'ZendX/Service/ZendServer/Exception.php';
			throw new ZendX_Service_ZendServer_Exception("appId to rollback must be an integer");
		}
		
		$params = array(
			'appId'		=> $appId,
		);
		
		$response = $this->_sendRequestCheckResponse('POST', __FUNCTION__, $params);
		
		require_once 'ZendX/Service/ZendServer/ApplicationInfo.php';
		return new ZendX_Service_ZendServer_ApplicationInfo($response->getXmlBody()->responseData->applicationInfo);
	}
		
	/**
	 * Set the client to retry ZSCM operations if the server is temporarily locked
	 * 
	 * @param integer | boolean $retries number of retires; TRUE means unlimited; FALSE means do not retry
	 * @param integer		   $wait	seconds to wait betwen retries 
	 */
	public function setRetryIfLocked($retries, $wait = null)
	{
		$wait = (int) $wait;
		if ($wait < 0) { 
			throw new ZendX_Service_ZendServer_Exception("\$retries is expected to be a positive integer");
		} elseif ($wait == 0) { 
			$wait = 5;
		}
		
		if ($retries === true | $retries === 0) { 
			$retries = 0;
		} elseif (! $retries) { 
			$retries = 1;
		} else {
			$retries = (int) $retries;
		}
		
		$this->_lockedRetries = $retries;
		$this->_lockedWait	= $wait;
	}

	/**
	 * Create a new Zend_Http_Client instance with default configuration
	 *
	 * @return Zend_Http_Client
	 */
	protected function _getDefaultHttpClient()
	{
		$client = new Zend_Http_Client();
		$client->setConfig(array(
			'useragent'	 => $this->_userAgent,
			'maxredirects'  => 0,
			'storeresponse' => false,
			'timeout'	   => 60
		));
		
		return $client;
	}
	
	/**
	 * Send API request and check for errors. If ZSCM returns a 'locked' 
	 * response, act based on retry settings
	 * 
	 * @param  string $method
	 * @param  string $action
	 * @param  array  $params
	 * @param  array  $files
	 * @throws ZendX_Service_ZendServer_Exception
	 * @return ZendX_Service_ZendServer_Response
	 */
	protected function _sendRequestHandleLockedServer($method, $action, array $params = array(), array $files = array())
	{
		$i = 0;
		do {
			$i++;
			try {
				$response = $this->_sendRequestCheckResponse($method, $action, $params, $files);
				return $response;
				
			} catch (ZendX_Service_ZendServer_Exception $ex) {
				if ($ex->getZendServerErrorCode() != 'temporarilyLocked' || $i == $this->_lockedRetries) {
					throw $ex;
				}
				sleep($this->_lockedWait);
			}
			
		} while ($this->_lockedRetries == 0 || $i < $this->_lockedRetries);
	}
	
	/**
	 * Send an API request, and check the response for errors
	 * 
	 * @param  string $method
	 * @param  string $action
	 * @param  array  $params
	 * @param  array  $files
	 * @throws ZendX_Service_ZendServer_Exception
	 * @return ZendX_Service_ZendServer_Response
	 */
	protected function _sendRequestCheckResponse($method, $action, array $params = array(), array $files = array())
	{
		$response = $this->_sendHttpRequest($method, $action, $params, $files);
		
		if ($response->isError()) { 
			require_once 'ZendX/Service/ZendServer/Exception.php';
			$ex = new ZendX_Service_ZendServer_Exception($response->getErrorMessage(), $response->getHttpStatusCode());
			$ex->setZendServerErrorCode($response->getErrorCode());
			
			throw $ex;
		}
		
		return $response;
	}
	
	/**
	 * Generate the request signature
	 * 
	 * @param  string $host
	 * @param  string $path
	 * @param  string $date
	 * @return string
	 */
	protected function _generateRequestSignature($host, $path, $date)
	{
		$data = "$host:$path:{$this->_userAgent}:$date";
		return hash_hmac('sha256', $data, $this->_apiKey);
	}
	
	/**
	 * Send the HTTP request to the web server
	 * 
	 * @param  string $method
	 * @param  string $action
	 * @param  array  $params
	 * @param  array  $files
	 * @return ZendX_Service_ZendServer_Response
	 */
	protected function _sendHttpRequest($method, $action, array $params = array(), array $files = array())
	{
		$this->_prepareHttpClient($method, $action, $params, $files);
		$response = $this->getHttpClient()->request($method);
		return new ZendX_Service_ZendServer_Response($response);
	}
	
	/**
	 * Prepare the HTTP client before sending a request
	 * 
	 * @param  string $method
	 * @param  string $action
	 * @param  array  $params
	 * @param  array  $files
	 * @throws ZendX_Service_ZendServer_Exception
	 */
	protected function _prepareHttpClient($method, $action, array $params, array $files)
	{
		$client = $this->getHttpClient();
		$client->resetParameters();
		
		$date = gmdate('D, d M y H:i:s ') . 'GMT';

		if ($this->_httpClient && ($host = $this->_httpClient->getHeader('host'))) {
			// host is taken from pre-set header in HTTP client
		} else {
			$host = $this->_serverUrl->getHost() . ':' . $this->_serverUrl->getPort();
		}
		
		$path = rtrim($this->_serverUrl->getPath(), '/') . '/Api/' . $action;
		
		$signature = $this->_generateRequestSignature($host, $path, $date);
		
		$client->setHeaders(array(
			'Accept'		   => self::ZSAPI_MEDIATYPE . ';version=' . self::ZSAPI_VERSION,  
			'Date'			 => $date,
			'Host'			 => $host,
			'X-Zend-Signature' => "$this->_apiKeyName; $signature"
		));
		
		$url = clone $this->_serverUrl;
		$url->setPath($path);
		
		$method = strtoupper($method);
		switch($method) {
			
			case 'GET':
				if (! empty($files)) { 
					require_once 'ZendX/Service/ZendServer/Exception.php';
					throw new ZendX_Service_ZendServer_Exception("Sending a GET request but \$files is not empty");
				}
				$client->setParameterGet($params);
				break;
				
			case 'POST':
				$client->setParameterPost($params);
				if ($files) { 
					foreach ($files as $key => $file) { 
						if (isset($file['path'])) { 
							$data = null;
							$path = $file['path'];
						} elseif (isset($file['data'])) { 
							$data = $file['data'];
							$path = $key;
						} else {
							require_once 'ZendX/Service/ZendServer/Exception.php';
							throw new ZendX_Service_ZendServer_Exception("File information for $key is missing both data or path");
						}
						
						if (! isset($file['ctype'])) { 
							$file['ctype'] = 'application/octet-stream';
						}
						
						$client->setFileUpload($path, $key, $data, $file['ctype']);
					}
				}
				break;
				
			default:
				require_once 'ZendX/Service/ZendServer/Exception.php';
				throw new ZendX_Service_ZendServer_Exception("Unexpected request method: $method");
				break;
		}
		
		$client->setUri($url);
	}
}
