<?php

class ZendX_Service_ZendServer_Response
{
    /**
     * Response code
     *
     * @var string
     */
    protected $_code    = null;

    /**
     * Response HTTP message
     *
     * @var string
     */
    protected $_message = null;

    /**
     * Response headers
     *
     * @var array
     */
    protected $_headers = array();

    /**
     * SimpleXML element containing the response XML (if any)
     *
     * @var SimpleXMLElement
     */
    protected $_xml     = null;

    /**
     * Response data for non-XML responses
     *
     * @var string
     */
    protected $_data    = null;

    /**
     * Response type API version
     *
     * @var string
     */
    protected $_version = null;

    /**
     * Create a new response object
     *
     * @param Zend_Http_Response $response
     */
    public function __construct(Zend_Http_Response $response)
    {
        $ctype = $response->getHeader('content-type');
        if (($pos = strpos($ctype, ';')) !== false) {
            if (preg_match('/;\s*version="?([\d+\.]+)"?/', $ctype, $match, 0, $pos)) {
                $this->_version = $match[1];
            }
            $ctype = substr($ctype, 0, $pos);
        }
        $ctype = trim($ctype);

        switch($ctype) {
            case ZendX_Service_ZendServer::ZSAPI_MEDIATYPE:
                $this->_loadVerifyXmlBody($response->getBody());
                break;

            case ZendX_Service_ZendServer::ZSAPI_CFGFILE_MEDIATYPE:
                $this->_data = $response->getBody();
                break;

            default:
                require_once 'ZendX/Service/ZendServer/Exception.php';
                throw new ZendX_Service_ZendServer_Exception("Unexpected response content type: $ctype");
                break;
        }

        $this->_code = $response->getStatus();

        if ($this->_code >= 200 && $this->_code < 300) {
            // Successful response
            if ($this->_xml) {
                if (! $this->_xml->responseData) {
                    require_once 'ZendX/Service/ZendServer/Exception.php';
                    throw new ZendX_Service_ZendServer_Exception("Response XML doesn't contain a responseData block");
                }
            }

        } elseif ($this->_code >= 400 && $this->_code < 600) {
            // Error response
            if (! ($this->_xml->errorData &&
                   $this->_xml->errorData->errorMessage &&
                   $this->_xml->errorData->errorCode)) {

                require_once 'ZendX/Service/ZendServer/Exception.php';
                throw new ZendX_Service_ZendServer_Exception("Error response XML doesn't contain error information");
            }

        } else {
            require_once 'ZendX/Service/ZendServer/Exception.php';
            throw new ZendX_Service_ZendServer_Exception("Unexpected response code: $this->_code");
        }

        $this->_message = $response->getMessage();
        $this->_headers = $response->getHeaders();
    }

    /**
     * Get the repsonse content type
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->getHeader('content-type');
    }

    /**
     * Get the content version (API version)
     *
     * @return string
     */
    public function getContentVersion()
    {
        return $this->_version;
    }

    /**
     * Get the value of a specified header
     *
     * @param  string $header
     * @return string
     */
    public function getHeader($header)
    {
        $header = ucwords(strtolower($header));
        if (isset($this->_headers[$header])) {
            return $this->_headers[$header];
        } else {
            return null;
        }
    }

    /**
     * Is the response an error response?
     *
     * @return boolean
     */
    public function isError()
    {
        return ($this->_code >= 400);
    }

    /**
     * Get the error code for an error response
     *
     * @return string
     */
    public function getErrorCode()
    {
        if ($this->_xml && $this->_xml->errorData) {
            return (string) $this->_xml->errorData->errorCode;
        }
    }

    /**
     * Get the error message for an error response
     *
     * @return string
     */
    public function getErrorMessage()
    {
        if ($this->_xml && $this->_xml->errorData) {
            return (string) $this->_xml->errorData->errorMessage;
        } else {
            return $this->_message;
        }
    }

    /**
     * Return the HTTP status code for this response
     *
     * @return integer
     */
    public function getHttpStatusCode()
    {
        return $this->_code;
    }

    /**
     * Get the XML body of the response
     *
     * @return SimpleXMLElement
     */
    public function getXmlBody()
    {
        return $this->_xml;
    }

    /**
     * Get response data for non-XML responses
     *
     * @return string
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * Load the XML response body into a SimpleXmlElement object and verify it
     *
     * @param  string $xml
     * @throws ZendX_Service_ZendServer_Exception
     */
    protected function _loadVerifyXmlBody($xml)
    {
        $useInternal = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml); /* @var $xml SimpleXmlElement */
        if (! $xml) {
            $error = libxml_get_last_error();
            require_once 'ZendX/Service/ZendServer/Exception.php';
            throw new ZendX_Service_ZendServer_Exception("Unable to parse response XML: " . $error->message);
        }
        libxml_use_internal_errors($useInternal);

        // TODO: dynamically decide on XML namespcae to register based on content type
        // $xml->registerXPathNamespace('zs', ZendX_Service_ZendServer::ZSAPI_XMLNS);

        if ($xml->getName() != 'zendServerAPIResponse') {
            require_once 'ZendX/Service/ZendServer/Exception.php';
            throw new ZendX_Service_ZendServer_Exception("Unexpected response XML root element: {$xml->getName()}");
        }

        $this->_xml = $xml;
    }
}