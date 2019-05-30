<?php
/**
 * Plugin Name:     Popup Zen
 * Plugin URI:      https://getpopupzen.com
 * Description:     Ridding the web of obnoxious popups, one site at a time.
 * Version:         0.0.3
 * Author:          Scott Bolinger
 * Author URI:      https://scottbolinger.com
 * Text Domain:     popup-zen
 *
 * @author          Scott Bolinger
 * @copyright       Copyright (c) Scott Bolinger 2019
 *
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'Popup_Zen' ) ) {

    /**
     * Main Popup_Zen class
     *
     * @since       0.1.0
     */
    class Popup_Zen {

        /**
         * @var         Popup_Zen $instance The one true Popup_Zen
         * @since       0.1.0
         */
        private static $instance;


        /**
         * Get active instance
         *
         * @access      public
         * @since       0.1.0
         * @return      self The one true Popup_Zen
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new Popup_Zen();
                self::$instance->setup_constants();
                self::$instance->includes();
                self::$instance->load_textdomain();
                self::$instance->hooks();
            }

            return self::$instance;
        }


        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       0.1.0
         * @return      void
         */
        private function setup_constants() {
            // Plugin version
            define( 'Popup_Zen_VER', '0.0.3' );

            // Plugin path
            define( 'Popup_Zen_DIR', plugin_dir_path( __FILE__ ) );

            // Plugin URL
            define( 'Popup_Zen_URL', plugin_dir_url( __FILE__ ) );
        }


        /**
         * Include necessary files
         *
         * @access      private
         * @since       0.1.0
         * @return      void
         */
        private function includes() {

            require_once Popup_Zen_DIR . 'includes/class-popup-zen-functions.php';
            require_once Popup_Zen_DIR . 'includes/class-popup-zen-ajax.php';

            if( is_admin() ) {
                require_once Popup_Zen_DIR . 'includes/class-popup-zen-admin.php';
            }
            
        }


        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       0.1.0
         * @return      void
         *
         *
         */
        private function hooks() {

        }


        /**
         * Internationalization
         *
         * @access      public
         * @since       0.1.0
         * @return      void
         */
        public function load_textdomain() {

            load_plugin_textdomain( 'popup-zen' );
            
        }

    }
} // End if class_exists check


/**
 * The main function responsible for returning the one true EDD_Metrics
 * instance to functions everywhere
 *
 * @since       0.1.0
 * @return      \Popup_Zen The one true Popup_Zen
 *
 */
function Popup_Zen_load() {
    return Popup_Zen::instance();
}
add_action( 'plugins_loaded', 'Popup_Zen_load' );


/**
 * The activation hook is called outside of the singleton because WordPress doesn't
 * register the call from within the class, since we are preferring the plugins_loaded
 * hook for compatibility, we also can't reference a function inside the plugin class
 * for the activation function. If you need an activation function, put it here.
 *
 * @since       0.1.0
 * @return      void
 */
function Popup_Zen_activation() {
    /* Activation functions here */

    // maybe set mailchimp API key from mc4wp plugin
    if( defined('MC4WP_VERSION') && empty( get_option( 'pzen_mc_api_key' ) ) ) {

        $mc4wp_option = get_option('mc4wp');

        if( $mc4wp_option && $mc4wp_option['api_key'] ) {
            update_option( 'pzen_mc_api_key', $mc4wp_option['api_key'] );
        }

    }
}
register_activation_hook( __FILE__, 'Popup_Zen_activation' );
