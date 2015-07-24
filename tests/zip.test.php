<?php

use splitbrain\PHPArchive\Zip;

class Zip_TestCase extends PHPUnit_Framework_TestCase
{

    /**
     * @expectedException splitbrain\PHPArchive\ArchiveIOException
     */
    public function test_missing()
    {
        $tar = new Zip();
        $tar->open('nope.zip');
    }

    /**
     * simple test that checks that the given filenames and contents can be grepped from
     * the uncompressed zip stream
     *
     * No check for format correctness
     */
    public function test_createdynamic()
    {
        $zip = new Zip();

        $dir  = dirname(__FILE__).'/zip';
        $tdir = ltrim($dir, '/');

        $zip->create();
        $zip->setCompression(0);
        $zip->AddFile("$dir/testdata1.txt", "$dir/testdata1.txt");
        $zip->AddFile("$dir/foobar/testdata2.txt", 'noway/testdata2.txt');
        $zip->addData('another/testdata3.txt', 'testcontent3');

        $data = $zip->getArchive();

        $this->assertTrue(strpos($data, 'testcontent1') !== false, 'Content 1 in ZIP');
        $this->assertTrue(strpos($data, 'testcontent2') !== false, 'Content 2 in ZIP');
        $this->assertTrue(strpos($data, 'testcontent3') !== false, 'Content 3 in ZIP');

        // fullpath might be too long to be stored as full path FS#2802
        $this->assertTrue(strpos($data, "$tdir") !== false, 'Path in ZIP');
        $this->assertTrue(strpos($data, "testdata1.txt") !== false, 'File in ZIP');

        $this->assertTrue(strpos($data, 'noway/testdata2.txt') !== false, 'Path in ZIP');
        $this->assertTrue(strpos($data, 'another/testdata3.txt') !== false, 'Path in ZIP');

        // fullpath might be too long to be stored as full path FS#2802
        $this->assertTrue(strpos($data, "$tdir/foobar") === false, 'Path not in ZIP');
        $this->assertTrue(strpos($data, "foobar.txt") === false, 'File not in ZIP');

        $this->assertTrue(strpos($data, "foobar") === false, 'Path not in ZIP');
    }

    /**
     * simple test that checks that the given filenames and contents can be grepped from the
     * uncompressed zip file
     *
     * No check for format correctness
     */
    public function test_createfile()
    {
        $zip = new Zip();

        $dir  = dirname(__FILE__).'/zip';
        $tdir = ltrim($dir, '/');
        $tmp  = tempnam(sys_get_temp_dir(), 'dwziptest');

        $zip->create($tmp);
        $zip->setCompression(0);
        $zip->AddFile("$dir/testdata1.txt", "$dir/testdata1.txt", 0);
        $zip->AddFile("$dir/foobar/testdata2.txt", 'noway/testdata2.txt', 0);
        $zip->addData('another/testdata3.txt', 'testcontent3', 0, 0);
        $zip->close();

        $this->assertTrue(filesize($tmp) > 30); //arbitrary non-zero number
        $data = file_get_contents($tmp);

        $this->assertTrue(strpos($data, 'testcontent1') !== false, 'Content in ZIP');
        $this->assertTrue(strpos($data, 'testcontent2') !== false, 'Content in ZIP');
        $this->assertTrue(strpos($data, 'testcontent3') !== false, 'Content in ZIP');

        // fullpath might be too long to be stored as full path FS#2802
        $this->assertTrue(strpos($data, "$tdir") !== false, "Path in ZIP '$tdir'");
        $this->assertTrue(strpos($data, "testdata1.txt") !== false, 'File in ZIP');

        $this->assertTrue(strpos($data, 'noway/testdata2.txt') !== false, 'Path in ZIP');
        $this->assertTrue(strpos($data, 'another/testdata3.txt') !== false, 'Path in ZIP');

        // fullpath might be too long to be stored as full path FS#2802
        $this->assertTrue(strpos($data, "$tdir/foobar") === false, 'Path not in ZIP');
        $this->assertTrue(strpos($data, "foobar.txt") === false, 'File not in ZIP');

        $this->assertTrue(strpos($data, "foobar") === false, 'Path not in ZIP');

        @unlink($tmp);
    }

    /**
     * List the contents of the prebuilt ZIP file
     */
    public function test_zipcontent()
    {
        $dir = dirname(__FILE__).'/zip';

        $zip  = new Zip();
        $file = "$dir/test.zip";

        $zip->open($file);
        $content = $zip->contents();

        $this->assertCount(5, $content, "Contents of $file");
        $this->assertEquals('zip/testdata1.txt', $content[2]->getPath(), "Contents of $file");
        $this->assertEquals(13, $content[2]->getSize(), "Contents of $file");

        $this->assertEquals('zip/foobar/testdata2.txt', $content[4]->getPath(), "Contents of $file");
        $this->assertEquals(13, $content[4]->getSize(), "Contents of $file");
    }

    /**
     * Extract the prebuilt zip files
     */
    public function test_zipextract()
    {
        $dir = dirname(__FILE__).'/zip';
        $out = sys_get_temp_dir().'/dwziptest'.md5(time());

        $zip  = new Zip();
        $file = "$dir/test.zip";

        $zip->open($file);
        $zip->extract($out);

        clearstatcache();

        $this->assertFileExists($out.'/zip/testdata1.txt', "Extracted $file");
        $this->assertEquals(13, filesize($out.'/zip/testdata1.txt'), "Extracted $file");

        $this->assertFileExists($out.'/zip/foobar/testdata2.txt', "Extracted $file");
        $this->assertEquals(13, filesize($out.'/zip/foobar/testdata2.txt'), "Extracted $file");

        $this->assertFileExists($out.'/zip/compressable.txt', "Extracted $file");
        $this->assertEquals(1836, filesize($out.'/zip/compressable.txt'), "Extracted $file");
        $this->assertFileNotExists($out.'/zip/compressable.txt.gz', "Extracted $file");

        self::rdelete($out);
    }

    /**
     * Extract the prebuilt zip files with component stripping
     */
    public function test_compstripextract()
    {
        $dir = dirname(__FILE__).'/zip';
        $out = sys_get_temp_dir().'/dwziptest'.md5(time());

        $zip  = new Zip();
        $file = "$dir/test.zip";

        $zip->open($file);
        $zip->extract($out, 1);

        clearstatcache();

        $this->assertFileExists($out.'/testdata1.txt', "Extracted $file");
        $this->assertEquals(13, filesize($out.'/testdata1.txt'), "Extracted $file");

        $this->assertFileExists($out.'/foobar/testdata2.txt', "Extracted $file");
        $this->assertEquals(13, filesize($out.'/foobar/testdata2.txt'), "Extracted $file");

        self::rdelete($out);
    }

    /**
     * Extract the prebuilt zip files with prefix stripping
     */
    public function test_prefixstripextract()
    {
        $dir = dirname(__FILE__).'/zip';
        $out = sys_get_temp_dir().'/dwziptest'.md5(time());

        $zip  = new Zip();
        $file = "$dir/test.zip";

        $zip->open($file);
        $zip->extract($out, 'zip/foobar/');

        clearstatcache();

        $this->assertFileExists($out.'/zip/testdata1.txt', "Extracted $file");
        $this->assertEquals(13, filesize($out.'/zip/testdata1.txt'), "Extracted $file");

        $this->assertFileExists($out.'/testdata2.txt', "Extracted $file");
        $this->assertEquals(13, filesize($out.'/testdata2.txt'), "Extracted $file");

        self::rdelete($out);
    }

    /**
     * Extract the prebuilt zip files with include regex
     */
    public function test_includeextract()
    {
        $dir = dirname(__FILE__).'/zip';
        $out = sys_get_temp_dir().'/dwziptest'.md5(time());

        $zip  = new Zip();
        $file = "$dir/test.zip";

        $zip->open($file);
        $zip->extract($out, '', '', '/\/foobar\//');

        clearstatcache();

        $this->assertFileNotExists($out.'/zip/testdata1.txt', "Extracted $file");

        $this->assertFileExists($out.'/zip/foobar/testdata2.txt', "Extracted $file");
        $this->assertEquals(13, filesize($out.'/zip/foobar/testdata2.txt'), "Extracted $file");

        self::rdelete($out);
    }

    /**
     * Extract the prebuilt zip files with exclude regex
     */
    public function test_excludeextract()
    {
        $dir = dirname(__FILE__).'/zip';
        $out = sys_get_temp_dir().'/dwziptest'.md5(time());

        $zip  = new Zip();
        $file = "$dir/test.zip";

        $zip->open($file);
        $zip->extract($out, '', '/\/foobar\//');

        clearstatcache();

        $this->assertFileExists($out.'/zip/testdata1.txt', "Extracted $file");
        $this->assertEquals(13, filesize($out.'/zip/testdata1.txt'), "Extracted $file");

        $this->assertFileNotExists($out.'/zip/foobar/testdata2.txt', "Extracted $file");

        self::rdelete($out);
    }

    /**
     * recursive rmdir()/unlink()
     *
     * @static
     * @param $target string
     */
    public static function rdelete($target)
    {
        if (!is_dir($target)) {
            unlink($target);
        } else {
            $dh = dir($target);
            while (false !== ($entry = $dh->read())) {
                if ($entry == '.' || $entry == '..') {
                    continue;
                }
                self::rdelete("$target/$entry");
            }
            $dh->close();
            rmdir($target);
        }
    }

}
