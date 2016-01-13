<?php

namespace ArcoVoltaico\ImportBundle\Services;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Importer {

    private $em, $conn, $tr, $ents, $namespace, $bundle, $parent;

    public function __construct(
    \Doctrine\ORM\EntityManager $em, \Symfony\Component\DependencyInjection\Container $sc, \Symfony\Component\Translation\DataCollectorTranslator $tr
    ) {
        $this->em = $em;
        $this->conn = $this->em->getConnection();
        $this->tr = $tr;
        $this->ents = $sc->getParameter('arco_voltaico_import.entities');
        $this->namespace = $sc->getParameter('arco_voltaico_import.namespace');
        $this->bundle = $sc->getParameter('arco_voltaico_import.bundle');
        $this->parent = '';
    }

    public function run($xml_entity, $id) {
        $doc = $this->em->getRepository($xml_entity)->find($id);
        if (!$doc) {
            throw new NotFoundHttpException('Not found XML');
        }

        $r2 = simplexml_load_file($doc->getWebPath());
        $this->prepareReset();
        $this->import($r2);
        $this->em->flush();
        $this->em->clear();
        return $this->tr->trans(
                        'imported_items', array('%number%' => count($r2))
        );
    }

    private function prepareReset() {
        $tables = Array();
        foreach ($this->ents as $entName => $ent) {

            if ($ent['clear']) {
                $tables[] = ucfirst($entName);
            }
        }
        $this->reset(array_reverse($tables));
    }

    private function reset($tables) {
        $this->conn->getConfiguration()->setSQLLogger(null);
        foreach ($tables as $table) {
            $this->conn->exec("TRUNCATE TABLE " . $table . ";");
            $this->conn->exec("ALTER TABLE " . $table . " AUTO_INCREMENT = 1;");
            $delete = "DELETE FROM ext_translations WHERE object_class like '%" . $table . "'";
            $this->conn->exec($delete);
        }
    }

    private function import($r2) {
        foreach ($r2 as $r) {
            foreach ($this->ents as $class => $ent) {
         
                $classname = '\\' . $this->namespace . '\\' . ucfirst($class);
                $k = 0;
                $end = 1;
                $multiple = null;

                if (array_key_exists('multiple', $ent)) {
                    if (is_array($ent['multiple'])) {
                        $multiple = 'array';
                        $end = count($ent['multiple']);
                    } else {
                        $multiple = $ent['multiple']; //fotos
                        $mult = explode('.', $multiple);
                        $rr = $r;
                        foreach ($mult as $m => $mu) {
                            $rr = $rr->$mu;
                        }
                        $end = count($rr);
                    }
                }

                do {
                    // ladybug_dump('K:' . $k);
                    $object = new $classname();
                    $null = false;
                    if (array_key_exists('parent', $ent)) {
                        $setter = 'set' . ucfirst($ent['parent']);
                        $object->$setter($this->parent); //object now is the children one
                    }



                    //SET ATTRIBUTES
                    foreach ($ent['import'] as $fname => $field) {
                        if (!$null) {
                            //GET VAL FROM XML
                            switch ($multiple) {
                                case 'array': //limited children (mirror)
                                    //ie: modes['sale'].price
                                    if (count($field['mirror']) > 0) {
                                        $v = $field['mirror'][$k];
                                    } else {
                                        $xml = $ent['multiple'][$k] . '.' . $field['xml'];
                                        $v = $this->getElementValue($r, $xml);
                                    }
                                    break;
                                case null: // parent entity
                                    $xml = $field['xml'];
                                    $v = $this->getElementValue($r, $xml);
                                    break;
                                default: //unlimited children
                                    //ie: offers[0].price
                                    $xml = $ent['multiple'] . '.' . $field['xml'];
                                    // ladybug_dump($fname . $k . '/' . $end);
                                    $v = $this->getElementValue($r, $xml, $k);

                                //   ladybug_dump($xml . ':' . $v);
                            }
                            // ladybug_dump($fname . $v);


                            if (is_null($v) && array_key_exists('nullable', $field) && $field['nullable'] == false) {
                                $null = true;
                                var_dump('NULL: ' . $fname);
                                continue;
                            }


                            // GET VAL FROM XML V
                            switch (true) {

                                case ($field['mirror']): //objects
                                    $reponame = $this->bundle . ':' . ucfirst($field['class']);
                                    if (array_key_exists($k, $field['mirror'])) {
                                        $val = $field['mirror'][$k];
                                        $fk = $this->em->getReference($reponame, $val);
                                        $this->setter($fname, $fk, $object);
                                    }
                                    break;

                                case ($field['map']): //objects
                                    $reponame = $this->bundle . ':' . ucfirst($field['class']);
                                    if (array_key_exists($v, $field['map'])) {
                                        $val = $field['map'][$v];
                                        $fk = $this->em->getReference($reponame, $val);
                                        $this->setter($fname, $fk, $object);
                                    }
                                    break;

                                case ($field['activate']): //booleans
                                    if (in_array($v, $field['activate'])) { //if this field is on the array activate, then will set the original field to true
                                        $this->setter($fname, true, $object);
                                    }
                                    break;

                                default:
                                    if ($v) {
                                        $this->setter($fname, $v, $object);
                                    }
                            }
                        }
                    }


                    if (!array_key_exists('parent', $ent)) {
                        $this->parent = $object;
                    }

                    if (!$null) {
                        $this->em->persist($object);
                        var_dump($object);
                    }
                    if (array_key_exists('upload', $ent) && $ent['upload']) {
                        var_dump($object->getUrl() . ' COPY2 ' . $object->getWebPath());
                        $filecontent = file_get_contents($object->getUrl());
                        file_put_contents($object->getWebPath(), $filecontent);
                    }

                    $k++;
                } while ($k < $end);
            }
        }
    }

    private function getElementValue($r, $xml, $k = null) {

        //set a field with xml = _index  w/ the $k (index position)
        //NOT WORKING !!!
        $compare = substr_compare($xml, '_index', -6, 6);
        if ($compare == 0 && $k > -1) {
            return $k + 1;
        }


        $levels = explode('.', $xml);

        //if Upwards xml // notation detected
        $nlev = count($levels);
        $up = strspn($levels[$nlev - 1], "<");
        if ($up > 0) {
            $levs = Array();
            do {

                $levs[] = $levels[$up];
                $up++;
            } while ($up < $nlev);

            $levels = str_replace('<', '', $levs);
            $nlev = count($levels);
        }


        foreach ($levels as $i => $level) {
            $r = $r->$level;
            if ($i == $nlev - 2 && $k > -1) {    //penultimo of levels and iterable ($k)
                //Fotografia
                $iterator = 0; // shoul be better use the children() simplexml method
                foreach ($r as $ri => $rval) {

                    if ($iterator == $k) {
                        $r = $rval;
                    }$iterator++;
                }
            }

            if (!$r) {
                return null;
            }
        }


        $v = $r->__toString(); //value of other field
        if (!$v) {
            $v = $r->attributes()->id;
        }
        $v = str_replace('-', '', $v);

        if (ctype_digit($v)) {
            $v = intval($v);
        }
        return $v;
    }

    private function setter($fname, $v, $object) {
        $setter = 'set' . ucfirst($fname);
        $object->$setter($v);
    }

}
