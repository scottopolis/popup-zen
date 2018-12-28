<?php
/**
 * Admin UI, register CPT and meta
 * @since       0.1.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

if( !class_exists( 'Popup_Zen_Admin' ) ) {

    /**
     * Popup_Zen_Admin class
     *
     * @since       0.2.0
     */
    class Popup_Zen_Admin extends Popup_Zen {

        /**
         * @var         Popup_Zen_Admin $instance The one true Popup_Zen_Admin
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
         * @return      self self::$instance The one true Popup_Zen_Admin
         */
        public static function instance() {
            if( !self::$instance ) {
                self::$instance = new Popup_Zen_Admin();
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

            add_action( 'admin_menu', array( $this, 'settings_page' ) );
            add_action( 'init', array( $this, 'register_cpt' ) );
            add_action( 'save_post', array( $this, 'save_settings' ), 10, 2 );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
            add_filter( 'manage_edit-popupzen_columns', array( $this, 'notification_columns' ) );
            add_action( 'manage_popupzen_posts_custom_column', array( $this, 'custom_columns' ), 10, 2 );
            add_action( 'transition_post_status',  array( $this, 'save_default_meta' ), 10, 3 );

            // add_action( 'admin_init', array( $this, 'maybe_show_upgrade_link' ) );

            // add_action( 'pzen_type_settings', array( $this, 'type_upsell' ), 99 );

            add_action( 'post_submitbox_minor_actions', array( $this, 'preview_link' ) );

            add_filter('page_row_actions', array( $this, 'row_actions' ), 10, 2 );

            add_action( 'edit_form_after_title', array( $this, 'type_settings' ) );

            // full version settings
            if( defined( 'Popup_Zen_Full' ) ) {
                add_action( 'pzen_mc_settings', array( $this, 'mc_groups' ) );
            }

        }

        /**
         * Show or hide upgrade link
         *
         * @access      public
         * @since       1.3.1
         * @return      void
         */
        public function maybe_show_upgrade_link() {

            if( !is_plugin_active('hollerbox-pro/holler-box-pro.php') ) {
                add_filter( 'plugin_action_links_holler-box/holler-box.php', array( $this, 'pzen_plugin_links' ) );
            }

        }

        /**
         * Scripts and styles
         *
         * @access      public
         * @since       0.1
         * @return      void
         */
        public function enqueue_scripts() {

            // Use minified libraries if SCRIPT_DEBUG is turned off
            $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

            // Date picker: https://gist.github.com/slushman/8fd9e1cc8161c395ec5b

            // Color picker: https://make.wordpress.org/core/2012/11/30/new-color-picker-in-wp-3-5/
            wp_enqueue_style( 'popup-zen-admin', Popup_Zen_URL . 'assets/css/popup-zen-admin' . $suffix . '.css', array( 'wp-color-picker' ), Popup_Zen_VER );

            wp_enqueue_script( 'popup-zen-admin-lite', Popup_Zen_URL . 'assets/js/popup-zen-admin-lite' . $suffix . '.js', array( 'wp-color-picker', 'jquery-ui-datepicker', 'suggest' ), Popup_Zen_VER, true );

            // load full script for extra features
            if( !defined( 'Popup_Zen_Full' ) )
                return;

            wp_enqueue_script( 'popup-zen-admin-full', Popup_Zen_URL . 'assets/js/popup-zen-admin-full' . $suffix . '.js', array( 'popup-zen-admin-lite' ), Popup_Zen_VER, true );

            $screen = get_current_screen();

            if( $screen->base === 'post' && $screen->post_type === 'popupzen' ) {

                global $post;

                wp_localize_script( 'popup-zen-admin-full', 'pzenAdmin', array(
                    'post_id' => $post->ID,
                    'current_mc_group' => get_post_meta( $post->ID, 'mc_groups', 1 ),
                    'current_mc_interests' => get_post_meta( $post->ID, 'mc_interests', 1 )
                    )
                );

            }
            
        }

        /**
         * Add settings
         *
         * @access      public
         * @since       0.1
         */
        public function settings_page() {

            add_submenu_page( 'edit.php?post_type=popupzen', 'Popup Zen Settings', 'Settings', 'manage_options', 'popupzen', array( $this, 'render_settings') );
            
        }

        /**
         * Add settings
         *
         * @access      public
         * @since       0.1
         */
        public function render_settings() {

            if( isset( $_POST['pzen_ck_api_key'] ) ) {
                update_option( 'pzen_ck_api_key', sanitize_text_field( $_POST['pzen_ck_api_key'] ) );
            }

            if( isset( $_POST['pzen_mc_api_key'] ) ) {
                update_option( 'pzen_mc_api_key', sanitize_text_field( $_POST['pzen_mc_api_key'] ) );
            }

            if( isset( $_POST['pzen_ac_api_key'] ) ) {
                update_option( 'pzen_ac_api_key', sanitize_text_field( $_POST['pzen_ac_api_key'] ) );
            }

            if( isset( $_POST['pzen_ac_url'] ) ) {
                update_option( 'pzen_ac_url', sanitize_text_field( $_POST['pzen_ac_url'] ) );
            }

            if( isset( $_POST['pzen_mc_status'] ) ) {
                update_option( 'pzen_mc_status', sanitize_text_field( $_POST['pzen_mc_status'] ) );
            } elseif( !empty( $_POST ) && empty( $_POST['pzen_mc_status'] )  ) {
                delete_option( 'pzen_mc_status' );
            }

            if( isset( $_POST['pzen_powered_by'] ) ) {
                update_option( 'pzen_powered_by', sanitize_text_field( $_POST['pzen_powered_by'] ) );
            } elseif( !empty( $_POST ) && empty( $_POST['pzen_powered_by'] )  ) {
                delete_option( 'pzen_powered_by' );
            }

            if( isset( $_POST['pzen_ga_tracking'] ) ) {
                update_option( 'pzen_ga_tracking', sanitize_text_field( $_POST['pzen_ga_tracking'] ) );
            } elseif( !empty( $_POST ) && empty( $_POST['pzen_ga_tracking'] )  ) {
                delete_option( 'pzen_ga_tracking' );
            }

            ?>
            <div id="pzen-settings-wrap" class="wrap">          

            <h2><?php _e('Settings', 'popup-zen'); ?></h2>

            <form method="post" action="edit.php?post_type=popupzen&page=popupzen">

                <h3><?php _e('Email Settings', 'popup-zen'); ?></h3>

                <p><?php _e('If you are using ConvertKit, entery your API key. It can be found on your <a href="https://app.convertkit.com/account/edit#account_info" target="_blank">account info page.</a>', 'popup-zen'); ?></p>
                
                <input id="pzen_ck_api_key" name="pzen_ck_api_key" value="<?php echo esc_html( get_option( 'pzen_ck_api_key' ) ); ?>" placeholder="ConvertKit API key" type="text" size="50" />

                <p><?php _e('If you are using Active Campaign, enter your url and API key. It can be found under My Settings -> Developer.', 'popup-zen'); ?></p>

                <input id="pzen_ac_url" name="pzen_ac_url" value="<?php echo esc_html( get_option( 'pzen_ac_url' ) ); ?>" placeholder="Active Campaign URL" type="text" size="50" /><br/>

                <input id="pzen_ac_api_key" name="pzen_ac_api_key" value="<?php echo esc_html( get_option( 'pzen_ac_api_key' ) ); ?>" placeholder="Active Campaign API key" type="password" size="50" /><br/>

                <p><?php _e('If you are using MailChimp, enter your API key. It can be found under Account -> Extras -> API Keys.', 'popup-zen'); ?></p>
                
                <input id="pzen_mc_api_key" name="pzen_mc_api_key" value="<?php echo esc_html( get_option( 'pzen_mc_api_key' ) ); ?>" placeholder="MailChimp API key" type="text" size="50" /><br/>

                <p>
                    <input type="checkbox" id="pzen_mc_status" name="pzen_mc_status" value="1" <?php checked('1', get_option( 'pzen_mc_status' ), true); ?> />
                    <?php _e( 'Disable MailChimp double-opt in? Check to subscribe users to your list without confirmation. If checked, MailChimp will not send a final welcome email.', 'popup-zen' ); ?>
                </p>

                <h3><?php _e('Google Analytics Tracking', 'popup-zen'); ?></h3>

                <p>
                    <input type="checkbox" id="pzen_ga_tracking" name="pzen_ga_tracking" value="1" <?php checked('1', get_option( 'pzen_ga_tracking' ), true); ?> />
                    <?php _e( 'Track popup views with Google Analytics? Must have <a href="https://kinsta.com/blog/add-google-analytics-to-wordpress/" target="_blank">GA tracking code installed.</a>', 'popup-zen' ); ?>
                </p>

                <h3><?php _e('Miscellaneous', 'popup-zen'); ?></h3>

                <p>
                    <input type="checkbox" id="pzen_powered_by" name="pzen_powered_by" value="1" <?php checked('1', get_option( 'pzen_powered_by' ), true); ?> />
                    <?php _e( 'Hide attribution links', 'popup-zen' ); ?>
                </p>

                <?php do_action( 'pzen_settings_page' ); ?>

            <?php submit_button(); ?>

            </form>

            </div>
            <?php
            
        }

        /**
         * Add columns
         *
         * @access      public
         * @since       0.1
         * @param       array $columns
         * @return      array
         */
        public function notification_columns( $columns ) {

            $date = $columns['date'];
            unset($columns['date']);

            $columns["conversions"] = "Conversions";
            
            $columns["active"] = "Active";
            $columns['date'] = $date;

            // remove wp seo columns
            unset( $columns['wpseo-score'] );
            unset( $columns['wpseo-title'] );
            unset( $columns['wpseo-metadesc'] );
            unset( $columns['wpseo-focuskw'] );
            unset( $columns['wpseo-score-readability'] );
            unset( $columns['wpseo-links'] );

            return $columns;
        }

        /**
         * Column content
         *
         * @access      public
         * @since       0.1
         * @param       string $column
         * @param       int $post_id
         * @return      void
         */
        public function custom_columns( $column, $post_id ) {

            $conversions = get_post_meta( $post_id, 'pzen_conversions', 1);

            switch ( $column ) {
                case 'conversions':
                    echo $conversions;
                    break;
                case 'active':
                    echo '<label class="pzen-switch"><input data-id="' . $post_id . '" type="checkbox" value="1" ' . checked(1, get_post_meta( $post_id, 'pzen_active', true ), false) . ' /><div class="pzen-slider pzen-round"></div></label>';
                    break;
            }

        }

        // Register Popup Zen post type
        public function register_cpt() {

            $labels = array(
                'name'              => __( 'Popup Zen', 'popup-zen' ),
                'singular_name'     => __( 'Popup Zen', 'popup-zen' ),
                'menu_name'         => __( 'Popup Zen', 'popup-zen' ),
                'name_admin_bar'        => __( 'Popup Zen', 'popup-zen' ),
                'add_new'           => __( 'Add New', 'popup-zen' ),
                'add_new_item'      => __( 'Add New Popup', 'popup-zen' ),
                'new_item'          => __( 'New Popup', 'popup-zen' ),
                'edit_item'         => __( 'Edit Popup', 'popup-zen' ),
                'view_item'         => __( 'View Popup', 'popup-zen' ),
                'all_items'         => __( 'All Popups', 'popup-zen' ),
                'search_items'      => __( 'Search Popups', 'popup-zen' ),
                'parent_item_colon' => __( 'Parent Popups:', 'popup-zen' ),
                'not_found'         => __( 'No Popups found.', 'popup-zen' ),
                'not_found_in_trash' => __( 'No Popups found in Trash.', 'popup-zen' )
            );

            $args = array(
                'labels'                => $labels,
                'public'                => true,
                'publicly_queryable' => false,
                'show_ui'           => true,
                'show_in_nav_menus' => false,
                'show_in_menu'      => true,
                'show_in_rest'      => false,
                'query_var'         => true,
                'capability_type'   => 'post',
                'has_archive'       => true,
                'hierarchical'      => true,
                //'menu_position'     => 50,
                'menu_icon'         => 'dashicons-email',
                'supports'          => array( 'title' ),
                'show_in_customizer' => false,
                'register_meta_box_cb' => array( $this, 'notification_meta_boxes' )
            );

            register_post_type( 'popupzen', $args );
        }

        /**
         * Add Meta Box
         *
         * @since     0.1
         */
        public function notification_meta_boxes() {

            add_meta_box(
                'display_meta_box',
                __( 'Display', 'popup-zen' ),
                array( $this, 'display_meta_box_callback' ),
                'popupzen',
                'normal',
                'high'
            );

            add_meta_box(
                'settings_meta_box',
                __( 'Advanced Settings', 'popup-zen' ),
                array( $this, 'settings_meta_box_callback' ),
                'popupzen',
                'normal',
                'high'
            );

        }

        public function type_settings() {

            global $post;

            if( $post->post_type != 'popupzen' )
                return;
            
            ?>
            
            <div class="postbox" style="margin-top:15px">
                <div class="inside">

                    <h4>
                        <label for="type"><?php _e( 'Choose a Popup Zen Type' ); ?></label>
                    </h4>
                    <p>

                        <label class="pzen-radio-withimage">
                            <span class="text">Header Bar</span>
                            <img src="<?php echo Popup_Zen_URL . 'assets/img/header-bar-icon.png'; ?>" class="pzen-radio-image" />
                            <input type="radio" name="pzen_type" value="header_bar" <?php checked( "header_bar", get_post_meta( $post->ID, 'pzen_type', true ) ); ?> />
                        </label>

                        <label class="pzen-radio-withimage">
                            <span class="text">Small Box</span>
                            <img src="<?php echo Popup_Zen_URL . 'assets/img/small-box-icon.png'; ?>" class="pzen-radio-image" />
                            <input type="radio" name="pzen_type" value="notification" <?php checked( "notification", get_post_meta( $post->ID, 'pzen_type', true ) ); ?> />
                        </label>

                        <label class="pzen-radio-withimage">
                            <span class="text">Footer Bar</span>
                            <img src="<?php echo Popup_Zen_URL . 'assets/img/footer-bar-icon.png'; ?>" class="pzen-radio-image" />
                            <input type="radio" name="pzen_type" value="footer_bar" <?php checked( "footer_bar", get_post_meta( $post->ID, 'pzen_type', true ) ); ?> />
                        </label>

                        <label class="pzen-radio-withimage">
                            <span class="text">Popup Link</span>
                            <img src="<?php echo Popup_Zen_URL . 'assets/img/popup-icon.png'; ?>" class="pzen-radio-image" />
                            <input type="radio" name="pzen_type" value="popup_link" <?php checked( "popup_link", get_post_meta( $post->ID, 'pzen_type', true ) ); ?> />
                        </label>

                        <?php do_action('pzen_type_settings', $post->ID); ?>
                    </p>

                    <div id="popup-options" style="display:block">
                        <h3>Popup Options</h3>
                        <p>Options for each type dynamically show here.</p>
                    </div>
                </div>
            </div>

            <?php
        }

        /**
         * Display upsell text if license is missing
         *
         * @since     1.0.0
         * @param     WP_Post $post
         */
        public function type_upsell() {

            $license_key = get_option( 'pzen_pro_edd_license' );
            if( $license_key )
                return;

            ?>
            <p style="clear:both;"><small><a href="https://getpopupzen.com/pro?utm_source=template_settings&utm_medium=link&utm_campaign=pzen_settings" target="_blank" style="color:#999">Get banners, sale notification popups, and more with Pro</a></small></p>
            <?php
        }

        /**
         * Add preview link to submit box
         *
         */
        public function preview_link( $post ) {

            $status = $post->post_status;
            $type = $post->post_type;

            if( $type != 'popupzen' )
                return;

            if( $status === 'draft' || $status === 'publish' ) {
                echo '<a href="' . home_url() . '?pzen_preview=' . $post->ID . '" target="_blank" class="button">Preview Box</a>';
            }

        }

        /**
         * Add preview link to row actions
         *
         */
        public function row_actions( $actions, $post ) {

            if ( $post->post_type === "popupzen" ) {

                $actions['pzen_preview'] = '<a href="' . home_url() . '?pzen_preview=' . $post->ID . '" target="_blank">Preview</a>';

            }

            return $actions;

        }

        /**
         * Display appearance meta box
         *
         * @since     0.1
         * @param     WP_Post $post
         */
        public function display_meta_box_callback( $post ) {

            ?>

            <?php wp_nonce_field( basename( __FILE__ ), 'popupzen_meta_box_nonce' ); ?>

            

            <div class="pzen-section" id="position-settings">

                <h4>
                    <label for="position"><?php _e( 'Position' ); ?></label>
                </h4>

                <input type="radio" name="position" value="pzen-bottomright" <?php checked( "pzen-bottomright", get_post_meta( $post->ID, 'position', true ) ); ?> />
                <label>Bottom Right</label>

                <input type="radio" name="position" value="pzen-bottomleft" <?php checked( "pzen-bottomleft", get_post_meta( $post->ID, 'position', 1 ) ); ?> />
                <label>Bottom Left</label>

                <input type="radio" name="position" value="pzen-topright" <?php checked( "pzen-topright", get_post_meta( $post->ID, 'position', 1 ) ); ?> />
                <label>Top Right</label>

                <input type="radio" name="position" value="pzen-topleft" <?php checked( "pzen-topleft", get_post_meta( $post->ID, 'position', 1 ) ); ?> />
                <label>Top Left</label>

                <?php do_action('pzen_position_settings', $post->ID); ?>
            </div>

            <?php do_action('pzen_after_position_settings', $post->ID); ?>

            <div class="pzen-section" id="popup-options">

                <h4>
                    <label for="position"><?php _e( 'Popup Options' ); ?></label>
                </h4>
                
                <p>
                    <?php _e( 'Upload a Custom Image', 'popup-zen' ); ?>
                </p>
                
                <img src="<?php echo get_post_meta( $post->ID, 'popup_image', 1 ); ?>" class="pzen-popup-image" />

                <input id="pzen-image-url" size="50" type="text" name="popup_image" value="<?php echo get_post_meta( $post->ID, 'popup_image', 1 ); ?>" />
                <input id="pzen-upload-btn" type="button" class="button" value="Upload Image" />

            </div>

            <div class="pzen-section" id="box-colors">
                
                <div id="send-btn-color">
                    <p><?php _e( 'Accent color', 'popup-zen' ); ?></p>
                    <input type="text" name="button_color1" value="<?php echo esc_html( get_post_meta( $post->ID, 'button_color1', true ) ); ?>" class="pzen-colors" data-default-color="#1191cb" />
                </div>
                
                <p><?php _e( 'Background color', 'popup-zen' ); ?></p>
                <input type="text" name="bg_color" value="<?php echo esc_html( get_post_meta( $post->ID, 'bg_color', true ) ); ?>" class="pzen-colors" data-default-color="#ffffff" />
                
                <p><?php _e( 'Text color', 'popup-zen' ); ?></p>
                <input type="text" name="text_color" value="<?php echo esc_html( get_post_meta( $post->ID, 'text_color', true ) ); ?>" class="pzen-colors" data-default-color="#333333" />

            </div>

            <div class="pzen-section noborder">

                <div id="show-email-options">

                    <h4>
                        <label for="position"><?php _e( 'Email Provider' ); ?></label>
                    </h4>

                    <select name="email_provider">

                        <option value="default" <?php selected( get_post_meta( $post->ID, 'email_provider', true ), "default"); ?> >
                            <?php _e( 'None', 'popup-zen' ); ?>
                        </option>

                        <option value="ck" <?php selected( get_post_meta( $post->ID, 'email_provider', true ), "ck"); ?> >
                            <?php _e( 'ConvertKit', 'popup-zen' ); ?>
                        </option>

                        <option value="mc" <?php selected( get_post_meta( $post->ID, 'email_provider', true ), "mc"); ?> >
                            <?php _e( 'MailChimp', 'popup-zen' ); ?>
                        </option>

                        <option value="ac" <?php selected( get_post_meta( $post->ID, 'email_provider', true ), "ac"); ?> >
                            <?php _e( 'Active Campaign', 'popup-zen' ); ?>
                        </option>

                        <option value="drip" <?php selected( get_post_meta( $post->ID, 'email_provider', true ), "drip"); ?> >
                            <?php _e( 'Drip', 'popup-zen' ); ?>
                        </option>

                        <?php if( class_exists('\MailPoet\API\API') ) : ?>

                        <option value="mailpoet" <?php selected( get_post_meta( $post->ID, 'email_provider', true ), "mailpoet"); ?> >
                            <?php _e( 'MailPoet', 'popup-zen' ); ?>
                        </option>

                        <?php endif; ?>

                        <option value="custom" <?php selected( get_post_meta( $post->ID, 'email_provider', true ), "custom"); ?> >
                            <?php _e( 'Custom', 'popup-zen' ); ?>
                        </option>

                    </select>

                    <div id="drip-fields">
                        <?php _e( 'Tags (comma separated)', 'popup-zen' ); ?>
                        <input id="drip_tags" name="drip_tags" class="widefat" value="<?php echo get_post_meta( $post->ID, 'drip_tags', 1 ); ?>" placeholder="Tag, Tag2" type="text" />
                    </div>

                    <?php do_action( 'pzen_below_provider_select', $post->ID ); ?>

                    <p id="convertkit-fields">
                        <?php _e( 'ConvertKit List ID, <a href=
                        "http://popupzen.helpscoutdocs.com/article/6-convertkit-integration" target="_blank">click for help.</a> <em>*required</em>', 'popup-zen' ); ?>
                        <input id="ck_id" name="ck_id" class="widefat" value="<?php echo get_post_meta( $post->ID, 'ck_id', 1 ); ?>" placeholder="ConvertKit list ID" type="text" />
                    </p>
                    
                    <div id="mailchimp-fields">
                        <p><strong><?php _e( 'MailChimp List *required', 'popup-zen' ); ?></strong></p>

                            <?php

                            $lists = self::get_mc_lists(); 

                            if( is_array($lists) && !empty( $lists ) ) :

                                echo '<select name="mc_list_id">';

                                foreach ($lists as $list) {
                                    echo '<option value="' . $list["id"] . '"' . selected( get_post_meta( $post->ID, "mc_list_id", 1 ), $list["id"] ) . '>';
                                    echo $list['name'];
                                    echo '</option>';
                                }

                                echo '</select>';

                            else:

                                echo '<p style="color:red">There was a problem getting your lists. Please check your MailChimp API key in the Popup Zen settings.</p>';

                            endif;

                            // echo apply_filters( 'pzen_mc_upsell', '<small>Want MailChimp groups and interests? <a href="https://getpopupzen.com/pro?utm_source=mc_upsell&utm_medium=link&utm_campaign=pzen_settings" target="_blank">Get Popup Zen Pro.</a></small>' );

                            do_action( 'pzen_mc_settings', $post->ID );

                            ?>

                    </div>

                    <div id="ac-fields">
                        <p><strong><?php _e( 'Active Campaign List *required', 'popup-zen' ); ?></strong></p>

                            <?php

                            $lists = self::get_ac_lists();

                            if( is_array($lists) && !empty( $lists ) ) :

                                echo '<select name="ac_list_id">';

                                foreach ($lists as $list) {
                                    echo '<option value="' . $list["id"] . '"' . selected( get_post_meta( $post->ID, "ac_list_id", 1 ), $list["id"] ) . '>';
                                    echo $list['name'];
                                    echo '</option>';
                                }

                                echo '</select>';

                            else:

                                echo '<p style="color:red">There was a problem getting your lists. Please check your Active Campaign API key in the Popup Zen settings.</p>';

                            endif;

                            ?>

                    </div>
                    
                    <?php if( class_exists('\MailPoet\API\API') ) : ?>

                        <div id="mailpoet-fields">

                        <p><strong><?php _e( 'MailPoet List <em>*required</em>', 'popup-zen' ); ?></strong></p>

                        <select name="mailpoet_list_id" id="mailpoet_list_id">
                    
                        <?php

                        $subscription_lists = \MailPoet\API\API::MP('v1')->getLists();

                        if( !empty( $subscription_lists ) ) :

                            foreach ($subscription_lists as $list) {
                                echo '<option value="' . $list['id'] . '"' . selected( get_post_meta( $post->ID, 'mailpoet_list_id', 1 ), $list['id'] ) . '">';
                                echo $list['name'];
                                echo '</option>';
                            };

                        else:

                            echo 'Please add a MailPoet List.';

                        endif;

                        ?>
                        </select>

                        </div>

                    <?php endif; ?>

                    
                    <div id="none-selected">
                        <p>
                            Please select an email provider.
                        </p>
                    </div>

                    <div id="custom-email-options">
                        <p>
                            <label for="custom_email_form"><?php _e( 'Insert HTML form code here', 'popup-zen' ); ?></label>
                            <textarea class="pzen-textarea" name="custom_email_form" id="custom_email_form"><?php echo esc_html( get_post_meta( $post->ID, 'custom_email_form', true ) ); ?></textarea>
                        </p>
                    </div>

                    <div id="default-email-options">

                        <div id="pzen-name-fields">

                        <p>
                            <?php _e( 'Name Field Placeholder', 'popup-zen' ); ?>
                            <input id="name_placeholder" name="name_placeholder" class="widefat" value="<?php echo get_post_meta( $post->ID, 'name_placeholder', 1 ); ?>" placeholder="First Name" type="text" />
                        </p>

                        <p>
                            <input type="checkbox" id="dont_show_name" name="dont_show_name" value="1" <?php checked('1', get_post_meta( $post->ID, 'dont_show_name', true ), true); ?> />
                            <?php _e( 'Don\'t show first name field', 'popup-zen' ); ?>
                        </p>

                        </div>

                        <p>
                            <label for="opt_in_message"><?php _e( 'Small text above email field', 'popup-zen' ); ?></label>
                            <input class="widefat" type="text" name="opt_in_message" id="opt_in_message" placeholder="We don't spam or share your information." value="<?php echo esc_attr( get_post_meta( $post->ID, 'opt_in_message', true ) ); ?>" size="20" />
                        </p>

                        <p>
                            <label for="opt_in_placeholder"><?php _e( 'Placeholder', 'popup-zen' ); ?></label>
                            <input class="widefat" type="text" name="opt_in_placeholder" id="opt_in_placeholder" value="<?php echo esc_attr( get_post_meta( $post->ID, 'opt_in_placeholder', true ) ); ?>" size="20" />
                        </p>

                        <p>
                            <label for="opt_in_confirmation"><?php _e( 'Confirmation Message', 'popup-zen' ); ?></label>
                            <input class="widefat" type="text" name="opt_in_confirmation" id="opt_in_confirmation" value="<?php echo esc_attr( get_post_meta( $post->ID, 'opt_in_confirmation', true ) ); ?>" size="20" />
                        </p>

                        <p>
                            <label for="submit_text"><?php _e( 'Submit Button Text', 'popup-zen' ); ?></label>
                            <input class="widefat" type="text" name="submit_text" id="submit_text" value="<?php echo esc_attr( get_post_meta( $post->ID, 'submit_text', true ) ); ?>" size="20" placeholder="Send" />
                        </p>

                        <p>
                        <?php _e('Redirect after submission? Enter full url, or leave blank for no redirect.'); ?>
                        <input type="text" class="widefat" placeholder="https://mysite.com/page" name="pzen_redirect" id="pzen_redirect" value="<?php echo get_post_meta( $post->ID, 'pzen_redirect', 1 ); ?>" size="20" />
                    </p>

                        <?php do_action( 'pzen_email_settings', $post->ID ); ?>

                    </div>

                </div>

            </div>

        <?php }

        /**
         * Get MailChimp lists
         * 
         *
         * @since       0.8.3
         * @return      void
         */
        public function get_mc_lists() {

            $transient = get_transient( 'pzen_mc_lists' );

            if( $transient != false )
                return $transient;

            // MailChimp API credentials
            $api_key = get_option('pzen_mc_api_key');

            if( empty( $api_key) )
                return 'Please add your MailChimp API key in the Popup Zen settings.';

            // MailChimp API URL
            $data_center = substr($api_key,strpos($api_key,'-')+1);
            $url = 'https://' . $data_center . '.api.mailchimp.com/3.0/lists/';

            $headers = array(
                'Authorization' => 'Basic ' . base64_encode( 'user:' . $api_key ),
                'Content-Type' => 'application/json'
              );

            $response = wp_remote_get( $url, array(
                'timeout' => 10,
                'body' => array( 'count' => 20 ),
                'headers' => $headers,
                )
            );

            if ( is_wp_error( $response ) ) {
               $error_message = $response->get_error_message();
               return $error_message;
            } else {
                $api_response = json_decode( wp_remote_retrieve_body( $response ), true );
                if( array_key_exists( 'lists', $api_response ) ) {
                    return $api_response['lists'];

                    set_transient( 'pzen_mc_lists', $api_response['lists'], HOUR_IN_SECONDS );

                } else {
                    return $api_response;
                }
            }

        }

        /**
         * Get Active Campaign lists
         * 
         *
         * @since       0.8.3
         * @return      void
         */
        public function get_ac_lists() {

            $transient = get_transient( 'pzen_ac_lists' );

            if( $transient != false )
                return $transient;

            // Active Campaign API credentials
            $api_key = get_option('pzen_ac_api_key');
            $api_url = get_option('pzen_ac_url') . '/api/3/lists';

            if( empty( $api_key) )
                return 'Please add your Active Campaign API key in the Popup Zen settings.';

            $headers = array(
                'Api-Token' => $api_key,
                'Content-Type' => 'application/json'
              );

            $response = wp_remote_get( $api_url, array(
                'timeout' => 10,
                'headers' => $headers,
                )
            );

            if ( is_wp_error( $response ) ) {
               $error_message = $response->get_error_message();
               return $error_message;
            } else {
                $api_response = json_decode( wp_remote_retrieve_body( $response ), true );


                if( is_array( $api_response) && array_key_exists( 'lists', $api_response ) ) {
                    return $api_response['lists'];

                    set_transient( 'pzen_ac_lists', $api_response['lists'], HOUR_IN_SECONDS );
                } else {
                    return $api_response;
                }
            }

        }

        /**
         * Advanced settings meta box
         *
         * @since     0.1
         * @param       WP_Post $post
         */
        public function settings_meta_box_callback( $post ) {
            $show_on = get_post_meta( $post->ID, 'show_on', 1 );
            ?>

            <?php do_action('pzen_advanced_settings_before', $post->ID ); ?>

            <div class="pzen-section">

                <p><label><?php _e( 'What pages?', 'popup-zen' ); ?></label></p>

                <div class="pzen-settings-group">
                    <?php if( is_array( $show_on ) ) echo '<p>We have updated this setting, please re-enter pages and save.</p>'; ?>
                    <input type="radio" name="show_on" value="all" <?php if( $show_on === "all" ) echo 'checked="checked"'; ?>> All pages<br>
                    <input type="radio" name="show_on" value="limited" <?php if( $show_on === "limited" ) echo 'checked="checked"'; ?>> Certain pages<br>
                    <div id="show-certain-pages" class="pzen-hidden-field">
                    <p><?php  _e('Show on pages', 'popup-zen' ); ?></p>
                    <input placeholder="Start typing page title" class="widefat" type="text" name="show_on_pages" id="show_on_pages" value="<?php echo get_post_meta( $post->ID, 'show_on_pages', 1 ); ?>" size="20" />
                    </div>

                    <div id="pzen-tags" class="pzen-hidden-field">
                        <p><?php _e('Show on tags'); ?></p>
                        <input type="text" class="widefat" placeholder="Start typing a tag name" name="pzen_show_on_tags" id="pzen_show_on_tags" value="<?php echo get_post_meta( $post->ID, 'pzen_show_on_tags', 1 ); ?>" size="20" />
                    </div>
                    
                    <div id="pzen-cats" class="pzen-hidden-field">
                        <p><?php _e('Show on categories'); ?></p>
                        <input type="text" placeholder="Start typing a category name" class="widefat" name="pzen_show_on_cats" id="pzen_show_on_cats" value="<?php echo get_post_meta( $post->ID, 'pzen_show_on_cats', 1 ); ?>" size="20" />
                    </div>

                    <div id="pzen-types" class="pzen-hidden-field">
                        <p><?php _e('Show on post types'); ?></p>
                        <input type="text" placeholder="Start typing a post type name" class="widefat" name="pzen_show_on_types" id="pzen_show_on_types" value="<?php echo get_post_meta( $post->ID, 'pzen_show_on_types', 1 ); ?>" size="20" />
                    </div>

                    <div id="pzen-exclude" class="pzen-hidden-field">
                        <p><?php _e('<strong>Do not</strong> show on these pages'); ?></p>
                        <input type="text" class="widefat" placeholder="Start typing a page name" name="pzen_show_exclude_pages" id="pzen_show_exclude_pages" value="<?php echo get_post_meta( $post->ID, 'pzen_show_exclude_pages', 1 ); ?>" size="20" />
                    </div>

                    <?php do_action('pzen_page_settings', $post->ID ); ?>

                </div>

            </div>

            <div class="pzen-section">

                <p><label><?php _e( 'Show to these visitors', 'popup-zen' ); ?></label></p>

                <div class="pzen-settings-group"> 
                    <input type="radio" name="logged_in" value="all" <?php checked('all', get_post_meta( $post->ID, 'logged_in', true ), true); ?>> <?php _e( 'All visitors', 'popup-zen' ); ?><br>
                    <input type="radio" name="logged_in" value="logged_in" <?php checked('logged_in', get_post_meta( $post->ID, 'logged_in', true ), true); ?>> <?php _e( 'Logged in only', 'popup-zen' ); ?><br>
                    <input type="radio" name="logged_in" value="logged_out" <?php checked('logged_out', get_post_meta( $post->ID, 'logged_in', true ), true); ?>> <?php _e( 'Logged out only', 'popup-zen' ); ?><br>
                </div>
            </div>

            <div class="pzen-section">

                <p><label for="visitor"><?php _e( 'New or returning', 'popup-zen' ); ?></label></p>

                <div class="pzen-settings-group">
                    <input type="radio" name="new_or_returning" value="all" <?php checked('all', get_post_meta( $post->ID, 'new_or_returning', true ), true); ?>> <?php _e( 'All visitors', 'popup-zen' ); ?><br>
                    <input type="radio" name="new_or_returning" value="new" <?php checked('new', get_post_meta( $post->ID, 'new_or_returning', true ), true); ?>> <?php _e( 'New visitors only', 'popup-zen' ); ?><br>
                    <input type="radio" name="new_or_returning" value="returning" <?php checked('returning', get_post_meta( $post->ID, 'new_or_returning', true ), true); ?>> <?php _e( 'Returning visitors only', 'popup-zen' ); ?><br>
                </div>
            </div>

            <div class="pzen-section">

                <p>
                    <label for="visitor"><?php _e( 'When should we show it?', 'popup-zen' ); ?></label>
                </p>

                <div class="pzen-settings-group">
                    <input type="radio" name="display_when" value="immediately" <?php checked('immediately', get_post_meta( $post->ID, 'display_when', true ), true); ?>> <?php _e( 'Immediately', 'popup-zen' ); ?><br>
                    <input type="radio" name="display_when" value="delay" <?php checked('delay', get_post_meta( $post->ID, 'display_when', true ), true); ?>> <?php _e( 'Delay of', 'popup-zen' ); ?> <input type="number" class="pzen-number-input" id="scroll_delay" name="scroll_delay" size="2" value="<?php echo intval( get_post_meta( $post->ID, 'scroll_delay', true ) ); ?>" /> <?php _e( 'seconds', 'popup-zen' ); ?><br>
                    <input type="radio" name="display_when" value="scroll" <?php checked('scroll', get_post_meta( $post->ID, 'display_when', true ), true); ?>> <?php _e( 'User scrolls halfway down the page', 'popup-zen' ); ?><br>
                    <!-- <input type="radio" name="display_when" value="exit" <?php checked('exit', get_post_meta( $post->ID, 'display_when', true ), true); ?>> <?php _e( 'Exit Detection', 'popup-zen' ); ?><br> -->

                    <?php do_action('pzen_display_when_settings', $post->ID ); ?>

                </div>
            </div>

            <div class="pzen-section" id="pzen-disappear">

                <p>
                    <label for="hide_after"><?php _e( 'After it displays, when should it disappear?', 'popup-zen' ); ?></label>
                </p>

                <div class="pzen-settings-group">
                    <input type="radio" name="hide_after" value="never" <?php checked('never', get_post_meta( $post->ID, 'hide_after', true ), true); ?>> <?php _e( 'When user clicks hide', 'popup-zen' ); ?><br>
                    <input type="radio" name="hide_after" value="delay" <?php checked('delay', get_post_meta( $post->ID, 'hide_after', true ), true); ?>> <?php _e( 'Delay of', 'popup-zen' ); ?> <input type="number" class="pzen-number-input" id="hide_after_delay" name="hide_after_delay" size="2" value="<?php echo intval( get_post_meta( $post->ID, 'hide_after_delay', true ) ); ?>" /> <?php _e( 'seconds', 'popup-zen' ); ?><br>
                </div>

            </div>

            <div class="pzen-section">

                <p>
                    <label for="show_settings"><?php _e( 'How often should we show it to each visitor?', 'popup-zen' ); ?></label>
                </p>

                <div class="pzen-settings-group">
                    <input type="radio" name="show_settings" value="interacts" <?php checked('interacts', get_post_meta( $post->ID, 'show_settings', true ), true); ?>> <?php _e( 'Hide after user interacts (Close or email submit)', 'popup-zen' ); ?><br>
                    <input type="radio" name="show_settings" value="always" <?php checked('always', get_post_meta( $post->ID, 'show_settings', true ), true); ?>> <?php _e( 'Every page load', 'popup-zen' ); ?><br>
                    <input type="radio" name="show_settings" value="hide_for" <?php checked('hide_for', get_post_meta( $post->ID, 'show_settings', true ), true); ?>> <?php _e( 'Show, then hide for', 'popup-zen' ); ?> <input type="number" class="pzen-number-input" id="hide_for_days" name="hide_for_days" size="2" value="<?php echo intval( get_post_meta( $post->ID, 'hide_for_days', true ) ); ?>" /> <?php _e( 'days', 'popup-zen' ); ?><br>
                </div>
            </div>

            <div class="pzen-section">

                <p>
                    <label for="hide_after"><?php _e( 'Show on Devices', 'popup-zen' ); ?></label>
                </p>

                <div class="pzen-settings-group">
                    <input type="radio" name="pzen_devices" value="all" <?php checked('all', get_post_meta( $post->ID, 'pzen_devices', true ), true); ?>> <?php _e( 'All devices', 'popup-zen' ); ?><br>
                    <input type="radio" name="pzen_devices" value="desktop_only" <?php checked('desktop_only', get_post_meta( $post->ID, 'pzen_devices', true ), true); ?>> <?php _e( 'Desktop only', 'popup-zen' ); ?><br>
                    <input type="radio" name="pzen_devices" value="mobile_only" <?php checked('mobile_only', get_post_meta( $post->ID, 'pzen_devices', true ), true); ?>> <?php _e( 'Mobile only', 'popup-zen' ); ?><br>
                </div>

            </div>

            <div class="pzen-section">

                <p>
                    <input type="checkbox" id="hide_btn" name="hide_btn" value="1" <?php checked(1, get_post_meta( $post->ID, 'hide_btn', true ), true); ?> />
                    <label for="hide_btn"><?php _e( 'Hide the floating button? (Appears when box is hidden.)', 'popup-zen' ); ?></label>
                </p>

            </div>

            <div class="pzen-section noborder">

                <p>
                    <input type="checkbox" name="expiration" value="1" <?php checked('1', get_post_meta( $post->ID, 'expiration', true ), true); ?>> Automatically deactivate on a certain date?<br>
                    <input type="text" placeholder="05/28/2018" value="<?php echo get_post_meta( $post->ID, 'pzen_until_date', true ); ?>" name="pzen_until_date" id="pzen-until-datepicker" class="pzen-datepicker" />
                </p>

                <?php do_action('pzen_advanced_settings_after', $post->ID ); ?>

                <?php 
                    
                    // if( !is_plugin_active('popupzen-pro/holler-box-pro.php') ) {
                    //     echo '<p>Get more powerful display and customization settings in <strong><a href="https://getpopupzen.com/pro?utm_source=after_settings&utm_medium=link&utm_campaign=pzen_settings">Popup Zen Pro</a></strong></p>';
                    // }
                ?>

            </div>

        <?php }

        /**
         * Save meta box defaults when new post is created
         *
         * @since     0.1
         * @param     string $new_status
         * @param     string $old_status
         * @param     WP_Post $post
         */
        public function save_default_meta( $new_status, $old_status, $post ) {
            
            if ( $old_status === 'new' && $new_status === 'auto-draft' && $post->post_type === 'popupzen' ) {

                $item_type = get_post_meta( $post->ID, 'pzen_type' );

                // if we already have a setting, bail
                if( !empty( $item_type ) )
                    return;

                $avatar_email = get_option('admin_email');

                // set some defaults
                update_post_meta( $post->ID, 'show_on', 'all' );
                update_post_meta( $post->ID, 'logged_in', 'all' );
                update_post_meta( $post->ID, 'avatar_email', $avatar_email );
                update_post_meta( $post->ID, 'display_when', 'delay' );
                update_post_meta( $post->ID, 'scroll_delay', 1 );
                update_post_meta( $post->ID, 'show_settings', 'interacts' );
                update_post_meta( $post->ID, 'new_or_returning', 'all' );
                update_post_meta( $post->ID, 'hide_after', 'never' );
                update_post_meta( $post->ID, 'hide_after_delay', 3 );
                update_post_meta( $post->ID, 'hide_for_days', 1 );
                update_post_meta( $post->ID, 'pzen_devices', 'all' );
                update_post_meta( $post->ID, 'pzen_active', '1' );
                update_post_meta( $post->ID, 'pzen_type', 'notification' );
                update_post_meta( $post->ID, 'position', 'pzen-bottomright' );
                update_post_meta( $post->ID, 'opt_in_placeholder', 'Enter your email' );
                update_post_meta( $post->ID, 'name_placeholder', 'First name' );

            }

        }

        /**
         * Save meta box settings
         *
         * @since     0.1
         * @param     int $post_id
         * @return    void
         */
        public function save_settings( $post_id ) {

            // nonce check
            if ( !isset( $_POST['popupzen_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['popupzen_meta_box_nonce'], basename( __FILE__ ) ) )
                return $post_id;

            $post_type = get_post_type($post_id);

            // If this isn't our post type, don't update it.
            if ( "popupzen" != $post_type ) 
                return;

            // Check if the current user has permission to edit the post.
            if ( !current_user_can( 'edit_post', $post_id ) )
                return $post_id;

            $keys = array(
                'show_on',
                'opt_in_message',
                'opt_in_confirmation',
                'opt_in_placeholder',
                'opt_in_send_to',
                'button_color1',
                'bg_color',
                'text_color',
                'show_on_pages',
                'logged_in',
                'new_or_returning',
                'show_settings',
                'hide_for_days',
                'hide_after',
                'hide_after_delay',
                'display_when',
                'scroll_delay',
                'position',
                'pzen_devices',
                'hide_btn',
                'email_provider',
                'custom_email_form',
                'ck_id',
                'mc_list_id',
                'ac_list_id',
                'mailpoet_list_id',
                'pzen_type',
                'name_placeholder',
                'dont_show_name',
                'popup_image',
                'submit_text' );

            $settings[] = 'pzen_show_on_cats';
            $settings[] = 'pzen_show_on_tags';
            $settings[] = 'pzen_show_on_types';
            $settings[] = 'pzen_show_exclude_pages';
            $settings[] = 'pzen_popout_editor';
            $settings[] = 'pzen_popout_btn_text';
            $settings[] = 'mc_groups';
            $settings[] = 'drip_tags';
            $settings[] = 'pzen_redirect';

            $keys = apply_filters( 'pzen_settings_array', $keys );

            global $allowedposttags;
            $allowedposttags["iframe"] = array(

                'align' => true,
                'width' => true,
                'height' => true,
                'frameborder' => true,
                'name' => true,
                'src' => true,
                'id' => true,
                'class' => true,
                'style' => true,
                'scrolling' => true,
                'marginwidth' => true,
                'marginheight' => true,
                'allowfullscreen' => true

            );
            $allowedposttags["input"] = array(
                'type' => true,
                'value' => true,
                'id' => true,
                'name' => true,
                'class' => true,
                'placeholder' => true,
            );
            $allowedposttags["div"] = array(
                'style' => true,
                'id' => true,
                'class' => true,
                'align' => true
            );

            // sanitize data
            foreach ($keys as $key => $value) {

                if( empty( $_POST[ $value ] ) ) {
                    delete_post_meta( $post_id, $value );
                    continue;
                }
                if( is_string( $_POST[ $value ] ) )
                    $trimmed = trim( $_POST[ $value ] );

                $sanitized = wp_kses( $trimmed, $allowedposttags);
                update_post_meta( $post_id, $value, $sanitized );
            }

            // notification expiration date
            if( empty( $_POST[ 'expiration' ] ) ) {
                delete_post_meta( $post_id, 'expiration' );
                delete_post_meta( $post_id, 'pzen_until_date' );
            } elseif( $_POST[ 'expiration' ] === '1' && !empty( $_POST[ 'pzen_until_date' ] ) ) {

                $sanitized = wp_kses( $_POST[ 'pzen_until_date' ], $allowedposttags);
                update_post_meta( $post_id, 'pzen_until_date', $sanitized );
                update_post_meta( $post_id, 'expiration', '1' );

            } else {
                update_post_meta( $post_id, 'expiration', $_POST[ 'expiration' ] );
            }

            // Check for type. If it's popup, delete the avatar.
            $type = get_post_meta( $post_id, 'pzen_type', 1 );
            if( $type === 'pzen-popup' )
                delete_post_meta( $post_id, 'avatar_email' );

            do_action( 'pzen_custom_settings_save', $post_id );
            
        }

        /**
         * Add upgrade link to plugin row
         *
         * @since     0.9.1
         * @return    void
         */
        public function pzen_plugin_links( $links ) {

            $links[] = '<a href="https://getpopupzen.com/pro?utm_source=plugin_row&utm_medium=link&utm_campaign=pzen_settings" target="_blank" style="font-weight:bold;color:green;">Upgrade</a>';
            return $links;

        }

        /**
         * Add MailChimp Groups
         *
         * @access      public
         * @since       0.1
         */
        public function mc_groups( $id ) {

            ?>
            <div id="mailchimp-groups">
                <p><strong><?php _e('MailChimp Group', 'popup-zen'); ?></strong></p>
                <select name="mc_groups">
                    <option>None</option>
                </select>
            </div>

            <div id="mailchimp-interests">
                <p><strong><?php _e('Group Interests', 'popup-zen'); ?></strong></p>
                <div id="mc_interest_checkboxes"></div>
            </div>
            <img src="<?php echo Popup_Zen_URL . 'assets/img/loading.gif'; ?>" class="pzen-loading" />
            <p id="pzen-no-interests"><?php _e('No interests found.', 'popup-zen'); ?></p>
            <?php
        }

    }

    $pzen_Admin = new Popup_Zen_Admin();
    $pzen_Admin->instance();

} // end class_exists check