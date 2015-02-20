<?php

use Archive\FileInfo;

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

}