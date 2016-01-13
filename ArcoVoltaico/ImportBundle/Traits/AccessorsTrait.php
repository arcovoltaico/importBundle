<?php

namespace ArcoVoltaico\ImportBundle\Traits;

trait AccessorsTrait {

    function isProxy() {
        $isProxy = ($class instanceof Proxy) ? get_parent_class($class) : get_class($class);
        return !$em->getMetadataFactory()->isTransient($class);
    }

    public function getAllProperties() {
        $props = get_object_vars($this);

        if (method_exists($this, '__isInitialized')) {
            unset($props['__cloner__'], $props['__initializer__'], $props['__isInitialized__']);
        }
        return $props;
    }

    public function getFieldnames() {
        $props = $this->getAllProperties();

        $fieldnames = Array();
        foreach ($props as $key => $value) {
            $fieldnames[] = $key;
        }

        return $fieldnames;
    }

    public function __toString() {
        return (string) $this->name;
    }

    public function getClassName() {
        return (new \ReflectionClass($this))->getShortName();
    }

    public function normalize($string) {
        $string = strtolower(preg_replace('~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', htmlentities($string, ENT_QUOTES, 'UTF-8')));
        $string = str_replace(' - ', '-', $string);
        $string = str_replace(' ', '-', $string);
        return $string;
    }

    public function toArray() {
        //como getAllProperties, pero los FK no son objetos sino el id
        $props = $this->getAllProperties();

        foreach ($props as $key => $v) {
            
            if ($v instanceof \DateTime){
                 $props[$key] = $v->format('Y-m-d H:i:s');
            } 
            elseif (is_object($v)) {
                $props[$key] = $v->getId();
                $props[ucfirst($key)] = $v->getId(); //compatibility with ArraResult with Metadata Hint
            }
        }
        return $props;
    }
    
}