<?php

namespace Codemanas\VczApi\Backend;

/**
 * Meeting Post Type Controller
 *
 * @since      3.0.0
 * @author     Deepen.
 * @modified 3.7.0
 * @created_on 11/18/19
 */
class PostType {

	/**
	 * Instance
	 * @var null
	 */
	private static $_instance = null;

	/**
	 * Create only one instance so that it may not Repeat
	 *
	 * @since 2.0.0
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Post Type Flag
	 *
	 * @var string
	 */
	private $post_type = 'zoom-meetings';

	/**
	 * Zoom_Video_Conferencing_Admin_PostType constructor.
	 */
	public function __construct() {
		add_action( 'restrict_manage_posts', [ $this, 'filtering' ], 10 );
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'admin_menu', [ $this, 'hide_post_type' ] );
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		add_action( 'save_post_' . $this->post_type, array( $this, 'save_metabox' ), 10, 2 );
		add_filter( 'single_template', array( $this, 'single' ) );
		add_filter( 'archive_template', array( $this, 'archive' ) );
		add_filter( 'template_include', [ $this, 'template_filter' ], 99 );
		add_action( 'before_delete_post', array( $this, 'delete' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_filter( 'manage_' . $this->post_type . '_posts_columns', array( $this, 'add_columns' ), 20 );
		add_action( 'manage_' . $this->post_type . '_posts_custom_column', array( $this, 'column_data' ), 20, 2 );
		add_action( 'manage_edit-' . $this->post_type . '_sortable_columns', array( $this, 'sortable_data' ), 30 );
	}

	/**
	 * Hide Post Type page
	 */
	public function hide_post_type() {
		if ( isset( $_GET['post_type'] ) && $_GET['post_type'] !== $this->post_type ) {
			return;
		}

		if ( ! video_conferencing_zoom_api_get_user_transients() ) {
			global $submenu;
			unset( $submenu['edit.php?post_type=zoom-meetings'][5] );
			unset( $submenu['edit.php?post_type=zoom-meetings'][10] );
			unset( $submenu['edit.php?post_type=zoom-meetings'][15] );
		}

	}

	/**
	 * Filters
	 *
	 * @param $post_type
	 */
	public function filtering( $post_type ) {
		if ( $this->post_type !== $post_type ) {
			return;
		}

		$taxnomy  = 'zoom-meeting';
		$taxonomy = get_taxonomy( $taxnomy );
		$selected = isset( $_REQUEST[ $taxnomy ] ) ? $_REQUEST[ $taxnomy ] : '';
		wp_dropdown_categories( array(
			'show_option_all' => $taxonomy->labels->all_items,
			'taxonomy'        => $taxnomy,
			'name'            => $taxnomy,
			'orderby'         => 'name',
			'value_field'     => 'slug',
			'selected'        => $selected,
			'hierarchical'    => true,
			'hide_if_empty'   => true
		) );
	}

	/**
	 * Add New Start Link column
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function add_columns( $columns ) {
		$columns['type']          = __( 'Type', 'video-conferencing-with-zoom-api' );
		$columns['start_meeting'] = __( 'Start Meeting', 'video-conferencing-with-zoom-api' );
		$columns['start_date']    = __( 'Start Date', 'video-conferencing-with-zoom-api' );
		$columns['meeting_id']    = __( 'Meeting ID', 'video-conferencing-with-zoom-api' );
		$columns['meeting_state'] = __( 'Meeting State', 'video-conferencing-with-zoom-api' );
		unset( $columns['author'] );

		return $columns;
	}

	/**
	 * Sortable Data Column
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function sortable_data( $columns ) {
		$columns['start_date'] = 'zoom_meeting_startdate';
		$columns['meeting_id'] = 'zoom_meeting_id';

		return $columns;
	}

	/**
	 * Render HTML
	 *
	 * @param $column
	 * @param $post_id
	 */
	public function column_data( $column, $post_id ) {
		$meeting = get_post_meta( $post_id, '_meeting_zoom_details', true );
		switch ( $column ) {
			case 'type':
				if ( ! empty( $meeting ) && ! empty( $meeting->type ) && ( $meeting->type === 5 || $meeting->type === 6 || $meeting->type === 9 ) ) {
					_e( 'Webinar', 'video-conferencing-with-zoom-api' );
				} else {
					_e( 'Meeting', 'video-conferencing-with-zoom-api' );
				}
				break;
			case 'start_meeting' :
				if ( ! empty( $meeting ) && ! empty( $meeting->start_url ) ) {
					echo '<a href="' . esc_url( $meeting->start_url ) . '" target="_blank">Start</a>';
				} else {
					_e( 'Meeting not created yet.', 'video-conferencing-with-zoom-api' );
				}
				break;
			case 'start_date' :
				if ( ! empty( $meeting ) && ! empty( $meeting->code ) && ! empty( $meeting->message ) ) {
					echo $meeting->message;
				} else if ( ! empty( $meeting ) && ! empty( $meeting->type ) && ( $meeting->type === 2 || $meeting->type === 5 ) && ! empty( $meeting->start_time ) ) {
					echo vczapi_dateConverter( $meeting->start_time, $meeting->timezone, 'F j, Y, g:i a' );
				} else if ( ! empty( $meeting ) && vczapi_pro_check_type( $meeting->type ) ) {
					_e( 'Recurring Meeting', 'video-conferencing-with-zoom-api' );
				} else {
					_e( 'Meeting not created yet.', 'video-conferencing-with-zoom-api' );
				}
				break;
			case 'meeting_id' :
				if ( ! empty( $meeting ) && ! empty( $meeting->code ) && ! empty( $meeting->message ) ) {
					echo $meeting->message;
				} else if ( ! empty( $meeting ) && ! empty( $meeting->id ) ) {
					echo $meeting->id;
				} else {
					_e( 'Meeting not created yet.', 'video-conferencing-with-zoom-api' );
				}
				break;
			case 'meeting_state' :
				wp_enqueue_script( 'video-conferencing-with-zoom-api-js' );
				if ( ! empty( $meeting ) ) {
					if ( ! empty( $meeting->code ) && ! empty( $meeting->message ) ) {
						echo $meeting->message;
					} else if ( empty( $meeting->state ) ) { ?>
                        <a href="javascript:void(0);" class="vczapi-meeting-state-change" data-type="post_type" data-state="end" data-postid="<?php echo $post_id; ?>" data-id="<?php echo $meeting->id ?>"><?php _e( 'Disable Join', 'video-conferencing-with-zoom-api' ); ?></a>
                        <p class="description"><?php _e( 'Restrict users to join this meeting before the start time or after the meeting is completed.', 'video-conferencing-with-zoom-api' ); ?></p>
					<?php } else { ?>
                        <a href="javascript:void(0);" class="vczapi-meeting-state-change" data-type="post_type" data-state="resume" data-postid="<?php echo $post_id; ?>" data-id="<?php echo $meeting->id ?>"><?php _e( 'Enable Join', 'video-conferencing-with-zoom-api' ); ?></a>
                        <p class="description"><?php _e( 'Resuming this will enable users to join this meeting.', 'video-conferencing-with-zoom-api' ); ?></p>
					<?php }
				} else {
					_e( 'Meeting not created yet.', 'video-conferencing-with-zoom-api' );
				}
				break;
		}
	}

	/**
	 * Register Post Type
	 *
	 * @since  3.0.0
	 * @author Deepen
	 */
	public function register() {
		$this->register_post_type();
		$this->register_taxonomy();
	}

	/**
	 * Register Taxonomy
	 */
	public function register_taxonomy() {
		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name'          => _x( 'Category', 'Category', 'video-conferencing-with-zoom-api' ),
			'singular_name' => _x( 'Category', 'Category', 'video-conferencing-with-zoom-api' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
		);

		register_taxonomy( 'zoom-meeting', array( $this->post_type ), $args );
	}

	/**
	 * Register Post Type
	 */
	public function register_post_type() {
		$labels = apply_filters( 'vczapi_admin_cpt_labels', array(
			'name'               => _x( 'Zoom Meetings and Webinars', 'Zoom Meetings and Webinars', 'video-conferencing-with-zoom-api' ),
			'singular_name'      => _x( 'Zoom Meeting', 'Zoom Meeting', 'video-conferencing-with-zoom-api' ),
			'menu_name'          => _x( 'Zoom Meeting', 'Zoom Meeting', 'video-conferencing-with-zoom-api' ),
			'name_admin_bar'     => _x( 'Zoom Meeting', 'Zoom Meeting', 'video-conferencing-with-zoom-api' ),
			'add_new'            => __( 'Add New', 'video-conferencing-with-zoom-api' ),
			'add_new_item'       => __( 'Add New meeting', 'video-conferencing-with-zoom-api' ),
			'new_item'           => __( 'New meeting', 'video-conferencing-with-zoom-api' ),
			'edit_item'          => __( 'Edit meeting', 'video-conferencing-with-zoom-api' ),
			'view_item'          => __( 'View meetings', 'video-conferencing-with-zoom-api' ),
			'all_items'          => __( 'All Meetings', 'video-conferencing-with-zoom-api' ),
			'search_items'       => __( 'Search meetings', 'video-conferencing-with-zoom-api' ),
			'parent_item_colon'  => __( 'Parent meetings:', 'video-conferencing-with-zoom-api' ),
			'not_found'          => __( 'No meetings found.', 'video-conferencing-with-zoom-api' ),
			'not_found_in_trash' => __( 'No meetings found in Trash.', 'video-conferencing-with-zoom-api' ),
		) );

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'menu_icon'          => 'dashicons-video-alt2',
			'capability_type'    => apply_filters( 'vczapi_cpt_capabilities_type', 'post' ),
			'capabilities'       => apply_filters( 'vczapi_cpt_capabilities', array() ),
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => apply_filters( 'vczapi_cpt_menu_position', 5 ),
			'map_meta_cap'       => apply_filters( 'vczapi_cpt_meta_cap', null ),
			'supports'           => array(
				'title',
				'editor',
				'author',
				'thumbnail',
			),
			'rewrite'            => array( 'slug' => apply_filters( 'vczapi_cpt_slug', $this->post_type ) ),
		);

		register_post_type( $this->post_type, $args );
	}

	/**
	 * Adds the meta box.
	 */
	public function add_metabox() {
		add_meta_box( 'zoom-meeting-meta', __( 'Zoom Details', 'video-conferencing-with-zoom-api' ), array(
			$this,
			'render_metabox'
		), $this->post_type, 'normal' );
		add_meta_box( 'zoom-meeting-meta-side', __( 'Meeting Details', 'video-conferencing-with-zoom-api' ), array(
			$this,
			'rendor_sidebox'
		), $this->post_type, 'side', 'high' );
		add_meta_box( 'zoom-meeting-debug-meta', __( 'Debug Log', 'video-conferencing-with-zoom-api' ), array(
			$this,
			'debug_metabox'
		), $this->post_type, 'normal' );
		if ( is_plugin_inactive( 'vczapi-woo-addon/vczapi-woo-addon.php' ) && is_plugin_inactive( 'vczapi-woocommerce-addon/vczapi-woocommerce-addon.php' ) ) {
			add_meta_box( 'zoom-meeting-woo-integration-info', __( 'WooCommerce Integration?', 'video-conferencing-with-zoom-api' ), array(
				$this,
				'render_woo_sidebox'
			), $this->post_type, 'side', 'normal' );
		}
	}

	public function render_woo_sidebox() {
		echo "<p>Enable this meeting to be purchased by your users ? </p><p>Check out <a href='" . admin_url( 'edit.php?post_type=zoom-meetings&page=zoom-video-conferencing-addons' ) . "'>WooCommerce addon</a> for this plugin.</p>";
	}

	/**
	 * Renders the meta box.
	 */
	public function render_metabox( $post ) {
		wp_enqueue_script( 'video-conferencing-with-zoom-api-js' );
		wp_enqueue_script( 'video-conferencing-with-zoom-api-select2-js' );
		wp_enqueue_script( 'video-conferencing-with-zoom-api-timepicker-js' );

		do_action( 'vczapi_before_fields_admin', $post );

		//Get Template
		require_once ZVC_PLUGIN_VIEWS_PATH . '/post-type/tpl-meeting-fields.php';
	}

	/**
	 * Rendor SideBox field
	 *
	 * @param $post
	 */
	function rendor_sidebox( $post ) {
		//Get Template
		require_once ZVC_PLUGIN_VIEWS_PATH . '/post-type/tpl-metabox.php';
	}

	/**
	 * Debug FUNCTION
	 *
	 * @param $post
	 */
	public function debug_metabox( $post ) {
		//Get Template
		require_once ZVC_PLUGIN_VIEWS_PATH . '/post-type/tpl-debugger.php';
	}

	/**
	 * Handles saving the meta box.
	 *
	 * @param int $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 */
	public function save_metabox( $post_id, $post ) {
		// Check if user has permissions to save data.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check if not an autosave.
		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Check if not a revision.
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$pwd                = sanitize_text_field( filter_input( INPUT_POST, 'password' ) );
		$pwd                = ! empty( $pwd ) ? $pwd : $post_id;
		$posted_data = array(
			'userId'                 => sanitize_text_field( filter_input( INPUT_POST, 'userId' ) ),
			'meeting_type'           => absint( sanitize_text_field( filter_input( INPUT_POST, 'meeting_type' ) ) ),
			'start_date'             => sanitize_text_field( filter_input( INPUT_POST, 'start_date' ) ),
			'timezone'               => sanitize_text_field( filter_input( INPUT_POST, 'timezone' ) ),
			'duration'               => sanitize_text_field( filter_input( INPUT_POST, 'duration' ) ),
			'password'               => $pwd,
			'meeting_authentication' => filter_input( INPUT_POST, 'meeting_authentication' ),
			'option_host_video'      => filter_input( INPUT_POST, 'option_host_video' ),
			'option_auto_recording'  => filter_input( INPUT_POST, 'option_auto_recording' ),
			'alternative_host_ids'   => filter_input( INPUT_POST, 'alternative_host_ids', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY ),
		);

		//If Webinar
		if ( ! empty( $posted_data['meeting_type'] ) && $posted_data['meeting_type'] === 2 ) {
			$posted_data['panelists_video']        = filter_input( INPUT_POST, 'panelists_video' );
			$posted_data['practice_session']       = filter_input( INPUT_POST, 'practice_session' );
			$posted_data['hd_video']               = filter_input( INPUT_POST, 'hd_video' );
			$posted_data['allow_multiple_devices'] = filter_input( INPUT_POST, 'allow_multiple_devices' );
		} else {
			$posted_data['join_before_host']          = filter_input( INPUT_POST, 'join_before_host' );
			$posted_data['option_participants_video'] = filter_input( INPUT_POST, 'option_participants_video' );
			$posted_data['option_mute_participants']  = filter_input( INPUT_POST, 'option_mute_participants' );
		}

		$posted_data['site_option_logged_in']        = filter_input( INPUT_POST, 'option_logged_in' );
		$posted_data['site_option_browser_join']     = filter_input( INPUT_POST, 'option_browser_join' );
		$posted_data['site_option_enable_debug_log'] = filter_input( INPUT_POST, 'option_enable_debug_logs' );

		//Call before meeting is created.
		do_action( 'vczapi_admin_before_zoom_meeting_is_created', $posted_data );

		//Saving Meta Field Values
		\Codemanas\VczApi\Datastore\PostType::save_meta_fields( $posted_data, $post_id );

		//Create Zoom Meeting Now
		$meeting_id = get_post_meta( $post_id, '_meeting_zoom_meeting_id', true );
		if ( empty( $meeting_id ) ) {
			\Codemanas\VczApi\Datastore\PostType::create_zoom_meeting( $posted_data, $post );
		} else {
			\Codemanas\VczApi\Datastore\PostType::update_zoom_meeting( $posted_data, $post, $meeting_id );
		}

		//Call this action after the Zoom Meeting completion created.
		do_action( 'vczapi_admin_after_zoom_meeting_is_created', $post_id, $post );
	}

	/**
	 * Single Page Template
	 *
	 * @param $template
	 *
	 * @return bool|string
	 * @since  3.0.0
	 *
	 * @author Deepen
	 */
	public function single( $template ) {
		global $post;

		if ( ! empty( $post ) && $post->post_type == $this->post_type ) {
			unset( $GLOBALS['zoom'] );

			$show_zoom_author_name = get_option( 'zoom_show_author' );

			$GLOBALS['zoom'] = get_post_meta( $post->ID, '_meeting_fields', true ); //For Backwards Compatibility ( Will be removed someday )
			$meeting_details = get_post_meta( $post->ID, '_meeting_zoom_details', true );

			$meeting_author = get_the_author();
			if ( ! empty( $show_zoom_author_name ) ) {
				$meeting_author = vczapi_get_meeting_author( $post->ID, $meeting_details, $meeting_author );
			}
			$GLOBALS['zoom']['host_name'] = $meeting_author;

			if ( ! empty( $meeting_details ) ) {
				$GLOBALS['zoom']['api'] = get_post_meta( $post->ID, '_meeting_zoom_details', true );
			}

			$terms = get_the_terms( $post->ID, 'zoom-meeting' );
			if ( ! empty( $terms ) ) {
				$set_terms = array();
				foreach ( $terms as $term ) {
					$set_terms[] = $term->name;
				}
				$GLOBALS['zoom']['terms'] = $set_terms;
			}

			if ( isset( $_GET['type'] ) && $_GET['type'] === "meeting" && isset( $_GET['join'] ) ) {
				if ( defined( 'VCZAPI_STATIC_CDN' ) ) {
					self::enqueue_zoom_static_resources_cdn();
				} else {
					self::enqueue_zoom_static_resources_local();
				}

				wp_enqueue_script( 'video-conferencing-with-zoom-api-browser', ZVC_PLUGIN_PUBLIC_ASSETS_URL . '/js/zoom-meeting.min.js', array( 'jquery' ), ZVC_PLUGIN_VERSION, true );
				wp_localize_script( 'video-conferencing-with-zoom-api-browser', 'zvc_ajx', array(
					'ajaxurl'       => admin_url( 'admin-ajax.php' ),
					'zvc_security'  => wp_create_nonce( "_nonce_zvc_security" ),
					'redirect_page' => apply_filters( 'vczapi_api_redirect_join_browser', esc_url( get_permalink( $post->ID ) ) ),
					'meeting_id'    => sanitize_text_field( absint( vczapi_encrypt_decrypt( 'decrypt', $_GET['join'] ) ) ),
					'meeting_pwd'   => ! empty( $_GET['pak'] ) ? sanitize_text_field( vczapi_encrypt_decrypt( 'decrypt', $_GET['pak'] ) ) : false
				) );

				$template = vczapi_get_template( 'join-web-browser.php' );
			} else {
				//Render View
				$template = vczapi_get_template( 'single-meeting.php' );
			}
		}

		//Call before single template file is loaded
		do_action( 'vczapi_before_single_template_load' );

		return $template;
	}

	/**
	 * Archive page template
	 *
	 * @param $template
	 *
	 * @return bool|string
	 * @return bool|string|void
	 * @since  3.0.0
	 *
	 * @author Deepen
	 */
	public function archive( $template ) {
		if ( ! is_post_type_archive( $this->post_type ) ) {
			return $template;
		}

		if ( isset( $_GET['type'] ) && $_GET['type'] === "meeting" && isset( $_GET['join'] ) ) {
			if ( defined( 'VCZAPI_STATIC_CDN' ) ) {
				self::enqueue_zoom_static_resources_cdn();
			} else {
				self::enqueue_zoom_static_resources_local();
			}

			wp_enqueue_script( 'video-conferencing-with-zoom-api-browser', ZVC_PLUGIN_PUBLIC_ASSETS_URL . '/js/zoom-meeting.min.js', array( 'jquery' ), ZVC_PLUGIN_VERSION, true );
			wp_localize_script( 'video-conferencing-with-zoom-api-browser', 'zvc_ajx', array(
				'ajaxurl'       => admin_url( 'admin-ajax.php' ),
				'zvc_security'  => wp_create_nonce( "_nonce_zvc_security" ),
				'redirect_page' => apply_filters( 'vczapi_api_redirect_join_browser', esc_url( home_url( '/' ) ) ),
				'meeting_id'    => absint( vczapi_encrypt_decrypt( 'decrypt', $_GET['join'] ) ),
				'meeting_pwd'   => ! empty( $_GET['pak'] ) ? sanitize_text_field( vczapi_encrypt_decrypt( 'decrypt', $_GET['pak'] ) ) : false
			) );

			$template = vczapi_get_template( 'join-web-browser.php' );
		} else {
			$template = vczapi_get_template( 'archive-meetings.php' );
		}

		return $template;
	}

	/**
	 * Delete the meeting
	 *
	 * @param $post_id
	 *
	 * @since  3.0.0
	 *
	 * @author Deepen
	 */
	public function delete( $post_id ) {
		if ( get_post_type( $post_id ) === $this->post_type ) {
			$meeting_details = get_post_meta( $post_id, '_meeting_fields', true );
			$meeting_id      = get_post_meta( $post_id, '_meeting_zoom_meeting_id', true );
			if ( ! empty( $meeting_id ) ) {
				do_action( 'vczapi_before_delete_meeting', $meeting_id );

				if ( ! empty( $meeting_details ) && $meeting_details['meeting_type'] === 2 ) {
					zoom_conference()->deleteAWebinar( $meeting_id );
				} else {
					zoom_conference()->deleteAMeeting( $meeting_id );
				}

				do_action( 'vczapi_after_delete_meeting' );
			}
		}
	}

	public function admin_notices() {
		$screen = get_current_screen();

		//If not on the screen with ID 'edit-post' abort.
		if ( $screen->id === 'edit-zoom-meetings' || $screen->id === $this->post_type ) {
			video_conferencing_zoom_api_show_like_popup();
		} else {
			return;
		}
	}

	/**
	 * Pull local jquery resources
	 */
	public static function enqueue_zoom_static_resources_local() {
		wp_enqueue_script( 'video-conferencing-with-zoom-api-jquery', ZVC_PLUGIN_VENDOR_ASSETS_URL . '/zoom/jquery.min.js', false, ZVC_PLUGIN_VERSION, true );
		wp_enqueue_script( 'video-conferencing-with-zoom-api-react', ZVC_PLUGIN_VENDOR_ASSETS_URL . '/zoom/react.production.min.js', array( 'video-conferencing-with-zoom-api-jquery' ), ZVC_PLUGIN_VERSION, true );
		wp_enqueue_script( 'video-conferencing-with-zoom-api-react-dom', ZVC_PLUGIN_VENDOR_ASSETS_URL . '/zoom/react-dom.production.min.js', array( 'video-conferencing-with-zoom-api-jquery' ), ZVC_PLUGIN_VERSION, true );
		wp_enqueue_script( 'video-conferencing-with-zoom-api-redux', ZVC_PLUGIN_VENDOR_ASSETS_URL . '/zoom/redux.min.js', array( 'video-conferencing-with-zoom-api-jquery' ), ZVC_PLUGIN_VERSION, true );
		wp_enqueue_script( 'video-conferencing-with-zoom-api-thunk', ZVC_PLUGIN_VENDOR_ASSETS_URL . '/zoom/redux-thunk.min.js', array( 'video-conferencing-with-zoom-api-jquery' ), ZVC_PLUGIN_VERSION, true );
		wp_enqueue_script( 'video-conferencing-with-zoom-api-lodash', ZVC_PLUGIN_VENDOR_ASSETS_URL . '/zoom/lodash.min.js', array( 'video-conferencing-with-zoom-api-jquery' ), ZVC_PLUGIN_VERSION, true );
		wp_enqueue_script( 'zoom-meeting-source', ZVC_PLUGIN_VENDOR_ASSETS_URL . '/zoom/zoom-meeting.min.js', array(
			'jquery',
			'video-conferencing-with-zoom-api-jquery',
			'video-conferencing-with-zoom-api-react',
			'video-conferencing-with-zoom-api-react-dom',
			'video-conferencing-with-zoom-api-redux',
			'video-conferencing-with-zoom-api-thunk',
			'video-conferencing-with-zoom-api-lodash'
		), ZVC_PLUGIN_VERSION, true );
	}

	/**
	 * Load CDN static resources
	 */
	public static function enqueue_zoom_static_resources_cdn() {
		wp_enqueue_script( 'video-conferencing-with-zoom-api-jquery', 'https://source.zoom.us/' . ZVC_ZOOM_WEBSDK_VERSION . '/lib/vendor/jquery.min.js', false, ZVC_PLUGIN_VERSION, true );
		wp_enqueue_script( 'video-conferencing-with-zoom-api-react', 'https://source.zoom.us/' . ZVC_ZOOM_WEBSDK_VERSION . '/lib/vendor/react.min.js', array( 'video-conferencing-with-zoom-api-jquery' ), ZVC_PLUGIN_VERSION, true );
		wp_enqueue_script( 'video-conferencing-with-zoom-api-react-dom', 'https://source.zoom.us/' . ZVC_ZOOM_WEBSDK_VERSION . '/lib/vendor/react-dom.min.js', array( 'video-conferencing-with-zoom-api-jquery' ), ZVC_PLUGIN_VERSION, true );
		wp_enqueue_script( 'video-conferencing-with-zoom-api-redux', 'https://source.zoom.us/' . ZVC_ZOOM_WEBSDK_VERSION . '/lib/vendor/redux.min.js', array( 'video-conferencing-with-zoom-api-jquery' ), ZVC_PLUGIN_VERSION, true );
		wp_enqueue_script( 'video-conferencing-with-zoom-api-thunk', 'https://source.zoom.us/' . ZVC_ZOOM_WEBSDK_VERSION . '/lib/vendor/redux-thunk.min.js', array( 'video-conferencing-with-zoom-api-jquery' ), false, true );
		wp_enqueue_script( 'video-conferencing-with-zoom-api-lodash', 'https://source.zoom.us/' . ZVC_ZOOM_WEBSDK_VERSION . '/lib/vendor/lodash.min.js', array( 'video-conferencing-with-zoom-api-jquery' ), ZVC_PLUGIN_VERSION, true );
		wp_enqueue_script( 'zoom-meeting-source', 'https://source.zoom.us/zoom-meeting-' . ZVC_ZOOM_WEBSDK_VERSION . '.min.js', array(
			'jquery',
			'video-conferencing-with-zoom-api-jquery',
			'video-conferencing-with-zoom-api-react',
			'video-conferencing-with-zoom-api-react-dom',
			'video-conferencing-with-zoom-api-redux',
			'video-conferencing-with-zoom-api-thunk',
			'video-conferencing-with-zoom-api-lodash'
		), ZVC_PLUGIN_VERSION, true );
	}

	/**
	 * Change Filter Name to Override Page Builders overridng join via browser window.
	 *
	 * @param $template_name
	 *
	 * @return string
	 */
	public function template_filter( $template_name ) {
		if ( is_post_type_archive( $this->post_type ) && isset( $_GET['type'] ) && $_GET['type'] === "meeting" && isset( $_GET['join'] ) ) {
			$template_name = ZVC_PLUGIN_DIR_PATH . 'templates/join-web-browser.php';
		}

		return $template_name;
	}
}

PostType::get_instance();