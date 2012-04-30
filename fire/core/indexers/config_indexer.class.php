<?php

require_once FIREPATH . 'core/config/spyc.class.php';

class ConfigIndexer extends Indexer
{

    function run()
    {
        foreach ($this->get_file_resources() as $file_metadata) {
            $this->index_file($file_metadata);
        }
    }

    private function index_file(FileMetadata $file_metadata)
    {
        $meta = new ConfigMetadata();
        $meta->type = 'config';
        $meta->path = $file_metadata->path;
        $meta->filepath = $file_metadata->filepath;
        $meta->filename = $file_metadata->filename;
        $meta->extension = $file_metadata->extension;

        $meta->data = Spyc::YAMLLoad($meta->filepath);

        $this->index->set_resource_metadata($meta->path, $meta);
    }

    /**
     * @return FileMetadata[]
     */
    private function get_file_resources()
    {
        $resources = array();
        /* @var $file_meta FileMetadata */
        foreach ($this->index->get_resources_of_type('file') as $file_meta) {
            if ($file_meta->extension == 'yml')
                $resources[] = $file_meta;
        }
        return $resources;
    }

}
