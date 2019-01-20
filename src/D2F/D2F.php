<?php

namespace D2F;

final class D2F {

    public $dir;

    public function  __construct(){
        $this->dir = dirname(__FILE__).DIRECTORY_SEPARATOR."Library";

        foreach(glob($this->dir.DIRECTORY_SEPARATOR.'*') as $file) {
           $this->readLibrary($file);
        }
    }

    public function readLibrary($json) {
        print_r(json_decode(file_get_contents($json),true));
    }


    public function print() {
        print_r(scandir($this->dir));
    }
}
