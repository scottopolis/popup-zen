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

            add_action( 'wp_footer', array( $this, 'preview_box' ) );

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

            $array['pzenNonce'] = wp_create_nonce('popup-zen');

            $array['expires'] = '999'; // how long we should show this in num days

            $array['isMobile'] = wp_is_mobile();

            $array['emailErr'] = __( 'Please enter a valid email address.', 'popup-zen' );

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
                    'email_label' => get_post_meta($value, 'email_label', 1),
                    'confirmMsg' => get_post_meta($value, 'opt_in_confirmation', 1),
                    'display_when' => get_post_meta($value, 'display_when', 1),
                    'delay' => get_post_meta($value, 'scroll_delay', 1),
                    'scrollPercent' => get_post_meta($value, 'page_scroll_percent', 1),
                    'showSettings' => get_post_meta($value, 'show_settings', 1),
                    'devices' => get_post_meta($value, 'pzen_devices', 1),
                    'bgColor' => get_post_meta($value, 'bg_color', 1),
                    'accentColor' => get_post_meta($value, 'accent_color', 1),
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
                $show_it = apply_filters( 'pzen_display_box', $show_it, $popup_id, $post_id  );

                if( $show_it === false )
                    continue;

                if( isset( $_GET['pzen_preview'] ) ) {
                    return;
                }

                $this->display_pzen_box( $popup_id );
                
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
        public function display_pzen_box( $id, $is_admin_customizer = false ) {

            $type = get_post_meta( $id, 'pzen_type', 1 );
            $bg_color = esc_html( get_post_meta( $id, 'bg_color', 1 ) );
            $accent_color = esc_html( get_post_meta( $id, 'accent_color', 1 ) );
            $btn_text_color = esc_html( get_post_meta( $id, 'btn_text_color', 1 ) );
            $image_padding = esc_html( get_post_meta( $id, 'image_padding', 1 ) );
            ?>
            <style type="text/css">
            #pzen-<?php echo intval( $id ); ?> .pzen-content, #pzen-<?php echo intval( $id ); ?> .pzen-title, #pzen-<?php echo intval( $id ); ?> label, #pzen-<?php echo intval( $id ); ?> input { color: <?php echo esc_html( get_post_meta( $id, 'text_color', 1 ) ); ?>; }
            #pzen-<?php echo intval( $id ); ?> input[type="text"], #pzen-<?php echo intval( $id ); ?> input[type="email"] { border-bottom-color: <?php echo esc_html( get_post_meta( $id, 'text_color', 1 ) ); ?>; }
            #pzen-<?php echo intval( $id ); ?> { background-color: <?php echo $bg_color; ?> }
            #pzen-<?php echo intval( $id ); ?> .pzen-btn, #pzen-<?php echo intval( $id ); ?> .pzen-btn:hover { background: <?php echo $accent_color; ?>; }
            #pzen-<?php echo intval( $id ); ?> .pzen-btn, #pzen-<?php echo intval( $id ); ?> .pzen-btn:hover { color: <?php echo $btn_text_color; ?>; }
            </style>
        
            <?php if( $type === 'pzen_popup' ) : ?>
                <div id="pzen-bd-<?php echo esc_attr( $id ); ?>" data-id="<?php echo esc_attr( $id ); ?>" class="pzen-backdrop pzen-hide"></div>
            <?php endif; ?>

            <div id="pzen-<?php echo esc_attr( $id ); ?>" class="popup-zen-box pzen-hide <?php echo apply_filters( 'pzen_classes', '', $id ); ?>">
                    
                <div class="pzen-collapse"><i class="icon icon-down-open"></i></div>

                <div class="pzen-close"><i class="icon icon-cancel"></i></div>

                <?php 

                $no_crop = get_post_meta( $id, 'image_no_crop', 1 );
                $src = get_post_meta( $id, 'pzen_image', 1 );

                if( !empty( $src ) && $no_crop ) {
                    echo '<div class="pzen-image no-crop"><img src="' . $src . '" class="popup-zen-image" /></div>';
                } else if( !empty( $src ) ) {
                    echo '<div class="pzen-image bg-cover" style="background-image: url(' . $src . ')"></div>';
                }

                ?>

                <?php do_action('pzen_above_content', $id); ?>

                <div class="pzen-inside">

                    <div class="pzen-content">

                        <h3 class="pzen-title"><?php echo get_post_meta( $id, 'pzen_title', 1 ); ?></h3>

                        <?php echo self::get_box_content( $id ); ?>

                        <button class="pzen-expand-btn pzen-btn"><?php echo get_post_meta( $id, 'expand_btn_text', 1 ); ?></button>

                    </div>

                    <div class="pzen-form">

                        <?php do_action('pzen_email_form', $id); ?>

                        <span id="pzen-err"></span>

                    </div>

                    <?php do_action('pzen_below_content', $id); ?>

                    <?php 

                    $powered_by = get_option( 'pzen_powered_by' );

                    if( empty( $powered_by ) ) : ?>
                        <div class="pzen-powered-by"><a href="https://getpopupzen.com" target="_blank">Powered by Popup Zen</a></div>
                    <?php endif; ?>

                </div>
 
            </div>
            <?php
        }

        /**
         * Display box content
         *
         * @since       1.1.0
         * @param       int $id
         * @return      string
         */
        public static function get_box_content( $id ) {

            $post_content = get_post_meta( $id, 'pzen_content', 1 );

            $content = apply_filters( 'wpautop', $post_content, $id );
            $content = do_shortcode( $content );

            return apply_filters( 'pzen_content', $content, $id );

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

            <span class="pzen-input pzen-email-input">
                <input class="pzen-field-animated <?php if( get_post_meta( $id, 'dont_show_name', 1 ) === '1' ) echo 'no-name'; ?>" id="<?php echo $id . '-email-input'; ?>" type="email" name="email"  autocomplete="fake-value" autocapitalize="off" />
                <label class="pzen-label-animated" for="email">
                    <span class="pzen-label-animated-content"><?php echo esc_attr( get_post_meta( $id, 'email_label', 1 ) ); ?></span>
                </label>
            </span>
            
            <button class="pzen-email-btn pzen-btn"><span><?php echo $btn_text; ?></span></button>
            <?php
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

            if( get_post_meta( $id, 'dont_show_name', 1 ) === '1' )
                return;

            ?>

            <span class="pzen-input">
                <input class="pzen-field-animated pzen-name" type="text" name="pzen-name" autocomplete="fake-value" autocapitalize="on" />
                <label class="pzen-label-animated" for="name">
                    <span class="pzen-label-animated-content"><?php echo esc_attr( get_post_meta( $id, 'name_label', 1 ) ); ?></span>
                </label>
            </span>

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
            if( $type === 'pzen_popup' ) {
                $classes .= 'pzen-popup';
            } else {
                $classes .= $type;
            }

            $src = get_post_meta( $id, 'pzen_image', 1 );

            if( empty( $src ) ) {
                $classes .= ' pzen-no-image';
            }

            $classes .= ' ' . get_post_meta( $id, 'position', 1 );

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

            self::$instance->display_pzen_box( $popup_id );

        }

    }

    $popup_zen_functions = new Popup_Zen_Functions();
    $popup_zen_functions->instance();

} // end class_exists check