<?php

require_once 'ZendX/Service/ZendServer/ServerInfo.php';

class ZendX_Service_ZendServer_ServersList implements ArrayAccess, Iterator, Countable
{
    protected $_pointer = 0;
    
    protected $_servers = array();
    
    protected $_serverIds = array();
    
    public function __construct(SimpleXMLElement $xml)
    {
        if ($xml->getName() !== 'serversList') {
            require_once 'ZendX/Service/ZendServer/Exception.php'; 
            throw new ZendX_Service_ZendServer_Exception("Unexpected base XML element: {$xml->getName()}");
        }
        
        $i = 0;
        foreach($xml->children() as $serverXml) {
            $serverInfo = new ZendX_Service_ZendServer_ServerInfo($serverXml);
            $this->_servers[$i] =  $serverInfo;
            $this->_serverIds[$serverInfo->getId()] = $i++; 
        }
    }
    
    public function current()
    { 
        return $this->_servers[$this->_pointer];
    }
    
    public function key() 
    { 
        return $this->_serverIds[$this->_pointer];
    }
    
    public function next() 
    { 
        $this->_pointer++;
    }
    
    public function offsetExists($offset)
    { 
        return isset($this->_serverIds[$offset]); 
    }
    
    public function offsetGet($offset)
    { 
        return ($this->_servers[$this->_serverIds[$offset]]);
    }
    
    public function offsetSet($offset, $value)
    { 
        require_once 'ZendX/Service/ZendServer/Exception.php';
        throw new ZendX_Service_ZendServer_Exception("Attempting to change a read-only list of servers");
    }
    
    public function offsetUnset($offset)
    { 
        require_once 'ZendX/Service/ZendServer/Exception.php';
        throw new ZendX_Service_ZendServer_Exception("Attempting to change a read-only list of servers");
    }
    
    public function rewind()
    { 
        $this->_pointer = 0;
    }
    
    public function valid()
    { 
        return (isset($this->_servers[$this->_pointer]));
    }
    
    public function count()
    {
        return count($this->_servers);
    }
}