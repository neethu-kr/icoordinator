<?php

namespace iCoordinator\Test\Helper;

class MockReader
{
    public static function read($filename)
    {
        return file_get_contents(dirname(dirname(__DIR__)) . '/data/' . $filename);
    }
}