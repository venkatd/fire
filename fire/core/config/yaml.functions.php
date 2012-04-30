<?php

/**
   * Spyc -- A Simple PHP YAML Class
   * @version 0.4.1
   * @author Chris Wanstrath <chris@ozmm.org>
   * @author Vlad Andersen <vlad@oneiros.ru>
   * @link http://spyc.sourceforge.net/
   * @copyright Copyright 2005-2006 Chris Wanstrath, 2006-2009 Vlad Andersen
   * @license http://www.opensource.org/licenses/mit-license.php MIT License
   * @package Spyc
   */

if (!function_exists('yaml_load')) {
  /**
   * Parses YAML to array.
   * @param string $string YAML string.
   * @return array
   */
  function yaml_load ($string) {
    return Spyc::YAMLLoadString($string);
  }
}

if (!function_exists('yaml_load_file')) {
  /**
   * Parses YAML to array.
   * @param string $file Path to YAML file.
   * @return array
   */
  function yaml_load_file ($file) {
    return Spyc::YAMLLoad($file);
  }
}
