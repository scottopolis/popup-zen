<?php
/**
 * Popup Zen Functions
 * @since       0.0.1
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'Popup_Zen_Functions' ) ) {

    /**
     * Popup_Zen_Functions class
     *
     * @since       0.2.0
     */
    class Popup_Zen_Functions {

        /**
         * @var         Popup_Zen_Functions $instance The one true Popup_Zen_Functions
         * @since       0.2.0
         */
        private static $instance;
        public static $errorpath = '../php-error-log.php';
        public static $active = array();
        // sample: error_log("meta: " . $meta . "\r\n",3,self::$errorpath);

        /**
         * Get active instance
         *
         * @access      public
         * @since       0.2.0
         * @return      object self::$instance The one true Popup_Zen_Functions
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new Popup_Zen_Functions();
                self::$instance->hooks();
            }

            return self::$instance;
        }


        /**
         * Include necessary files
         *
         * @access      private
         * @since       0.2.0
         * @return      void
         */
        private function hooks() {

            add_action( 'wp', array( $this, 'get_active_items' ) );
            add_action( 'wp_footer', array( $this, 'maybe_display_items' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'scripts_styles' ) );
            add_action( 'pzen_email_form', array( $this, 'email_forms' ) );

            add_filter( 'pzen_classes', array( $this, 'add_pzen_classes'), 10, 2 );

            add_action( 'init', array( $this, 'preview_box' ) );

        }

        /**
         * Load scripts
         *
         * @since       0.1.0
         * @return      void
         */
        public function scripts_styles( $hook ) {

            if( ! empty( self::$active ) ) {

                // Use minified libraries if SCRIPT_DEBUG is turned off
                $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

                wp_enqueue_script( 'popup-zen-js', Popup_Zen_URL . 'assets/js/popup-zen-frontend' . $suffix . '.js', array( 'jquery' ), Popup_Zen_VER, true );
                wp_enqueue_style( 'popup-zen-css', Popup_Zen_URL . 'assets/css/popup-zen-frontend' . $suffix . '.css', null, Popup_Zen_VER );

                wp_localize_script( 'popup-zen-js', 'popupZenVars', $this->get_localized_vars() );

            }

        }

        /**
         * Return localized vars from settings
         *
         * @since       0.1.0
         * @return      array
         */
        public function get_localized_vars() {

            $array = array();

            $array['ajaxurl'] = admin_url( 'admin-ajax.php' );

            $array['pluginUrl'] = Popup_Zen_URL;

            $array['pzenNonce'] = wp_create_nonce('popup-zen-lite');

            $array['expires'] = '999'; // how long we should show this in num days

            $array['isMobile'] = wp_is_mobile();

            $array['disable_tracking'] = get_option('pzen_disable_tracking');

            // active notification IDs
            $array['active'] = self::$active;

            foreach (self::$active as $key => $value) {

                $type = get_post_meta( $value, 'pzen_type', 1 );
                
                $array[$value] = array( 
                    'type' => $type,
                    'emailProvider' => get_post_meta( $value, 'email_provider', 1 ),
                    'redirect' => get_post_meta( $value, 'pzen_redirect', 1 ),
                    'ckApi' => get_option( 'pzen_ck_api_key' ),
                    'ga_tracking' => get_option( 'pzen_ga_tracking' ),
                    'visitor' => get_post_meta($value, 'new_or_returning', 1),
                    'hideBtn' => get_post_meta($value, 'hide_btn', 1),
                    'placeholder' => get_post_meta($value, 'opt_in_placeholder', 1),
                    'confirmMsg' => get_post_meta($value, 'opt_in_confirmation', 1),
                    'emailErr' => __( 'Please enter a valid email address.', 'popup-zen-lite' ),
                    'display_when' => get_post_meta($value, 'display_when', 1),
                    'delay' => get_post_meta($value, 'scroll_delay', 1),
                    'showSettings' => get_post_meta($value, 'show_settings', 1),
                    'devices' => get_post_meta($value, 'pzen_devices', 1),
                    'bgColor' => get_post_meta($value, 'bg_color', 1),
                    'btnColor1' => get_post_meta($value, 'button_color1', 1),
                    'position' => get_post_meta($value, 'position', 1)
                );

                $array[$value] = apply_filters( 'pzen_localized_vars', $array[$value], $value );

            }

            return $array;
        }

        /**
         * Show the box
         *
         * @since       0.1.0
         * @return      string
         */
        public function maybe_display_items() { 

            // do checks for page conditionals, logged in, etc here
            // if any of the checks are true, we show it
            $post_id = get_queried_object_id();
            $logged_in = is_user_logged_in();

            foreach (self::$active as $key => $popup_id) {

                $show_it = false;

                $should_expire = get_post_meta( $popup_id, 'expiration', 1 );
                $expiration = get_post_meta( $popup_id, 'pzen_until_date', 1 );

                if( $should_expire === '1' && !empty( $expiration ) ) {
                    // check if we've passed expiration date
                    if( strtotime('now') >= strtotime( $expiration ) ) {
                        delete_post_meta( $popup_id, 'pzen_active' );
                        $show_it = true;
                    }
                }

                $logged_in_meta = get_post_meta( $popup_id, 'logged_in', 1 );

                // check logged in conditional
                if( $logged_in && $logged_in_meta === 'logged_out' || !$logged_in && $logged_in_meta === 'logged_in' )
                    continue;

                $show_on = get_post_meta( $popup_id, 'show_on', 1 );

                $show_on_pages = get_post_meta( $popup_id, 'show_on_pages', 1 );

                // check if we should show on current page by id
                if( $show_on === 'limited' && !empty( $show_on_pages ) ) {

                    // turn titles into array of ids
                    $arr = self::titles_to_ids( $show_on_pages );
                    
                    if( in_array( $post_id, $arr ) )
                        $show_it = true;

                } elseif ( $show_on === 'all' ) {

                    $show_it = true;

                }

                // if show_it is true, that means we display this notification. $popup_id is popup id
                $show_it = apply_filters( 'pzen_display_notification', $show_it, $popup_id, $post_id  );

                if( $show_it === false )
                    continue;

                self::$instance->show_box_types( $popup_id );
                
            }

        }

        /**
         * Show box based on what type it is
         *
         */
        public function show_box_types( $popup_id ) {

            $type = get_post_meta( $popup_id, 'pzen_type', 1 );

            if( $type === 'pzen-popup' ) {
                $this->display_popup( $popup_id );
            } elseif ( $type === 'footer-bar' ) {
                $this->display_footer_bar( $popup_id );
            } else {
                $this->display_notification_box( $popup_id );
            }

        }

        /**
         * Loop through items, store active items in self::$active[] for later use
         *
         * @since       0.1.0
         * @return      void
         */
        public function get_active_items() {

            $args = array( 'post_type' => 'popupzen', 'posts_per_page'=> -1 );
            // The Query
            $the_query = new WP_Query( $args );

            // The Loop
            if ( $the_query->have_posts() ) {

                while ( $the_query->have_posts() ) {
                    $the_query->the_post();
                    $id = get_the_id();

                    if( get_post_meta( $id, 'pzen_active', 1 ) != '1' )
                        continue;

                    self::$active[] = strval( $id );

                }

                /* Restore original Post Data */
                wp_reset_postdata();
            }

        }

        /**
         * Output notification markup
         *
         * @since       0.1.0
         * @param       int $id
         * @return      string
         */
        public function display_notification_box( $id ) {

            $type = get_post_meta( $id, 'pzen_type', 1 );
            $bg_color = esc_html( get_post_meta( $id, 'bg_color', 1 ) );
            $btn_color = esc_html( get_post_meta( $id, 'button_color1', 1 ) );
            ?>
            <style type="text/css">
            #pzen-<?php echo intval( $id ); ?>, #pzen-<?php echo intval( $id ); ?> a, #pzen-<?php echo intval( $id ); ?> i, #pzen-<?php echo intval( $id ); ?> .pzen-inside { color: <?php echo esc_html( get_post_meta( $id, 'text_color', 1 ) ); ?> !important; }
            #pzen-<?php echo intval( $id ); ?>, #pzen-<?php echo intval( $id ); ?> .pzen-row { background-color: <?php echo $bg_color; ?> }
            #pzen-<?php echo intval( $id ); ?> .pzen-email-btn, .pzen-floating-btn.pzen-btn-<?php echo intval( $id ); ?> { background-color: <?php echo $btn_color; ?>; }
            </style>

            <?php if( $type != 'pzen-banner' ) : ?>
            <div data-id="<?php echo esc_attr( $id ); ?>" class="pzen-floating-btn pzen-btn-<?php echo esc_attr( $id ); ?> <?php echo esc_attr( get_post_meta( $id, 'position', 1 ) ); ?>"><i class="icon icon-chat"></i></div>
            <?php endif; ?>

            <div id="pzen-<?php echo esc_attr( $id ); ?>" class="popup-zen-box pzen-hide <?php echo apply_filters( 'pzen_classes', '', $id ); ?>">

                <div class="pzen-inside">
                
                    <div class="pzen-close"><i class="icon icon-cancel"></i></div>

                    <?php do_action('pzen_above_content', $id); ?>

                    <div class="pzen-content">

                        <?php echo self::get_box_content( $id ); ?>

                    </div>

                    <div class="pzen-form">

                        <?php do_action('pzen_email_form', $id); ?>

                    </div>

                    <?php do_action('pzen_below_content', $id); ?>

                    <?php 

                    $powered_by = get_option( 'pzen_powered_by' );

                    if( empty( $powered_by ) ) : ?>
                        <span class="pzen-powered-by"><a href="https://getpopupzen.com" target="_blank">Popup Zen</a></span>
                    <?php endif; ?>

                </div>
 
            </div>
            <?php
        }

        /**
         * Output popup markup
         *
         * @since       1.0.0
         * @param       int $id
         * @return      string
         */
        public function display_popup( $id ) {

            $img_url = get_post_meta( $id, 'popup_image', 1 );
            $img = ( !empty( $img_url ) ? $img_url : Popup_Zen_URL . 'assets/img/ebook-mockup-300.png' );
            $bg_color = esc_html( get_post_meta( $id, 'bg_color', 1 ) );

            ?>

            <style type="text/css">
            #pzen-<?php echo intval( $id ); ?>, #pzen-<?php echo intval( $id ); ?> a, #pzen-<?php echo intval( $id ); ?> i, #pzen-<?php echo intval( $id ); ?> .pzen-inside, #pzen-<?php echo intval( $id ); ?> .pzen-title { color: <?php echo esc_html( get_post_meta( $id, 'text_color', 1 ) ); ?> !important; }
            #pzen-<?php echo intval( $id ); ?>.pzen-template-4 { border-top-color: <?php echo esc_html( get_post_meta( $id, 'button_color1', 1 ) ); ?>; }
            #pzen-<?php echo intval( $id ); ?>, #pzen-<?php echo intval( $id ); ?> .pzen-first-row { background-color: <?php echo $bg_color; ?> }
            #pzen-<?php echo intval( $id ); ?> .pzen-email-btn, #pzen-<?php echo intval( $id ); ?> .pzen-progress > span, #pzen-<?php echo intval( $id ); ?> .pzen-email-btn { background-color: <?php echo esc_html( get_post_meta( $id, 'button_color1', 1 ) ); ?> }
            <?php if( $template === 'pzen-template-5' ) : ?>
            #pzen-<?php echo intval( $id ); ?>.pzen-template-5 { background: url( <?php echo '"' . esc_url( $img ) . '"'; ?> ) no-repeat center; background-size: cover; }
            <?php endif; ?>
            <?php if( $template === 'pzen-template-6' && $bg_color ) : ?>
            #pzen-<?php echo intval( $id ); ?>.pzen-template-6 .pzen-email-row:before { border-color: <?php echo $bg_color; ?> transparent transparent transparent; }
            <?php endif; ?>
            </style>

            <div id="pzen-bd-<?php echo esc_attr( $id ); ?>" data-id="<?php echo esc_attr( $id ); ?>" class="pzen-backdrop pzen-hide"></div>
            
            <div id="pzen-<?php echo esc_attr( $id ); ?>" class="popup-zen-box pzen-hide <?php echo apply_filters( 'pzen_classes', '', $id ); ?>">

            <?php self::get_popup_template( $template, $id, $img ); ?>
 
            </div>
            <?php
        }

        /**
         * Returns the desired template markup
         *
         * @since       1.0.0
         * @param       int $id
         * @return      string
         */
        public function get_popup_template( $template, $id, $img ) {

            ?>

            <?php if( $template === 'pzen-template-progress' ) : ?>
                <div class="pzen-progress">
                  <span style="width: 50%"></span>
                </div>
                <span class="pzen-progress-text"><?php _e( '50% Complete', 'popup-zen-lite' ); ?></span>
            <?php endif; ?>

            <div class="popup-zen-box-inside">

                <?php if( $template === 'pzen-template-2' || $template === 'pzen-template-progress' ) : ?>
                    <div class="pzen-img-wrap">
                        <img src="<?php echo $img; ?>" class="popup-zen-box-image" />
                    </div>
                <?php endif; ?>
                
                <div class="pzen-close"><i class="icon icon-cancel"></i></div>

                <?php if( $template === 'pzen-template-3' ) : ?>
                    <div class="pzen-img-wrap">
                        <img src="<?php echo $img; ?>" class="popup-zen-box-image" />
                    </div>
                <?php endif; ?>

                <h2 class="pzen-title"><?php echo get_the_title( $id ); ?></h2>

                <?php do_action('pzen_above_content', $id); ?>

                <?php echo self::get_box_content( $id ); ?>

                <?php if( $template === 'pzen-template-4' ) : ?>
                    <div class="pzen-img-wrap">
                        <img src="<?php echo $img; ?>" class="popup-zen-box-image" />
                    </div>
                <?php endif; ?>

                <?php do_action('pzen_email_form', $id); ?>

                <?php do_action('pzen_below_content', $id); ?>

                <?php 

                $powered_by = get_option( 'pzen_powered_by' );

                if( empty( $powered_by ) ) : ?>
                    <span class="pzen-powered-by"><a href="https://getpopupzen.com" target="_blank">Popup Zen</a></span>
                <?php endif; ?>

            </div>

            <?php

        }

        /**
         * Output footer bar markup
         *
         * @since       1.0.1
         * @param       int $id
         * @return      string
         */
        public function display_footer_bar( $id ) {

            $img_url = get_post_meta( $id, 'popup_image', 1 );
            $img = ( !empty( $img_url ) ? $img_url : Popup_Zen_URL . 'assets/img/ebook-mockup-cropped.png' );
            $bg_color = esc_html( get_post_meta( $id, 'bg_color', 1 ) );
            $btn_color = esc_html( get_post_meta( $id, 'button_color1', 1 ) );
            ?>

            <style type="text/css">
            #pzen-<?php echo intval( $id ); ?>, #pzen-<?php echo intval( $id ); ?> a, #pzen-<?php echo intval( $id ); ?> i, #pzen-<?php echo intval( $id ); ?> .pzen-inside, #pzen-<?php echo intval( $id ); ?> .pzen-title { color: <?php echo esc_html( get_post_meta( $id, 'text_color', 1 ) ); ?> !important; }
            #pzen-<?php echo intval( $id ); ?>.pzen-template-4 { border-top-color: <?php echo $btn_color; ?>; }
            #pzen-<?php echo intval( $id ); ?> .pzen-email-btn { background-color: <?php echo $btn_color; ?>; }
            #pzen-<?php echo intval( $id ); ?>, #pzen-<?php echo intval( $id ); ?> .pzen-first-row { background-color: <?php echo $bg_color; ?> }
            </style>
            
            <div id="pzen-<?php echo esc_attr( $id ); ?>" class="popup-zen-box pzen-hide <?php echo apply_filters( 'pzen_classes', '', $id ); ?>">                

                <div class="pzen-inside">

                    <div class="pzen-img-wrap">
                        <img src="<?php echo $img; ?>" class="popup-zen-box-image" />
                    </div>
                
                    <div class="pzen-close"><i class="icon icon-cancel"></i></div>

                    <div class="pzen-content-wrap">

                        <?php do_action('pzen_above_content', $id); ?>

                        <h2 class="pzen-title"><?php echo get_the_title( $id ); ?></h2>

                        <?php echo self::get_box_content( $id ); ?>

                    </div>

                    <div class="pzen-row pzen-note-optin pzen-email-row pzen-hide">
                        <?php do_action('pzen_email_form', $id); ?>
                    </div>

                    <?php do_action('pzen_below_content', $id); ?>

                </div>
 
            </div>
            <?php
        }

        /**
         * Display box content
         * Content was added dynamically with Javscript prior to 1.1.0, now it's echoed with PHP to maximize compatibility.
         *
         * @since       1.1.0
         * @param       int $id
         * @return      string
         */
        public static function get_box_content( $id ) {

            $post_content = get_post($id);
            $content = $post_content->post_content;
            $content = apply_filters('pzen_content', $content, $id );
            return do_shortcode( $content );

        }

        /**
         * Handle different email provider forms
         *
         * @since       0.1.0
         * @param       int $id
         * @return      string
         */
        public function email_forms( $id ) {

            $provider = get_post_meta( $id, 'email_provider', 1 );

            $mc_list_id = esc_attr( get_post_meta( $id, 'mc_list_id', 1 ) );

            $mc_url = get_post_meta( $id, 'mc_url', 1 );

            $ac_list_id = esc_attr( get_post_meta( $id, 'ac_list_id', 1 ) );

            $drip_tags = esc_html( get_post_meta( $id, 'drip_tags', 1 ) );

            $submit_text = get_post_meta( $id, 'submit_text', 1 );
            $btn_text = ( !empty( $submit_text ) ? $submit_text : 'Send' );

            if( $mc_url && empty( $mc_list_id ) && is_user_logged_in() ) {
                echo 'Site admin: please update your MailChimp settings.';
            }

            if( $provider === 'custom' ) {

                echo get_post_meta( $id, 'custom_email_form', 1 );

            } else {

                if( $provider === 'ck' ) {
                    echo '<input type="hidden" class="ck-form-id" value="' . esc_attr( get_post_meta( $id, 'ck_id', 1 ) ) . '" />';
                } elseif( $provider === 'mc' && !empty( $mc_list_id ) ) {
                    echo '<input type="hidden" class="mc-list-id" value="' . $mc_list_id . '" />';
                    echo '<input type="hidden" class="mc-interests" value=' . json_encode( get_post_meta( $id, 'mc_interests', 1 ) ) . ' />';
                } elseif( $provider === 'mailpoet' ) {
                    echo '<input type="hidden" class="mailpoet-list-id" value="' . esc_attr( get_post_meta( $id, 'mailpoet_list_id', 1 ) ) . '" />';
                } elseif( $provider === 'ac' && !empty( $ac_list_id ) ) {
                    echo '<input type="hidden" class="ac-list-id" value="' . $ac_list_id . '" />';
                } elseif( $provider === 'drip' && !empty( $drip_tags ) ) {
                    echo '<input type="hidden" class="drip-tags" value="' . $drip_tags . '" />';
                }
                ?>
                <div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="pzen_hp" tabindex="-1" value=""></div>
                
                <?php self::name_row( $id ); ?>

                <input type="email" value="" id="<?php echo $id . '-email-input'; ?>" name="email" class="pzen-email-input <?php if( get_post_meta( $id, 'dont_show_name', 1 ) === '1' ) echo 'no-name'; ?>" placeholder="<?php _e( 'Enter email', 'popup-zen-lite' ); ?>" autocomplete="fake-value" autocapitalize="off" />
                <button class="pzen-email-btn"><?php echo $btn_text; ?></button>
                <?php
            }
        }

        /**
         * Email form name
         *
         * @since       1.0.0
         * @param       int $id
         * @return      string
         */
        public function name_row( $id ) {

            $type = get_post_meta( $id, 'pzen_type', 1 );

            if( get_post_meta( $id, 'dont_show_name', 1 ) === '1' || $type != 'pzen-popup' && $type != 'popout' )
                return;

            ?>
            <input type="text" placeholder="<?php echo esc_attr( get_post_meta( $id, 'name_placeholder', 1 ) ); ?>" class="pzen-name" />
            <?php
        }

        /**
         * Turn string of page titles into array of page IDs
         *
         * @param string $string
         * @return array
         */
        public static function titles_to_ids( $string ) {

            // explode into array
            $arr = explode( ",", $string );

            $newarr = array();

            foreach ($arr as $key => $value) {
                $title = trim( $value );
                $title = str_replace("’","'", $title );
                $title = str_replace( array("“", "”"),'"', $title );
                $page = get_page_by_title( $title );

                // cant get id of null
                if( !$page ) continue;

                $newarr[] = $page->ID;
            }

            return $newarr;

        }

        /**
         * Add extra classes to pzen element
         *
         * @param string $classes
         * @param int $id
         * @return string
         */
        public static function add_pzen_classes( $classes, $id ) {

            $type = get_post_meta( $id, 'pzen_type', 1 );
            if( $type === 'pzen-popup' ) {
                $classes .= 'pzen-popup';
            } else if( $type === 'pzen_header_bar' ) {
                $classes .= 'pzen-header-bar';
            } else if( $type === 'pzen_footer_bar' ) {
                $classes .= 'pzen-footer-bar';
            } else {
                $classes .= $type . ' ' . get_post_meta( $id, 'position', 1 );
            }

            $display_when = get_post_meta( $id, 'display_when', 1 );
            if( $display_when === 'exit' ) {
                $classes .= ' pzen-show-on-exit';
            }

            return $classes;
        }

        /**
         * Preview box on front end
         *
         */
        public static function preview_box() {

            if( isset( $_GET['pzen_preview'] ) ) {

                $popup_id = $_GET['pzen_preview'];

            } else {

                return;

            }

            self::$active[] = $popup_id;

            self::$instance->show_box_types( $popup_id );

        }

    }

    $popup_zen_functions = new Popup_Zen_Functions();
    $popup_zen_functions->instance();

} // end class_exists check