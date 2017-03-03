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
        $zip->addFile("$dir/testdata1.txt", "$dir/testdata1.txt");
        $zip->addFile("$dir/foobar/testdata2.txt", 'noway/testdata2.txt');
        $zip->addData('another/testdata3.txt', 'testcontent3');
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

        $this->nativeCheck($tmp);
        $this->native7ZipCheck($tmp);

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
     * Create an archive and unpack it again
     */
    public function test_dogfood()
    {

        $input = glob(dirname(__FILE__) . '/../src/*');
        $archive = sys_get_temp_dir() . '/dwziptest' . md5(time()) . '.zip';
        $extract = sys_get_temp_dir() . '/dwziptest' . md5(time() + 1);

        $zip = new Zip();
        $zip->create($archive);
        foreach($input as $path) {
            $file = basename($path);
            $zip->addFile($path, $file);
        }
        $zip->close();
        $this->assertFileExists($archive);

        $zip = new Zip();
        $zip->open($archive);
        $zip->extract($extract, '', '/FileInfo\\.php/', '/.*\\.php/');

        $this->assertFileExists("$extract/Tar.php");
        $this->assertFileExists("$extract/Zip.php");
        $this->assertFileNotExists("$extract/FileInfo.php");

        $this->nativeCheck($archive);
        $this->native7ZipCheck($archive);

        self::rdelete($extract);
        unlink($archive);
    }

    public function test_utf8() {
        $archive = sys_get_temp_dir() . '/dwziptest' . md5(time()) . '.zip';
        $extract = sys_get_temp_dir() . '/dwziptest' . md5(time() + 1);

        $zip = new Zip();
        $zip->create($archive);
        $zip->addData('tüst.txt', 'test');
        $zip->addData('snowy☃.txt', 'test');
        $zip->close();
        $this->assertFileExists($archive);

        $zip = new Zip();
        $zip->open($archive);
        $zip->extract($extract);

        $this->assertFileExists($extract.'/tüst.txt');
        $this->assertFileExists($extract.'/snowy☃.txt');

        $this->nativeCheck($archive);
        $this->native7ZipCheck($archive);

        self::rdelete($extract);
        unlink($archive);
    }


    /**
     * Test the given archive with a native zip installation (if available)
     *
     * @param $archive
     */
    protected function nativeCheck($archive)
    {
        if (!is_executable('/usr/bin/zipinfo')) {
            return;
        }
        $archive = escapeshellarg($archive);

        $return = 0;
        $output = array();
        $ok = exec("/usr/bin/zipinfo $archive 2>&1 >/dev/null", $output, $return);
        $output = join("\n", $output);

        $this->assertNotFalse($ok, "native zip execution for $archive failed:\n$output");
        $this->assertSame(0, $return, "native zip execution for $archive had non-zero exit code $return:\n$output");
        $this->assertSame('', $output, "native zip execution for $archive had non-empty output:\n$output");
    }

    /**
     * Test the given archive with a native 7zip installation (if available)
     *
     * @param $archive
     */
    protected function native7ZipCheck($archive)
    {
        if (!is_executable('/usr/bin/7z')) {
            return;
        }
        $archive = escapeshellarg($archive);

        $return = 0;
        $output = array();
        $ok = exec("/usr/bin/7z t $archive 2>&1 >/dev/null", $output, $return);
        $output = join("\n", $output);

        $this->assertNotFalse($ok, "native 7zip execution for $archive failed:\n$output");
        $this->assertSame(0, $return, "native 7zip execution for $archive had non-zero exit code $return:\n$output");
        $this->assertSame('', $output, "native 7zip execution for $archive had non-empty output:\n$output");
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

    public function test_umlautWinrar()
    {
        $out = sys_get_temp_dir().'/dwziptest'.md5(time());

        $zip = new Zip();
        $zip->open(__DIR__ . '/zip/issue14-winrar.zip');
        $zip->extract($out);
        $this->assertFileExists("$out/tüst.txt");
    }

    public function test_umlautWindows()
    {
        $out = sys_get_temp_dir().'/dwziptest'.md5(time());

        $zip = new Zip();
        $zip->open(__DIR__ . '/zip/issue14-windows.zip');
        $zip->extract($out);
        $this->assertFileExists("$out/täst.txt");
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
