<?php
/**
 * Plugin Name: Premier Shop This Post
 * Plugin URI: 
 * Description: Customize display of Shop This Post Widget
 * Version: 1.0
 * Author: Andrew Dushane
 * Author URI: http://premierprograming.com
 * Text Domain: premier-shop-this-post
 * License: GPL2
 */

/**
 * Adds meta box to post editing screen
 */
function premier_shop_this_post_meta_box() {
    add_meta_box(
        'premier-shop-this-post',
        __( 'Shop This Post Widget', 'premier-shop-this-post' ),
        'premier_shop_this_post_meta_box_callback',
        'post',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes_post', 'premier_shop_this_post_meta_box' );

/**
 * Prints the box content.
 * 
 * @param WP_Post $post The object for the current post/page.
 */
function premier_shop_this_post_meta_box_callback( $post ) {

	// Add a nonce field so we can check for it later.
	wp_nonce_field( 'premier_shop_this_post_save_meta_box', 'premier_shop_this_post_meta_box_nonce' );

	/*
	 * Use get_post_meta() to retrieve an existing value
	 * from the database and use the value for the form.
	 */
	$meta = get_post_meta( $post->ID, '_premier_shop_this_post', true );
    if ( $meta && !is_array( $meta ) ) { //Preserve functionality when updating to new version of plugin
        $title = 'Shop This Post';
        $widget = $meta;
    } elseif( is_array( $meta ) ) {
        $title = $meta['title'];
        $widget = $meta['widget'];
    } else {
        $title = '';
        $widget = '';
    }
    echo '<label for="premier_shop_this_post_title">';
    _e( 'Shop This Post Widget Title:', 'premier-shop-this-post' );
    echo '</label><br>';
    echo '<input type="text" id="premier_shop_this_post_title" name="premier_shop_this_post_title" class="regular-text" value="' . $title . '" />';
	echo '<br><label for="premier_shop_this_post_widget">';
	_e( 'Paste Shop This Post widget here:', 'premier-shop-this-post' );
	echo '</label><br>';
	echo '<textarea id="premier_shop_this_post_widget" name="premier_shop_this_post_widget" cols="80" rows="10" class="large-text">';
    echo $widget;
    echo '</textarea>';
}

/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function premier_shop_this_post_save_meta_box( $post_id ) {

	/*
	 * We need to verify this came from our screen and with proper authorization,
	 * because the save_post action can be triggered at other times.
	 */

	// Check if our nonce is set.
	if ( ! isset( $_POST['premier_shop_this_post_meta_box_nonce'] ) ) {
		return;
	}

	// Verify that the nonce is valid.
	if ( ! wp_verify_nonce( $_POST['premier_shop_this_post_meta_box_nonce'], 'premier_shop_this_post_save_meta_box' ) ) {
		return;
	}

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check the user's permissions.
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

	/* OK, it's safe for us to save the data now. */
	
	// Make sure that it is set.
	if ( ! isset( $_POST['premier_shop_this_post_widget'] ) ) {
		return;
	}

	$update = array();
    if ( isset( $_POST['premier_shop_this_post_title'] ) ) {
        $update['title'] = $_POST['premier_shop_this_post_title'];
    }
    $update['widget'] = $_POST['premier_shop_this_post_widget'];

	// Update the meta field in the database.
	update_post_meta( $post_id, '_premier_shop_this_post', $update );
}
add_action( 'save_post', 'premier_shop_this_post_save_meta_box' );

/**
 * Enqueue Style Sheet
 */
function premier_shop_this_post_style() {
    wp_register_style( 'premier_shop_this_post_css', plugins_url( 'css/style.css', __FILE__ ) );
    wp_enqueue_style( 'premier_shop_this_post_css' );
}
add_action( 'wp_enqueue_scripts' , 'premier_shop_this_post_style' );

/**
 * Generate Shop This Post markup
 */
function premier_shop_this_post_markup( $content ) {
    $postid = get_the_ID();
    $meta = get_post_meta( $postid, '_premier_shop_this_post', true );
    if ( $meta && !is_array( $meta ) ) { //Preserve functionality when updating to new version of plugin
        $title = 'Shop This Post';
        $widget = $meta;
    } elseif( is_array( $meta ) ) {
        $title = $meta['title'];
        $widget = $meta['widget'];
    }
    if( isset( $widget ) ) {
        $shop_display = '<div class="premier-shop-this-post" id="premier-shop-this-post-' . $postid . '">';
        $shop_display .= '<h4>' . $title . '</h4>';
        $shop_display .= do_shortcode( $widget );
        $shop_display .= '</div>';
        return $shop_display;
    } else return false;
}

/**
 * Display Shop This Post at the end of the post
 */
function premier_shop_this_post_display_single( $content ) {
	if ( is_single() ) {
        $shop_display = premier_shop_this_post_markup( $content );
        if ( $shop_display && $shop_display != '') {
            $content .= $shop_display;   
        }
	}
	return $content;
}
add_filter('the_content', 'premier_shop_this_post_display_single');

/**
 * Display Shop This Post with the excerpt
 */
function premier_shop_this_post_display_excerpt( $more ) {
     $shop_display = premier_shop_this_post_markup( $more );
    if ( $shop_display && $shop_display != '') {
        $split_more = preg_split( '@(?=<a)@' , $more );
        $more = $split_more[0] . $shop_display . $split_more[1];   
    }
return $more;
}
add_filter( 'excerpt_more', 'premier_shop_this_post_display_excerpt' );
