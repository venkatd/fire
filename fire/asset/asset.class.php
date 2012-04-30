<?php

class Asset
{

    /**
     * @var Index
     */
    private $index;

    private $js = array();

    function __construct(Index $index)
    {
        $this->index = $index;
    }

    function load_js($js_name)
    {
        $this->js[] = $js_name;
    }

    function scripts()
    {
        $scripts = array();
        foreach ($this->get_loaded_js() as $js) {
            $scripts[] = $this->script($js);
        }
        return implode("\n", $scripts);
    }

    function get_loaded_js()
    {
        $tree = $this->get_dependency_tree();
        $ts = new TopologicalSort($tree);
        return $ts->dependencies_of($this->js);
    }

    private function script($name)
    {
        $tpl = '<script type="text/javascript" src="%s"></script>';
        $path = $this->get_file_path($name);
        $path = string_before_last('.js', $path) . '.' . $this->get_modification_time($name) . '.js';
        return sprintf($tpl, $path);
    }

    private function get_file_path($js_name)
    {
        /* @var $js_meta JsMetadata */
        $js_meta = $this->index->get_metadata($js_name);

        $filepath = $js_meta->filepath;
        $filepath = str_replace('\\', '/', $filepath);

        $root_dir = dirname($_SERVER['SCRIPT_FILENAME']);

        if (string_starts_with($root_dir, $filepath))
            $filepath = string_after_first($root_dir, $filepath);

        return $filepath;
    }

    private function get_modification_time($js_name)
    {
        /* @var $js_meta JsMetadata */
        $js_meta = $this->index->get_metadata($js_name);
        return filemtime($js_meta->filepath);
    }

    function get_dependency_tree()
    {
        $dependencies = array();
        /* @var $js_meta JsMetadata */
        foreach ($this->index->get_resources_of_type('js') as $js_meta) {
            if (empty($js_meta->direct_dependencies))
                continue;

            $dependencies[$js_meta->filename] = $js_meta->direct_dependencies;
        }
        return $dependencies;
    }

}
