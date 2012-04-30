<?php

class FileRepository extends Component
{
    
    function create($destination_filename, $source_filepath, $metadata = array())
    {
        $this->create_without_metadata($destination_filename, $source_filepath);
        $this->save_metadata($destination_filename, $metadata);
    }

    function delete($filename)
    {
        $this->delete_without_metadata($filename);
        $this->driver->delete($filename);
        $this->delete_metadata($filename);
    }

    function exists($filename)
    {
        return $this->driver->exists($filename);
    }

    function url($filename)
    {
        benchmark::start(__METHOD__);
        $url = $this->driver->url($filename);
        benchmark::end(__METHOD__);
        return $url;
    }

    function get_file_names()
    {
        $unfiltered_file_names = $this->driver->get_file_names();
        $filtered_file_names = array();

        foreach ($unfiltered_file_names as $cur_file_name) {
            if (!$this->is_metadata_file($cur_file_name))
                $filtered_file_names[] = $cur_file_name;
        }

        return $filtered_file_names;
    }

    function create_from_text($destination_filename, $text)
    {
        $temp_filepath = tempnam(sys_get_temp_dir(), 'filerepository_temp_');
        file_put_contents($temp_filepath, $text);
        $this->create_without_metadata($destination_filename, $temp_filepath);
        @unlink($temp_filepath);
    }

    function load_text($filename)
    {
        return $this->driver()->load_text($filename);
    }

    function create_from_data($destination_filename, $data)
    {
        $serialized_data = serialize($data);
        $this->create_from_text($destination_filename, $serialized_data);
    }

    function load_data($filename)
    {
        $text = $this->load_text($filename);
        $data = @unserialize($text);
        return $data;
    }

    function load_metadata($filename)
    {
        return $this->load_data($this->metadata_filename($filename));
    }

    function save_metadata($filename, $metadata)
    {
        $this->create_from_data($this->metadata_filename($filename), $metadata);
    }

    function delete_metadata($filename)
    {
        $this->delete_without_metadata($this->metadata_filename($filename));
    }

    private function is_metadata_file($filename)
    {
        return string_ends_with('.meta', $filename);
    }

    private function create_without_metadata($destination_filename, $source_filepath)
    {
        $this->driver->create($destination_filename, $source_filepath);
    }

    private function delete_without_metadata($filename)
    {
        $this->driver->delete($filename);
    }

    private function metadata_filename($filename)
    {
        return "$filename.meta";
    }

}
