<?php

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   ZendX
 * @package    ZendX_Service_ZendServer
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Exception.php 20096 2010-01-06 02:05:09Z bkarwin $
 */

/**
 * This class represents a LicenseInfo element in Zend Server service responses,
 * containing information about a Zend Server license. 
 * 
 * @category   ZendX
 * @package    ZendX_Service_ZendServer
 * @copyright  Copyright (c) 2005-2011 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class ZendX_Service_ZendServer_LicenseInfo
{
    /**
     * License status codes
     */
    const OK                  = 'OK';
    const NOT_REQUIRED        = 'notRequired';
    const INVALID             = 'invalid';
    const EXPIRED             = 'expired';
    const NODE_LIMIT_EXCEEDED = 'nodeLimitExceeded';
    
    /**
     * License status
     * 
     * @var string
     */
    protected $_status = null;
    
    /**
     * Order number
     * 
     * @var string
     */
    protected $_orderNumber = null;
    
    /**
     * License expiry date
     * 
     * @var integer
     */
    protected $_validUntil = null;
    
    /**
     * Node limit for ZSCM licenses. For ZS licenses expected to be 0
     * 
     * @var integer
     */
    protected $_nodeLimit = null;
    
    public function __construct(SimpleXMLElement $xml)
    {
        $this->_status      = (string) $xml->status;
        $this->_orderNumber = (string) $xml->orderNumber;
        $this->_validUntil  = strtotime((string) $xml->validUntil);
        $this->_nodeLimit   = (int)    $xml->nodeLimit; 
        
        if (! $this->_status) { 
            require_once 'ZendX/Service/ZendServer/Exception.php';
            throw new ZendX_Service_ZendServer_Exception("Missing license status in licenseInfo XML");
        }
    }
    
	/**
	 * Get the license status
	 * 
     * @return string
     */
    public function getStatus()
    {
        return $this->_status;
    }

	/**
	 * Get the license order number
	 * 
     * @return string
     */
    public function getOrderNumber()
    {
        return $this->_orderNumber;
    }

	/**
	 * Get the license expiry time
	 * 
     * @return integer
     */
    public function getValidUntil()
    {
        return $this->_validUntil;
    }

	/**
	 * Get the node limit
	 * 
     * @return integer
     */
    public function getNodeLimit()
    {
        return $this->_nodeLimit;
    }
}