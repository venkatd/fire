<?php

class Clock
{

    private $delta = 0;
    private $timezone;
    
    function __construct(DateTimeZone $timezone)
    {
        $this->timezone = $timezone;
    }
    
    function set_time($time)
    {
        if (is_string($time))
            $time = new XDateTime($time, $this->timezone);

        $this->set_delta( $time->getTimestamp() - $this->actual_time()->getTimestamp() );
    }

    /**
     * @return XDateTime
     */
    function get_time()
    {
        $time = $this->actual_time();
        $time->modify("+{$this->delta} seconds");
        return $time;
    }

    function today()
    {
        return $this->get_time()->getDay(0);
    }

    function set_delta($seconds)
    {
        $this->delta = $seconds;
    }

    function get_delta()
    {
        return $this->delta;
    }

    private function actual_time()
    {
        return new XDateTime('now', $this->timezone);
    }

}
