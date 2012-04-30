<?php

class Indexer
{

    protected $index;

    function __construct(Index $index)
    {
        $this->index = $index;
    }

    function run()
    {

    }

    protected function scan_files($path, $include_subdirectories = false)
    {
        if (!is_dir($path))
            return false;

        $files = array();

        $iterator = $this->get_directory_iterator($path, $include_subdirectories);

        foreach ($iterator as $file) {
            // isDot method is only available in DirectoryIterator items
            // isDot check skips '.' and '..'
            if (method_exists($file, 'isDot') && $file->isDot())
                continue;

            // Standardize to forward slashes
            $filepath = str_replace('\\', '/', $file->getPathName());

            if ($file->isFile()) {
                $files[] = $filepath;
            }
        }

        return $files;
    }

    protected function scan_directories($path, $include_subdirectories = false)
    {
        if (!is_dir($path))
            return false;

        $folders = array();

        $iterator = $this->get_directory_iterator($path, $include_subdirectories);

        foreach ($iterator as $file) {
            // isDot method is only available in DirectoryIterator items
            // isDot check skips '.' and '..'
            if (method_exists($file, 'isDot') && $file->isDot())
                continue;

            // Standardize to forward slashes
            $filepath = str_replace('\\', '/', $file->getPathName());

            if ($file->isDir()) {
                $folders[] = $filepath;
            }
        }

        return $folders;
    }

    protected function get_directory_iterator($path, $include_subdirectories)
    {
        if ($include_subdirectories) {
            return new RecursiveIteratorIterator(
                new IgnoreFilesRecursiveFilterIterator(
                    new RecursiveDirectoryIterator($path)
                ),
                RecursiveIteratorIterator::SELF_FIRST
            );
        }
        else {
            return new IgnoreFilesIterator(
                new DirectoryIterator($path)
            );
        }
    }

}


class IgnoreFilesRecursiveFilterIterator extends RecursiveFilterIterator
{
    public function accept()
    {
        /* @var $current_file SplFileInfo */
        $current_file = $this->current();
        $filename = $current_file->getFilename();
        if ($current_file->isDir() && substr($filename, 0, 1) == '.')
            return false;
        else
            return true;
    }
}

class IgnoreFilesIterator extends FilterIterator
{
    public function accept()
    {
        /* @var $current_file SplFileInfo */
        $current_file = $this->current();
        $filename = $current_file->getFilename();
        var_dump($filename);
        if ($current_file->isDir() && substr($filename, 0, 1) == '.')
            return false;
        else
            return true;
    }
}
