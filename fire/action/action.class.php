<?php

abstract class Action
{
    function is_ajax()
    {
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        return $isAjax;
    }
}
