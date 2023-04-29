<?php
/*
Plugin Name: Search Field for Gravity Forms
Plugin URI: https://www.wpsunshine.com/plugins/gravity-forms-search-field
Description: Searches selected post types after a user types, displaying results below field.
Version: 1.1.1
Author: WP Sunshine
Author URI: https://www.wpsunshine.com
Text Domain: gravityforms-search
*/

define( 'WPSUNSHINE_GF_SEARCH_VERSION', '1.1.1' );
define( 'WPSUNSHINE_GF_SEARCH_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPSUNSHINE_GF_SEARCH_URL', plugin_dir_url( __FILE__ ) );

add_action( 'gform_loaded', array( 'WPSunshine_GF_Search_Field_Bootstrap', 'load' ), 5 );
class WPSunshine_GF_Search_Field_Bootstrap {

    public static function load() {

        if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
            return;
        }

        require_once( 'class-gfwpsunshinesearchfieldaddon.php' );

        GFAddOn::register( 'WPSunshine_Search_Field_Addon' );

    }

}
