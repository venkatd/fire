<?php

abstract class FileRepositoryDriver extends Driver
{
    abstract function create($destination_filename, $source_filepath);

    abstract function delete($filename);

    abstract function exists($filename);

    abstract function url($filename);

    function load_text($filename)
    {
        $text = @file_get_contents($this->url($filename));
        return $text;
    }

}
