<?php
/*
* class: Projects_Plugin_Admin
* since: 0.0.1
* description: Creates admin interface in WordPress
* version: 0.3
* text-domain: projects
*/
if( !class_exists('Projects_Plugin_Admin') ) {

	class Projects_Plugin_Admin {

		public static $_instance;
		public static $version;
		public static $ns;
		public static $screen;

		public function __construct() {

			/* define base variables */
			self::$ns = 'projects_plugin';

			/* load admin */
			add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ), 10, 0 );
			/* register settings */
			add_action( 'admin_init', array( __CLASS__, 'register_settings' ), 0, 0 );

			/* project columns */
			add_filter( 'manage_project_posts_columns', array( __CLASS__, 'add_project_image_column' ), 10, 1 );
			add_filter( 'manage_project_posts_custom_column', array( __CLASS__, 'display_project_image_column' ), 10, 2 );
			/* project table scripts */
			add_action( 'load-edit.php', array( __CLASS__, 'hook_project_list_table_scripts' ), 10, 0 );
			/* edit / add new project scripts */
			add_action( 'load-post.php', array( __CLASS__, 'hook_project_edit_scripts' ), 10, 0 );
			add_action( 'load-post-new.php', array( __CLASS__, 'hook_project_edit_scripts' ), 10, 0 );
			
			/* projects summary editor */
			add_action( 'edit_form_after_editor', array( __CLASS__, 'project_summary_metabox' ), 10, 1 );
			/* hide metaboxes */
			add_action( 'add_meta_boxes_project', array( __CLASS__, 'add_meta_boxes' ), 10, 1 );
			/* save post meta */
			add_action( 'save_post', array( __CLASS__, 'save_project_meta' ), 10, 1 );

			/* projects_plugin_admin__construct hook */
			do_action('projects_plugin_admin__construct');
		}
		
		/* saves post meta */
		public static function save_project_meta( $post_id = 0 )
		{
			if (
				( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || 
				!isset( $_REQUEST['_wpnonce'] ) || !wp_verify_nonce( $_REQUEST['_wpnonce'], 'update-post_' . $post_id )
			)
			{
				return $post_id;
			}
			$fields = array(
				'_project_summary' => array(
					'stripslashes',
					'trim'
				),
				'_prjcts_hide_widgetarea' => array(
					'absint'
				),
				'_prjcts_project_link_url' => array(
					'esc_url_raw'
				),
				'_prjcts_project_link_text' => array(
					'sanitize_text_field'
				),
				'_prjcts_project_link_title' => array(
					'sanitize_text_field'
				),
				'_prjcts_hide_project_link' => array(
					'absint'
				)
			);
			/* save them */
			foreach( $fields as $field => $callbacks )
			{
				if( !isset( $_REQUEST[ $field ] ) || self::is_empty( $submitted = $_REQUEST[ $field ] ) )
				{
					delete_post_meta( $post_id, $field );
				}
				else
				{
					foreach( $callbacks as $callback )
					{
						$submitted = call_user_func( $callback, $submitted );
					}
					if( empty( $submitted ) )
					{
						delete_post_meta( $post_id, $field );
					}
					else
					{
						update_post_meta( $post_id, $field, $submitted );
					}
				}
			}
			/* return */
			return $post_id;
		}
		
		/* PROJECTS METABOXES */
		
		/* adds metaboxes */
		public static function add_meta_boxes( WP_Post $post )
		{
			/* project button */
			add_meta_box(
				'project-link',
				__( 'Project Link', 'projects' ),
				array( __CLASS__, 'project_link_metabox' ),
				'project',
				'normal',
				'high',
				array(
					array(
						'id' => '_prjcts_project_link_url',
						'type' => 'text',
						'attributes' => array_filter( array(
							'type' => 'text',
							'id' => '_prjcts_project_link_url',
							'name' => '_prjcts_project_link_url',
							'value' => esc_attr( get_post_meta( $post->ID, '_prjcts_project_link_url', true ) ),
							'placeholder' => esc_attr( _x( 'http://example-domain.com/example-project/', 'example URL', 'projects' ) )
						) ),
						'label' => __( 'Link URL', 'projects' ),
						'description' => __( 'Please, enter here the URL to point the project link to. If left empty the link will not be displayed.', 'projects' )
					),
					array(
						'id' => '_prjcts_project_link_text',
						'type' => 'text',
						'attributes' => array_filter( array(
							'type' => 'text',
							'id' => '_prjcts_project_link_text',
							'name' => '_prjcts_project_link_text',
							'value' => esc_attr( get_post_meta( $post->ID, '_prjcts_project_link_text', true ) ),
							'placeholder' => esc_attr( Projects_Plugin::get_not_empty_option( 'prjcts_project_link_text', __( 'View project', 'projects' ) ) )
						) ),
						'label' => __( 'Link text', 'projects' ),
						'description' => __( 'Please, enter here the text to use for the project link. If left empty, the default will be used.', 'projects' )
					),
					array(
						'id' => '_prjcts_project_link_title',
						'type' => 'text',
						'attributes' => array_filter( array(
							'type' => 'text',
							'id' => '_prjcts_project_link_title',
							'name' => '_prjcts_project_link_title',
							'value' => esc_attr( get_post_meta( $post->ID, '_prjcts_project_link_title', true ) )
						) ),
						'label' => __( 'Link title attribute', 'projects' ),
						'description' => __( 'Please, enter here the text to use for the project link title attribute. If left empty, the link text will be used.', 'projects' )
					),
					array(
						'id' => '_prjcts_hide_project_link',
						'type' => 'checkbox',
						'attributes' => array_filter( array(
							'type' => 'checkbox',
							'id' => '_prjcts_hide_project_link',
							'name' => '_prjcts_hide_project_link',
							'value' => '1',
							'checked' => ( ( self::is_empty( get_post_meta( $post->ID, '_prjcts_hide_project_link', true ) ) ) ? '' : 'checked' )
						) ),
						'label' => __( 'Hide project link?', 'projects' ),
						'description' => __( 'If you do not want to display the project link on this page - check the box above.', 'projects' )
					)
				)
			);
			/* project widget area */
			add_meta_box(
				'project-widgetarea',
				__( 'After Project Widget Area', 'projects' ),
				array( __CLASS__, 'input_metabox' ),
				'project',
				'side',
				'default',
				array(
					'id' => '_prjcts_hide_widgetarea',
					'type' => 'checkbox',
					'attributes' => array_filter( array(
						'type' => 'checkbox',
						'id' => '_prjcts_hide_widgetarea',
						'name' => '_prjcts_hide_widgetarea',
						'value' => '1',
						'checked' => ( ( self::is_empty( get_post_meta( $post->ID, '_prjcts_hide_widgetarea', true ) ) ) ? '' : 'checked' )
					) ),
					'label' => __( 'Hide widget area?', 'projects' ),
					'description' => __( 'If you do not want to display the after project widget area on this page - check the box above.', 'projects' )
				)
			);
		}
		
		/* project link metabox */
		public static function project_link_metabox( WP_Post $post, $args )
		{
			foreach( $args['args'] as $field )
			{
				self::the_input_field( $field );
			}
		}
		
		/* input metabox */
		public static function input_metabox( WP_Post $post, $args )
		{
			self::the_input_field( $args['args'] );
		}
		
		/* prints input field */
		public static function the_input_field( $args = array() )
		{
			echo self::get_the_input_field( $args );
		}
		
		/* returns input field */
		public static function get_the_input_field( $args = array() )
		{
			$out = '';
			extract( $args, EXTR_SKIP );
			if( empty( $id ) || empty( $type ) || empty( $attributes ) )
			{
				return sprintf(
					'<p>%1$s: %2$s</p><pre>%3$s</pre>',
					__( 'An error occured', 'projects' ),
					__( 'some necessary parameters are missing.', 'projects' ),
					print_r( $args, true )
				);
			}
			$the_attributes = self::assoc_arr_to_html_attr( $attributes );
			$the_input = sprintf(
				'<input %1$s/>',
				$the_attributes
			);
			$the_label = ( empty( $label ) ) ? '' : sprintf(
				'<label for="%1$s">%2$s</label>',
				$id,
				$label
			);
			$the_description = ( empty( $description ) ) ? '' : sprintf(
				'<p class="description">%1$s</p>',
				$description
			);
			switch( $type )
			{
				case 'checkbox':
					$out = sprintf(
						'<div class="form-field"><p>%1$s</p>%2$s</div>',
						trim( sprintf(
							'%1$s %2$s',
							$the_input,
							$the_label
						) ),
						$the_description
					);
				break;
				
				case 'text':
					$out = sprintf(
						'<div class="form-field"><p>%1$s</p>%2$s</div>',
						trim( sprintf(
							'%1$s %2$s',
							$the_label,
							$the_input
						) ),
						$the_description
					);
				break;
				
				case 'hidden':
					$out = $the_input;
				break;
				
				default:
					$out = sprintf(
						'<p>%1$s: %2$s</p><pre>%3$s</pre>',
						__( 'An error occured', 'projects' ),
						__( 'type is not recognized.', 'projects' ),
						print_r( $args, true )
					);
				break;
			}
			return $out;
		}
		
		
		/* project summary */
		public static function project_summary_metabox( WP_Post $post )
		{
			if( empty( $post->post_type ) || ( 'project' !== $post->post_type ) )
			{
				return;
			}
			$data = Projects_Plugin::get_project_summary_text( $post->ID );
			printf(
				'<div id="%1$s" class="%2$s"><h2>%3$s</h2>',
				'projectsummary',
				'postarea',
				__( 'Project Summary', 'projects' )
			);
			wp_editor( 
				$data, 
				'_project_summary', 
				array(
					'media_buttons' => false,
					'textarea_name' => '_project_summary',
					'textarea_rows' => 5
				) 
			);
			echo '</div>';
		}

		/* register settings */
		public static function register_settings() {
			/* project display */
			register_setting( self::$ns, 'prjcts_no_summary', 'absint' );
			register_setting( self::$ns, 'prjcts_show_archive_links' );
			register_setting( self::$ns, 'prjcts_archive_links' );
			register_setting( self::$ns, 'prjcts_show_author_link', 'absint' );
			register_setting( self::$ns, 'prjcts_author_label', 'sanitize_text_field' );
			register_setting( self::$ns, 'prjcts_show_project_links' );
			register_setting( self::$ns, 'prjcts_project_link_text', 'sanitize_text_field' );
			register_setting( self::$ns, 'prjcts_project_link_class', array( __CLASS__, 'sanitize_html_classes' ) );
			register_setting( self::$ns, 'prjcts_hide_widgetarea', 'absint' );
			register_setting( self::$ns, 'prjcts_no_css', 'absint' );
			/* project permalinks */
			foreach( array_keys( self::get_permalink_fields() ) as $permalink_field ) {
				register_setting( self::$ns, "prjcts_{$permalink_field}_slug", array( __CLASS__, 'sanitize_slug' ) );
			}
			register_setting( self::$ns, 'prjcts_author_portfolio_slug', array( __CLASS__, 'sanitize_slug' ) );
			/* uninstall */
			register_setting( self::$ns, 'prjcts_uninstall', 'absint' );
			register_setting( self::$ns, 'prjcts_reset_settings', array( __CLASS__, 'reset_settings_on_sanitize' ) );

			/* projects_plugin_admin_register_settings hook */
			do_action( 'projects_plugin_admin_register_settings', self::$ns );
		}

		/* hooks project list table scripts */
		public static function hook_project_list_table_scripts() {
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_project_list_table_scripts' ), 10, 0 );
		}

		/* hooks setting page scripts */
		public static function hook_setting_page_scripts() {
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_settings_page_scripts' ), 10, 0 );
		}
		
		/* loads project edit scripts */
		public static function hook_project_edit_scripts()
		{
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'load_project_edit_scripts' ), 10, 0 );
		}
		
		/* loads project edit script */
		public static function load_project_edit_scripts()
		{
			global $post_type;
			if( 'project' === $post_type ) {
				wp_enqueue_style( 'project-edit', PRJCTS_URL . 'admin/css/project-edit.css', null, PRJCTS_VERSION, 'all' );
			}
		}

		/* loads project list table scripts */
		public static function load_project_list_table_scripts() {
			global $post_type;
			if( 'project' === $post_type ) {
				wp_enqueue_style( 'projects-list-table', PRJCTS_URL . 'admin/css/projects-list-table.css', null, PRJCTS_VERSION, 'all' );
			}
		}

		/* loads settings page scripts */
		public static function load_settings_page_scripts() {
			wp_enqueue_script( 'prjcts-settings', PRJCTS_URL . 'admin/js/settings.js', array( 'jquery-ui-sortable', 'jquery' ), PRJCTS_VERSION, true );
			wp_enqueue_style( 'prjcts-settings', PRJCTS_URL . 'admin/css/settings.css', null, PRJCTS_VERSION, 'all' );
			/* fire hook */
			do_action('projects_plugin_admin_load_settings_page_scripts');
		}

		/* registers admin page */
		public static function add_settings_page() {

			/* add settings page */
			self::$screen = add_submenu_page(
				'edit.php?post_type=project',
				__( 'Projects Plugin Settings', 'projects' ),
				__( 'Settings', 'projects' ),
				'manage_options',
				self::$ns,
				array( __CLASS__, 'settings_page' )
			);

			/* load admin tabs script and styles */
			add_action( 'load-'. self::$screen, array( __CLASS__, 'hook_setting_page_scripts' ), 10, 0 );

			/* display settings errors */
			add_action( 'admin_footer-'. self::$screen, 'settings_errors', 10, 0 );

			/* CREATE SETTINGS SECTIONS */

			/* fire projects_plugin_admin_add_settings_page hook to register sections by addons */
			do_action( 'projects_plugin_admin_add_settings_page', self::$ns, self::$screen );
			
			/* display */
			add_settings_section(
				'display',
				__( 'Display', 'projects' ),
				array( __CLASS__, 'project_display_section' ),
				self::$ns
			);
			
			/* summary */
			add_settings_field(
				'prjcts_no_summary',
				__( 'Summary', 'projects' ),
				array( __CLASS__, 'display_checkbox_field' ),
				self::$ns,
				'display',
				array(
					'label_for' => 'prjcts_no_summary',
					'label' => __( 'Do not show project summary?', 'projects' ),
					'description' => __( 'By default the project summary is shown on singular project pages. If you for some reason you need to hide it - check the box above.', 'projects' )
				)
			);
			
			/* archive links */
			add_settings_field(
				'prjcts_show_archive_links',
				__( 'Display archive links', 'projects' ),
				array( __CLASS__, 'display_select_field' ),
				self::$ns,
				'display',
				array(
					'label_for' => 'prjcts_show_archive_links',
					'options' => array(
						'' => __( 'Hidden', 'projects' ),
						'before' => __( 'Before the content', 'projects' ),
						'after' => __( 'After the content', 'projects' )
					),
					'default' => '',
					'description' => __( 'If you wish to display project archive links, please select the position you would like them to appear at.', 'projects' )
				)
			);
			
			/* archive links */
			add_settings_field(
				'prjcts_archive_links',
				__( 'Archive links', 'projects' ),
				array( __CLASS__, 'display_archive_links_field' ),
				self::$ns,
				'display',
				array(
					'name' => 'prjcts_archive_links',
					'description' => __( 'Here you may adjust the archive links display options and positions within the container individually.', 'projects' )
				)
			);
			
			/* show author link */
			add_settings_field(
				'prjcts_show_author_link',
				__( 'Author link', 'projects' ),
				array( __CLASS__, 'display_checkbox_field' ),
				self::$ns,
				'display',
				array(
					'label_for' => 'prjcts_show_author_link',
					'label' => __( 'Display project author link?', 'projects' ),
					'description' => __( 'Check this box to display a link to author portfolio (if you are a team and each member needs a separate portfolio, please note that Display archive links option above should not be Hidden).', 'projects' )
				)
			);
			
			/* author link label */
			add_settings_field(
				'prjcts_author_label',
				__( 'Author link label', 'projects' ),
				array( __CLASS__, 'the_input_field' ),
				self::$ns,
				'display',
				array(
					'type' => 'text',
					'id' => 'prjcts_author_label',
					'label_for' => 'prjcts_author_label',
					'attributes' => array(
						'type' => 'text',
						'id' => 'prjcts_author_label',
						'name' => 'prjcts_author_label',
						'value' => esc_attr( get_option('prjcts_author_label') ),
						'placeholder' => esc_attr( __( 'by', 'projects' ) )
					),
					'description' => __( 'Please, enter here the text to display before the project author portfolio link.', 'projects' )
				)
			);
			
			/* project links */
			add_settings_field(
				'prjcts_show_project_links',
				__( 'Display project links', 'projects' ),
				array( __CLASS__, 'display_select_field' ),
				self::$ns,
				'display',
				array(
					'label_for' => 'prjcts_show_project_links',
					'options' => array(
						'' => __( 'Hidden', 'projects' ),
						'before' => __( 'Before the content', 'projects' ),
						'after' => __( 'After the content', 'projects' ),
						'both' => __( 'Both, before and after the content', 'projects' )
					),
					'default' => '',
					'description' => __( 'If you wish to display project links (buttons), please select the position you would like it to appear at.', 'projects' )
				)
			);
			
			/* project link text */
			add_settings_field(
				'prjcts_project_link_text',
				__( 'Project link text', 'projects' ),
				array( __CLASS__, 'the_input_field' ),
				self::$ns,
				'display',
				array(
					'type' => 'text',
					'id' => 'prjcts_project_link_text',
					'label_for' => 'prjcts_project_link_text',
					'attributes' => array(
						'type' => 'text',
						'id' => 'prjcts_project_link_text',
						'name' => 'prjcts_project_link_text',
						'value' => esc_attr( get_option('prjcts_project_link_text') ),
						'placeholder' => esc_attr( __( 'View project', 'projects' ) )
					),
					'description' => __( 'Please, enter here the text to use by default for a project link. This can be overwritten on per project basis.', 'projects' )
				)
			);
			
			/* project link class */
			add_settings_field(
				'prjcts_project_link_class',
				__( 'Project link classes', 'projects' ),
				array( __CLASS__, 'the_input_field' ),
				self::$ns,
				'display',
				array(
					'type' => 'text',
					'id' => 'prjcts_project_link_class',
					'label_for' => 'prjcts_project_link_class',
					'attributes' => array(
						'type' => 'text',
						'id' => 'prjcts_project_link_class',
						'name' => 'prjcts_project_link_class',
						'value' => esc_attr( get_option('prjcts_project_link_class') ),
						'placeholder' => 'button project-button'
					),
					'description' => __( 'Please, enter here space separated CSS classes you would like to use for the project links.', 'projects' )
				)
			);
			
			/* widgetarea */
			add_settings_field(
				'prjcts_hide_widgetarea',
				__( 'Widget Area', 'projects' ),
				array( __CLASS__, 'display_checkbox_field' ),
				self::$ns,
				'display',
				array(
					'label_for' => 'prjcts_hide_widgetarea',
					'label' => __( 'Hide after project widget area?', 'projects' ),
					'description' => __( 'If checked, the plugin will stop displaying the widget area after content on singular project pages.', 'projects' )
				)
			);
			
			/* CSS */
			add_settings_field(
				'prjcts_no_css',
				__( 'Styling', 'projects' ),
				array( __CLASS__, 'display_checkbox_field' ),
				self::$ns,
				'display',
				array(
					'label_for' => 'prjcts_no_css',
					'label' => __( 'Do not load plugin stylesheet?', 'projects' ),
					'description' => __( 'If checked, the pplugin will stop loading its stylesheet on the front end. You are free to add your own styles to the stylesheet of your choice.', 'projects' )
				)
			);
			

			/* permalinks */
			add_settings_section(
				'permalinks',
				__( 'Permalinks', 'projects' ),
				array( __CLASS__, 'project_permalinks_section' ),
				self::$ns
			);
			$permalink_fields = self::get_permalink_fields();
			foreach( $permalink_fields as $permalink_field => $permalink_field_label ) {
				$permalink_field_id = "prjcts_{$permalink_field}_slug";
				add_settings_field(
					$permalink_field_id,
					$permalink_field_label,
					array( __CLASS__, 'display_permalink_field' ),
					self::$ns,
					'permalinks',
					array(
						'label_for' => $permalink_field_id,
						'label' => $permalink_field_label,
						'key' => $permalink_field
					)
				);
			}
			
			/* author portfolio slug */
			add_settings_field(
				'prjcts_author_portfolio_slug',
				__( 'Author portfolio slug', 'projects' ),
				array( __CLASS__, 'the_input_field' ),
				self::$ns,
				'permalinks',
				array(
					'type' => 'text',
					'id' => 'prjcts_author_portfolio_slug',
					'label_for' => 'prjcts_author_portfolio_slug',
					'attributes' => array(
						'type' => 'text',
						'id' => 'prjcts_author_portfolio_slug',
						'name' => 'prjcts_author_portfolio_slug',
						'value' => esc_attr( get_option('prjcts_author_portfolio_slug') ),
						'placeholder' => esc_attr( _x( 'portfolios', 'URL friendly slug', 'projects' ) )
					),
					'description' => sprintf(
						__( 'Please, enter here the slug you would like to use to display projects by author. The author portfolio URl will look like following: %1$s', 'projects' ),
						sprintf(
							'<br /><strong>%1$s/%2$s/%3$s/</strong>',
							get_bloginfo('url'),
							Projects_Plugin::get_author_portfolio_slug(),
							'%' . _x( 'author-name', 'URL friendly slug', 'projects' ) . '%'
						)
					)
				)
			);

			/* uninstall */
			add_settings_section(
				'uninstall',
				__( 'Uninstall', 'projects' ),
				array( __CLASS__, 'project_uninstall_section' ),
				self::$ns
			);
			add_settings_field(
				'prjcts_uninstall',
				__( 'Clean Database', 'projects' ),
				array( __CLASS__, 'display_checkbox_field' ),
				self::$ns,
				'uninstall',
				array(
					'label_for' => 'prjcts_uninstall',
					'label' => __( 'Clean database upon plugin deinstallation?', 'projects' ),
					'description' => __( 'DO NOT CHECK THIS BOX! Unless you are ready to get rid of this plugin.', 'projects' )
				)
			);
			add_settings_field(
				'prjcts_reset_settings',
				__( 'Reset Settings', 'projects' ),
				array( __CLASS__, 'display_checkbox_field' ),
				self::$ns,
				'uninstall',
				array(
					'label_for' => 'prjcts_reset_settings',
					'label' => __( 'Reset plugin settings?', 'projects' ),
					'description' => __( 'If you wish to reset plugin settings to their defaults, please check this box and save changes.', 'projects' )
				)
			);
		}
		
		/* dislays archive links field */
		public static function display_archive_links_field( $args )
		{
			extract( $args, EXTR_SKIP );
			$user_options = self::is_empty( $user_options = get_option( $name, array() ) ) ? 
				array() : 
				array_filter( $user_options );
			$user_options_keys = array_keys( $user_options );
			$taxonomy_names = array_keys( $taxonomies = array_filter( get_object_taxonomies( 'project', 'objects' ), array( __CLASS__, 'is_public_taxonomy' ) ) );
			/* clean old taxonomies */
			foreach( array_diff( $user_options_keys, $taxonomy_names ) as $to_remove )
			{
				unset( $user_options[ $to_remove ] );
			}
			/* add new taxonomies with their defaults */
			foreach( array_diff( $taxonomy_names, $user_options_keys ) as $to_add )
			{
				$user_options[ $to_add ] = array(
					'name' => $to_add,
					'show' => 0
				);
			}
			/* start interface */
			$fields = array();
			foreach( $user_options as $tax => $data )
			{
				$show = empty( $data['show'] ) ? 0 : 1;
				$fields[] = sprintf(
					'<p class="sortable-field"><span class="hndl">%1$s %2$s:</span> <input type="%3$s" id="%4$s" name="%5$s" value="1" %6$s/> <label for="%4$s">%7$s</label>%8$s</p>',
					'<span class="dashicons dashicons-move"></span>',
					sprintf(
						'<strong>%1$s</strong>',
						$taxonomies[ $tax ]->label
					),
					'checkbox',
					sprintf(
						'%1$s-%2$s-show',
						$name,
						$tax
					),
					sprintf(
						'%1$s[%2$s][show]',
						$name,
						$tax
					),
					checked( $show, 1, false ),
					sprintf(
						__( 'Display archive links for %s?', 'projects' ),
						strtolower( $taxonomies[ $tax ]->label )
					),
					sprintf(
						'<input type="hidden" name="%1$s" value="%2$s"/>',
						sprintf(
							'%1$s[%2$s][name]',
							$name,
							$tax
						),
						$tax
					)
				);
			}
			printf(
				'<div class="sortable-fields-wrapper">%1$s</div>',
				join( '', $fields )
			);
			if( !empty( $description ) )
			{
				printf(
					'<p class="description">%1$s</p>',
					$description
				);
			}
		}
		
		/* displays select field */
		public static function display_select_field( $args )
		{
			extract( $args, EXTR_SKIP );
			$value = get_option( $label_for, $default );
			$options_html = '';
			foreach( $options as $key => $label )
			{
				$options_html .= sprintf(
					'<option value="%1$s" %2$s>%3$s</option>',
					esc_attr( $key ),
					selected( $value, $key, false ),
					$label
				);
			}
			printf(
				'<select id="%1$s" name="%1$s">%2$s</select>',
				$label_for,
				$options_html
			);
			if( !empty( $description ) )
			{
				printf(
					'<p class="description">%1$s</p>',
					$description
				);
			}
		}
		
		/* displays description for the project_display_section */
		public static function project_display_section()
		{
			printf( '<p>%s</p>',
				__( 'Here you may adjust the display of your projects.', 'projects' )
			);
		}

		/* displays description for the project_uninstall_section */
		public static function project_uninstall_section() {
			printf( '<p>%s</p>',
				__( 'Here you may reset plugin settings or prepare the plugin for removal.', 'projects' )
			);
		}

		/* displays permalink field */
		public static function display_permalink_field( $args ) {
			extract( $args, EXTR_SKIP );
			$default_slugs = apply_filters( 'prjcts_display_permalink_field_default_slugs', array(
				'project' => _x( 'projects', 'URL friendly slug', 'projects' ),
				'project_type' => _x( 'project-types', 'URL friendly slug', 'projects' ),
				'project_category' => _x( 'project-categories', 'URL friendly slug', 'projects' ),
				'project_client' => _x( 'project-clients', 'URL friendly slug', 'projects' ),
				'project_collection' => _x( 'project-collections', 'URL friendly slug', 'projects' )
			) );
			printf( '%1$s/<input id="%2$s" type="text" name="%2$s" value="%3$s" class="medium-text code" placeholder="%4$s" />/%5$s/',
				get_bloginfo('url'),
				$label_for,
				esc_attr( get_option( $label_for ) ),
				esc_attr( ( ( empty( $default_slugs[ $key ] ) ) ? '' : $default_slugs[ $key ] ) ),
				str_replace( '_', '-', "%{$key}%" )
			);
		}

		/* returns permalink fields array */
		public static function get_permalink_fields() {
			return array_merge(
				array( 'project' => _x( 'Projects', 'Post Type General Name', 'projects' ) ),
				Projects_Plugin_Content_Type::get_project_taxonomy_full_names()
			);
		}

		/* displays description for project permalinks section and flushes permalinks */
		public static function project_permalinks_section() {
			flush_rewrite_rules();
			printf( '<p>%s</p>',
				__( 'Here you may adjust permalinks for your project pages.', 'projects' )
			);
		}

		/* displays setting sections */
		public static function do_settings_sections( $page ) {
			global $wp_settings_sections, $wp_settings_fields;
			if ( ! isset( $wp_settings_sections[$page] ) ) {
				return;
			}
			echo '<div class="settings-sections-container">';
			foreach ( (array) $wp_settings_sections[$page] as $section ) {
				printf( '<div class="settings-section" id="settings-section-%1$s">',
					$section['id']
				);
				if ( $section['title'] ) {
					echo "<h2>{$section['title']}</h2>\n";
				}
				if ( $section['callback'] ) {
					call_user_func( $section['callback'], $section );
				}
				if ( isset( $wp_settings_fields ) && isset( $wp_settings_fields[$page] ) && isset( $wp_settings_fields[$page][$section['id']] ) ) {
					echo '<table class="form-table">';
					do_settings_fields( $page, $section['id'] );
					echo '</table>';
				}
				echo '</div>';
			}
			echo '</div>';
		}

		/* displays admin page */
		public static function settings_page() {
			printf( '<div class="wrap %1$s-settings-page"><h2>%2$s</h2><form action="%3$s" method="post">',
				self::$ns,
				esc_html( get_admin_page_title() ),
				admin_url('options.php')
			);
			settings_fields( self::$ns );
			self::do_settings_sections( self::$ns );
			submit_button();
			echo '</form></div>';
		}

		/* displays image column in project list table */
		public static function display_project_image_column( $column_name, $id ) {
			if( 'image' === $column_name ) {
				echo ( 0 < ( $img_id = absint( get_post_thumbnail_id( $id ) ) ) ) ? wp_get_attachment_image( $img_id, array( 44, 44 ), false, array( 'class' => 'post-image' ) ) : '';
			}
		}

		/* adds image column to project list table */
		public static function add_project_image_column( $columns = array() ) {
			$term_image_column = array(
				'image' => sprintf( '<span class="vers dashicons dashicons-format-image" title="%1$s"><span class="screen-reader-text">%1$s</span></span>', esc_attr( __( 'Image', 'projects' ) ) )
			);
			return array_merge( array_slice( $columns, 0, 2, true ), $term_image_column, array_slice( $columns, 2, ( count( $columns ) - 2 ), true ) );
		}

		/*** HELPERS ***/
		
		/* sanitizes html classes input */
		public static function sanitize_html_classes( $classes = '' )
		{
			return trim( join( ' ', array_filter( array_map( 'sanitize_html_class', explode( ' ', trim( strtolower( $classes ) ) ) ) ) ) );
		}

		/* displays checkbox field */
		public static function display_checkbox_field( $args ) {
			extract( $args, EXTR_SKIP );
			printf( '<input id="%1$s" type="checkbox" name="%1$s" value="1" %2$s/> <label for="%1$s">%3$s</label>%4$s',
				$label_for,
				checked( 1, absint( get_option( $label_for ) ), false ),
				$label,
				( empty( $description ) ? '' : sprintf( '<p class="description">%s</p>', $description ) )
			);
		}

		public static function reset_settings() {
			global $new_whitelist_options;
			foreach( $new_whitelist_options[ self::$ns ] as $option ) {
				delete_option( $option );
			}
		}

		public static function reset_settings_on_sanitize( $data ) {
			if( !empty( $data ) ) {
				self::reset_settings();
				add_settings_error(
					self::$ns,
					esc_attr( 'settings_updated' ),
					__( 'Settings reset the their defaults.', 'projects' ),
					'updated'
				);
			}
			return null;
		}
		
		public static function assoc_arr_to_html_attr( $attributes = array() )
		{
			$out = '';
			foreach( $attributes as $key => $value )
			{
				$out .= sprintf(
					' %1$s="%2$s"',
					$key,
					esc_attr( $value )
				);
			}
			return trim( $out );
		}

		public static function sanitize_slug( $input = '' ) {
			return self::is_empty( $slug = sanitize_title_with_dashes( $input ) ) ? null : $slug;
		}
		
		public static function not_null( $data )
		{
			return !is_null( $data );
		}
		
		public static function is_public_taxonomy( $taxonomy )
		{
			return !empty( $taxonomy->public );
		}

		public static function is_empty( $data = null ) {
			return empty( $data );
		}

		public static function load_admin() {
			return self::getInstance();
		}

		public static function getInstance() {
			if ( !isset( self::$_instance ) ) {
				self::$_instance = new self();
			}
			/* projects_plugin_admin_init hook */
			do_action( 'projects_plugin_admin_init', self::$_instance );
			/* return instance */
			return self::$_instance;
		}

	}

}