<?php
// discussion-template.php

// Ensure that this file is being used in the correct context
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Get the comment ID from the query variable
$comment_answers_id = get_query_var( 'comment_answers_id' );

// Default values for author and email
$comment_author = '';
$comment_author_email = '';

// If the user is logged in, use their details
if ( is_user_logged_in() ) {
    $current_user = wp_get_current_user();
    $comment_author = $current_user->display_name; // User's name
    $comment_author_email = $current_user->user_email; // User's email
}

if ( $comment_answers_id ) {
    // Get the parent comment (question)
    $parent_comment = get_comment( $comment_answers_id );

    if ( $parent_comment && $parent_comment->comment_parent == 0 && $parent_comment->comment_type == 'discussion' ) {
        // Get the post that the comment belongs to (likely a WooCommerce product or any other post type)
        $post = get_post( $parent_comment->comment_post_ID );
        setup_postdata( $post );

        // Get the product details if it's a product (WooCommerce)
        if ( 'product' === $post->post_type ) {
            $product_name = get_the_title( $post->ID );  // Product name
            $product_image = get_the_post_thumbnail( $post->ID, 'custom-product-thumbnail' ); // Product image (thumbnail size)
        }

        ?>

        <!-- Display the product image and name -->
        <h3>
            <?php if ( ! empty( $product_image ) ) : ?>
                <div class="product-info">
                    <div class="product-image">
                        <?php echo wp_kses_post( $product_image ); ?>
                    </div>
                    <div class="product-name">
                        <strong><?php echo esc_html( $product_name ); ?></strong>
                    </div>
                </div>
            <?php endif; ?>
            <p><?php echo esc_html( $parent_comment->comment_content ); ?><p>
        </h3>

        <p><?php echo esc_html( get_comment_author( $parent_comment ) ); ?> asked this question on <?php echo esc_html( get_comment_date( 'F j, Y', $parent_comment ) ); ?></p>

        <h3><?php esc_html_e( 'Answers:', 'yourtheme' ); ?></h3>

        <?php
        // Query the child comments (answers) for this parent comment
        $args = array(
            'status'   => 'approve',
            'parent'   => $comment_answers_id, // Only replies (answers) to this parent comment
            'post_id'  => $parent_comment->comment_post_ID, // Filter by the post ID
        );
        $answers = get_comments( $args );

        // Display answers if available
        if ( $answers ) {
            foreach ( $answers as $answer ) {
                ?>
                <div class="answer">
                    <p><strong><?php echo esc_html( get_comment_author( $answer ) ); ?>:</strong> <?php echo esc_html( $answer->comment_content ); ?></p>
                    <p><?php echo esc_html( get_comment_date( 'F j, Y', $answer ) ); ?></p>
                </div>
                <?php
            }
        } else {
            echo '<p>' . esc_html__( 'No answers yet. Be the first to answer!', 'yourtheme' ) . '</p>';
        }

        // Reset post data after custom query
        wp_reset_postdata();

        // Display the comment form for submitting answers (discussion comments)
        ?>

        <?php if ( is_user_logged_in() ) : // Check if the user is logged in ?>
            <h3><?php esc_html_e( 'Add Your Answer', 'yourtheme' ); ?></h3>

            <?php
            // Customize the comment form to submit as 'discussion' type
            $comment_form_args = array(
                'title_reply' => esc_html__( 'Add your answer to this question', 'yourtheme' ),
                'comment_field' => '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'Your Answer', 'yourtheme' ) . '</label><textarea id="comment" name="comment" rows="5" required="required"></textarea></p>',
                'fields' => array(
                    'author' => '<p class="comment-form-author"><label for="author">' . esc_html__( 'Your Name', 'yourtheme' ) . '</label><input id="author" name="author" type="text" value="' . esc_attr( $comment_author ) . '" size="30" required="required" /></p>',
                    'email'  => '<p class="comment-form-email"><label for="email">' . esc_html__( 'Your Email', 'yourtheme' ) . '</label><input id="email" name="email" type="email" value="' . esc_attr( $comment_author_email ) . '" size="30" required="required" /></p>',
                ),
                'comment_type' => 'discussion',  // Empty to keep default, or set to 'discussion' if needed
                'label_submit' => 'Answer',
                'action' => site_url( '/wp-comments-post.php' ),
                'post_id' => $parent_comment->comment_post_ID, // Ensure this is valid
                'parent' => $parent_comment->comment_ID,  // Correctly pass the parent comment ID
            );

            $comment_form_args['comment_field'] .= '<input type="hidden" name="parent_comment_post_id" value="' . esc_attr( $parent_comment->comment_post_ID ) . '" />';

            $comment_form_args['comment_field'] .= '<input type="hidden" name="parent_comment_id" value="' . esc_attr( $parent_comment->comment_ID ) . '" />';

            comment_form( $comment_form_args );
        else :
            // Optionally, display a message if the user is not logged in
            echo '<p>' . esc_html__( 'You must be logged in to answer this question.', 'yourtheme' ) . '</p>';
        endif;

    } else {
        echo '<p>' . esc_html__( 'This is not a valid discussion comment.', 'yourtheme' ) . '</p>';
    }
} else {
    echo '<p>' . esc_html__( 'No discussion comment ID found.', 'yourtheme' ) . '</p>';
}
?>