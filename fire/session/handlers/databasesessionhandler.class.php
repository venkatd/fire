<?php

class DatabaseSessionHandler extends SessionHandler
{

    /* @var $db Database */
    private $db;

    /* @var $clock Clock */
    private $clock;

    private $sessions_table = 'sessions';

    function __construct(Database $database, Clock $clock)
    {
        $this->db = $database;
        $this->clock = $clock;
    }
    
    function open($path, $name)
    {
        return true;
    }

    function close()
    {
        return true;
    }

    function read($sess_id)
    {
        $row = $this->table()->row($sess_id);
        return $row ? $row->data : '';
    }

    function write($sess_id, $data)
    {
        $row = $this->table()->row($sess_id);
        $current_time = $this->clock->get_time();
        
        if ($row) {
            $row->data = $data;
            $row->updated = $current_time;
            $row->save();
        }
        else {
            $this->table()->create_row(array(
                                           'id' => $sess_id,
                                           'created' => $current_time,
                                           'updated' => $current_time,
                                           'data' => $data,
                                       ));
        }
        
        return true;
    }

    function destroy($sess_id)
    {
        $this->table()->destroy_row($sess_id);
        return true;
    }

    function gc($sess_maxlifetime)
    {
        //todo: garbage collection
        return true;
    }

    /**
     * @return DatabaseTable
     */
    private function table()
    {
        return $this->db->table($this->sessions_table);
    }

}
