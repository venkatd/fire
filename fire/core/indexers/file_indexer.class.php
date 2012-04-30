<?php

class FileIndexer extends Indexer
{
    function run()
    {
        $files = $this->scan_files($this->index->root(), true);
        foreach ($files as $filepath) {
            $this->index_file($filepath);
        }
    }


    private function index_file($filepath)
    {
        $root = realpath($this->index->root());
        $filepath = realpath($filepath);
        $resource_path = string_after_first($root, $filepath);
        $resource_path = str_replace('\\', '/', $resource_path);
        $resource_path = substr($resource_path, 1);

        $meta = new FileMetadata();
        $meta->type = 'file';
        $meta->path = $resource_path;
        $meta->filepath = $filepath;
        $meta->filename = basename($filepath);
        $meta->extension = string_after_last('.', $meta->filename);

        $this->index->set_resource_metadata($resource_path, $meta);
        $this->index->create_alias($meta->filename, $meta);
    }
}
