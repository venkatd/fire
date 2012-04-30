<?php

class LocalFileRepositoryDriver extends FileRepositoryDriver
{

    function __construct($options)
    {
        parent::__construct($options);
        $this->check_path();
    }

    function create($destination_filename, $source_filepath)
    {
        copy($source_filepath, $this->filepath($destination_filename));
    }

    function delete($filename)
    {
        @unlink($this->filepath($filename));
    }

    function exists($filename)
    {
        return file_exists($this->filepath($filename));
    }

    function url($filename)
    {
        return $this->base_url() . $this->filepath($filename);
    }

    function load_text($filename)
    {
        $text = @file_get_contents($this->filepath($filename));
        return $text;
    }

    function get_file_names()
    {
        $files = array();
        
        $iterator = new DirectoryIterator( $this->options['path'] );
        foreach ($iterator as $file) {
            if ($file->isDot())
                continue;
            $files[] = $file->getFilename();
        }

        return $files;
    }

    private function filepath($filename)
    {
        return $this->options['path'] . '/' . $filename;
    }

    private function base_url()
    {
        if (isset($this->options['base_url']))
            return $this->options['base_url'];
        else
            return "http://{$_SERVER['HTTP_HOST']}/";
    }

    private function check_path()
    {
        if (!is_writable($this->options['path'])) {
            throw new Exception("The path " . $this->options['path'] . " doesn't exist or is not writeable.");
        }
    }

}
