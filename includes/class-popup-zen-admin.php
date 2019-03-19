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

            add_action( 'post_submitbox_misc_actions', array( $this, 'active_switch' ) );

            add_filter('page_row_actions', array( $this, 'row_actions' ), 10, 2 );

            add_action( 'edit_form_after_title', array( $this, 'pzen_cpt_admin_output' ) );

        }

        /**
         * Show or hide upgrade link
         *
         * @access      public
         * @since       1.3.1
         * @return      void
         */
        public function maybe_show_upgrade_link() {

            // if( !is_plugin_active('hollerbox-pro/holler-box-pro.php') ) {
            //     add_filter( 'plugin_action_links_holler-box/holler-box.php', array( $this, 'pzen_plugin_links' ) );
            // }

        }

        /**
         * Scripts and styles
         *
         * @access      public
         * @since       0.1
         * @return      void
         */
        public function enqueue_scripts() {

            if( get_current_screen()->post_type === 'popupzen' ) {

                // Use minified libraries if SCRIPT_DEBUG is turned off
                $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

                // Date picker: https://gist.github.com/slushman/8fd9e1cc8161c395ec5b

                // Color picker: https://make.wordpress.org/core/2012/11/30/new-color-picker-in-wp-3-5/
                wp_enqueue_style( 'popup-zen-admin', Popup_Zen_URL . 'assets/css/popup-zen-admin' . $suffix . '.css', array( 'wp-color-picker' ), Popup_Zen_VER );

                wp_enqueue_script( 'popup-zen-admin', Popup_Zen_URL . 'assets/js/popup-zen-admin' . $suffix . '.js', array( 'wp-color-picker', 'jquery-ui-datepicker', 'jquery-ui-core', 'jquery-ui-autocomplete' ), Popup_Zen_VER, true );
            
                wp_enqueue_style( 'popup-zen-frontend', Popup_Zen_URL . 'assets/css/popup-zen-frontend' . $suffix . '.css', array( 'popup-zen-admin' ), Popup_Zen_VER );
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
            );

            register_post_type( 'popupzen', $args );
        }

        /* 
         * Output the CPT admin screen
         */        
        public function pzen_cpt_admin_output() {

            global $post;

            if( $post->post_type != 'popupzen' )
                return;
            
            ?>
            
            <div class="pzen-tab-box">

                    <div class="pzen-tab-nav">
                      <button class="pzen-tab-link active" onclick="pzenAdmin.openTab(event, 'pzen-type')"><?php _e( 'Options', 'popup-zen' ); ?></button>
                      <button class="pzen-tab-link" onclick="pzenAdmin.openTab(event, 'pzen-customize')"><?php _e( 'Customize', 'popup-zen' ); ?></button>
                      <button class="pzen-tab-link" onclick="pzenAdmin.openTab(event, 'pzen-email')"><?php _e( 'Email Integrations', 'popup-zen' ); ?></button>
                      <button class="pzen-tab-link" onclick="pzenAdmin.openTab(event, 'pzen-display')"><?php _e( 'Display Settings', 'popup-zen' ); ?></button>
                        
                        <div class="pzen-preview-btn-wrap">
                            <span>Save first, then click Preview.</span>
                            <a href="#" target="_blank" class="pzen-preview-btn"><?php _e( 'Preview', 'popup-zen' ); ?></a>

                            <a class="pzen-site-preview-link" href="<?php echo home_url() . '?pzen_preview=' . $post->ID; ?>" target="_blank"><?php _e( 'View on site', 'popup-zen' ); ?></a>
                        </div>
                    </div>

                    <div id="pzen-type" class="pzen-tab-content first">
                      <?php $this->type_settings( $post ); ?>
                    </div>

                    <div id="pzen-customize" class="pzen-tab-content">
                      <?php $this->customize_settings( $post ); ?>
                    </div>

                    <div id="pzen-email" class="pzen-tab-content">
                      <h3><?php _e( 'Email Integrations', 'popup-zen' ); ?></h3>
                      <?php $this->email_settings( $post ); ?>
                    </div>

                    <div id="pzen-display" class="pzen-tab-content">
                      <h3><?php _e( 'Display Settings', 'popup-zen' ); ?></h3>
                      <?php $this->display_settings( $post ); ?>
                    </div>

                <br style="clear:both"/>

            </div>

            <div id="pzen-customize-wrap">

                <?php 

                $pzen_funcs = new Popup_Zen_Functions();
                $pzen_funcs->display_pzen_box( $post->ID, true );

                ?>

            </div>

            <?php
        }

        /* 
         * Output the type settings
         */
        public function type_settings( $post ) {

            ?>

            <h4><?php _e( 'Popup Options', 'popup-zen' ); ?></h4>

            <p>
                <?php _e( 'Choose a position for the teaser box.', 'popup-zen' ); ?>
            </p>

            <div id="position-settings">

                <label class="pzen-radio-withimage">
                    <span class="text">Bottom Right</span>
                    <img src="<?php echo Popup_Zen_URL . 'assets/img/bottomright-icon.png'; ?>" class="pzen-radio-image" />
                    <input type="radio" name="position" value="pzen-bottomright" <?php checked( "pzen-bottomright", get_post_meta( $post->ID, 'position', true ) ); ?> />
                </label>

               <label class="pzen-radio-withimage">
                    <span class="text">Bottom Left</span>
                    <img src="<?php echo Popup_Zen_URL . 'assets/img/bottomleft-icon.png'; ?>" class="pzen-radio-image" />
                    <input type="radio" name="position" value="pzen-bottomleft" <?php checked( "pzen-bottomleft", get_post_meta( $post->ID, 'position', true ) ); ?> />
                </label>

                <?php do_action('pzen_position_settings', $post->ID); ?>

            </div>

            <p>
                <?php _e( 'Choose what the expanded popup should look like.', 'popup-zen' ); ?>
            </p>

            <p>
                
                <label class="pzen-radio-withimage">
                    <span class="text">Zen Box</span>
                    <img src="<?php echo Popup_Zen_URL . 'assets/img/box-expanded-icon.png'; ?>" class="pzen-radio-image" />
                    <input type="radio" name="pzen_type" value="pzen_box" <?php checked( "pzen_box", get_post_meta( $post->ID, 'pzen_type', true ) ); ?> />
                </label>

                <label class="pzen-radio-withimage">
                    <span class="text">Popup</span>
                    <img src="<?php echo Popup_Zen_URL . 'assets/img/popup-icon.png'; ?>" class="pzen-radio-image" />
                    <input type="radio" name="pzen_type" value="pzen_popup" <?php checked( "pzen_popup", get_post_meta( $post->ID, 'pzen_type', true ) ); ?> />
                </label>

                <?php do_action('pzen_type_settings', $post->ID); ?>
            </p>

            <?php
        }

        /* 
         * Output the display settings
         */
        public function customize_settings( $post ) {

            ?>

            <?php wp_nonce_field( basename( __FILE__ ), 'popupzen_meta_box_nonce' ); ?>

            <div class="pzen-section" id="box-colors">

                <h4><?php _e( 'Colors', 'popup-zen' ); ?></h4>
                

                <p><?php _e( 'Button Background', 'popup-zen' ); ?></p>
                <input type="text" name="accent_color" value="<?php echo esc_html( get_post_meta( $post->ID, 'accent_color', true ) ); ?>" class="pzen-accent-color" data-default-color="#1191cb" />

                <p><?php _e( 'Button Text Color', 'popup-zen' ); ?></p>
                <input type="text" name="btn_text_color" value="<?php echo esc_html( get_post_meta( $post->ID, 'btn_text_color', true ) ); ?>" class="pzen-btn-text-color" data-default-color="#ffffff" />
                
                <p><?php _e( 'Background color', 'popup-zen' ); ?></p>
                <input type="text" name="bg_color" value="<?php echo esc_html( get_post_meta( $post->ID, 'bg_color', true ) ); ?>" class="pzen-bg-color" data-default-color="#ffffff" />
                
                <p><?php _e( 'Text color', 'popup-zen' ); ?></p>
                <input type="text" name="text_color" value="<?php echo esc_html( get_post_meta( $post->ID, 'text_color', true ) ); ?>" class="pzen-text-color" data-default-color="#333333" />

            </div>

            <div class="pzen-section">
                <p><?php _e( 'Title', 'popup-zen' ); ?></p>

                <?php 
                // get default title
                $title = get_post_meta( $post->ID, 'pzen_title', true );
                $title = ( !empty( $title ) ? $title : get_the_title( $post->ID ) );
                ?>
                <input type="text" name="pzen_title" value="<?php echo esc_html( $title ); ?>" class="widefat"  />

                <p><?php _e( 'Content', 'popup-zen' ); ?></p>

                <?php wp_editor( get_post_meta( $post->ID, 'pzen_content', true ), 'pzen_content' ); ?>
            </div>

            <div class="pzen-section">

                <h4><?php _e( 'Image', 'popup-zen' ); ?></h4>
                
                <p>
                    <?php _e( 'Upload a Custom Image (458x450)', 'popup-zen' ); ?>
                </p>

                <input id="pzen-image-url" size="50" type="text" name="pzen_image" value="<?php echo get_post_meta( $post->ID, 'pzen_image', 1 ); ?>" />
                <input id="pzen-upload-btn" type="button" class="button" value="Upload Image" />

                <p>
                    <input type="checkbox" id="image_no_crop" name="image_no_crop" value="no-crop" <?php checked('no-crop', get_post_meta( $post->ID, 'image_no_crop', true ), true); ?> />
                    <?php _e( 'Do not crop (shows white space around image)', 'popup-zen' ); ?>
                </p>

            </div>

            <div class="pzen-section">

                <h4><?php _e( 'Fields', 'popup-zen' ); ?></h4>

                <p>
                    <label for="expand_btn_text"><?php _e( 'Expand Button Text', 'popup-zen' ); ?></label>
                    <input class="widefat" type="text" name="expand_btn_text" id="expand_btn_text" value="<?php echo esc_attr( get_post_meta( $post->ID, 'expand_btn_text', true ) ); ?>" size="20" />
                </p>

                <p>
                    <?php _e( 'Name Field Label', 'popup-zen' ); ?>
                    <input id="name_label" name="name_label" class="widefat" value="<?php echo get_post_meta( $post->ID, 'name_label', 1 ); ?>" placeholder="First Name" type="text" />
                </p>

                <p>
                    <input type="checkbox" id="dont_show_name" name="dont_show_name" value="1" <?php checked('1', get_post_meta( $post->ID, 'dont_show_name', true ), true); ?> />
                    <?php _e( 'Don\'t show first name field', 'popup-zen' ); ?>
                </p>

                <p>
                    <label for="email_label"><?php _e( 'Email Field Label', 'popup-zen' ); ?></label>
                    <input class="widefat" type="text" name="email_label" id="email_label" value="<?php echo esc_attr( get_post_meta( $post->ID, 'email_label', true ) ); ?>" size="20" />
                </p>

                <p>
                    <label for="opt_in_confirmation"><?php _e( 'Confirmation Message', 'popup-zen' ); ?></label>
                    <input class="widefat" type="text" name="opt_in_confirmation" id="opt_in_confirmation" value="<?php echo esc_attr( get_post_meta( $post->ID, 'opt_in_confirmation', true ) ); ?>" size="20" />
                </p>

                <p>
                    <label for="submit_text"><?php _e( 'Submit Button Text', 'popup-zen' ); ?></label>
                    <input class="widefat" type="text" name="submit_text" id="submit_text" value="<?php echo esc_attr( get_post_meta( $post->ID, 'submit_text', true ) ); ?>" size="20" placeholder="Send" />
                </p>

            </div>

        <?php }

        /**
         * Email settings
         *
         */
        public function email_settings( $post ) {

            ?>

            <div class="pzen-section noborder">

                <div id="pzen-email-options">

                    <h4>
                        <label for="position"><?php _e( 'Email Provider' ); ?></label>
                    </h4>

                    <select name="email_provider">

                        <option value="default" <?php selected( get_post_meta( $post->ID, 'email_provider', true ), "default"); ?> >
                            <?php _e( 'Email Site Admin', 'popup-zen' ); ?>
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

                    </select>

                    <?php do_action( 'pzen_below_provider_select', $post->ID ); ?>

                    <p id="convertkit-fields">
                        <?php _e( 'ConvertKit List ID (click list and look in address bar in ConvertKit) <em>*required</em>', 'popup-zen' ); ?>
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

                    <?php do_action( 'pzen_email_settings', $post->ID ); ?>

                </div>

            </div>

            <?php

        }

        /**
         * Advanced settings
         *
         */
        public function display_settings( $post ) {
            $show_on = get_post_meta( $post->ID, 'show_on', 1 );
            ?>

            <?php do_action('pzen_display_settings_before', $post->ID ); ?>

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

                    <input type="radio" name="display_when" value="scroll" <?php checked('scroll', get_post_meta( $post->ID, 'display_when', true ), true); ?>> 
                    <?php _e( 'User scrolls...', 'popup-zen' ); ?> <input type="number" class="pzen-number-input" step="25" max="100" id="page_scroll_percent" name="page_scroll_percent" size="2" value="<?php echo intval( get_post_meta( $post->ID, 'page_scroll_percent', true ) ); ?>" /> <?php _e( '% down page. (50 = halfway down page. Max is 100)', 'popup-zen' ); ?><br>
                    <input type="radio" name="display_when" value="immediately" <?php checked('immediately', get_post_meta( $post->ID, 'display_when', true ), true); ?>> <?php _e( 'Immediately', 'popup-zen' ); ?><br>
                    <input type="radio" name="display_when" value="delay" <?php checked('delay', get_post_meta( $post->ID, 'display_when', true ), true); ?>> <?php _e( 'Delay of', 'popup-zen' ); ?> <input type="number" class="pzen-number-input" id="scroll_delay" name="scroll_delay" size="2" value="<?php echo intval( get_post_meta( $post->ID, 'scroll_delay', true ) ); ?>" /> <?php _e( 'seconds', 'popup-zen' ); ?><br>
                    <!-- <input type="radio" name="display_when" value="exit" <?php checked('exit', get_post_meta( $post->ID, 'display_when', true ), true); ?>> <?php _e( 'Exit Detection', 'popup-zen' ); ?><br> -->

                    <?php do_action('pzen_display_when_settings', $post->ID ); ?>

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
                    <label for="devices"><?php _e( 'Show on Devices', 'popup-zen' ); ?></label>
                </p>

                <div class="pzen-settings-group">
                    <input type="radio" name="pzen_devices" value="all" <?php checked('all', get_post_meta( $post->ID, 'pzen_devices', true ), true); ?>> <?php _e( 'All devices', 'popup-zen' ); ?><br>
                    <input type="radio" name="pzen_devices" value="desktop_only" <?php checked('desktop_only', get_post_meta( $post->ID, 'pzen_devices', true ), true); ?>> <?php _e( 'Desktop only', 'popup-zen' ); ?><br>
                    <input type="radio" name="pzen_devices" value="mobile_only" <?php checked('mobile_only', get_post_meta( $post->ID, 'pzen_devices', true ), true); ?>> <?php _e( 'Mobile only', 'popup-zen' ); ?><br>
                </div>

            </div>

            <div class="pzen-section noborder">

                <?php do_action('pzen_display_settings_after', $post->ID ); ?>

            </div>

        <?php }

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

                // set some defaults
                update_post_meta( $post->ID, 'show_on', 'all' );
                update_post_meta( $post->ID, 'logged_in', 'all' );
                update_post_meta( $post->ID, 'display_when', 'scroll' );
                update_post_meta( $post->ID, 'page_scroll_percent', '25' );
                update_post_meta( $post->ID, 'position', 'pzen-bottomright' );
                update_post_meta( $post->ID, 'show_settings', 'interacts' );
                update_post_meta( $post->ID, 'new_or_returning', 'all' );
                update_post_meta( $post->ID, 'pzen_devices', 'all' );
                update_post_meta( $post->ID, 'pzen_active', '1' );
                update_post_meta( $post->ID, 'pzen_type', 'pzen_box' );
                update_post_meta( $post->ID, 'email_label', 'Email' );
                update_post_meta( $post->ID, 'name_label', 'First Name' );
                update_post_meta( $post->ID, 'expand_btn_text', 'Learn More' );
                update_post_meta( $post->ID, 'pzen_content', 'Enter your information to get the goods.' );

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
                'opt_in_confirmation',
                'email_label',
                'accent_color',
                'btn_text_color',
                'bg_color',
                'text_color',
                'show_on_pages',
                'logged_in',
                'new_or_returning',
                'show_settings',
                'display_when',
                'page_scroll_percent',
                'scroll_delay',
                'position',
                'pzen_devices',
                'email_provider',
                'ck_id',
                'mc_list_id',
                'ac_list_id',
                'mailpoet_list_id',
                'pzen_type',
                'name_label',
                'dont_show_name',
                'pzen_image',
                'expand_btn_text',
                'image_no_crop',
                'pzen_title',
                'submit_text' );

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
            $allowedposttags["p"] = array(
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

            update_post_meta( $post_id, 'pzen_content', wp_kses_post( $_POST['pzen_content'] ) );

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
         * Add active switch to submit box
         *
         */
        public function active_switch( $post ) {

            if( $post->post_type != 'popupzen' )
                return;

            echo '<span class="pzen-activate-text">' . __('Activate popup', 'popup-zen') . '</span> <label class="pzen-switch"><input data-id="' . $post->ID . '" type="checkbox" value="1" ' . checked(1, get_post_meta( $post->ID, 'pzen_active', true ), false) . ' /><div class="pzen-slider pzen-round"></div></label>';

        }

    }

    $pzen_Admin = new Popup_Zen_Admin();
    $pzen_Admin->instance();

} // end class_exists check