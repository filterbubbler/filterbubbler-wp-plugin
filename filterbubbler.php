<?php

/*
Plugin Name: FilterBubbler Plugin
Description: This plug-in makes your WordPress site into an FilterBubbler repository for corpora and recipes
Author: Brainfood
Author URI: http://www.brainfood.com
*/

defined( 'ABSPATH' ) or die( 'No direct access allowed' );

/**
 * Register custom post types for FilterBubbler
 */
add_action( 'init', 'filterbubbler_cpt' );
function filterbubbler_cpt() {
    // Register the classification post type
    register_post_type( 'fb_corpus', array(
        'labels' => array(
          'name' => 'Corpura',
          'singular_name' => 'Corpus',
        ),
        'description' => 'Corpura for FilterBubbler',
        'public' => true,
        'menu_position' => 20,
        'supports' => array( 'title', 'editor', 'custom-fields' )
    ));

    // Register the classification post type
    register_post_type( 'fb_classification', array(
        'labels' => array(
          'name' => 'Classifications',
          'singular_name' => 'Classification',
        ),
        'description' => 'Classifications for FilterBubbler',
        'public' => true,
        'menu_position' => 20,
        'supports' => array( 'title', 'editor', 'custom-fields' )
    ));

    // Register the recipe post type
    register_post_type( 'fb_recipe', array(
        'labels' => array(
          'name' => 'Recipes',
          'singular_name' => 'Recipe',
        ),
        'description' => 'Recipes for FilterBubbler',
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
function fb_list_corpura( $data ) {
    $corpus_posts = get_posts(array(
        'post_type' => 'fb_corpus',
        'orderby' => 'title'
    ));

    $corpura = array();

    foreach ($corpus_posts as $post) {
        array_push($corpura, array('name' => $post->post_name, 'description' => $post->post_content));
    }

    return new WP_REST_Response($corpura, 200);
}

// Register the fb_get_corpura REST function
add_action( 'rest_api_init', function () {
  register_rest_route( 'filterbubbler/v1', '/corpus', array(
    'methods' => 'GET',
    'callback' => 'fb_list_corpura',
  ) );
} );

/**
 * Read a corpus
 *
 * @param array $data Options for the function.
 * @return string|null corpora names,  * or null if none.
 */
function fb_read_corpus( $data ) {
    $corpus = get_posts(array(
        'post_type' => 'fb_corpus',
        'orderby' => 'title'
    ));
    return new WP_REST_Response($corpus, 200);
}

// Register the fb_get_corpura REST function
add_action( 'rest_api_init', function () {
  register_rest_route( 'filterbubbler/v1', '/corpus/(?P<id>\d+)', array(
    'methods' => 'GET',
    'callback' => 'fb_read_corpura',
  ) );
} );

/**
 * Create a new corpura
 *
 * @param array $data Options for the function.
 * @return string|null corpora names,  * or null if none.
 */
function fb_create_corpus( $data ) {
    $corpus_name = strtolower(wp_strip_all_tags($data['title']));
    $corpus_description = wp_strip_all_tags($data['description']);

    if (fb_corpus_exists($corpus_name)) {
        return new WP_Error( 'code', 'Corpus '.$corpus_name.' already exists');
    }

    // Create post object
    $post = array(
      'post_type'     => 'fb_corpus',
      'post_title'    => wp_strip_all_tags($data['title']),
      'post_name'     => $corpus_name,
      'post_content'  => $corpus_description,
      'post_status'   => 'publish'
    );
     
    // Insert the post into the database
    $post_id = wp_insert_post( $post );

    return new WP_REST_Response(array(
        'id' => $post_id,
    ), 200);
}

// Register the fb_create_corpura REST function
add_action( 'rest_api_init', function () {
  register_rest_route( 'filterbubbler/v1', '/corpus', array(
    'methods' => 'POST',
    'callback' => 'fb_create_corpus',
  ) );
} );

/**
 * Get a list of classifications
 *
 * @param array $data Options for the function.
 * @return string|null corpora names,  * or null if none.
 */
function fb_list_classifications( $data ) {
    $corpura_posts = get_posts(array(
        'post_type' => 'fb_classification',
        'orderby' => 'title'
    ));

    $corpura = array();

    foreach($corpura_posts as $post) {
        array_push($corpura, array(
            'corpus' => get_post_meta($post->ID, 'corpus', true),
            'url' => get_post_meta($post->ID, 'url', true),
            'classification' => get_post_meta($post->ID, 'classification', true),
        ));
    }

    return new WP_REST_Response(array(
        'corpus' => $data['corpus'],
        'classifications' => $corpura,
    ), 200);
}

// Register the fb_get_classifications REST function
add_action( 'rest_api_init', function () {
  register_rest_route( 'filterbubbler/v1', '/classification', array(
    'methods' => 'GET',
    'callback' => 'fb_list_classifications',
  ) );

  // With corpus
  register_rest_route( 'filterbubbler/v1', '/classification/(?P<corpus>\w+)', array(
    'methods' => 'GET',
    'callback' => 'fb_list_classifications',
  ) );
} );

/**
 * Create a new classification
 *
 * @param array $data Options for the function.
 * @return string|null corpora names,  * or null if none.
 */
function fb_create_classification( $data ) {
    $corpus = strtolower(wp_strip_all_tags($data['corpus']));
    $classification = strtolower(wp_strip_all_tags($data['classification']));
    $url = wp_strip_all_tags($data['url']);

    if (!fb_corpus_exists($corpus)) {
        return new WP_Error( 'code', 'Unknown corpus '.$corpus);
    }

    if (fb_classification_exists($corpus, $url, $classification)) {
        return new WP_Error( 'code', 'Classification '.$classification.' already exists for '.$corpus);
    }

    // Create post object
    $post = array(
      'post_type'     => 'fb_classification',
      'post_title'    => $corpus.','.$url.','.$classification,
      'post_name'     => sha1($corpus.$url.$classification),
      'post_status'   => 'publish',
      'meta_input'    => array(
          'corpus' => $corpus,
          'url' => $url,
          'classification' => $classification
      )
    );
     
    // Insert the post into the database
    $post_id = wp_insert_post( $post );

    return new WP_REST_Response(array(
        'id' => $post_id,
    ), 200);
}

// Register the fb_get_classifications REST function
add_action( 'rest_api_init', function () {
  register_rest_route( 'filterbubbler/v1', '/classification', array(
    'methods' => 'POST',
    'callback' => 'fb_create_classification',
  ) );
} );

/**
 * Get a list of recipes
 *
 * @param array $data Options for the function.
 * @return string|null corpora names,  * or null if none.
 */
function fb_get_recipes( $data ) {
    // Stub
    return null;
}

// Register the fb_get_recipes REST function
add_action( 'rest_api_init', function () {
  register_rest_route( 'filterbubbler/v1', '/recipe/(?P<id>\d+)', array(
    'methods' => 'GET',
    'callback' => 'fb_get_recipes',
  ) );
} );

/**
 * Basic support functions
 */

/**
 * Check if a corpura exists
 */
function fb_corpus_exists($name) {
    $corpura = get_posts(array(
        'post_type' => 'fb_corpus',
        'name' => $name
    ));

    return count($corpura) > 0;
}

/**
 * Check if a classification exists
 */
function fb_classification_exists($corpus, $url, $classification) {
    $classifications = get_posts(array(
        'post_type' => 'fb_classification',
        'name' => sha1($corpus.$url.$classification),
    ));

    return count($classifications) > 0;
}
?>
