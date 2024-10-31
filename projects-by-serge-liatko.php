<?php
/*
* Plugin Name: Projects by Serge Liatko
* Plugin URI:  
* Description: Easy way to publish and organize your portfolio online. Show off what you have done and your current projects. Easy to use, light and flexible.
* Version:     0.5
* Author:      Serge Liatko
* Author URI:  http://sergeliatko.com/?utm_source=projects&utm_medium=textlink&utm_content=authorlink&utm_campaign=wpplugins
* License:     GPL2
* License URI: https://www.gnu.org/licenses/gpl-2.0.html
* Domain Path: /languages
* Text Domain: projects
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*
* Copyright 2016 Serge Liatko <contact@sergeliatko.com> http://sergeliatko.com
*/

/* prevent direct loading */
defined('ABSPATH') or die( sprintf( 'Please, do not load this file directly. File: %s', __FILE__ ) );

/* define paths */
define( 'PRJCTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'PRJCTS_URL', plugin_dir_url( __FILE__ ) );
define( 'PRJCTS_VERSION', '0.5' );

/* start plugin class */
if( !class_exists('Projects_Plugin') ) {

	class Projects_Plugin
	{

		/* declare variables */
		public static $_instance;
		public static $version;
		public static $ns;

		public function __construct()
		{

			/* define basic variables */
			self::$ns = 'projects_plugin';

			/* load text domain */
			load_plugin_textdomain( 'projects', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );

			/* register post type */
			add_action( 'init', array( __CLASS__, 'register_project_content_type' ), 0, 0 );
			
			/* handle author portfolio endpoints and redirects */
			add_action( 'init', array( __CLASS__, 'register_endpoints' ), 10, 0 );
			add_action( 'pre_get_posts', array( __CLASS__, 'handle_author_portfolio_endpoint' ), 10, 1 );
			add_action( 'template_redirect', array( __CLASS__, 'redirect_empty_portfolio' ), 10, 0 );

			/* LOAD ADMIN */
			if( is_admin() )
			{
				/* add plugin settings link */
				add_filter( 'plugin_action_links', array( __CLASS__, 'add_settings_link' ), 10, 2 );
				/* add plugin meta links */
				add_filter( 'plugin_row_meta', array( __CLASS__, 'add_meta_links' ), 10, 2 );

				/* load admin */
				require_once( PRJCTS_PATH . 'admin/class-projects-plugin-admin.php' );
				Projects_Plugin_Admin::load_admin();
			}
			/* FRONT END ONLY */
			else
			{
				/* load styles only if allowed */
				if( self::is_empty( get_option('prjcts_no_css') ) )
				{
					add_action( 'wp_enqueue_scripts', array( __CLASS__, 'load_front_end_styles' ), 10, 0 );
				}
				/* load project links only if allowed */
				if( !self::is_empty( get_option('prjcts_show_project_links') ) )
				{
					add_filter( 'the_content', array( __CLASS__, 'add_project_links' ), 5, 1 );
				}
				/* load archive links only if allowed */
				if( !self::is_empty( get_option('prjcts_show_archive_links') ) )
				{
					add_filter( 'the_content', array( __CLASS__, 'add_project_archive_links' ), 5, 1 );
				}
				/* load summary only if allowed */
				if( self::is_empty( get_option('prjcts_no_summary') ) )
				{
					add_filter( 'the_content', array( __CLASS__, 'add_project_summary' ), 5, 1 );
				}
				/* load widget area only if allowed */
				if( self::is_empty( get_option('prjcts_hide_widgetarea') ) )
				{
					add_filter( 'the_content', array( __CLASS__, 'add_project_widgetarea' ), 5, 1 );
				}
				/* disable pagination on author archives */
				if( true === apply_filters( 'projects_plugin_author_portfolio_nopaging', true ) )
				{
					add_action( 'pre_get_posts', array( __CLASS__, 'handle_author_portfolio_nopaging' ), 10, 1 );
				}
			}
			/* EVERYWHERE */
			
			/* register widget area */
			add_action( 'widgets_init', array( __CLASS__, 'widgets_init' ), 15, 0 );

			/* projects_plugin__construct hook */
			do_action('projects_plugin__construct');
		}
		
		/* REWRITE / REDIRECT */
		
		/* registers end points */
		public static function register_endpoints()
		{
			add_rewrite_endpoint( Projects_Plugin::get_author_portfolio_slug(), EP_ROOT );
		}
		
		/* disables the paginationon author portfolios */
		public static function handle_author_portfolio_endpoint( $query )
		{
			if( 
				!is_admin() && 
				$query->is_main_query() &&
				( null !== ( $author = get_query_var( Projects_Plugin::get_author_portfolio_slug(), null ) ) ) 
			)
			{
				$query->set( 'post_type', 'project' );
				$query->set( 'author_name', $author );
				/* reparse query to populate query vars */
				$query->parse_query_vars();
				/* unset is_home */
				$query->is_home = null;
			}
		}
		
		/* redirects portfolio without author parameter to projects archive */
		public static function redirect_empty_portfolio()
		{
			if( 
				!is_null( $author = get_query_var( Projects_Plugin::get_author_portfolio_slug(), null ) ) && 
				empty( $author ) 
			)
			{
				wp_redirect( get_post_type_archive_link('project') );
				exit();
			}
		}
		
		/* FRONT END */
		
		/* disables the paginationon author portfolios */
		public static function handle_author_portfolio_nopaging( $query )
		{
			if( 
				$query->is_main_query() && 
				$query->is_author && 
				( 'project' === $query->get('post_type') ) 
			)
			{
				$query->set( 'nopaging', true );
				$query->set( 'posts_per_page', -1 );
			}
		}
		
		/* adds after project widget area */
		public static function add_project_widgetarea( $content = '' )
		{
			global $post;
			if( 
				is_singular('project') && 
				( true === apply_filters( 'projects_plugin_show_widgetarea', true) ) && 
				self::is_empty( get_option('prjcts_hide_widgetarea') ) && 
				self::is_empty( get_post_meta( $post->ID, '_prjcts_hide_widgetarea', true ) )
			)
			{
				if( is_active_sidebar('after-project') )
				{
					ob_start();
					dynamic_sidebar('after-project');
					$widgets = ob_get_clean();
					$content .= "\n\n" . sprintf(
						'<div id="after-project-widget-area" class="%1$s">%2$s</div>',
						'widget-area content-widget-area after-project-widget-area',
						$widgets
					);
				}
			}
			return $content;
		}
		
		/* adds project archive links */
		public static function add_project_archive_links( $content = '' )
		{
			if( 
				is_singular('project') && 
				( true === apply_filters( 'projects_plugin_show_archive_links', true) ) && 
				!self::is_empty( $show = get_option('prjcts_show_archive_links') )
			)
			{
				global $post;
				$user_options = self::is_empty( $user_options = get_option( 'prjcts_archive_links', array() ) ) ? 
					array() : 
					array_filter( $user_options, array( __CLASS__, 'is_to_show' ) );
				$taxonomy_names = array_keys( $taxonomies = get_object_taxonomies( 'project', 'objects' ) );
				/* clean old taxonomies */
				foreach( array_diff( array_keys( $user_options ), $taxonomy_names ) as $to_remove )
				{
					unset( $user_options[ $to_remove ] );
				}
				if( empty( $user_options ) )
				{
					return $content;
				}
				/* ready to build */
				$term_links = array();
				foreach( array_keys( $user_options ) as $tax )
				{
					$args = apply_filters( 'projects_plugin_archive_links_args', array(
						'before' => sprintf(
							'<span class="term-links-title">%1$s:</span> ',
							( empty( $taxonomies[ $tax ]->labels->singular_name ) ? $taxonomies[ $tax ]->label : $taxonomies[ $tax ]->labels->singular_name )
						),
						'separator' => ', ',
						'after' => '.'
					), $tax );
					if( is_wp_error( $links = get_the_term_list( $post->ID, $tax, $args['before'], $args['separator'], $args['after'] ) ) || empty( $links ) )
					{
						continue;
					}
					$term_links[] = sprintf(
						'<p class="term-links term-links-%1$s">%2$s</p>',
						$tax,
						$links
					);
				}
				/* author links */
				if( !self::is_empty( get_option('prjcts_show_author_link') ) )
				{
					$args = apply_filters( 'projects_plugin_archive_links_args', array(
						'before' => sprintf(
							'<span class="term-links-title">%1$s</span> ',
							Projects_Plugin::get_not_empty_option( 'prjcts_author_label', __( 'by', 'projects' ) )
						),
						'separator' => ', ',
						'after' => '.'
					), 'author' );
					$author_display_name = get_the_author();
					$term_links[] = sprintf(
						'<p class="term-links term-links-%1$s">%2$s</p>',
						'author',
						sprintf(
							'%1$s<a href="%2$s" title="%3$s">%4$s</a>%5$s',
							$args['before'],
							sprintf(
								'%1$s/%2$s/%3$s/',
								get_bloginfo('url'),
								self::get_author_portfolio_slug(),
								get_the_author_meta( 'nicename', $post->post_author )
							),
							esc_attr( $author_display_name ),
							$author_display_name,
							$args['after']
						)
					);
				}
				$return  = ( empty( $term_links ) ) ? '' : sprintf(
					'<div class="%1$s">%2$s</div>',
					'project-archive-links',
					join( '', $term_links )
				);
				return ( 'before' === $show ) ? $return . "\n\n" . $content : $content . "\n\n" . $return;
			}
			return $content;
		}
		
		/* adds project links */
		public static function add_project_links( $content = '' )
		{
			global $post;
			/* check if we do need to display anything - return content if not */
			if(
				!is_singular('project') || 
				( true !== apply_filters( 'projects_plugin_show_project_links', true ) ) || 
				!self::is_empty( get_post_meta( $post->ID, '_prjcts_hide_project_link', true ) ) || 
				self::is_empty( $url = get_post_meta( $post->ID, '_prjcts_project_link_url', true ) )
			)
			{
				return $content;
			}
			/* build it */
			$display = get_option('prjcts_show_project_links');
			$text = self::get_not_empty_post_meta( 
				$post->ID, 
				'_prjcts_project_link_text', 
				true, 
				self::get_not_empty_option( 'prjcts_project_link_text', __( 'View project', 'projects' ) ) 
			);
			$title = esc_attr( self::get_not_empty_post_meta( 
				$post->ID, 
				'_prjcts_project_link_title', 
				true, 
				self::get_not_empty_option( 'prjcts_project_link_text', $text ) 
			) );
			$class = self::get_not_empty_option( 'prjcts_project_link_class', 'button project-button' );
			$link = sprintf(
				'<p class="project-link"><a href="%1$s" title="%2$s" class="%3$s" %4$s>%5$s</a></p>',
				esc_url( $url ),
				$title,
				$class,
				apply_filters( 'projects_plugin_project_links_inner_attributes', 'target="_blank"' ),
				$text
			);
			/* insert in place */
			switch( $display )
			{
				case 'before':
					$content = $link . "\n\n" . $content;
				break;
				
				case 'after':
					$content = $content . "\n\n" . $link;
				break;
				
				case 'both':
					$content = $link . "\n\n" . $content . "\n\n" . $link;
				break;
				
				default:
					$content = $content . "\n\n" . $link;
				break;
			}
			/* return the result */
			return $content;
		}
		
		/* adds project summary */
		public static function add_project_summary( $content = '' )
		{
			global $post;
			if( is_singular('project') && ( true === apply_filters( 'projects_plugin_show_summary', true) ) && !self::is_empty( $summary = self::get_project_summary_text( $post->ID ) ) )
			{
				$content = sprintf(
					'<div class="project-summary">%1$s</div>' . "\n\n" . '%2$s',
					wpautop( $summary, false ),
					$content
				);
			}
			return $content;
		}
		
		/* loads front end styles */
		public static function load_front_end_styles()
		{
			if( true === apply_filters( 'projects_plugin_load_css', true ) )
			{
				wp_enqueue_style( 'projects', PRJCTS_URL . 'css/styles.css', null, PRJCTS_VERSION, 'all' );
			}
		}

		/*** POST TYPE ***/

		/* registers project content type and taxonomies */
		public static function register_project_content_type()
		{
			if( !class_exists('Projects_Plugin_Content_Type') ) {
				require_once( PRJCTS_PATH . 'includes/classes/class-projects-plugin-content-type.php' );
			}
			Projects_Plugin_Content_Type::register_project_content_type();
			Projects_Plugin_Content_Type::register_project_taxonomies();
			/* projects_plugin_register_project_content_type hook */
			do_action('projects_plugin_register_project_content_type');
		}
		
		/* WIDGET AREA */
		public static function widgets_init()
		{
			register_sidebar( 
				array(
					'name' => __( 'After Project Widget Area', 'projects' ),
					'id' => 'after-project',
					'description' => __( 'Widgets in this area will be shown after the content on singular project pages.', 'projects' ),
					'before_widget' => '<div id="%1$s" class="widget content-widget %2$s"><div class="widget-inner">',
					'after_widget' => '</div></div>',
					'before_title' => '<h2 class="widgettitle content-widgettitle">',
					'after_title' => '</h2>'
				) 
			);
		}
		
		/* PERMALINKS */
		
		/* returns author portfolio slug */
		public static function get_author_portfolio_slug()
		{
			return self::get_not_empty_option( 'prjcts_author_portfolio_slug', _x( 'portfolios', 'URL friendly slug', 'projects' ) );
		}

		/*** HELPERS ***/
		
		/* returns post meta with default */
		public static function get_not_empty_post_meta( $post_id, $meta_key = '', $singular = false, $default = false )
		{
			return self::is_empty( $data = get_post_meta( $post_id, $meta_key, $singular ) ) ? $default : $data;
		}
		
		/* returns option with default */
		public static function get_not_empty_option( $option, $default )
		{
			return ( self::is_empty( $data = get_option( $option, $default ) ) ) ? $default : $data;
		}
		
		/* validates user option for taxonomy links */
		public static function is_to_show( $data = array() )
		{
			return !empty( $data['show'] );
		}
		
		/* returns project summary meta value */
		public static function get_project_summary_text( $post_id = 0 )
		{
			return ( self::is_empty( $data = get_post_meta( $post_id, '_project_summary', true ) ) ) ? '' : trim( stripcslashes( $data ) );
		}

		/* checks if variable is empty */
		public static function is_empty( $data = null )
		{
			return empty( $data );
		}

		/* returns plugin instance */
		public static function getInstance()
		{
			if ( !isset( self::$_instance ) ) {
				self::$_instance = new self();
			}
			/* projects_plugin_init hook */
			do_action( 'projects_plugin_init', self::$_instance );
			/* return instance */
			return self::$_instance;
		}

		/* loads plugin instance */
		public static function load_plugin()
		{
			return self::getInstance();
		}

		/* fires on plugin activation */
		public static function on_activate()
		{
			/* remove uninstall option */
			delete_option('prjcts_uninstall');
			/* flush rewrite rules */
			load_plugin_textdomain( 'projects', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
			self::register_project_content_type();
			self::register_endpoints();
			flush_rewrite_rules();
			/* projects_plugin_on_activate hook */
			do_action('projects_plugin_on_activate');
		}

		/* fires on plugin uninstall */
		public static function on_uninstall()
		{
			/* check user uninstall / abort if not */
			if( self::is_empty( get_option('prjcts_uninstall') ) )
			{
				return;
			}
			/* remove options */
			foreach( array(
				'prjcts_uninstall',
				'prjcts_reset_settings',
				'prjcts_project_slug',
				'prjcts_project_category_slug',
				'prjcts_project_type_slug',
				'prjcts_project_collection_slug',
				'prjcts_project_client_slug',
				'prjcts_author_portfolio_slug',
				'prjcts_no_summary',
				'prjcts_show_archive_links',
				'prjcts_archive_links',
				'prjcts_show_author_link',
				'prjcts_author_label',
				'prjcts_show_project_links',
				'prjcts_project_link_text',
				'prjcts_project_link_class',
				'prjcts_hide_widgetarea',
				'prjcts_no_css'
			) as $option )
			{
				delete_option( $option );
			}

			/* TODO: delete all projects / their revisions and postmeta */

			/* projects_plugin_on_uninstall hook */
			do_action('projects_plugin_on_uninstall');

			/* flush rewrite rules */
			flush_rewrite_rules();
		}

		/* adds plugin settings link */
		public static function add_settings_link( $links, $file )
		{
			if( $file === plugin_basename( __FILE__ ) )
			{
				array_unshift(
					$links,
					sprintf( '<a href="%s">%s</a>',
						add_query_arg( array(
								'post_type' => 'project',
								'page' => 'projects_plugin'
							),
							admin_url('edit.php')
						),
						__( 'Settings', 'projects' )
					)
				);
			}
			return $links;
		}

		/* adds plugin meta links */
		public static function add_meta_links( $links, $file )
		{
			if( $file === plugin_basename( __FILE__ ) )
			{
				$links[] = sprintf( '<a href="%s" target="_blank">%s</a>',
					'https://wordpress.org/support/plugin/projects-by-serge-liatko/',
					__( 'Support', 'projects' )
				);
				$links[] = sprintf( '<a href="%s" target="_blank">%s</a>',
					'https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=QPF2QLR5BGKGS',
					__( 'Donate', 'projects' )
				);
				$links[] = sprintf( '<a href="%s" target="_blank">%s</a>',
					'https://wordpress.org/plugins/projects-by-serge-liatko/reviews/',
					__( 'Rate this plugin', 'projects' )
				);
			}
			return $links;
		}
	}

	/* load plugin */
	add_action( 'plugins_loaded', array( 'Projects_Plugin', 'load_plugin' ), 0, 0 );

	/* activate hook */
	register_activation_hook( __FILE__, array( 'Projects_Plugin', 'on_activate' ) );

	/* uninstall hook */
	register_uninstall_hook( __FILE__, array( 'Projects_Plugin', 'on_uninstall' ) );
}
