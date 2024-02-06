<?php
/*
Plugin Name: SA Directory
Plugin URI: https://stateaffairs.com/
Description: Directory of State Affairs Representatives
Version: 1.0.0
Author: Fetchly
Author URI: https://www.fetch.ly/
License: GPL2
*/

if (!defined('WPINC')) {
    die;
}

require_once plugin_dir_path(__FILE__) . 'includes/sa-directory-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-sa-directory-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-sa-directory-email-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/sa-directory-rewrite.php';

$currentURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://")
    . $_SERVER['HTTP_HOST']
    . $_SERVER['REQUEST_URI'];

if (class_exists('SaDirectoryAPI')) {
    $api = new SaDirectoryAPI('');
    $prodApi = new SaDirectoryAPI('');
} else {
    die('The SaDirectoryAPI class is not defined.');
}

if (class_exists('SaDirectoryEmailAPI')) {
    $emailApi = new SaDirectoryEmailAPI('');
} else {
    die('The SaDirectoryEmailAPI class is not defined.');
}




if (class_exists('SaDirectoryRewrite')) {
    new SaDirectoryRewrite();
} else {
    die('The SaDirectoryRewrite class is not defined.');
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'sa_directory_activation');
register_deactivation_hook(__FILE__, 'sa_directory_deactivation');

function sa_directory_activation()
{
    // Flush rewrite rules on plugin activation
    flush_rewrite_rules();
}

function sa_directory_deactivation()
{
    // Flush rewrite rules on plugin deactivation
    remove_rewrite_rules();
    flush_rewrite_rules();
}

// Function to remove rewrite rules

function remove_rewrite_rules()
{
    global $wp_rewrite;

    foreach ($wp_rewrite->rules as $rule => $rewrite) {
        if (false !== strpos($rule, 'pro/directories')) {
            unset($wp_rewrite->rules[$rule]);
        }
    }
}
