<?php
/**
 * Admin UI, register CPT and meta
 * @since       0.1.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'Popup_Zen_Full_Ajax' ) ) {

    /**
     * Popup_Zen_Full_Ajax class
     *
     * @since       0.2.0
     */
    class Popup_Zen_Full_Ajax extends Popup_Zen {

        /**
         * @var         Popup_Zen_Full_Ajax $instance The one true Popup_Zen_Full_Ajax
         * @since       0.2.0
         */
        private static $instance;
        public static $errorpath = '../php-error-log.php';
        // sample: error_log("meta: " . $meta . "\r\n",3,self::$errorpath);

        /**
         * Get active instance
         *
         * @access      public
         * @since       0.2.0
         * @return      object self::$instance The one true Popup_Zen_Full_Ajax
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new Popup_Zen_Full_Ajax();
                self::$instance->hooks();
            }

            return self::$instance;
        }


        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       0.2.0
         * @return      void
         *
         *
         */
        private function hooks() {

            add_action( 'wp_ajax_pzen_ajax_type_search', array( $this, 'type_search' ) );

            add_action( 'wp_ajax_pzen_get_mc_groups', array( $this, 'pzen_get_mc_groups' ) );
            add_action( 'wp_ajax_pzen_get_mc_group_interests', array( $this, 'pzen_get_mc_group_interests' ) );

        }

        /**
         * Used for autofilling post types via admin text field, using jQuery suggest
         *
         * @access      public
         * @since       0.1
         * @return      void
         */
        public function type_search() {

            $s = wp_unslash( $_GET['q'] );

            $comma = _x( ',', 'type delimiter' );
            if ( ',' !== $comma )
                $s = str_replace( $comma, ',', $s );
            if ( false !== strpos( $s, ',' ) ) {
                $s = explode( ',', $s );
                $s = $s[count( $s ) - 1];
            }
            $s = trim( $s );

            $term_search_min_chars = 2;

            $types = get_post_types( array( 'public' => 'true' ) );

            echo join( $types, "\n" );
            wp_die();
        }

        /**
         * Get MailChimp groups (pro only)
         * 
         *
         * @since       0.8.3
         * @return      void
         */
        public function pzen_get_mc_groups() {

            if( empty( $_GET['nonce'] ) || !wp_verify_nonce( $_GET['nonce'], 'class-popup-zen-admin.php' ) ) {
                wp_send_json_error('Verification failed.' );
            }

            // MailChimp API credentials
            $api_key = get_option('pzen_mc_api_key');
            $list_id = $_GET['list_id'];

            $transient = get_transient( $list_id );

            if( false === $transient ) {

                // MailChimp API URL
                $data_center = substr($api_key,strpos($api_key,'-')+1);
                $url = 'https://' . $data_center . '.api.mailchimp.com/3.0/lists/' . $list_id . '/interest-categories';

                $headers = array(
                    'Authorization' => 'Basic ' . base64_encode( 'user:' . $api_key ),
                    'Content-Type' => 'application/json'
                  );

                $response = wp_remote_get( $url, array(
                    'timeout' => 15,
                    'body' => null,
                    'headers' => $headers,
                    )
                );

                if ( is_wp_error( $response ) ) {
                   $error_message = $response->get_error_message();
                   wp_send_json_error( $error_message );
                } else {
                    $api_response = wp_remote_retrieve_body( $response );
                    set_transient( $list_id, $api_response, 15 * MINUTE_IN_SECONDS );
                    wp_send_json_success( $api_response );
                }

            } else {
                wp_send_json_success( $transient );
            }

        }

        /**
         * Get MailChimp group interests (pro only)
         * 
         *
         * @since       0.8.3
         * @return      void
         */
        public function pzen_get_mc_group_interests() {

            if( empty( $_GET['nonce'] ) || !wp_verify_nonce( $_GET['nonce'], 'class-popup-zen-admin.php' ) ) {
                wp_send_json_error('Verification failed.' );
            }

            // MailChimp API credentials
            $api_key = get_option('pzen_mc_api_key');
            $list_id = $_GET['list_id'];
            $group_id = $_GET['group_id'];

            // MailChimp API URL
            $data_center = substr($api_key,strpos($api_key,'-')+1);
            $url = 'https://' . $data_center . '.api.mailchimp.com/3.0/lists/' . $list_id . '/interest-categories/' . $group_id . '/interests';

            $headers = array(
                'Authorization' => 'Basic ' . base64_encode( 'user:' . $api_key ),
                'Content-Type' => 'application/json'
              );

            $response = wp_remote_get( $url, array(
                'timeout' => 15,
                'body' => null,
                'headers' => $headers,
                )
            );

            if ( is_wp_error( $response ) ) {
               $error_message = $response->get_error_message();
               wp_send_json_error( $error_message );
            } else {
                $api_response = wp_remote_retrieve_body( $response );
                wp_send_json_success( $api_response );
            }

        }

    }


    $Popup_Zen_Full_Ajax = new Popup_Zen_Full_Ajax();
    $Popup_Zen_Full_Ajax->instance();

} // end class_exists check