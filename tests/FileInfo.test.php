<?php

use splitbrain\PHPArchive\FileInfo;

class FileInfoTest extends PHPUnit_Framework_TestCase
{

    public function testDefaults()
    {
        $fileinfo = new FileInfo('foobar');

        $this->assertEquals('foobar', $fileinfo->getPath());
        $this->assertTrue($fileinfo->getMtime() > time() - 30);
        $this->assertFalse($fileinfo->getIsdir());
        $this->assertEquals(0, $fileinfo->getSize());
        $this->assertEquals(0, $fileinfo->getCompressedSize());
        $this->assertEquals(0664, $fileinfo->getMode());
        $this->assertEquals(0, $fileinfo->getGid());
        $this->assertEquals(0, $fileinfo->getUid());
        $this->assertEquals('', $fileinfo->getOwner());
        $this->assertEquals('', $fileinfo->getGroup());
        $this->assertEquals('', $fileinfo->getComment());
    }

    public function testClean()
    {
        $data = array(
            array('foo', 'foo'),
            array('/foo/', 'foo'),
            array('/foo/../bar', 'bar'),
            array('/foo/../../bar', 'bar'),
            array('/foo/../baz/../bar', 'bar'),
            array('/foo/baz/../bar', 'foo/bar'),
            array('\\foo/baz\\../bar', 'foo/bar'),
            array('/foo/bar', 'foo/bar'),
            array('/foo/bar/', 'foo/bar'),
            array('foo//bar', 'foo/bar'),
            array('foo/0/bar', 'foo/0/bar'),
            array('foo/../bar', 'bar'),
            array('foo/bang/bang/../../bar', 'foo/bar'),
            array('foo/../../bar', 'bar'),
            array('foo/.././../bar', 'bar'),

        );

        $fileinfo = new FileInfo();
        foreach ($data as $test) {
            $fileinfo->setPath($test[0]);
            $this->assertEquals($test[1], $fileinfo->getPath());
        }
    }

    public function testStrip()
    {
        $fileinfo = new FileInfo('foo/bar/baz/bang');
        $this->assertEquals('foo/bar/baz/bang', $fileinfo->getPath());

        $fileinfo->strip(1);
        $this->assertEquals('bar/baz/bang', $fileinfo->getPath());

        $fileinfo->strip(2);
        $this->assertEquals('bang', $fileinfo->getPath());

        $fileinfo = new FileInfo('foo/bar/baz/bang');
        $fileinfo->strip('nomatch');
        $this->assertEquals('foo/bar/baz/bang', $fileinfo->getPath());

        $fileinfo->strip('foo/bar');
        $this->assertEquals('baz/bang', $fileinfo->getPath());
    }

    public function testMatch()
    {
        $fileinfo = new FileInfo('foo/bar/baz/bang');

        $this->assertTrue($fileinfo->match());
        $this->assertTrue($fileinfo->match('/bang/'));
        $this->assertFalse($fileinfo->match('/bark/'));

        $this->assertFalse($fileinfo->match('', '/bang/'));
        $this->assertTrue($fileinfo->match('', '/bark/'));

        $this->assertFalse($fileinfo->match('/bang/', '/foo/'));
        $this->assertTrue($fileinfo->match('/bang/', '/bark/'));
    }
}