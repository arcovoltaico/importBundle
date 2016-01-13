<?php

namespace ArcoVoltaico\ImportBundle\Entity;

use ArcoVoltaico\ImportBundle\Traits\AccessorsTrait;
use ArcoVoltaico\ImportBundle\Traits\UploadTrait;
use Doctrine\ORM\Mapping as ORM;

class Import {

    use AccessorsTrait,
        UploadTrait;

    private $id;
    private $name;
    private $created;

    public function __construct() {
        $this->created = new \DateTime();
    }

    public function getId() {
        return $this->id;
    }

    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    public function getName() {
        return $this->name;
    }

    public function setCreated() {
        $this->created = new \DateTime();
        return $this;
    }

    public function getCreated() {
        return $this->created;
    }

}
