<?php

namespace vitech\D2F;

use vitech\D2F\Exception\InvalidDirException;

define("DEFAULT_POWER",0.5);
define("MINUMUM_MATCH",0.7);

class D2F {

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
     * @throws InvalidDirException
     * @return mixed
     */
    public function analyze($dir,$deep = true,$simple = false) {

        //First layer match
        $firstLayer = $this->layerSet($dir);

        if (count($firstLayer) == 0) 
            throw new InvalidDirException("Input dir structure cannot be an empty array");

        // $rec = [];
        // foreach($this->libs as $framework) {
        //     if ($this->compareLayer($firstLayer,$key -> $framework) >= MINUMUM_MATCH) $rec[] += $key;
        // }
        // if (count($rec) == 0) return []; //空
        
        $ans = [];
        if ($deep) {
            //TODO: Deep Search
            foreach($this->libs as $framework) {
                $ans[] = [
                    "name" => $framework["name"],
                    "result" => $this->deepCompare($dir,$framework["dir"])
                ];
            }
        } else {
            //TODO: Simple Search
        }
        
        return $ans;

    }

    protected function deepCompare($dir,$lib) {
        $ans = [
            "tag" => [],
            "version" => "",
        ];
        // dir 和 lib都是count不为0的数组

        $sum = 0;
        $match = 0;
        foreach ($lib as $libVal) {
            $sum ++;
            $pow = DEFAULT_POWER;
            $libHasChildren = false;
            $libIsArray = is_array($libVal);
            $libTag = [];
            $libVersion = "";

            if ($libIsArray) {
                $pow = array_key_exists("power",$libVal) ? $libVal["power"] : DEFAULT_POWER;
                $name = $libVal["name"];
                $libHasChildren = array_key_exists("children",$libVal);
                $libTag = array_key_exists("tag",$libVal) ? $libVal["tag"] : [];
                $libVersion = array_key_exists("version",$libVal) ? $libVal["version"] : "";
            } else {
                $name = $libVal;
            }
            $tempMatch = 1/($pow + 0.001);
            foreach($dir as $dirVal) {
                if (is_array($dirVal) && validDirArray($dirVal)) {
                    if ($dirVal["name"] == $name) {
                        $tm = $tempMatch;
                        
                        if (array_key_exists("children",$dirVal) && $libHasChildren && !empty($dirVal["children"])) {
                            $deepAns = $this->deepCompare($dirVal["children"],$libVal["children"]);
                            if ($deepAns["match"] > 0) {
                                $ans = $this->mergeAns($ans,$deepAns);
                                $tm *= $deepAns["match"];
                            } else break;
                        }
                        $ans = $this->mergeAns($ans,["tag"=>$libTag,"version"=> $libVersion]);
                        $match += $tm;
                        break;
                    } 
                } else if (is_string($dirVal) && $this->validDirString($dirVal)) {
                    if ($dirVal == $name) {
                        $ans = $this->mergeAns($ans,["tag"=>$libTag,"version"=> $libVersion]);
                        $match += $tempMatch;
                        break;
                    }
                } else {
                    throw new InvalidDirException("Input dir structure contains invalid type");
                }
            }
            
            //Power Bigger Than 1 with no match would stop searching
            if ($match == 0 && $pow >= 1) return ["match" => 0];
        }
        $ans["match"] = $match / $sum;
        
        return $ans;
    }

    protected function mergeAns($ans1,$ans2) {
        if (empty($ans1)) return $ans2;
        if (empty($ans2)) return [];

        $tag = \array_merge(array_key_exists("tag",$ans1) ? $ans1["tag"] : [],array_key_exists("tag",$ans2) ? $ans2["tag"] : []);
        if (array_key_exists("version",$ans1)) {
            $version = new VersionRange($ans1["version"]);
            if (array_key_exists("version",$ans2)) $version->addRange($ans2["version"]);
        } else if (array_key_exists("version",$ans2)) $version = new VersionRange($ans2["version"]);
        else $version = new VersionRange("");
        return [
            "tag" => $tag,
            "version" => $version->getRange(),
        ];
    }

    /**
     * Compare one layer to one framework json
     *
     * @param array $layer
     * @param array $framework
     * @return double
     */
    protected function compareLayer($layer,$framework) {

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
    protected function readLibrary($file) {
        $json = json_decode(file_get_contents($file),true);
        if ($json != null) {
            $this->libs[] = $json;
        }
    }

    /**
     * Export one layer of input dir
     *
     * @param array $layer
     * @return array
     */
    protected function layerSet($layer) {
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
    protected function validDirString($str) {
        $ss = trim($str);
        if (strlen($ss) == 0 || $ss == '/' || $ss == '//' || $ss == "/ /")
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
    protected function validDirArray($arr) {
        if (!array_key_exists("name",$arr) || !is_string($arr["name"]))
            throw new InvalidDirException("Input dir structure contains invalid file/dir array");

        if (array_key_exists("children",$arr) && $arr["name"][0] != '/')
            throw new InvalidDirException("Input dir structure contains invalid file/dir array: Dir structure name does not start with '/'");

        // if (array_key_exists("tag",$arr) && array_key_exists("power",$arr))
        //     throw new InvalidDirException("Input dir structure contains invalid file/dir array: Dir structure can't countain both 'tag' and 'power'");

        $this->validDirString($arr["name"]);

        return true;
    }
}
