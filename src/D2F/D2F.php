<?php

namespace D2F;

final class D2F {

    public $dir;
    public $libs = [];
    public function  __construct(){
        $this->dir = dirname(__FILE__).DIRECTORY_SEPARATOR."Library";

        foreach(glob($this->dir.DIRECTORY_SEPARATOR.'*') as $file) {
           $this->readLibrary($file);
        }
    }

    private function readLibrary($file) {
        $json = json_decode(file_get_contents($file),true);
        if ($json != null) {
            $this->libs[] = $json;
        }
    }

    public function analyze($dir) {
        
    }

    private function checkDir($dir) {
        return true;
    }
}
