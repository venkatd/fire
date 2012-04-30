<?php

require_once 'html.functions.php';

define('COREPATH', FIREPATH . 'core/');

function environment()
{
    if (getenv('environment')) {
        return getenv('environment');
    }
    else {
        $environment = $_SERVER['SERVER_NAME'];
        $parts = explode('.', $environment);
        $parts = array_diff($parts, array('www', 'com'));
        return array_shift($parts);
    }
}

function site_url($path = '')
{
    $protocol = 'http://';
    $host = $_SERVER['HTTP_HOST'];
    if ($path)
        return $protocol . $host . url($path);
    else
        return $protocol . $host;
}

function _load_core_component($name, $driver)
{
    require_once COREPATH . 'driver/component.class.php';
    require_once COREPATH . 'driver/driver.class.php';

    require_once COREPATH . "$name/$name.class.php";
    require_once COREPATH . "$name/{$name}driver.class.php";
    require_once COREPATH . "$name/drivers/{$driver}{$name}driver.class.php";
}

/**
 * @return Cache
 */
function _build_index_cache($path)
{
    _load_core_component('filerepository', 'local');
    _load_core_component('cache', 'storage');

    $storage = new FileRepository(array(
        'driver' => 'local',
        'path' => $path,
    ));

    $cache = new Cache(array(
        'driver' => 'storage',
        'storage' => $storage,
    ));

    return $cache;
}

/**
 * @return Factory
 */
function factory($environment = null)
{
    global $_factories;
    if (!$_factories)
        $_factories = array();

    if ($environment == null)
        $environment = environment();

    if (!defined('FIREPATH'))
        throw new Exception('You must define FIREPATH in your index.php file');

    if (!defined('APPPATH'))
        throw new Exception('You must define APPPATH in your index.php file');

    if (!isset($_factories[$environment])) {
        require_once COREPATH . 'index.class.php';
        require_once COREPATH . 'classloader.class.php';

        $index_cache = _build_index_cache(APPPATH . 'cache');

        $index = new Index(APPPATH, $index_cache);

        $class_loader = new ClassLoader($index);
        $class_loader->enable_autoload();
        $class_loader->register('index', $index);

        $config_source = new ConfigSource($index, $environment);

        $_factories[$environment] = new Factory($config_source, $class_loader);
    }

    return $_factories[$environment];
}

/**
 * @param $item_name
 * @param null $param1
 * @param null $param2
 * @return object
 */
function build($item_name, $param1 = null, $param2 = null)
{
    return call_user_func_array(array(factory(), 'build'), func_get_args());
}

/**
 * @return WhoWentOutApp
 */
function app()
{
    /* @var $_app FireApp */
    global $_app;

    if (!$_app) {
        $_app = factory()->build('app');
        $_app->enable_autoload();
    }

    return $_app;
}

/**
 * @return Database
 */
function db()
{
    return app()->database();
}

/**
 * @return FacebookAuth
 */
function auth()
{
    return factory()->build('auth');
}

/**
 * @return JsWindowObject
 */
function js()
{
    static $js = null;
    if (!$js && class_exists('JsWindowObject'))
        $js = new JsWindowObject();

    return $js;
}

function array_get($arr, $path)
{
    if (!$path)
        return null;

    $segments = is_array($path) ? $path : explode('.', $path);
    $cur =& $arr;
    foreach ($segments as $segment) {
        if (!isset($cur[$segment]))
            return null;

        $cur = $cur[$segment];
    }

    return $cur;
}

function array_set(&$arr, $path, $value)
{
    if (!$path)
        return null;

    $segments = is_array($path) ? $path : explode('.', $path);
    $cur =& $arr;
    foreach ($segments as $segment) {
        if (!isset($cur[$segment]))
            $cur[$segment] = array();
        $cur =& $cur[$segment];
    }
    $cur = $value;
}

function current_url()
{
    return isset($_SERVER['PATH_INFO'])
            ? substr($_SERVER['PATH_INFO'], 1)
            : '';
}

function route_uri_request()
{
    /* @var $router ActionRouter */
    $router = build('router');
    $router->route_request();
}

function show_404()
{
    print "<h1>404 page not found</h1>";
    exit;
}

function url($path)
{
    return $path == '/' ? '/'
                        : '/' . $path;
}

function is_active($path)
{
    $current_url = current_url();
    return string_starts_with($path, $current_url);
}

function conjunct($words, $join = 'and')
{
    $last_word = array_pop($words);
    $comma = count($words) > 2 ? ',' : '';
    return empty($words) ? $last_word
            : implode(', ', $words) . "$comma $join $last_word";
}

function check_required_options($options_to_check, $required_options)
{
    $missing = array();

    foreach ($required_options as $key) {
        if (!isset($options_to_check[$key])) {
            $missing[] = $key;
        }
    }

    if (count($missing) > 0) {
        throw new Exception("You are missing " . conjunct($missing) . '.');
    }
}

function redirect($destination)
{
    $url = site_url($destination);
    header("Location: $url");
    exit;
}

function run_command($args)
{
    $command_name = isset($args[1]) ? $args[1] : 'empty';
    $args = array_slice($args, 2);

    /* @var $command Command */
    $command = app()->class_loader()->init_subclass('Command', $command_name);
    if ($command)
        $command->run($args);
    else
        print "The command '$command_name' doesn't exist.";
}

function string_ends_with($end_of_string, $string)
{
    return substr($string, -strlen($end_of_string)) === $end_of_string;
}

function string_starts_with($start_of_string, $source)
{
    return strncmp($source, $start_of_string, strlen($start_of_string)) == 0;
}

function string_after_first($needle, $haystack)
{
    $pos = strpos($haystack, $needle);
    if ($pos === FALSE) {
        return FALSE;
    } else {
        return substr($haystack, $pos + strlen($needle));
    }
}

function string_before_first($needle, $haystack)
{
    $pos = strpos($haystack, $needle);
    if ($pos === FALSE) {
        return FALSE;
    } else {
        return substr($haystack, 0, $pos);
    }
}

function string_after_last($needle, $haystack)
{
    $pos = strrpos($haystack, $needle);
    if ($pos === FALSE) {
        return FALSE;
    } else {
        return substr($haystack, $pos + strlen($needle));
    }
}

function string_before_last($needle, $haystack)
{
    $pos = strrpos($haystack, $needle);
    if ($pos === FALSE) {
        return FALSE;
    } else {
        return substr($haystack, 0, $pos);
    }
}
