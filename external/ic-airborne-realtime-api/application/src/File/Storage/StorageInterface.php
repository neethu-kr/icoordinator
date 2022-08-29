<?php

namespace iCoordinator\File\Storage;

interface StorageInterface extends \Upload\StorageInterface
{
    public function download($fileName, $offset = 0, $length = -1);

    public function delete($fileName);

    public function rename($oldName, $newName);

    public function copy($originalFile, $copyFile);

    public function clear();

    public function fopen($filename, $mode);

    public function getStreamUrl($filename);

    public function setStreamFilter($filterName, $options);
}
