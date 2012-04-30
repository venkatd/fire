<?php

class XDateTime extends DateTime
{

    function __construct($time = 'now', $timezone = NULL)
    {
        if (func_num_args() == 1)
            parent::__construct($time);
        else
            parent::__construct($time, $timezone);
    }

    function getDay($offset = 0)
    {
        $day = clone $this;
        $day->setTime(0, 0, 0);
        $day->modify("+$offset days");
        return $day;
    }
    
    function getDayOfWeek()
    {
        return $this->format('l');
    }

    function getDayOfType($filter, $target_offset)
    {
        $max_limit = 30;
        $target_offset = intval($target_offset);
        $filtered_offset = 0;
        $actual_offset = 0;

        $step = $target_offset > 0 ? 1 : -1;

        $cur_day = $this->getDay($actual_offset);

        //today won't always work since today might satisfy the conditions
        if ($target_offset == 0)
            return $filter(clone $cur_day) ? $cur_day : FALSE;

        do {
            $actual_offset += $step;
            $cur_day = $this->getDay($actual_offset);

            if ($filter(clone $cur_day))
                $filtered_offset += $step;

            if ($actual_offset > $max_limit)
                throw new Exception('Exceeded the offset limit.');

        } while ($filtered_offset != $target_offset);

        return $cur_day;
    }

    function getUTCTime()
    {
        $utc = clone $this;
        $utc->setTimezone(new DateTimeZone('UTC'));
        return $utc;
    }

    function getMySqlDatestamp()
    {
        return $this->getUTCTime()->format('Y-m-d');
    }

    function getMySqlTimestamp()
    {
        return $this->getUTCTime()->format('Y-m-d H:i:s');
    }

}
