<?php
/*
Plugin Name: wc-custom-file-approval
Plugin URI: http://ijasonwhite.uk/wp/wc-custom-file-approval
Description: wc-custom-file-approval
Author: Jason White
Version: 0.0.1
Author URI: http://jasonwhite.uk
*/
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}


foreach (glob(plugin_dir_path(__FILE__) . "glob/*.php") as $file) {
    if (
        (basename($file) != 'index.php') &&
        (basename($file) != 'settings.php')) {
        include $file;
    }

}
