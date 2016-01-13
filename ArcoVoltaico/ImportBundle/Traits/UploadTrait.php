<?php

namespace ArcoVoltaico\ImportBundle\Traits;

use Symfony\Component\HttpFoundation\File\UploadedFile;

trait UploadTrait {

    public $path;
    public $file;

    public function getClassFolder() {
        $fc = __CLASS__;
        $sc = substr(strrchr($fc, '\\'), 1);

        if (substr($sc, -1) != 's') {
            $sc.='s';
        }
        return strtolower($sc);
    }

    public function setFile(UploadedFile $file = null) {
        $this->file = $file;
        // check if we have an old image path
        if (isset($this->path)) {
            // store the old name to delete after the update
            $this->temp = $this->path;
            $this->path = null;
        } else {
            $this->path = 'initial';
        }
    }

    public function getFile() {
        return $this->file;
    }

    function getPath() {
        return $this->path;
    }

    function setPath($path) {
        $this->path = $path;
    }

    public function getAbsolutePath() {
        return null === $this->path ? null : $this->getUploadRootDir() . '/' . $this->path;
    }

    public function getWebPath() {
        return null === $this->path ? null : $this->getUploadDir() . '/' . $this->path;
    }

    public function getUploadRootDir() {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__ . '/../../../../web/' . $this->getUploadDir();
    }

    public function getUploadDir() {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        return 'uploads/' . $this->getClassFolder();
    }

    public function preUpload() {
        if (null !== $this->getFile()) {
            // do whatever you want to generate a unique name
            $filename = sha1(uniqid(mt_rand(), true));
            $this->path = $filename . '.' . $this->getFile()->guessExtension();
        }
    }

    
        public function preUpdate() {
//        if (null !== $this->getFile()) {
            // do whatever you want to generate a unique name
           // $filename = sha1(uniqid(mt_rand(), true));
         //   $this->path = $filename . '.' . $this->getFile()->guessExtension();
       // }
    }
    
    
    
    public function upload() {
        if (null === $this->getFile()) {
            return;
        }

        // if there is an error when moving the file, an exception will
        // be automatically thrown by move(). This will properly prevent
        // the entity from being persisted to the database on error



        $this->getFile()->move($this->getUploadRootDir(), $this->path);

        // START RESIZE ADAPTAR!!! //

        /*
          $path = $document->getWebPath();                                // domain relative path to full sized image
          $tpath = $document->getRootDir().$document->getThumbPath();     // absolute path of saved thumbnail

          $container = $this->container;                                  // the DI container
          $dataManager = $container->get('liip_imagine.data.manager');    // the data manager service
          $filterManager = $container->get('liip_imagine.filter.manager');// the filter manager service

          $image = $dataManager->find($filter, $path);                    // find the image and determine its type
          $response = $filterManager->get($this->getRequest(), $filter, $image, $path); // run the filter
          $thumb = $response->getContent();                               // get the image from the response

          $f = fopen($tpath, 'w');                                        // create thumbnail file
          fwrite($f, $thumb);                                             // write the thumbnail
          fclose($f);

          // close the file
         */

        //FIN MOVER Y RESIZE//
        // check if we have an old image
        if (isset($this->temp)) {
            // delete the old image
            //    unlink($this->getUploadRootDir() . '/' . $this->temp);  // JORDI, no va con multiupload!!!
            // clear the temp image path
            $this->temp = null;
        }
        $this->file = null;
    }

    public function removeUpload() {
        if ($file = $this->getAbsolutePath()) {
            unlink($file);
        }
    }

}
