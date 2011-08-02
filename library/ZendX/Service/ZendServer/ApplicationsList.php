<?php

require_once 'ZendX/Service/ZendServer/ApplicationInfo.php';

class ZendX_Service_ZendServer_ApplicationsList implements ArrayAccess, Iterator, Countable
{
    protected $_pointer = 0;

    protected $_apps = array();

    protected $_appIds = array();

    public function __construct(SimpleXMLElement $xml)
    {
        if ($xml->getName() != 'applicationsList') {
            require_once 'ZendX/Service/ZendServer/Exception.php';
            throw new ZendX_Service_ZendServer_Exception("Unexpected base XML element: {$xml->getName()}");
        }

        $i = 0;
        foreach($xml->children() as $appXml) {
            $appInfo = new ZendX_Service_ZendServer_ApplicationInfo($appXml);
            $this->addApplication($appInfo);
        }
    }

    public function addApplication(ZendX_Service_ZendServer_ApplicationInfo $app)
    {
        $lastId = end($this->_appIds);
        if ($lastId === false) {
            $lastId = 0;
        } else {
            $lastId++;
        }

        $this->_apps[$lastId] = $app;
        $this->_appIds[$app->getId()] = $lastId;
    }

    /**
     * Get application by it's ID (this is sugar coatig around offsetGet())
     *
     * @param integer $id
     * @return ZendX_Service_ZendServer_ApplicationInfo
     */
    public function byId($id)
    {
        return $this->offsetGet($id);
    }

    /**
     * Iterator Interface Methods
     */

    public function current()
    {
        return $this->_apps[$this->_pointer];
    }

    public function key()
    {
        return $this->_appIds[$this->_pointer];
    }

    public function next()
    {
        $this->_pointer++;
    }

    public function offsetExists($offset)
    {
        return isset($this->_appIds[$offset]);
    }

    public function offsetGet($offset)
    {
        return ($this->_apps[$this->_appIds[$offset]]);
    }

    public function offsetSet($offset, $value)
    {
        require_once 'ZendX/Service/ZendServer/Exception.php';
        throw new ZendX_Service_ZendServer_Exception("Attempting to change a read-only list of applications");
    }

    public function offsetUnset($offset)
    {
        require_once 'ZendX/Service/ZendServer/Exception.php';
        throw new ZendX_Service_ZendServer_Exception("Attempting to change a read-only list of applications");
    }

    public function rewind()
    {
        $this->_pointer = 0;
    }

    public function valid()
    {
        return (isset($this->_apps[$this->_pointer]));
    }

    public function count()
    {
        return count($this->_apps);
    }
}
