<?php

namespace D2F;

use PHPUnit\Framework\TestCase;
use vitech\D2F\D2F;
use vitech\D2F\VersionRange;

class TestTest extends TestCase {
    public function testD2F() {
        print_r("\nTest D2F::\n");
        $d = new D2F;
        
        $this->assertInstanceOf(D2F::class,$d);
    }

    public function testVersionRange(){ 
        print_r("\nTest VersionRange::\n");
        $v = new VersionRange(">2.0.0");
        $v->addRange("<1.0.0");
        //$v->addRange("<2.3.1");
        print_r((string)$v);

        print_r("\nTest VersionRange2::\n");
        $v2 = new VersionRange("");
        $v2->addRange("");
        print_r((string)$v2 == "");
        print_r((string)$v2);
        $this->assertInstanceOf(VersionRange::class,$v);
    }
}
