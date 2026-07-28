<?php
/*
* Creating a function to create theme CPTs
*/
 
function lptv_register_theme_custom_post_types() {

    // Set UI labels for Custom Post Type quick Repeats
    $quick_repeats_labels = array(
        'name'                => _x( 'Quick Repeats', 'Post Type General Name', 'lptv' ),
        'singular_name'       => _x( 'Quick Repeat', 'Post Type Singular Name', 'lptv' ),
        'menu_name'           => __( 'Quick Repeats', 'lptv' ),
        'parent_item_colon'   => __( 'Parent Quick Repeat', 'lptv' ),
        'all_items'           => __( 'All Quick Repeats', 'lptv' ),
        'view_item'           => __( 'View Quick Repeat', 'lptv' ),
        'add_new_item'        => __( 'Add New Quick Repeat', 'lptv' ),
        'add_new'             => __( 'Add New', 'lptv' ),
        'edit_item'           => __( 'Edit Quick Repeat', 'lptv' ),
        'update_item'         => __( 'Update Quick Repeat', 'lptv' ),
        'search_items'        => __( 'Search Quick Repeat', 'lptv' ),
        'not_found'           => __( 'Not Found', 'lptv' ),
        'not_found_in_trash'  => __( 'Not found in Trash', 'lptv' ),
    );
     
    // Set other options for Custom Post Type
    $quick_repeats_args = array(
        'label'               => __( 'Quick Repeats', 'lptv' ),
        'description'         => __( 'Quick Repeats list', 'lptv' ),
        'labels'              => $quick_repeats_labels,
        // Features this CPT supports in Post Editor
        'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'custom-fields', 'page-attributes' ),    
        'hierarchical'        => false,
        'public'              => true,
        'publicly_queryable'  => true,
        'query_var'           => true,
        'rewrite'             => array( 'slug' => 'repeat' ),
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-controls-repeat',
        'can_export'          => true,
        'has_archive'         => true,
        'capability_type'     => 'post',
        'show_in_rest'        => true,  
        'exclude_from_search' => true,
    );
     
    // Registering your Custom Post Type
    register_post_type( 'quick_repeats', $quick_repeats_args );



    // Set UI labels for Custom Post Type Section
    $section_labels = array(
        'name'                => _x( 'Sections', 'Post Type General Name', 'lptv' ),
        'singular_name'       => _x( 'Section', 'Post Type Singular Name', 'lptv' ),
        'menu_name'           => __( 'Sections', 'lptv' ),
        'parent_item_colon'   => __( 'Parent Section', 'lptv' ),
        'all_items'           => __( 'All Sections', 'lptv' ),
        'view_item'           => __( 'View Section', 'lptv' ),
        'add_new_item'        => __( 'Add New Section', 'lptv' ),
        'add_new'             => __( 'Add New', 'lptv' ),
        'edit_item'           => __( 'Edit Section', 'lptv' ),
        'update_item'         => __( 'Update Section', 'lptv' ),
        'search_items'        => __( 'Search Section', 'lptv' ),
        'not_found'           => __( 'Not Found', 'lptv' ),
        'not_found_in_trash'  => __( 'Not found in Trash', 'lptv' ),
    );
     
    // Set other options for Custom Post Type
    $section_args = array(
        'label'               => __( 'Sections', 'lptv' ),
        'description'         => __( 'Section list', 'lptv' ),
        'labels'              => $section_labels,
        // Features this CPT supports in Post Editor
        'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'thumbnail', 'comments', 'revisions', 'custom-fields', 'page-attributes' ),    
        'hierarchical'        => false,
        'public'              => true,
        'publicly_queryable'  => true,
        'query_var'           => true,
        'rewrite'             => array( 'slug' => 'section' ),
        'show_ui'             => true,
        'show_in_menu'        => true,
        'show_in_nav_menus'   => true,
        'show_in_admin_bar'   => true,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-layout',
        'can_export'          => true,
        'has_archive'         => true,
        'capability_type'     => 'post',
        'show_in_rest'        => true,  
        'exclude_from_search' => true,
    );
     
    // Registering your Custom Post Type
    register_post_type( 'section', $section_args );

     


          
 
}
 
/* Hook into the 'init' action so that the function
* Containing our post type registration is not 
* unnecessarily executed. 
*/
 
add_action( 'init', 'lptv_register_theme_custom_post_types' );