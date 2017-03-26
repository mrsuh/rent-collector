<?php

namespace AppBundle\Storage;

use AppBundle\Exception\FileException;

class FileStorage
{
    private $dir;

    public function __construct($dir)
    {
        $this->dir = $dir;
    }

    public function put($path, $file_content)
    {
        if (!is_dir($this->dir) && !@mkdir($this->dir)) {
            throw new FileException('Can not create directory ' . $this->dir);
        }

        $folders = explode(DIRECTORY_SEPARATOR, $path);

        array_pop($folders);//file name

        $dir = $this->dir;
        foreach ($folders as $folder) {
            $dir .= DIRECTORY_SEPARATOR . $folder;
            if (!is_dir($dir) && !@mkdir($dir)) {
                throw new FileException('Can not create directory ' . $dir);
            }
        }

        file_put_contents($this->dir . DIRECTORY_SEPARATOR . $path, json_encode($file_content));

        return true;
    }

    public function get($path)
    {
        if (!$this->exists($path)) {
            $this->put($path, []);

            return [];
        }

        return json_decode(file_get_contents($this->dir . DIRECTORY_SEPARATOR . $path), true);
    }

    public function delete($path)
    {
        $it    = new \RecursiveDirectoryIterator($this->dir . DIRECTORY_SEPARATOR . $path, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $name => $file) {

            if ($file->isDir()) {
                if (!@rmdir($file->getRealPath())) {
                    $this->delete($file->getRealPath());
                }
            } else {
                unlink($file->getRealPath());
            }
        }

        return true;
    }

    public function exists($path)
    {
        return file_exists($this->dir . DIRECTORY_SEPARATOR . $path);
    }
}
