<?php
class ConfigSource
{
    
    /* @var Index */
    private $index;
    private $environment;

    private $config = null;

    function __construct(Index $index, $environment)
    {
        $this->index = $index;
        $this->environment = $environment;
    }

    function load()
    {
        if (!$this->config) {
            $this->config = $this->load_config_from_files();
        }

        return $this->config;
    }

    private function load_config_from_files()
    {
        $config = array();

        $resources = $this->get_config_resources();
        foreach ($resources as $r) {
            foreach ($r->data as $k => $v)
                $config[$k] = $v;
        }

        return $config;
    }

    /**
     * @return ConfigMetadata[]
     */
    private function get_config_resources()
    {
        $resources = $this->index->get_resources_of_type('config');
        usort($resources, array($this, 'priority_sort_asc'));

        foreach ($resources as $k => $resource) {
            if ($this->get_priority($resource) == 0)
                unset($resources[$k]);
        }

        return array_values($resources);
    }

    private function priority_sort_asc(ConfigMetadata $a, ConfigMetadata $b)
    {
        return -1 * ($this->get_priority($b) - $this->get_priority($a));
    }

    private function get_priority(ConfigMetadata $meta)
    {
        if ($this->is_environment_specific($meta) && $this->is_for_current_environment($meta))
            return 2;
        elseif (!$this->is_environment_specific($meta)) {
            return 1;
        }
        elseif ($this->is_environment_specific($meta) && !$this->is_for_current_environment($meta)) {
            return 0; //doesn't even belong here
        }

        throw new Exception("Invalid priority for config");
    }

    private function is_for_current_environment(ConfigMetadata $meta)
    {
        return $this->get_config_environment($meta) == $this->environment;
    }

    private function get_config_environment(ConfigMetadata $meta)
    {
        preg_match('/\w+\.(\w+)\.yml/', $meta->filename, $m);
        return isset($m[1]) ? $m[1] : null;
    }

    private function is_environment_specific(ConfigMetadata $meta)
    {
        return preg_match('/\w+\.\w+\.yml/', $meta->filename);
    }

}
