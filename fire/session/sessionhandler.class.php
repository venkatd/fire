<?php

abstract class SessionHandler
{
    abstract function open($path, $name);

    abstract function close();

    abstract function read($sess_id);

    abstract function write($sess_id, $data);

    abstract function destroy($sess_id);

    abstract function gc($sess_maxlifetime);
}
