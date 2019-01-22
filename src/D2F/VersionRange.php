<?php

namespace D2F;

use vierbergenlars\SemVer\expression;

class VersionRange {

    protected $ee = "";
    protected $exps = [];

    public function  __construct($range){
        $this->addRange($range);
    }

    public function addRange($range) {
        
        if (\in_array($range,$this->exps)) return true;
        $this->exps[] = $range;
        $exp = new expression(\implode(" || ",$this->exps));
        if ($exp->validRange() == null) return false;
        $this->ee = $exp->validRange();
        return true;
    }

    public function getRange() {
        return $this->ee;
    }

    public function __toString() {
        return $this->ee;
    }

}
