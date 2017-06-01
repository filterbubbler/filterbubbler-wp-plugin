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
          'name' => 'Corpora',
          'singular_name' => 'Corpus',
        ),
        'description' => 'Corpora for FilterBubbler',
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

// Bind functions to REST calls
add_action( 'rest_api_init', function () {
  register_rest_route( 'filterbubbler/v1', '/classification', array(
    'methods' => 'GET',
    'callback' => 'fb_list_classifications',
  ) );

  register_rest_route( 'filterbubbler/v1', '/classification/(?P<corpus>\w+)', array(
    'methods' => 'GET',
    'callback' => 'fb_list_classifications',
  ) );

  register_rest_route( 'filterbubbler/v1', '/corpus', array(
    'methods' => 'GET',
    'callback' => 'fb_list_corpora',
  ) );

  register_rest_route( 'filterbubbler/v1', '/corpus/(?P<corpus>\w+)', array(
    'methods' => 'GET',
    'callback' => 'fb_list_classifications',
  ) );

  register_rest_route( 'filterbubbler/v1', '/corpus', array(
    'methods' => 'POST',
    'callback' => 'fb_create_corpus',
  ) );

  register_rest_route( 'filterbubbler/v1', '/classification', array(
    'methods' => 'POST',
    'callback' => 'fb_create_classification',
  ) );

  register_rest_route( 'filterbubbler/v1', '/corpus/(?P<corpus>\w+)', array(
    'methods' => 'POST',
    'callback' => 'fb_create_classification',
  ) );

  register_rest_route( 'filterbubbler/v1', '/recipe', array(
    'methods' => 'GET',
    'callback' => 'fb_get_recipes',
  ) );
} );


/**
 * Get a list of corpora
 *
 * @param array $data Options for the function.
 * @return string|null corpora names,  * or null if none.
 */
function fb_list_corpora( $data ) {
    $corpus_posts = get_posts(array(
        'post_type' => 'fb_corpus',
        'orderby' => 'title'
    ));

    $corpora = array();

    foreach ($corpus_posts as $post) {
        array_push($corpora, array('name' => $post->post_name, 'description' => $post->post_content));
    }

    return new WP_REST_Response($corpora, 200);
}

/**
 * Read a corpus
 *
 * @param array $data Options for the function.
 * @return string|null corpora names,  * or null if none.
 */
function fb_read_corpus( $data ) {
    $corpus_posts = get_posts(array(
        'post_type' => 'fb_corpus',
        'orderby' => 'title',
        'post_title' => $data['id']
    ));

    $corpora = array();

    foreach ($corpus_posts as $post) {
        array_push($corpora, array('name' => $post->post_name, 'description' => $post->post_content));
    }

    return new WP_REST_Response($corpora, 200);
}

/**
 * Create a new corpora
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

/**
 * Get a list of classifications
 *
 * @param array $data Options for the function.
 * @return string|null corpora names,  * or null if none.
 */
function fb_list_classifications( $data ) {
    $corpora_posts = get_posts(array(
        'post_type' => 'fb_classification',
        'orderby' => 'title'
    ));

    $corpora = array();

    foreach($corpora_posts as $post) {
        array_push($corpora, array(
            'url' => get_post_meta($post->ID, 'url', true),
            'classification' => get_post_meta($post->ID, 'classification', true),
        ));
    }

    return new WP_REST_Response(array(
        'corpus' => $data['corpus'],
        'classifications' => $corpora,
    ), 200);
}

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

/**
 * Get a list of recipes
 *
 * @param array $data Options for the function.
 * @return string|null corpora names,  * or null if none.
 */
function fb_get_recipes( $data ) {
    // Stub
    $recipe_posts = get_posts(array(
        'post_type' => 'fb_recipe',
        'orderby' => 'title'
    ));

    $recipes = array();

    foreach($recipe_posts as $post) {
        array_push($recipes, json_decode($post->post_content));
    }

    return new WP_REST_Response($recipes, 200);
    return null;
}

/**
 * Basic support functions
 */

/**
 * Check if a corpora exists
 */
function fb_corpus_exists($name) {
    $corpora = get_posts(array(
        'post_type' => 'fb_corpus',
        'name' => $name
    ));

    return count($corpora) > 0;
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
