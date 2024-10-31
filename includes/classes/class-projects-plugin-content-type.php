<?php
/*
* class: Projects_Plugin_Content_Type
* since: 0.0.1
* description: Registers Project content type and associated taxonomies in WordPress
* version: 0.0.1
* text-domain: projects
*/
if( !class_exists('Projects_Plugin_Content_Type') ) {

	class Projects_Plugin_Content_Type {

		public static $_instance;

		public static function getInstance() {
			if ( !isset( self::$_instance ) ) {
				self::$_instance = new self();
			}
			/* projects_plugin_content_type_init hook */
			do_action( 'projects_plugin_content_type_init', self::$_instance );
			/* return instance */
			return self::$_instance;
		}

		public static function get_project_taxonomy_full_names() {
			$data = self::get_project_taxonomy_names();
			$out = array();
			foreach( $data as $tax ) {
				$taxonomy = get_taxonomy( $tax );
				$out[ $tax ] = $taxonomy->labels->name;
			}
			return $out;
		}

		public static function get_project_taxonomy_names() {
			return array_keys( self::registration_get_project_taxonomies() );
		}

		public static function registration_get_project_taxonomies() {
			return apply_filters( 'prjcts_register_project_taxonomies', array(
				'project_category' => array( __CLASS__, 'register_project_taxonomy_category' ),
				'project_type' => array( __CLASS__, 'register_project_taxonomy_type' ),
				'project_collection' => array( __CLASS__, 'register_project_taxonomy_collection' ),
				'project_client' => array( __CLASS__, 'register_project_taxonomy_client' )
			) );
		}

		public static function register_project_taxonomies() {
			$taxonomies = self::registration_get_project_taxonomies();
			foreach( $taxonomies as $taxonomy => $callback ) {
				call_user_func( $callback );
			}
		}

		public static function register_project_taxonomy_client() {
			$labels = array(
				'name'                       => _x( 'Clients', 'Taxonomy General Name', 'projects' ),
				'singular_name'              => _x( 'Client', 'Taxonomy Singular Name', 'projects' ),
				'menu_name'                  => __( 'Clients', 'projects' ),
				'all_items'                  => __( 'All Clients', 'projects' ),
				'parent_item'                => __( 'Parent Client', 'projects' ),
				'parent_item_colon'          => __( 'Parent Client:', 'projects' ),
				'new_item_name'              => __( 'New Client Name', 'projects' ),
				'add_new_item'               => __( 'Add New Client', 'projects' ),
				'edit_item'                  => __( 'Edit Client', 'projects' ),
				'update_item'                => __( 'Update Client', 'projects' ),
				'view_item'                  => __( 'View Client', 'projects' ),
				'separate_items_with_commas' => __( 'Separate clients with commas', 'projects' ),
				'add_or_remove_items'        => __( 'Add or remove clients', 'projects' ),
				'choose_from_most_used'      => __( 'Choose from the most used clients', 'projects' ),
				'popular_items'              => __( 'Popular Clients', 'projects' ),
				'search_items'               => __( 'Search Clients', 'projects' ),
				'not_found'                  => __( 'No clients found', 'projects' ),
				'no_terms'                   => __( 'No clients', 'projects' ),
				'items_list'                 => __( 'clients list', 'projects' ),
				'items_list_navigation'      => __( 'clients list navigation', 'projects' ),
			);
			$rewrite = array(
				'slug'                       => self::get_slug_option( 'prjcts_project_client_slug', _x( 'project-clients', 'URL friendly slug', 'projects' ) ),
				'with_front'                 => false,
				'hierarchical'               => false,
			);
			$args = array(
				'labels'                     => $labels,
				'hierarchical'               => false,
				'public'                     => true,
				'show_ui'                    => true,
				'show_admin_column'          => true,
				'show_in_nav_menus'          => true,
				'show_tagcloud'              => true,
				'rewrite'                    => $rewrite,
			);
			register_taxonomy( 'project_client', array( 'project' ), $args );
		}

		public static function register_project_taxonomy_collection() {
			$labels = array(
				'name'                       => _x( 'Collections', 'Taxonomy General Name', 'projects' ),
				'singular_name'              => _x( 'Collection', 'Taxonomy Singular Name', 'projects' ),
				'menu_name'                  => __( 'Collections', 'projects' ),
				'all_items'                  => __( 'All Collections', 'projects' ),
				'parent_item'                => __( 'Parent Collection', 'projects' ),
				'parent_item_colon'          => __( 'Parent Collection:', 'projects' ),
				'new_item_name'              => __( 'New Collection Name', 'projects' ),
				'add_new_item'               => __( 'Add New Collection', 'projects' ),
				'edit_item'                  => __( 'Edit Collection', 'projects' ),
				'update_item'                => __( 'Update Collection', 'projects' ),
				'view_item'                  => __( 'View Collection', 'projects' ),
				'separate_items_with_commas' => __( 'Separate collections with commas', 'projects' ),
				'add_or_remove_items'        => __( 'Add or remove collections', 'projects' ),
				'choose_from_most_used'      => __( 'Choose from the most used collections', 'projects' ),
				'popular_items'              => __( 'Popular Collections', 'projects' ),
				'search_items'               => __( 'Search Collections', 'projects' ),
				'not_found'                  => __( 'Not collections found', 'projects' ),
				'no_terms'                   => __( 'No collections', 'projects' ),
				'items_list'                 => __( 'collections list', 'projects' ),
				'items_list_navigation'      => __( 'collections list navigation', 'projects' ),
			);
			$rewrite = array(
				'slug'                       => self::get_slug_option( 'prjcts_project_collection_slug', _x( 'project-collections', 'URL friendly slug', 'projects' ) ),
				'with_front'                 => false,
				'hierarchical'               => false,
			);
			$args = array(
				'labels'                     => $labels,
				'hierarchical'               => false,
				'public'                     => true,
				'show_ui'                    => true,
				'show_admin_column'          => true,
				'show_in_nav_menus'          => true,
				'show_tagcloud'              => true,
				'rewrite'                    => $rewrite,
			);
			register_taxonomy( 'project_collection', array( 'project' ), $args );
		}

		public static function register_project_taxonomy_type() {
			$labels = array(
				'name'                       => _x( 'Types', 'Taxonomy General Name', 'projects' ),
				'singular_name'              => _x( 'Type', 'Taxonomy Singular Name', 'projects' ),
				'menu_name'                  => __( 'Types', 'projects' ),
				'all_items'                  => __( 'All Types', 'projects' ),
				'parent_item'                => __( 'Parent Type', 'projects' ),
				'parent_item_colon'          => __( 'Parent Type:', 'projects' ),
				'new_item_name'              => __( 'New Type Name', 'projects' ),
				'add_new_item'               => __( 'Add New Type', 'projects' ),
				'edit_item'                  => __( 'Edit Type', 'projects' ),
				'update_item'                => __( 'Update Type', 'projects' ),
				'view_item'                  => __( 'View Type', 'projects' ),
				'separate_items_with_commas' => __( 'Separate types with commas', 'projects' ),
				'add_or_remove_items'        => __( 'Add or remove types', 'projects' ),
				'choose_from_most_used'      => __( 'Choose from the most used types', 'projects' ),
				'popular_items'              => __( 'Popular Types', 'projects' ),
				'search_items'               => __( 'Search Types', 'projects' ),
				'not_found'                  => __( 'No types found', 'projects' ),
				'no_terms'                   => __( 'No types', 'projects' ),
				'items_list'                 => __( 'types list', 'projects' ),
				'items_list_navigation'      => __( 'types list navigation', 'projects' ),
			);
			$rewrite = array(
				'slug'                       => self::get_slug_option( 'prjcts_project_type_slug', _x( 'project-types', 'URL friendly slug', 'projects' ) ),
				'with_front'                 => false,
				'hierarchical'               => true,
			);
			$args = array(
				'labels'                     => $labels,
				'hierarchical'               => true,
				'public'                     => true,
				'show_ui'                    => true,
				'show_admin_column'          => true,
				'show_in_nav_menus'          => true,
				'show_tagcloud'              => true,
				'rewrite'                    => $rewrite,
			);
			register_taxonomy( 'project_type', array( 'project' ), $args );
		}

		public static function register_project_taxonomy_category() {
			$labels = array(
				'name'                       => _x( 'Categories', 'Taxonomy General Name', 'projects' ),
				'singular_name'              => _x( 'Category', 'Taxonomy Singular Name', 'projects' ),
				'menu_name'                  => __( 'Categories', 'projects' ),
				'all_items'                  => __( 'All Categories', 'projects' ),
				'parent_item'                => __( 'Parent Category', 'projects' ),
				'parent_item_colon'          => __( 'Parent Category:', 'projects' ),
				'new_item_name'              => __( 'New Category Name', 'projects' ),
				'add_new_item'               => __( 'Add New Category', 'projects' ),
				'edit_item'                  => __( 'Edit Category', 'projects' ),
				'update_item'                => __( 'Update Category', 'projects' ),
				'view_item'                  => __( 'View Category', 'projects' ),
				'separate_items_with_commas' => __( 'Separate categories with commas', 'projects' ),
				'add_or_remove_items'        => __( 'Add or remove categories', 'projects' ),
				'choose_from_most_used'      => __( 'Choose from the most used categories', 'projects' ),
				'popular_items'              => __( 'Popular Categories', 'projects' ),
				'search_items'               => __( 'Search Categories', 'projects' ),
				'not_found'                  => __( 'No categories found', 'projects' ),
				'no_terms'                   => __( 'No categories', 'projects' ),
				'items_list'                 => __( 'categories list', 'projects' ),
				'items_list_navigation'      => __( 'categories list navigation', 'projects' ),
			);
			$rewrite = array(
				'slug'                       => self::get_slug_option( 'prjcts_project_category_slug', _x( 'project-categories', 'URL friendly slug', 'projects' ) ),
				'with_front'                 => false,
				'hierarchical'               => true,
			);
			$args = array(
				'labels'                     => $labels,
				'hierarchical'               => true,
				'public'                     => true,
				'show_ui'                    => true,
				'show_admin_column'          => true,
				'show_in_nav_menus'          => true,
				'show_tagcloud'              => true,
				'rewrite'                    => $rewrite,
			);
			register_taxonomy( 'project_category', array( 'project' ), $args );
		}

		public static function register_project_content_type() {
			$labels = array(
				'name'                  => _x( 'Projects', 'Post Type General Name', 'projects' ),
				'singular_name'         => _x( 'Project', 'Post Type Singular Name', 'projects' ),
				'menu_name'             => __( 'Projects', 'projects' ),
				'name_admin_bar'        => _x( 'Project', 'New in Admin menu bar', 'projects' ),
				'archives'              => __( 'Project Archives', 'projects' ),
				'parent_item_colon'     => __( 'Parent Project:', 'projects' ),
				'all_items'             => __( 'All Projects', 'projects' ),
				'add_new_item'          => __( 'Add New Project', 'projects' ),
				'add_new'               => _x( 'Add New', 'project', 'projects' ),
				'new_item'              => __( 'New Project', 'projects' ),
				'edit_item'             => __( 'Edit Project', 'projects' ),
				'update_item'           => __( 'Update Project', 'projects' ),
				'view_item'             => __( 'View Project', 'projects' ),
				'search_items'          => __( 'Search Projects', 'projects' ),
				'not_found'             => __( 'Not found', 'projects' ),
				'not_found_in_trash'    => __( 'Not found in Trash', 'projects' ),
				'featured_image'        => __( 'Featured Image', 'projects' ),
				'set_featured_image'    => __( 'Set featured image', 'projects' ),
				'remove_featured_image' => __( 'Remove featured image', 'projects' ),
				'use_featured_image'    => __( 'Use as featured image', 'projects' ),
				'insert_into_item'      => __( 'Insert into project', 'projects' ),
				'uploaded_to_this_item' => __( 'Uploaded to this project', 'projects' ),
				'items_list'            => __( 'Projects list', 'projects' ),
				'items_list_navigation' => __( 'Projects list navigation', 'projects' ),
				'filter_items_list'     => __( 'Filter projects list', 'projects' ),
			);
			$rewrite = array(
				'slug'                  => self::get_slug_option( 'prjcts_project_slug', _x( 'projects', 'URL friendly slug', 'projects' ) ),
				'with_front'            => false,
				'pages'                 => true,
				'feeds'                 => true,
			);
			$args = array(
				'label'                 => __( 'Project', 'projects' ),
				'description'           => __( 'Current and accoplished projects.', 'projects' ),
				'labels'                => $labels,
				'supports'              => array( 'title', 'editor', 'author', 'excerpt', 'thumbnail', 'revisions', 'custom-fields' ),
				'taxonomies'            => self::get_project_taxonomy_names(),
				'hierarchical'          => false,
				'public'                => true,
				'show_ui'               => true,
				'show_in_menu'          => true,
				'menu_position'         => 5,
				'menu_icon'             => 'dashicons-clipboard',
				'show_in_admin_bar'     => true,
				'show_in_nav_menus'     => true,
				'can_export'            => true,
				'has_archive'           => true,
				'exclude_from_search'   => false,
				'publicly_queryable'    => true,
				'rewrite'               => $rewrite,
				'capability_type'       => 'post',
			);
			register_post_type( 'project', $args );
		}

		public static function get_slug_option( $option, $default ) {
			return self::is_empty( $slug = get_option( $option ) ) ? $default : $slug;
		}

		public static function is_empty( $data = null ) {
			return empty( $data );
		}

	}
}
