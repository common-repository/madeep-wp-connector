<?php

/**
 * @package Madeep WP Connector
 * @version 0.4.5
 */
/*
 * Plugin Name: Madeep WP Connector
 * Plugin URI: http://www.madeep.com/
 * Description: Madeep Booking Interface downloader
 * Version: 0.4.5
 * Author: Madeep
 * Author URI: http://www.madeep.com
 */

if (!function_exists('add_action')) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

define('Madeep_Name', 'Madeep WP Connector');
define('Madeep_Version', '0.4.5');
define('Madeep_Version_Wp_Version', '5.0');
define('Madeep_Dir', plugin_dir_path(__FILE__));
define('Madeep_Url', plugins_url('',dirname(__FILE__)) . '/'. strtolower(str_replace(' ', '-', Madeep_Name)).'/');

require_once( ABSPATH . WPINC . '/functions.php' );
require_once( Madeep_Dir . 'sources/Madeep.php' );

add_filter('cron_schedules', 'madeep_add_cron_interval');

function madeep_add_cron_interval($schedules) {
    $schedules['six_hours'] = array(
        'interval' => 60 * 60 * 6,
        'display' => esc_html__('Every six hours'),
    );

    return $schedules;
}

//$log = new log('MadeepBase');
add_action('init', array('Madeep', 'init'));
register_activation_hook(__FILE__, array('Madeep', 'createDB'));
register_activation_hook(__FILE__, array('Madeep', 'cronSet'));
register_deactivation_hook(__FILE__, array('Madeep', 'deleteDB'));
register_deactivation_hook(__FILE__, array('Madeep', 'cronUnset'));
add_action('madeep_refresh_cache', array('Madeep', 'cronExec'));
add_filter( 'plugin_row_meta', array('Madeep','rowMeta'), 10, 2 );
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( 'Madeep', 'pluginSettingsLink' ) );
