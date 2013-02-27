<?php

namespace tests\SPL\Archive;

use PHPUnit_Framework_TestCase;
use SPL\Archive\Zip;
use SPL\Directory\Directory;

class ZipTest extends PHPUnit_Framework_TestCase
{
    protected static $layers = 3;

    /**
     * @var \SPL\Archive\Zip
     */
    protected $zip;

    const TEST_DIR = 'test_archive_dir';
    const TEST_FILE = 'random_file.txt';
    const ARCHIVE_FILE = 'testArch.zip';
    const DS = DIRECTORY_SEPARATOR;

    public static function setUpBeforeClass()
    {
        if(!is_dir(self::TEST_DIR))
        {
            mkdir(self::TEST_DIR);

            $last_dir = self::TEST_DIR;

            for($i = 1; $i <= self::$layers; $i++)
            {
                $file = $last_dir . self::DS . self::TEST_FILE;

                if(!is_file($file))
                {
                    touch($file);
                    $file = realpath($file);
                    $fh = fopen($file, 'w');
                    fwrite($fh, 'This is a test file for the archive creation');
                    fclose($fh);
                }

                $last_dir = $last_dir . DIRECTORY_SEPARATOR . md5(time());
                mkdir($last_dir);
                $last_dir = realpath($last_dir);
            }
        }
    }

    public function setUp()
    {
        $this->zip = new Zip();
    }

    public function testTestDirCreated()
    {
        $this->assertTrue(is_dir(self::TEST_DIR));
    }

    /**
     * @depends testTestDirCreated
     */
    public function testTestFileCreated()
    {
        $this->assertTrue(is_file(realpath(self::TEST_DIR . self::DS . self::TEST_FILE)));
    }

    /**
     * @depends testTestFileCreated
     */
    public function testCreateArchive()
    {
        $this->zip->pack(self::TEST_DIR, self::ARCHIVE_FILE);

        $this->assertTrue(is_file(self::ARCHIVE_FILE));
    }

    /**
     * @depends testCreateArchive
     */
    public function testUnpackArchive()
    {
        $this->zip->unpack(self::ARCHIVE_FILE, self::TEST_DIR . '_unpacked');

        $this->assertTrue($this->compareDirectories(self::TEST_DIR, self::TEST_DIR . '_unpacked'));
    }

    /**
     * @param $original
     * @param $copy
     * @return bool
     */
    protected function compareDirectories($original, $copy)
    {
        $result = true;
// TODO: implement the compareDirectories method
//        $originalContents = scandir($original);
//
//        foreach($originalContents as $path)
//        {
//            if($path != '.' && $path != '..')
//            {
//                if(!is_dir($copy . $path))
//                {
//
//                }
//            }
//        }

        return $result;
    }

    public static function tearDownAfterClass()
    {
        $cleanup = array(
            self::TEST_DIR,
            self::TEST_DIR . '_unpacked',
        );

        foreach($cleanup as $dir)
        {
            if(is_dir($dir))
            {
                Directory::cleanup($dir);
                rmdir($dir);
            }
        }

        if(is_file(self::ARCHIVE_FILE))
        {
            unlink(self::ARCHIVE_FILE);
        }
    }
}