<?php

namespace D2F;

use PHPUnit\Framework\TestCase;
use vitech\D2F\D2F;

class D2FTest extends TestCase {
    public function testReadDirVueCli() {
        $d = new D2F;
        print_r($d->analyzeDir("D:\\xampp\\htdocs\\test\\test1",1));
        $this->assertInstanceOf(D2F::class,$d);
    }

    public function testReadDirLaravel54() {
        print_r("\nTest ReadDir::\n");
        $d = new D2F;

        print_r($d->analyzeDir("D:\\xampp\\htdocs\\vcp",1));
        $this->assertInstanceOf(D2F::class,$d);
    }

}
