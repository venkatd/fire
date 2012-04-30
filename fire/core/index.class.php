<?php

class Index
{

    private $root;
    /**
     * @var \Cache
     */
    private $cache;

    private $data = array();

    private $resource_types = array('directory', 'file', 'class', 'config', 'js');

    function __construct($root, Cache $cache)
    {
        $this->load_requirements();

        $this->root = $root;
        $this->cache = $cache;

        if ($this->requires_rebuild()) {
            $this->rebuild();
        }
        else {
            $this->load_from_cache();
        }
    }

    function data()
    {
        return $this->data;
    }

    function root()
    {
        return $this->data['root'];
    }

    /**
     * @param $type
     * @return Metadata[]
     */
    function get_resources_of_type($type)
    {
        $resources = array();

        $meta_class = $type . 'metadata';

        foreach ($this->data['resources'] as $meta)
            if ($meta instanceof $meta_class)
                $resources[] = $meta;

        return $resources;
    }

    /**
     * @param $resource_name
     *
     * @return Metadata|null
     */
    function get_metadata($resource_name)
    {
        $alias_path = $this->get_alias_path($resource_name);
        if ($alias_path) {
            $resource_path = $alias_path;
        }
        else {
            $resource_path = $resource_name;
        }

        $data = isset($this->data['resources'][$resource_path])
                ? $this->data['resources'][$resource_path]
                : null;

        return $data;
    }

    function set_resource_metadata($path, Metadata $metadata)
    {
        $this->data['resources'][$path] = $metadata;
    }

    function get_alias_path($alias)
    {
        $alias = strtolower($alias);
        if (!isset($this->data['aliases'][$alias]))
            return false;

        if (count($this->data['aliases'][$alias]) > 1) {
            throw new Exception("Ambiguous alias $alias.");
        }

        return $this->data['aliases'][$alias][0];
    }

    private function load_requirements()
    {
        require_once 'meta/metadata.class.php';
        require_once 'meta/directory_metadata.class.php';
        require_once 'meta/file_metadata.class.php';
        require_once 'meta/class_metadata.class.php';
        require_once 'meta/config_metadata.class.php';
        require_once 'meta/js_metadata.class.php';

        require_once 'indexers/indexer.class.php';
        require_once 'indexers/directory_indexer.class.php';
        require_once 'indexers/file_indexer.class.php';
        require_once 'indexers/class_indexer.class.php';
        require_once 'indexers/config_indexer.class.php';
        require_once 'indexers/js_indexer.class.php';
    }

    private function save_to_cache()
    {
        $this->cache_set('index', $this->data);
        $this->cache_set('version', $this->fetch_real_version());
    }

    private function load_from_cache()
    {
        $this->data = $this->cache_get('index');
    }

    private function rebuild()
    {
        set_time_limit(60 * 2);
        $this->data = array(
            'root' => realpath($this->root),
        );

        foreach ($this->resource_types as $type) {
            $indexer = $this->get_indexer($type);
            $indexer->run();
        }

        $this->save_to_cache();
        
        return $this->data;
    }

    /**
     * @param $resource_type
     * @return Indexer
     */
    private function get_indexer($resource_type)
    {
        $class_name = $resource_type . 'indexer';
        return new $class_name($this);
    }

    function create_alias($alias, Metadata $metadata)
    {
        $alias = strtolower($alias);
        $this->data['aliases'][$alias][] = $metadata->path;
    }

    private function requires_rebuild()
    {
        if (!$this->cache_exists('index'))
            return true;

        $cached_version = $this->fetch_cached_version();
        $real_version = $this->fetch_real_version();

        return version_compare($cached_version, $real_version, '!=');
    }

    private function fetch_real_version()
    {
        return @file_get_contents($this->root . 'version.txt');
    }

    private function fetch_cached_version()
    {
        return $this->cache_get('version');
    }

    private function cache_key()
    {
        return 'blox_index_' . md5($this->root);
    }

    private function cache_set($key, $value)
    {
        $namespaced_cache_key = $this->cache_key() . '_' . $key;
        $this->cache->set($namespaced_cache_key, $value);
    }

    private function cache_get($key)
    {
        $namespaced_cache_key = $this->cache_key() . '_' . $key;
        return $this->cache->get($namespaced_cache_key);
    }

    private function cache_exists($key)
    {
        $namespaced_cache_key = $this->cache_key() . '_' . $key;
        return $this->cache->exists($namespaced_cache_key);
    }

}
