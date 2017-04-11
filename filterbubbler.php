<?php

/*
Plugin Name: InfoBubbles Plugin
Description: This plug-in makes your WordPress site into an InfoBubbles repository for corpora and recipes
Author: Brainfood
Author URI: http://www.brainfood.com
*/

defined( 'ABSPATH' ) or die( 'No direct access allowed' );

/**
 * Register custom post types for InfoBubbles
 */
add_action( 'init', 'infobubbles_cpt' );
function infobubbles_cpt() {
    // Register the classification post type
    register_post_type( 'ib_corpus', array(
        'labels' => array(
          'name' => 'Corpura',
          'singular_name' => 'Corpus',
        ),
        'description' => 'Corpura for InfoBubbles',
        'public' => true,
        'menu_position' => 20,
        'supports' => array( 'title', 'editor', 'custom-fields' )
    ));

    // Register the classification post type
    register_post_type( 'ib_classification', array(
        'labels' => array(
          'name' => 'Classifications',
          'singular_name' => 'Classification',
        ),
        'description' => 'Classifications for InfoBubbles',
        'public' => false,
        'menu_position' => 20,
        'supports' => array( 'title', 'editor', 'custom-fields' )
    ));

    // Register the recipe post type
    register_post_type( 'ib_recipe', array(
        'labels' => array(
          'name' => 'Recipes',
          'singular_name' => 'Recipe',
        ),
        'description' => 'Recipes for InfoBubbles',
        'public' => true,
        'menu_position' => 20,
        'supports' => array( 'title', 'editor', 'custom-fields' )
    ));
}

/**
 * Get a list of corpura
 *
 * @param array $data Options for the function.
 * @return string|null corpora names,  * or null if none.
 */
function ib_get_corpura( $data ) {
    // Stub
    return null;
}

// Register the ib_get_corpura REST function
add_action( 'rest_api_init', function () {
  register_rest_route( 'infobubbles/v1', '/corpura/(?P<id>\d+)', array(
    'methods' => 'GET',
    'callback' => 'ib_get_corpura',
  ) );
} );

/**
 * Get a list of classifications
 *
 * @param array $data Options for the function.
 * @return string|null corpora names,  * or null if none.
 */
function ib_get_classifications( $data ) {
    // Stub
    return null;
}

// Register the ib_get_classifications REST function
add_action( 'rest_api_init', function () {
  register_rest_route( 'infobubbles/v1', '/classifications/(?P<id>\d+)', array(
    'methods' => 'GET',
    'callback' => 'ib_get_classifications',
  ) );
} );


/**
 * Get a list of recipes
 *
 * @param array $data Options for the function.
 * @return string|null corpora names,  * or null if none.
 */
function ib_get_recipes( $data ) {
    // Stub
    return null;
}

// Register the ib_get_recipes REST function
add_action( 'rest_api_init', function () {
  register_rest_route( 'infobubbles/v1', '/recipes/(?P<id>\d+)', array(
    'methods' => 'GET',
    'callback' => 'ib_get_recipes',
  ) );
} );

?>
