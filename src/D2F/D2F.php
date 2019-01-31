<?php

namespace vitech\D2F;

use vitech\D2F\Exception\InvalidDirException;
use InvalidArgumentException;

define("DEFAULT_POWER",0.5);
define("MINUMUM_MATCH",1);

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
    protected $libs = [];

    protected $globalLib = [];

    protected $tempTags = [];

    public function  __construct(){
        $this->dir = dirname(__FILE__).DIRECTORY_SEPARATOR."Library";

        foreach(glob($this->dir.DIRECTORY_SEPARATOR.'*') as $file) {
           $this->readLibrary($file);
        }

        $this->globalLib =  json_decode(file_get_contents($this->dir.DIRECTORY_SEPARATOR."global.json"),true);
    }

    /**
     * Main function for judging the framework based on input dir structure
     *
     * @param array $dir Input dir
     * @param boolean $deep Use deep searching
     * @param boolean $simple Whether the output is simple string
     * @param boolean $showAll Show low-match result
     * @throws InvalidDirException
     * @return array
     */
    public function analyze($dir,$deep = true,$simple = false,$showAll = false) {
        
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
        $first = true;
        if ($deep) {

            foreach($this->libs as $framework) {
                $fd = $framework["dir"];    
                $res = $this->deepCompare($dir,$fd);
                $minMatch = $showAll ? 0 : MINUMUM_MATCH;
                if ($res["match"] > $minMatch)
                    $ans[] = [
                        "name" => $framework["name"],
                        "result" => $this->deepCompare($dir,$framework["dir"])
                    ];
            }
        } else {
            //TODO: Simple Search
        }

        if (empty($ans)) {
            return [];
        }

        uasort($ans,[$this,"matchSort"]);
        
        if ($simple) {
            $ansSimple = [];
            foreach($ans as $val) {
                $ansSimple[] = $val["name"];
            }
            return $ansSimple;
        } 

        $this->tempTags = $this->compareGlobal($firstLayer);

        return array_map([$this,"formatResult"],$ans);

    }

    /**
     * Scan dir structure with directory
     *
     * @param string $dir Directory string
     * @param integer $depth Positive, the scanning depth
     * @throws InvalidArgumentException
     * @return array
     */
    public function readDir($dir,$depth = 0){
        if ($depth < 0) throw new InvalidArgumentException("Input dir depth must be positive");

        if (!is_string($dir)) throw new InvalidArgumentException("Input dir must be a string");
        
        clearstatcache();
        return $this->scan($dir,$depth);
    }
    /**
     * Judging the framework based on input directory
     *
     * @param string $dir Directory string
     * @param integer $depth Positive, the scanning depth
     * @param boolean $deep Use deep searching
     * @param boolean $simple Whether the output is simple string
     * @param boolean $showAll Show low-match result
     * @throws InvalidDirException
     * @throws InvalidArgumentException
     * @return array
     */
    public function analyzeDir($dir,$depth = 0,$deep = true,$simple = false,$showAll = false) {
        return $this->analyze($this->readDir($dir,$depth),$deep,$simple,$showAll);
    }

    /**
     * Dir match sorting
     *
     * @param array $a
     * @param array $b
     * @return integer
     */
    protected function matchSort($a,$b) {
        $aa = $a["result"]["match"];
        $bb = $b["result"]["match"];
        if ($aa == $bb) return 0;
        return ($aa > $bb) ? -1 : 1;
    }


    protected function formatResult($ans) {
        $ansC= $ans;
        if (strpos($ansC["name"],"#")) {
            $dd = explode("#",$ansC["name"]);
            $ansC["name"] = $dd[0];
            $ansC["subname"] = $dd[1];
        } else {
            $ansC["subname"] = "";
        }
        if (!empty($this->tempTags)) $ansC["result"]["tag"] = array_unique(array_merge($ansC["result"]["tag"],$this->tempTags));

        return $ansC;
    }

    /**
     * Deep Compare Dir with Lib
     *
     * @param array $dir Input dir array
     * @param array $lib Library dir array
     * @throws InvalidDirException
     * @return array
     */
    protected function deepCompare($dir,$lib) {
        $ans = [
            "tag" => [],
            "version" => "",
        ];

        $sum = 0;
        $match = 0;
        foreach ($lib as $libVal) {
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

            //Count tagged dirs and dirs with version as 0.5 power with no sum
            if (empty($libTag) && $libVersion == "") $sum ++;

            //Global Tag
            if ($libIsArray && array_key_exists("global",$libVal)) $tempMatch = 0;
            else $tempMatch = 1/($pow + 0.001);

            foreach($dir as $dirVal) {
                if (is_array($dirVal) && $this->validDirArray($dirVal)) {
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
            if ($match == 0 && $pow >= 1) return ["tag"=>[],"version"=>"","match" => 0];
        }
        $ans["match"] = $match / $sum;
        
        return $ans;
    }

    /**
     * Merge result array
     *
     * @param array $ans1
     * @param array $ans2
     * @return array
     */
    protected function mergeAns($ans1,$ans2) {
        if (empty($ans1)) return $ans2;
        if (empty($ans2)) return [];

        $tag =  array_unique(array_merge(array_key_exists("tag",$ans1) ? $ans1["tag"] : [],array_key_exists("tag",$ans2) ? $ans2["tag"] : []));
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
     * Compare main layer with global tags
     *
     * @param array $layer
     * @return array
     */
    protected function compareGlobal($layer) {
        $tags = [];
        foreach($this->globalLib as $val) {
            if (in_array($val["name"],$layer)) {
                $tags = array_merge($val["tag"],$tags);
            } 
        }
        array_unique($tags);
        return $tags;
    }

    /**
     * Read Json Library
     *
     * @param string $file
     * @return void
     */
    protected function readLibrary($file) {
        if (strpos($file,"global.json")!=false) return;
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

    /**
     * Scan dir
     *
     * @param string $dir
     * @param integer $restDepth
     * @return array
     */
    protected function scan($dir,$restDepth) {
        $m = scandir($dir);
        if (!$m || count($m) < 3) return [];
        if ($m[0] == '.') array_shift($m);
        if ($m[0] == '..') array_shift($m);

        $ans = [];
        foreach($m as $d) {
            $child = $dir.DIRECTORY_SEPARATOR.$d;
            if (is_dir($child)) {
                if ($restDepth > 0) {
                    $ans[] = [
                        "name"=>"/".$d,
                        "children"=> $this->scan($child,$restDepth-1)
                    ];    
                } else {
                    $ans[] = "/".$d;
                }
            } else if (is_link($child)) {
                $ans[] = "//".$d;
            }
            else{
                $ans[] = $d;
            }
        }
        return $ans;
    }
}
