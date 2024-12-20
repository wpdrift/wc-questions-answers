<?php

function custom_css_on_frontend() {
    // Ensure this runs only on the front-end
    if ( ! is_admin() ) {
        // Register the style (we're registering it but not linking to an actual CSS file, hence 'false' as the URL)
        wp_register_style( 'custom-css-on-frontend', false );

        // Enqueue the registered style
        wp_enqueue_style( 'custom-css-on-frontend' );

        // Add the inline CSS to hide the product thumbnail in the cart widget
        $custom_css = '
        #discussion ul,#discussion ol {
            list-style: none;
        }
        .comment-form-author,
        .comment-form-email,
        .comment-form-comment {
            margin-bottom: 15px;
        }

        .comment-form-author label,
        .comment-form-email label,
        .comment-form-comment label {
            font-weight: bold;
        }

        .comment-form-comment textarea {
            width: 100%;
            height: 100px;
            padding: 10px;
        }

        .comment-form-author input,
        .comment-form-email input {
            width: 100%;
            padding: 10px;
        }


        ';
        wp_add_inline_style( 'custom-css-on-frontend', $custom_css );
    }
}
add_action( 'wp_enqueue_scripts', 'custom_css_on_frontend' );


function handle_parent_comment_id( $commentdata ) {

    if ( isset( $_POST['parent_comment_id'] ) ) {
        $commentdata['comment_parent'] = intval( $_POST['parent_comment_id'] );
    }
    if ( isset( $_POST['parent_comment_post_id'] ) ) {
        $commentdata['comment_post_ID'] = intval( $_POST['parent_comment_post_id'] );
    }
    
    return $commentdata;
}
add_filter( 'preprocess_comment', 'handle_parent_comment_id' );


// Add custom image size for product images
function custom_image_sizes() {
    add_image_size( 'custom-product-thumbnail', 75, 75, true ); // Width: 150px, Height: 150px, Crop enabled
}
add_action( 'after_setup_theme', 'custom_image_sizes' );
