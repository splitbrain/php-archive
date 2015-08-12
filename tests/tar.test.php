<?php

use splitbrain\PHPArchive\Tar;

class Tar_TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * file extensions that several tests use
     */
    protected $extensions = array('tar');

    public function setUp()
    {
        parent::setUp();
        if (extension_loaded('zlib')) {
            $this->extensions[] = 'tgz';
            $this->extensions[] = 'tar.gz';
        }
        if (extension_loaded('bz2')) {
            $this->extensions[] = 'tbz';
            $this->extensions[] = 'tar.bz2';
        }
    }

    /*
     * dependency for tests needing zlib extension to pass
     */
    public function test_ext_zlib()
    {
        if (!extension_loaded('zlib')) {
            $this->markTestSkipped('skipping all zlib tests.  Need zlib extension');
        }
    }

    /*
     * dependency for tests needing zlib extension to pass
     */
    public function test_ext_bz2()
    {
        if (!extension_loaded('bz2')) {
            $this->markTestSkipped('skipping all bzip2 tests.  Need bz2 extension');
        }
    }

    /**
     * @expectedException splitbrain\PHPArchive\ArchiveIOException
     */
    public function test_missing()
    {
        $tar = new Tar();
        $tar->open('nope.tar');
    }

    /**
     * simple test that checks that the given filenames and contents can be grepped from
     * the uncompressed tar stream
     *
     * No check for format correctness
     */
    public function test_createdynamic()
    {
        $tar = new Tar();

        $dir  = dirname(__FILE__).'/tar';
        $tdir = ltrim($dir, '/');

        $tar->create();
        $tar->AddFile("$dir/testdata1.txt");
        $tar->AddFile("$dir/foobar/testdata2.txt", 'noway/testdata2.txt');
        $tar->addData('another/testdata3.txt', 'testcontent3');

        $data = $tar->getArchive();

        $this->assertTrue(strpos($data, 'testcontent1') !== false, 'Content in TAR');
        $this->assertTrue(strpos($data, 'testcontent2') !== false, 'Content in TAR');
        $this->assertTrue(strpos($data, 'testcontent3') !== false, 'Content in TAR');

        // fullpath might be too long to be stored as full path FS#2802
        $this->assertTrue(strpos($data, "$tdir") !== false, 'Path in TAR');
        $this->assertTrue(strpos($data, "testdata1.txt") !== false, 'File in TAR');

        $this->assertTrue(strpos($data, 'noway/testdata2.txt') !== false, 'Path in TAR');
        $this->assertTrue(strpos($data, 'another/testdata3.txt') !== false, 'Path in TAR');

        // fullpath might be too long to be stored as full path FS#2802
        $this->assertTrue(strpos($data, "$tdir/foobar") === false, 'Path not in TAR');
        $this->assertTrue(strpos($data, "foobar.txt") === false, 'File not in TAR');

        $this->assertTrue(strpos($data, "foobar") === false, 'Path not in TAR');
    }

    /**
     * simple test that checks that the given filenames and contents can be grepped from the
     * uncompressed tar file
     *
     * No check for format correctness
     */
    public function test_createfile()
    {
        $tar = new Tar();

        $dir  = dirname(__FILE__).'/tar';
        $tdir = ltrim($dir, '/');
        $tmp  = tempnam(sys_get_temp_dir(), 'dwtartest');

        $tar->create($tmp);
        $tar->AddFile("$dir/testdata1.txt");
        $tar->AddFile("$dir/foobar/testdata2.txt", 'noway/testdata2.txt');
        $tar->addData('another/testdata3.txt', 'testcontent3');
        $tar->close();

        $this->assertTrue(filesize($tmp) > 30); //arbitrary non-zero number
        $data = file_get_contents($tmp);

        $this->assertTrue(strpos($data, 'testcontent1') !== false, 'Content in TAR');
        $this->assertTrue(strpos($data, 'testcontent2') !== false, 'Content in TAR');
        $this->assertTrue(strpos($data, 'testcontent3') !== false, 'Content in TAR');

        // fullpath might be too long to be stored as full path FS#2802
        $this->assertTrue(strpos($data, "$tdir") !== false, "Path in TAR '$tdir'");
        $this->assertTrue(strpos($data, "testdata1.txt") !== false, 'File in TAR');

        $this->assertTrue(strpos($data, 'noway/testdata2.txt') !== false, 'Path in TAR');
        $this->assertTrue(strpos($data, 'another/testdata3.txt') !== false, 'Path in TAR');

        // fullpath might be too long to be stored as full path FS#2802
        $this->assertTrue(strpos($data, "$tdir/foobar") === false, 'Path not in TAR');
        $this->assertTrue(strpos($data, "foobar.txt") === false, 'File not in TAR');

        $this->assertTrue(strpos($data, "foobar") === false, 'Path not in TAR');

        @unlink($tmp);
    }

    /**
     * List the contents of the prebuilt TAR files
     */
    public function test_tarcontent()
    {
        $dir = dirname(__FILE__).'/tar';

        foreach ($this->extensions as $ext) {
            $tar  = new Tar();
            $file = "$dir/test.$ext";

            $tar->open($file);
            $content = $tar->contents();

            $this->assertCount(4, $content, "Contents of $file");
            $this->assertEquals('tar/testdata1.txt', $content[1]->getPath(), "Contents of $file");
            $this->assertEquals(13, $content[1]->getSize(), "Contents of $file");

            $this->assertEquals('tar/foobar/testdata2.txt', $content[3]->getPath(), "Contents of $file");
            $this->assertEquals(13, $content[3]->getSize(), "Contents of $file");
        }
    }

    /**
     * Create an archive and unpack it again
     */
    public function test_dogfood()
    {
        foreach ($this->extensions as $ext) {
            $input = glob(dirname(__FILE__) . '/../src/*');
            $archive = sys_get_temp_dir() . '/dwtartest' . md5(time()) . '.' . $ext;
            $extract = sys_get_temp_dir() . '/dwtartest' . md5(time() + 1);

            $tar = new Tar();
            $tar->create($archive);
            foreach($input as $path) {
                $file = basename($path);
                $tar->addFile($path, $file);
            }
            $tar->close();
            $this->assertFileExists($archive);

            $tar = new Tar();
            $tar->open($archive);
            $tar->extract($extract, '', '/FileInfo\\.php/', '/.*\\.php/');

            $this->assertFileExists("$extract/Tar.php");
            $this->assertFileExists("$extract/Zip.php");
            $this->assertFileNotExists("$extract/FileInfo.php");

            self::rdelete($extract);
            unlink($archive);
        }
    }

    /**
     * Extract the prebuilt tar files
     */
    public function test_tarextract()
    {
        $dir = dirname(__FILE__).'/tar';
        $out = sys_get_temp_dir().'/dwtartest'.md5(time());

        foreach ($this->extensions as $ext) {
            $tar  = new Tar();
            $file = "$dir/test.$ext";

            $tar->open($file);
            $tar->extract($out);

            clearstatcache();

            $this->assertFileExists($out.'/tar/testdata1.txt', "Extracted $file");
            $this->assertEquals(13, filesize($out.'/tar/testdata1.txt'), "Extracted $file");

            $this->assertFileExists($out.'/tar/foobar/testdata2.txt', "Extracted $file");
            $this->assertEquals(13, filesize($out.'/tar/foobar/testdata2.txt'), "Extracted $file");

            self::rdelete($out);
        }
    }

    /**
     * Extract the prebuilt tar files with component stripping
     */
    public function test_compstripextract()
    {
        $dir = dirname(__FILE__).'/tar';
        $out = sys_get_temp_dir().'/dwtartest'.md5(time());

        foreach ($this->extensions as $ext) {
            $tar  = new Tar();
            $file = "$dir/test.$ext";

            $tar->open($file);
            $tar->extract($out, 1);

            clearstatcache();

            $this->assertFileExists($out.'/testdata1.txt', "Extracted $file");
            $this->assertEquals(13, filesize($out.'/testdata1.txt'), "Extracted $file");

            $this->assertFileExists($out.'/foobar/testdata2.txt', "Extracted $file");
            $this->assertEquals(13, filesize($out.'/foobar/testdata2.txt'), "Extracted $file");

            self::rdelete($out);
        }
    }

    /**
     * Extract the prebuilt tar files with prefix stripping
     */
    public function test_prefixstripextract()
    {
        $dir = dirname(__FILE__).'/tar';
        $out = sys_get_temp_dir().'/dwtartest'.md5(time());

        foreach ($this->extensions as $ext) {
            $tar  = new Tar();
            $file = "$dir/test.$ext";

            $tar->open($file);
            $tar->extract($out, 'tar/foobar/');

            clearstatcache();

            $this->assertFileExists($out.'/tar/testdata1.txt', "Extracted $file");
            $this->assertEquals(13, filesize($out.'/tar/testdata1.txt'), "Extracted $file");

            $this->assertFileExists($out.'/testdata2.txt', "Extracted $file");
            $this->assertEquals(13, filesize($out.'/testdata2.txt'), "Extracted $file");

            self::rdelete($out);
        }
    }

    /**
     * Extract the prebuilt tar files with include regex
     */
    public function test_includeextract()
    {
        $dir = dirname(__FILE__).'/tar';
        $out = sys_get_temp_dir().'/dwtartest'.md5(time());

        foreach ($this->extensions as $ext) {
            $tar  = new Tar();
            $file = "$dir/test.$ext";

            $tar->open($file);
            $tar->extract($out, '', '', '/\/foobar\//');

            clearstatcache();

            $this->assertFileNotExists($out.'/tar/testdata1.txt', "Extracted $file");

            $this->assertFileExists($out.'/tar/foobar/testdata2.txt', "Extracted $file");
            $this->assertEquals(13, filesize($out.'/tar/foobar/testdata2.txt'), "Extracted $file");

            self::rdelete($out);
        }
    }

    /**
     * Extract the prebuilt tar files with exclude regex
     */
    public function test_excludeextract()
    {
        $dir = dirname(__FILE__).'/tar';
        $out = sys_get_temp_dir().'/dwtartest'.md5(time());

        foreach ($this->extensions as $ext) {
            $tar  = new Tar();
            $file = "$dir/test.$ext";

            $tar->open($file);
            $tar->extract($out, '', '/\/foobar\//');

            clearstatcache();

            $this->assertFileExists($out.'/tar/testdata1.txt', "Extracted $file");
            $this->assertEquals(13, filesize($out.'/tar/testdata1.txt'), "Extracted $file");

            $this->assertFileNotExists($out.'/tar/foobar/testdata2.txt', "Extracted $file");

            self::rdelete($out);
        }
    }

    /**
     * Check the extension to compression guesser
     */
    public function test_filetype()
    {
        $tar = new Tar();
        $this->assertEquals(Tar::COMPRESS_NONE, $tar->filetype('foo'));
        $this->assertEquals(Tar::COMPRESS_GZIP, $tar->filetype('foo.tgz'));
        $this->assertEquals(Tar::COMPRESS_GZIP, $tar->filetype('foo.tGZ'));
        $this->assertEquals(Tar::COMPRESS_GZIP, $tar->filetype('foo.tar.GZ'));
        $this->assertEquals(Tar::COMPRESS_GZIP, $tar->filetype('foo.tar.gz'));
        $this->assertEquals(Tar::COMPRESS_BZIP, $tar->filetype('foo.tbz'));
        $this->assertEquals(Tar::COMPRESS_BZIP, $tar->filetype('foo.tBZ'));
        $this->assertEquals(Tar::COMPRESS_BZIP, $tar->filetype('foo.tar.BZ2'));
        $this->assertEquals(Tar::COMPRESS_BZIP, $tar->filetype('foo.tar.bz2'));

        $dir = dirname(__FILE__).'/tar';
        $this->assertEquals(Tar::COMPRESS_NONE, $tar->filetype("$dir/test.tar"));
        $this->assertEquals(Tar::COMPRESS_GZIP, $tar->filetype("$dir/test.tgz"));
        $this->assertEquals(Tar::COMPRESS_BZIP, $tar->filetype("$dir/test.tbz"));
        $this->assertEquals(Tar::COMPRESS_NONE, $tar->filetype("$dir/test.tar.guess"));
        $this->assertEquals(Tar::COMPRESS_GZIP, $tar->filetype("$dir/test.tgz.guess"));
        $this->assertEquals(Tar::COMPRESS_BZIP, $tar->filetype("$dir/test.tbz.guess"));
    }

    /**
     * @depends test_ext_zlib
     */
    public function test_longpathextract()
    {
        $dir = dirname(__FILE__).'/tar';
        $out = sys_get_temp_dir().'/dwtartest'.md5(time());

        foreach (array('ustar', 'gnu') as $format) {
            $tar = new Tar();
            $tar->open("$dir/longpath-$format.tgz");
            $tar->extract($out);

            $this->assertFileExists(
                $out.'/1234567890/1234567890/1234567890/1234567890/1234567890/1234567890/1234567890/1234567890/1234567890/1234567890/1234567890/1234567890/test.txt'
            );

            self::rdelete($out);
        }
    }

    // FS#1442
    public function test_createlongfile()
    {
        $tar = new Tar();
        $tar->setCompression(0);
        $tmp = tempnam(sys_get_temp_dir(), 'dwtartest');

        $path = '0123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789.txt';

        $tar->create($tmp);
        $tar->addData($path, 'testcontent1');
        $tar->close();

        $this->assertTrue(filesize($tmp) > 30); //arbitrary non-zero number
        $data = file_get_contents($tmp);

        // We should find the complete path and a longlink entry
        $this->assertTrue(strpos($data, 'testcontent1') !== false, 'content in TAR');
        $this->assertTrue(strpos($data, $path) !== false, 'path in TAR');
        $this->assertTrue(strpos($data, '@LongLink') !== false, '@LongLink in TAR');

        @unlink($tmp);
    }

    public function test_createlongpathustar()
    {
        $tar = new Tar();
        $tar->setCompression(0);
        $tmp = tempnam(sys_get_temp_dir(), 'dwtartest');

        $path = '';
        for ($i = 0; $i < 11; $i++) {
            $path .= '1234567890/';
        }
        $path = rtrim($path, '/');

        $tar->create($tmp);
        $tar->addData("$path/test.txt", 'testcontent1');
        $tar->close();

        $this->assertTrue(filesize($tmp) > 30); //arbitrary non-zero number
        $data = file_get_contents($tmp);

        // We should find the path and filename separated, no longlink entry
        $this->assertTrue(strpos($data, 'testcontent1') !== false, 'content in TAR');
        $this->assertTrue(strpos($data, 'test.txt') !== false, 'filename in TAR');
        $this->assertTrue(strpos($data, $path) !== false, 'path in TAR');
        $this->assertFalse(strpos($data, "$path/test.txt") !== false, 'full filename in TAR');
        $this->assertFalse(strpos($data, '@LongLink') !== false, '@LongLink in TAR');

        @unlink($tmp);
    }

    public function test_createlongpathgnu()
    {
        $tar = new Tar();
        $tar->setCompression(0);
        $tmp = tempnam(sys_get_temp_dir(), 'dwtartest');

        $path = '';
        for ($i = 0; $i < 20; $i++) {
            $path .= '1234567890/';
        }
        $path = rtrim($path, '/');

        $tar->create($tmp);
        $tar->addData("$path/test.txt", 'testcontent1');
        $tar->close();

        $this->assertTrue(filesize($tmp) > 30); //arbitrary non-zero number
        $data = file_get_contents($tmp);

        // We should find the complete path/filename and a longlink entry
        $this->assertTrue(strpos($data, 'testcontent1') !== false, 'content in TAR');
        $this->assertTrue(strpos($data, 'test.txt') !== false, 'filename in TAR');
        $this->assertTrue(strpos($data, $path) !== false, 'path in TAR');
        $this->assertTrue(strpos($data, "$path/test.txt") !== false, 'full filename in TAR');
        $this->assertTrue(strpos($data, '@LongLink') !== false, '@LongLink in TAR');

        @unlink($tmp);
    }

    /**
     * Extract a tarbomomb
     * @depends test_ext_zlib
     */
    public function test_tarbomb()
    {
        $dir = dirname(__FILE__).'/tar';
        $out = sys_get_temp_dir().'/dwtartest'.md5(time());

        $tar = new Tar();

        $tar->open("$dir/tarbomb.tgz");
        $tar->extract($out);

        clearstatcache();

        $this->assertFileExists(
            $out.'/AAAAAAAAAAAAAAAAA/BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB.txt'
        );

        self::rdelete($out);
    }

    /**
     * A single zero file should be just a header block + the footer
     */
    public function test_zerofile()
    {
        $dir = dirname(__FILE__).'/tar';
        $tar = new Tar();
        $tar->setCompression(0);
        $tar->create();
        $tar->addFile("$dir/zero.txt", 'zero.txt');
        $file = $tar->getArchive();

        $this->assertEquals(512 * 3, strlen($file)); // 1 header block + 2 footer blocks
    }

    public function test_zerodata()
    {
        $tar = new Tar();
        $tar->setCompression(0);
        $tar->create();
        $tar->addData('zero.txt', '');
        $file = $tar->getArchive();

        $this->assertEquals(512 * 3, strlen($file)); // 1 header block + 2 footer blocks
    }

    /**
     * A file of exactly one block should be just a header block + data block + the footer
     */
    public function test_blockfile()
    {
        $dir = dirname(__FILE__).'/tar';
        $tar = new Tar();
        $tar->setCompression(0);
        $tar->create();
        $tar->addFile("$dir/block.txt", 'block.txt');
        $file = $tar->getArchive();

        $this->assertEquals(512 * 4, strlen($file)); // 1 header block + data block + 2 footer blocks
    }

    public function test_blockdata()
    {
        $tar = new Tar();
        $tar->setCompression(0);
        $tar->create();
        $tar->addData('block.txt', str_pad('', 512, 'x'));
        $file = $tar->getArchive();

        $this->assertEquals(512 * 4, strlen($file)); // 1 header block + data block + 2 footer blocks
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
