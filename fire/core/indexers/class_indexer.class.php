<?php

require_once FIREPATH . 'core/phpclassparser.class.php';

class ClassIndexer extends Indexer
{

    function run()
    {
        $file_resources = $this->get_file_resources();
        foreach ($file_resources as $file_meta) {
            $this->index_file($file_meta);
        }

        $this->index_php_class_heirarchy();
    }

    private function index_file(FileMetadata $file_metadata)
    {
        $parser = new PHPClassParser();

        $filepath = $file_metadata->filepath;

        $classes_in_filepath = $parser->get_file_classes($filepath);

        foreach ($classes_in_filepath as $class_name => &$class_metadata) {
            $meta = new ClassMetadata();
            foreach ($class_metadata as $k => $v)
                $meta->$k = $v;

            $meta->type = 'class';
            $meta->path = $file_metadata->path . '/' . $class_name;
            $meta->file = $file_metadata->path;

            $this->index->set_resource_metadata($meta->path, $meta);

            $this->index->create_alias($class_name, $meta);
            $this->index->create_alias($class_name . ' class', $meta);
        }
    }

    private function index_php_class_heirarchy()
    {
        /* @var $file_meta FileMetadata */
        foreach ($this->index->get_resources_of_type('class') as $class_meta) {
            if (isset($class_meta->parent)) {
                $superclass_resource_path = $this->index->get_alias_path("$class_meta->parent class");
                if ($superclass_resource_path) {
                    $superclass_meta = $this->index->get_metadata($superclass_resource_path);
                    $superclass_meta->subclasses[] = $class_meta->name;
                }
            }
        }
    }

    /**
     * @return FileMetadata[]
     */
    private function get_file_resources()
    {
        $resources = array();
        /* @var $file_meta FileMetadata */
        foreach ($this->index->get_resources_of_type('file') as $file_meta) {
            if ($file_meta->extension == 'php')
                $resources[] = $file_meta;
        }
        return $resources;
    }

}
