<?php

class DirectoryIndexer extends Indexer
{

    function run()
    {
        $directories = $this->scan_directories($this->index->root(), true);
        foreach ($directories as $directory) {
            $this->index_directory($directory);
        }
    }

    private function index_directory($dirpath)
    {
        $root = realpath($this->index->root());
        $path = realpath($dirpath);
        $resource_path = string_after_first($root, $path);
        $resource_path = str_replace('\\', '/', $resource_path);
        $resource_path = substr($resource_path, 1);

        $meta = new DirectoryMetadata();
        $meta->type = 'directory';
        $meta->path = $resource_path;
        $meta->directory_path = $dirpath;

        $this->index->set_resource_metadata($meta->path, $meta);
    }

}
