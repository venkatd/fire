<?php

class browser
{

    protected static $devices = array('blackberry', 'android', 'iphone');

    public static $settings = array();

    static function classes()
    {
        $classes = array();

        $classes[] = static::is_mobile() ? 'mobile' : 'desktop';

        foreach (static::$devices as $device) {
            if (static::is($device))
                $classes[] = $device;
        }

        return implode(' ', $classes);
    }

    static function is_mobile()
    {
		if (isset($_GET['mobile']) && $_GET['mobile'] == 'true')
		    return true;
		
        if(preg_match('/(alcatel|amoi|android|avantgo|blackberry|benq|cell|cricket|docomo|elaine|htc|iemobile|iphone|ipaq|ipod|j2me|java|midp|mini|mmp|mobi|motorola|nec-|nokia|palm|panasonic|philips|phone|sagem|sharp|sie-|smartphone|sony|symbian|t-mobile|telus|up\.browser|up\.link|vodafone|wap|webos|wireless|xda|xoom|zte)/i', $_SERVER['HTTP_USER_AGENT']))
            return true;
        else
            return false;
    }

    static function is_blackberry()
    {
        return static::is('blackberry');
    }

    static function is_android()
    {
        return static::is('android');
    }

    static function is_iphone()
    {
        return static::is('iphone');
    }

    static function is($type)
    {
        $type = preg_quote($type);
        return preg_match("/($type)/i", $_SERVER['HTTP_USER_AGENT']) ? true : false;
    }

    static function is_desktop()
    {
        return !static::is_mobile();
    }

    static function assign_settings()
    {
        $window = new JsWindowObject();
        $window->settings = static::$settings;
        return $window->script();
    }

}


