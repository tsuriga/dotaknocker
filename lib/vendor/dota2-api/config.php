<?php

// dota2 api key (you can get_info it here - http://steamcommunity.com/dev/apikey)
$steamApiKey = file_get_contents('INSERT_STEAM_API_KEY_FILEPATH_HERE');
define ('API_KEY', $steamApiKey);

//The language to retrieve results in (see http://en.wikipedia.org/wiki/ISO_639-1 for the language codes (first two characters) and http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes for the country codes (last two characters))
define ('LANGUAGE', 'en_us');

/**
 * Basic class with system's configuration data
 */
class config {
    /**
     * Configuration data
     * @access private
     * @static
     * @var array
     */
    private static $_data = array(
        'db_user' => '',
        'db_pass' => '',
        'db_host' => '',
        'db_name' => '',
        'db_table_prefix' => ''
    );

    /**
     * Private construct to avoid object initializing
     * @access private
     */
    private function __construct() {}
    public static function init() {
        self::$_data['base_path'] = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'includes';

        /* Ignore the database connection as redundant for our use case
        $db = db::obtain(self::get('db_host'), self::get('db_user'), self::get('db_pass'), self::get('db_name'), self::get('db_table_prefix'));
        if (!$db->connect_pdo()) {
            var_dump($db->get_error());
            die();
        }
        */
    }
    /**
     * Get configuration parameter by key
     * @param string $key data-array key
     * @return null
     */
    public static function get($key) {
        if (isset(self::$_data[$key])) {
            return self::$_data[$key];
        }
        return null;
    }
}

config::init();

function addCustomIncludePath($path) {
    set_include_path(get_include_path() . PATH_SEPARATOR . $path);
}

// I tried a more sophisticated directory reader solution but
// this was the first thing that worked, I swear
addCustomIncludePath(__DIR__ . '/includes/data');
addCustomIncludePath(__DIR__ . '/includes/mappers');
addCustomIncludePath(__DIR__ . '/includes/models');
addCustomIncludePath(__DIR__ . '/includes/utils');

function dota_autoloader($class) {
    require_once "class.{$class}.php";
}

spl_autoload_register('dota_autoloader', true, true);
