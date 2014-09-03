<?php

class Zip_TestCase extends DokuWikiTest {

    /**
     * simple test that checks that the given filenames and contents can be grepped from
     * the uncompressed zip stream
     *
     * No check for format correctness
     */
    public function test_createdynamic() {
        $zip = new Zip();

        $dir  = dirname(__FILE__).'/zip';
        $tdir = ltrim($dir, '/');

        $zip->create();
        $zip->AddFile("$dir/testdata1.txt", "$dir/testdata1.txt", 0);
        $zip->AddFile("$dir/foobar/testdata2.txt", 'noway/testdata2.txt', 0);
        $zip->addData('another/testdata3.txt', 'testcontent3', 0, 0);

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
    public function test_createfile() {
        $zip = new Zip();

        $dir  = dirname(__FILE__).'/zip';
        $tdir = ltrim($dir, '/');
        $tmp  = tempnam(sys_get_temp_dir(), 'dwziptest');

        $zip->create($tmp);
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
    public function test_zipcontent() {
        $dir = dirname(__FILE__).'/zip';

        $zip  = new Zip();
        $file = "$dir/test.zip";

        $zip->open($file);
        $content = $zip->contents();

        $this->assertCount(4, $content, "Contents of $file");
        $this->assertEquals('zip/testdata1.txt', $content[1]['filename'], "Contents of $file");
        $this->assertEquals(13, $content[1]['size'], "Contents of $file");

        $this->assertEquals('zip/foobar/testdata2.txt', $content[3]['filename'], "Contents of $file");
        $this->assertEquals(13, $content[1]['size'], "Contents of $file");
    }

    /**
     * Extract the prebuilt zip files
     */
    public function test_zipextract() {
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

        //TestUtils::rdelete($out);
    }

    /**
     * Extract the prebuilt zip files with component stripping
     */
    public function test_compstripextract() {
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

        TestUtils::rdelete($out);
    }

    /**
     * Extract the prebuilt zip files with prefix stripping
     */
    public function test_prefixstripextract() {
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

        TestUtils::rdelete($out);

    }

    /**
     * Extract the prebuilt zip files with include regex
     */
    public function test_includeextract() {
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

        TestUtils::rdelete($out);
    }

    /**
     * Extract the prebuilt zip files with exclude regex
     */
    public function test_excludeextract() {
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

        TestUtils::rdelete($out);

    }

    /**
     * @depends test_ext_zlib
     */
    /*public function test_longpathextract() {
        $dir = dirname(__FILE__).'/zip';
        $out = sys_get_temp_dir().'/dwziptest'.md5(time());

        foreach(array('uszip', 'gnu') as $format) {
            $zip = new Zip();
            $zip->open("$dir/longpath-$format.tgz");
            $zip->extract($out);

            $this->assertFileExists($out.'/1234567890/1234567890/1234567890/1234567890/1234567890/1234567890/1234567890/1234567890/1234567890/1234567890/1234567890/1234567890/test.txt');

            TestUtils::rdelete($out);
        }
    }*/

    // FS#1442
    /*public function test_createlongfile() {
        $zip = new Zip();
        $tmp = tempnam(sys_get_temp_dir(), 'dwziptest');

        $path = '0123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789.txt';

        $zip->create($tmp, Zip::COMPRESS_NONE);
        $zip->addData($path, 'testcontent1');
        $zip->close();

        $this->assertTrue(filesize($tmp) > 30); //arbitrary non-zero number
        $data = file_get_contents($tmp);

        // We should find the complete path and a longlink entry
        $this->assertTrue(strpos($data, 'testcontent1') !== false, 'content in ZIP');
        $this->assertTrue(strpos($data, $path) !== false, 'path in ZIP');
        $this->assertTrue(strpos($data, '@LongLink') !== false, '@LongLink in ZIP');

        @unlink($tmp);
    }*/

    /*public function test_createlongpathuszip() {
        $zip = new Zip();
        $tmp = tempnam(sys_get_temp_dir(), 'dwziptest');

        $path = '';
        for($i=0; $i<11; $i++) $path .= '1234567890/';
        $path = rtrim($path,'/');

        $zip->create($tmp, Zip::COMPRESS_NONE);
        $zip->addData("$path/test.txt", 'testcontent1');
        $zip->close();

        $this->assertTrue(filesize($tmp) > 30); //arbitrary non-zero number
        $data = file_get_contents($tmp);

        // We should find the path and filename separated, no longlink entry
        $this->assertTrue(strpos($data, 'testcontent1') !== false, 'content in ZIP');
        $this->assertTrue(strpos($data, 'test.txt') !== false, 'filename in ZIP');
        $this->assertTrue(strpos($data, $path) !== false, 'path in ZIP');
        $this->assertFalse(strpos($data, "$path/test.txt") !== false, 'full filename in ZIP');
        $this->assertFalse(strpos($data, '@LongLink') !== false, '@LongLink in ZIP');

        @unlink($tmp);
    }*/

    /*public function test_createlongpathgnu() {
        $zip = new Zip();
        $tmp = tempnam(sys_get_temp_dir(), 'dwziptest');

        $path = '';
        for($i=0; $i<20; $i++) $path .= '1234567890/';
        $path = rtrim($path,'/');

        $zip->create($tmp, Zip::COMPRESS_NONE);
        $zip->addData("$path/test.txt", 'testcontent1');
        $zip->close();

        $this->assertTrue(filesize($tmp) > 30); //arbitrary non-zero number
        $data = file_get_contents($tmp);

        // We should find the complete path/filename and a longlink entry
        $this->assertTrue(strpos($data, 'testcontent1') !== false, 'content in ZIP');
        $this->assertTrue(strpos($data, 'test.txt') !== false, 'filename in ZIP');
        $this->assertTrue(strpos($data, $path) !== false, 'path in ZIP');
        $this->assertTrue(strpos($data, "$path/test.txt") !== false, 'full filename in ZIP');
        $this->assertTrue(strpos($data, '@LongLink') !== false, '@LongLink in ZIP');

        @unlink($tmp);
    }*/

    /**
     * Extract a zipbomomb
     * @depends test_ext_zlib
     */
    /*public function test_zipbomb() {
        $dir = dirname(__FILE__).'/zip';
        $out = sys_get_temp_dir().'/dwziptest'.md5(time());

        $zip  = new Zip();

        $zip->open("$dir/zipbomb.tgz");
        $zip->extract($out);

        clearstatcache();

        $this->assertFileExists($out.'/AAAAAAAAAAAAAAAAA/BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB.txt');

        TestUtils::rdelete($out);
    }*/

    /**
     * A single zero file should be just a header block + the footer
     */
    /*public function test_zerofile(){
        $dir = dirname(__FILE__).'/zip';
        $zip = new Zip();
        $zip->create();
        $zip->addFile("$dir/zero.txt", 'zero.txt');
        $file = $zip->getArchive(Zip::COMPRESS_NONE);

        $this->assertEquals(512*3, strlen($file)); // 1 header block + 2 footer blocks
    }*/

    /*public function test_zerodata(){
        $zip = new Zip();
        $zip->create();
        $zip->addData('zero.txt','');
        $file = $zip->getArchive(Zip::COMPRESS_NONE);

        $this->assertEquals(512*3, strlen($file)); // 1 header block + 2 footer blocks
    }*/

    /**
     * A file of exactly one block should be just a header block + data block + the footer
     */
    /*public function test_blockfile(){
        $dir = dirname(__FILE__).'/zip';
        $zip = new Zip();
        $zip->create();
        $zip->addFile("$dir/block.txt", 'block.txt');
        $file = $zip->getArchive(Zip::COMPRESS_NONE);

        $this->assertEquals(512*4, strlen($file)); // 1 header block + data block + 2 footer blocks
    }*/

    /*public function test_blockdata(){
        $zip = new Zip();
        $zip->create();
        $zip->addData('block.txt', str_pad('', 512, 'x'));
        $file = $zip->getArchive(Zip::COMPRESS_NONE);

        $this->assertEquals(512*4, strlen($file)); // 1 header block + data block + 2 footer blocks
    }*/

    public function test_cleanPath() {
        $zip   = new Zip();
        $tests = array(
            '/foo/bar'                => 'foo/bar',
            '/foo/bar/'               => 'foo/bar',
            'foo//bar'                => 'foo/bar',
            'foo/0/bar'               => 'foo/0/bar',
            'foo/../bar'              => 'bar',
            'foo/bang/bang/../../bar' => 'foo/bar',
            'foo/../../bar'           => 'bar',
            'foo/.././../bar'         => 'bar',
            'foo\..\./../bar'         => 'bar'
        );

        foreach($tests as $in => $out) {
            $this->assertEquals($out, $zip->cleanPath($in), "Input: $in");
        }
    }
}
