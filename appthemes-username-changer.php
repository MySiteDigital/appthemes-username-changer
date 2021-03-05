<?php
/*
Plugin Name: Appthemes Username Changer
Plugin URI: https://github.com/MySiteDigital/appthemes-username-changers
Description: Allows users to change thier usernames from their profile page on the frontend
Author: MySite Digital
Version: 0.1
Author URI: https://mysite.digital
Text Domain: example-plugin
*/

namespace MySiteDigital;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

final class AppthemesUsernameChanger
{

    /**
     * WPJobAlerts Constructor.
     */
    public function __construct()
    {
        $this->define_constants();
        $this->includes();
    }

    /*
	 * Define WPJobAlerts Constants.
	 */
    private function define_constants()
    {
        if (!defined('MDAUC_PLUGIN_PATH')) {
            define('MDAUC_PLUGIN_PATH', plugin_dir_path(__FILE__));
        }
        if (!defined('MDAUC_PLUGIN_URL')) {
            define('MDAUC_PLUGIN_URL', plugin_dir_url(__FILE__));
        }
    }

    /**
     * Include required core files used in admin and on the frontend.
     */
    public function includes()
    {
        include_once(MDAUC_PLUGIN_PATH . 'includes/class-md-user-profile-extensions.php');
    }
}

new AppthemesUsernameChanger();
