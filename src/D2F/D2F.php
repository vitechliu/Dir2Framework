<?php

namespace D2F;

use D2F\Exception\InvalidDirException;

define("DEFAULT_POWER",0.5);
define("MINUMUM_MATCH",0.7);

final class D2F {

    /**
     * Dir URL
     *
     * @var string
     */
    public $dir;

    /**
     * Framework library array
     *
     * @var array
     */
    public $libs = [];


    public function  __construct(){
        $this->dir = dirname(__FILE__).DIRECTORY_SEPARATOR."Library";

        foreach(glob($this->dir.DIRECTORY_SEPARATOR.'*') as $file) {
           $this->readLibrary($file);
        }
        
        print_r($this->layerSet($this->libs[0]["dir"],true));
    }

    /**
     * Main function for judging the framework based on input dir structure
     *
     * @param array $dir Input dir
     * @param boolean $deep Use deep searching
     * @param boolean $simple Whether the output is simple string
     * @return mixed
     */
    public function analyze($dir,$deep = true,$simple = false) {
        //First layer match
        $firstLayer = $this->layerSet($dir);
        $rec = [];
        foreach($this->libs as $framework) {
            if ($this->compareLayer($firstLayer,$key -> $framework) >= MINUMUM_MATCH) $rec[] += $key;
        }
        if (count($rec) == 0) return []; //空
        
        if (!$deep) {
            //TODO: Simple Search
        } else {
            //TODO: Deep Search
        }
        return [];

    }

    /**
     * Compare one layer to one framework json
     *
     * @param array $layer
     * @param array $framework
     * @return double
     */
    private function compareLayer($layer,$framework) {

        $match = 0;
        $sum = 0;
        foreach($framework["dir"] as $val) {
            $sum ++;
            $pow = DEFAULT_POWER;
            if (is_array($val)) {
                $pow = array_key_exists("power",$val) ? $val["power"] : DEFAULT_POWER;
                $name = $val["name"];
            } else {
                $name = $val;
            }
            if (in_array($name,$layer)) {
                $match += 0.5/$pow;
            } else {
                if ($pow == 1) return 0;
            }
            
        }
        return $match / $sum;
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

    /**
     * Export one layer of input dir
     *
     * @param mixed $layer
     * @return array
     */
    private function layerSet($layer) {
        if (!is_array($layer)) 
            throw new InvalidDirException("Input dir structure is not an array");

        $ans = [];
        foreach ($layer as $val) {
            if (is_string($val) && $this->validDirString($val)) {
                $ans[] = trim($val);
            } else if (is_array($val) && $this->validDirArray($val)) {
                $ans[] = trim($val["name"]);
            }
        }   
        return $ans;
    }

    /**
     * Check whether file/dir name is valid
     *
     * @param string $str
     * @return boolean
     * @throws InvalidDirException
     */
    private function validDirString($str) {
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
    private function validDirArray($arr) {
        if (!array_key_exists("name",$arr) || !is_string($arr["name"]))
            throw new InvalidDirException("Input dir structure contains invalid file/dir array");

        if (array_ley_exists("children",$arr) && $arr["name"][0] != '/')
            throw new InvalidDirException("Input dir structure contains invalid file/dir array: Dir structure name does not start with '/'");

        $this->validDirString($arr["name"]);

        return true;
    }
}
