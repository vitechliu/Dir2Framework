<?php

namespace D2F;

use D2F\Exception\InvalidDirException;

final class D2F {

    public $dir;
    public $libs = [];

    public function  __construct(){
        $this->dir = dirname(__FILE__).DIRECTORY_SEPARATOR."Library";

        foreach(glob($this->dir.DIRECTORY_SEPARATOR.'*') as $file) {
           $this->readLibrary($file);
        }
        
        print_r($this->layerSet($this->libs[0]["dir"],true));
    }

    /**
     * Read Json Library
     *
     * @param string $file
     * @return void
     */
    private function readLibrary($file) {
        $json = json_decode(file_get_contents($file),true);
        if ($json != null) {
            $this->libs[] = $json;
        }
    }

    public function analyze($dir) {
        
    }

    /**
     * Undocumented function
     *
     * @param mixed $layer
     * @param boolean $lib
     * @return array
     */
    public function layerSet($layer,$lib = false) {
        if (!is_array($layer)) 
            throw new InvalidDirException("Input dir structure is not an array");

        $ans = [];
        foreach ($layer as $val) {
            if (is_string($val) && ($lib || $this->validDirString($val))) {
                $ans[] = trim($val);
            } else if (is_array($val) && ($lib || $this->validDirArray($val))) {
                $prefix = "";
                if($val["type"] == "dir") $prefix = "/";
                else if ($val["type"] == "symlink") $prefix = "//";
                $ans[] = $prefix.trim($val["name"]);
            }
        }   
        //print_r($ans);
        return $ans;
    }

    /**
     * Check whether file/dir name is valid
     *
     * @param string $str
     * @return boolean
     * @throws InvalidDirException
     */
    function validDirString($str) {
        if (strlen(trim($str)) == 0)
            throw new InvalidDirException("Input dir structure contains invalid file/dir");

        return true;
    }

    /**
     * Check whether file/dir array is valid
     *
     * @param array $arr
     * @return boolean
     * @throws InvalidDirException
     */
    function validDirArray($arr) {
        if (!array_key_exists("name",$arr) || !array_key_exists("type",$arr))
            throw new InvalidDirException("Input dir structure contains invalid file/dir array");

        if (!is_string($arr["name"]) || !is_string($arr["type"]))
            throw new InvalidDirException("Input dir structure contains invalid file/dir array");

        $this->validDirString($arr["name"]);
        $this->validDirString($arr["type"]);

        if(!in_array($arr["type"],["file","dir","symlink"]))
            throw new InvalidDirException("Input dir structure contains invalid 'type'");

        return true;
    }
}
