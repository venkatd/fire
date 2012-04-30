<?php

class benchmark
{

    /* @var $benchmarkers Benchmarker[] */
    private static $benchmarkers;

    /**
     * @static
     * @param string $group
     * @return Benchmarker
     */
    public static function benchmarker($group = 'main')
    {
        if (!isset(static::$benchmarkers[$group]))
            static::$benchmarkers[$group] = new Benchmarker();

        return static::$benchmarkers[$group];
    }

    public static function start($marker, $group = 'main')
    {
        return static::benchmarker($group)->start($marker);
    }

    public static function end($marker, $group = 'main')
    {
        return static::benchmarker($group)->end($marker, $group);
    }

    public static function elapsed($marker, $group = 'main')
    {
        return static::benchmarker($group)->elapsed($marker);
    }

    public static function count($marker, $group = 'main')
    {
        return static::benchmarker($group)->count($marker);
    }

    public static function summary($group = 'main')
    {
        return static::benchmarker($group)->summary();
    }

    public static function stats()
    {
        return array(
            'memory_used' => static::memory_used()
        );
    }

    public static function memory_used()
    {
        $memory_in_bytes = memory_get_peak_usage(true);
        $memory_in_megabytes = $memory_in_bytes / 1048576;
        return round($memory_in_megabytes, 2);
    }

}

class Benchmarker
{

    private $blocks = array();
    private $start_times = array();

    function start($marker)
    {
        if (isset($this->start_times[$marker]))
            throw new Exception("Already started $marker.");

        $this->start_times[$marker] = microtime(true);
    }

    function end($marker)
    {
        if (!isset($this->start_times[$marker]))
                    throw new Exception("Never started $marker.");

        $block = array(
            'marker' => $marker,
            'start' => $this->start_times[$marker],
            'end' => microtime(true),
        );
        $block['elapsed'] = $block['end'] - $block['start'];

        unset($this->start_times[$marker]);

        $this->blocks[$marker][] = $block;
    }

    function elapsed($marker)
    {
        if (!isset($this->blocks[$marker]))
            return 0;

        $elasped = 0;
        foreach ($this->blocks[$marker] as $block)
            $elasped += $block['elapsed'];

        return $elasped;
    }

    function count($marker)
    {
        if (!isset($this->blocks[$marker]))
            return 0;

        return count($this->blocks[$marker]);
    }

    function summary()
    {
        $summary = array();

        foreach ($this->blocks as $marker => $blocks) {
            $summary[] = array(
                'marker' => $marker,
                'elapsed' => $this->elapsed($marker),
                'count' => $this->count($marker),
            );
        }

        usort($summary, function($a, $b) {
            return $b['elapsed'] * 10000 - $a['elapsed'] * 10000;
        });

        return $summary;
    }

}
