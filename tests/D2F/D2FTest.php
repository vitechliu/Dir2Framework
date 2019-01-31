<?php

namespace D2F;

use PHPUnit\Framework\TestCase;
use vitech\D2F\D2F;

class D2FTest extends TestCase {
    public function testD2F() {
        print_r("\nTest D2F::\n");
        $d = new D2F;
        $this->assertInstanceOf(D2F::class,$d);
    }

    public function testDeepSearchLaravel57() {
        $d = new D2F;
        $testdir = [
            "/.git","/app","/bootstrap","/config","/routes","/public","resources",
            "/database","/node_modules","/storage","/tests","/vendor",".editorconfig",
            ".env",".env.example","gitattributes",".gitignore","artisan","composer.json",
            "composer.lock","package.json","package-lock.json","phpunit.xml","readme.md",
            "server.php","webpack.mix.js"
        ];
        $res = $d->analyze($testdir,true,true,false);
        print_r($res[0]);

        $this->assertEquals($res[0],"laravel");
    }

    public function testDeepSearchLaravel51() {
        $d = new D2F;
        $testdir = [
            "/app","/bootstrap","/config","/public","/resources",
            "/storage","/tests",".gitignore","artisan","composer.json",
            "composer.lock","package.json","README.md","server.php",
        ];
        $res = $d->analyze($testdir,true,true,false);
        print_r($res[0]);

        $this->assertEquals($res[0],"laravel");
    }
}
