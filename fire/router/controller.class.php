<?php

class Controller
{

    function __construct()
    {
        app()->class_loader()->load('Template');
    }
    
}
