<?php

namespace Edison\Object\Base;
abstract class ModelObject implements \ArrayAccess, \serializable, \JsonSerializable
{
    protected $_properties = [];
    protected $_modifiedProperties = [];
    protected $_cachableProperties = [];
    protected $_objDS;
    protected $options = [];
    public function __construct($properties = [], $options = []) {
        $this->_properties = $properties;
        $this->options = array_merge($this->options, $options);
    }
    
    /**
     * @return string
     */
    abstract public function primaryKey();

    abstract public function getModel();

    public function getProperties()
    {
        return $this->_properties;
    }

    public function getModifiedProperties()
    {
        return $this->_modifiedProperties;
    }

    public function isFromDB()
    {
        return isset($this->options['isFromDB']) && $this->options['isFromDB'];
    }

    public function save()
    {   
        return $this->getModel()->save($this);
    }

    public function __set($name,$value){
        if ($this->_properties[$name] != $value) {
            $this->_modifiedProperties[$name] = $value;
            $this->_properties[$name] = $value;
        }
    }

    public function __get($name){
        if (method_exists($this, $name)) {
            return $this->$name();
        }

        if (method_exists($this, 'caclable_'.$name)) {
            if (!isset($this->_cachableProperties[$name])) {
                $func = 'caclable_'.$name;
                $value = $this->$func();
                $this->_cachableProperties[$name] = $value;
            }
            return $this->_cachableProperties[$name];
        }

        return isset($this->_properties[$name]) ? $this->_properties[$name] : null;
    }

    public function __isset($name)
    {
        return isset($this->_properties[$name]);
    }

    public function offsetGet($offset){
        return ($this->offsetExists($offset) ? $this->_properties[$offset] : null);
    }
    public function offsetSet($offset, $value){
        if ($this->_properties[$offset] != $value) {
            $this->_modifiedProperties[$offset] = $value;
            $this->_properties[$offset] = $value;
        }
    }
    public function offsetExists($offset){
        return isset($this->_properties[$offset]);
    }

    public function offsetUnset($offset){

    }

    public function serialize()
    {
        return serialize($this->_properties);
    }

    public function unserialize($serialized)
    {
        $this->_properties = unserialize($serialized);
        $this->options['isFromDB'] = true;
    }

    public function jsonSerialize() {
        return empty($this->_properties) ? new \stdClass(): $this->_properties;
    }



}
