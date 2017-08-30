<?php

namespace AppBundle\Storage;

use AppBundle\Exception\FileException;

class FileStorage
{
    private $dir;

    /**
     * FileStorage constructor.
     * @param string $dir
     */
    public function __construct(string $dir)
    {
        $this->dir = $dir;
    }

    /**
     * @param string $path
     * @param string $file_content
     * @return bool
     * @throws FileException
     */
    public function put(string $path, string $file_content)
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

        file_put_contents($this->dir . DIRECTORY_SEPARATOR . $path, $file_content);

        return true;
    }

    /**
     * @param string $path
     * @return string
     */
    public function get(string $path)
    {
        if (!$this->exists($path)) {
            $this->put($path, []);

            return '';
        }

        return file_get_contents($this->dir . DIRECTORY_SEPARATOR . $path);
    }

    /**
     * @param string $path
     * @return bool
     */
    public function delete(string $path): bool
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

    /**
     * @param string $path
     * @return bool
     */
    public function exists(string $path): bool
    {
        return file_exists($this->dir . DIRECTORY_SEPARATOR . $path);
    }
}
