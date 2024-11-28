<?php
/*
Plugin Name: WooCommerce Questions And Answers
Plugin URI: http://upnrunn.com
Description: Show an Questions & Answers section as a Tab on WooCommerce Product Single page
Author: Kishore Sahoo
Version: 0.0.1
Author URI: http://upnrunn.com
*/

// Ensure the plugin is being run in WordPress
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Include custom functions file
include plugin_dir_path( __FILE__ ) . 'includes/functions.php';
include plugin_dir_path( __FILE__ ) . 'includes/discussion-rewrite-rule.php';



/**
 * Add a Questions & Answers tab
 */
add_filter( 'woocommerce_product_tabs', 'wc_questions_answers_tab' );
// Set comment type.
add_action( 'preprocess_comment', 'update_comment_type', 1 );


/**
 * Update comment type of product reviews.
 *
 * @since 3.5.0
 * @param array $comment_data Comment data.
 * @return array
 */
function update_comment_type( $comment_data ) {
	if ( ! is_admin() && isset( $_POST['comment_post_ID'], $comment_data['comment_type'] ) && 'product' === get_post_type( absint( $_POST['comment_post_ID'] ) ) && $_POST['submit'] == 'Ask' ) { // WPCS: input var ok, CSRF ok.
		$comment_data['comment_type'] = 'discussion';
	}

	if ( ! is_admin() && $_POST['submit'] == 'Answer' ) { // WPCS: input var ok, CSRF ok.
		$comment_data['comment_type'] = 'discussion';
	}

	if ( is_admin() && isset( $_POST['comment_post_ID'], $comment_data['comment_type'] ) && 'product' === get_post_type( absint( $_POST['comment_post_ID'] ) ) ) { // WPCS: input var ok, CSRF ok.

		$comment_id = $comment_data['comment_parent'];
		$comment = get_comment( $comment_id );

		if ( $comment && $comment->comment_type === 'discussion' ) {
		    $comment_data['comment_type'] = 'discussion';
		}

	}

	return $comment_data;
}

function wc_questions_answers_tab( $tabs ) {
	
	// Adds the new tab
	
	$tabs['discussion'] = array(
		'title' 	=> __( 'Customers Q&As', 'woocommerce' ),
		'priority' 	=> 50,
		'callback' 	=> 'wc_questions_answers_tab_content'
	);

	return $tabs;

}

function wc_questions_answers_tab_content() {
    ?>
    <div id="discussion" class="woocommerce-discussion">
        <div id="discussion-list">
            <?php if ( have_comments() ) : ?>
            <ol class="commentlist">
                <?php
                    wp_list_comments( array(
                        'max_depth'   => 2,          // Limit to 1 level of replies
                        'type'        => 'discussion',  // Only show 'discussion' type comments
                        'callback'    => 'custom_comment_list', // Custom callback for each comment
                        'per_page'    => 5, 
                    ) );
                ?>
            </ol>
            <?php else : ?>
                <p class="woocommerce-noreviews"><?php esc_html_e( 'There are no discussions yet.', 'woocommerce' ); ?></p>
            <?php endif; ?>
        </div>
        <div id="question-form">
        <?php
            // Modify the comment form to stay on the same page after submission
            comment_form( array(
                'comment_field' => '<p class="comment-form-comment"><label for="comment">Your Question <span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="8" required="required"></textarea></p>',
                'label_submit' => 'Ask', // Change the button text
            ) );
        ?>
        </div>
    </div>
    <?php
}



function include_only_reviews( $args ) {
    // Get only regular comments (exclude pingbacks and trackbacks)
    $args['type'] = 'review'; // Only include regular comments (this excludes trackbacks and pingbacks)
    
    return $args;
}

add_filter( 'woocommerce_product_review_list_args', 'include_only_reviews' );


/**
 * Add 'discussion' type to WooCommerce product reviews filter dropdown.
 */
function add_custom_review_type_to_admin_dropdown( $types ) {
    // Add the 'discussion' custom type
    $types['discussion'] = __( 'Discussion', 'textdomain' );

    return $types;
}
add_filter( 'woocommerce_product_reviews_list_table_item_types', 'add_custom_review_type_to_admin_dropdown' );

/**
 * Filter product reviews based on selected 'discussion' type.
 */
function filter_reviews_by_custom_type( $query ) {
    if ( is_admin() && isset( $_GET['comment_type'] ) && 'discussion' === $_GET['comment_type'] ) {
        // Modify the query to only show 'discussion' type comments
        $query->query_vars['comment_type'] = 'discussion'; // Ensure this matches your custom comment type
    }
}
add_action( 'pre_get_comments', 'filter_reviews_by_custom_type' );


function custom_comment_list( $comment, $args, $depth ) {
    $GLOBALS['comment'] = $comment;
    $comment_type = get_comment_type(); // Get the type of the comment (regular, trackback, pingback)
    
    // Example: Custom styling for different comment types
    if ( $comment_type === 'discussion' ) {
        ?>
        <li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
            <article id="div-comment-<?php comment_ID(); ?>" class="comment-body">
                <footer class="comment-meta">
                    <div class="comment-author vcard">
                        <cite class="fn"><?php comment_author(); ?></cite>
                    </div><!-- .comment-author -->
                </footer><!-- .comment-meta -->

                <div class="comment-content">
                	<?php comment_text(); // "Question:" or "Answer:" will be prepended in the filter. ?>
            	</div><!-- .comment-content -->

            </article><!-- .comment-body -->
        </li><!-- #comment-## -->
        <?php
    }
}

function add_question_answer_labels_to_comments( $comment_text, $comment ) {
    // Check if the comment is of type 'discussion' (only modify 'discussion' comments)
    $comment_type = get_comment_type( $comment );

    if ( $comment_type === 'discussion' ) {
        // If it's a parent comment (question), prepend "Question:"
        if ( $comment->comment_parent == 0 ) {
        	$question_url = esc_url( get_comment_link( $comment ) ); // Get the URL of the comment
            $comment_text = sprintf(
			    /* translators: %s is the question text */
			    '<strong>%1$s:</strong> <a href="%2$s" target="_blank">%3$s</a>',
			    esc_html__( 'Question', 'your-text-domain' ), // Translatable "Question" label
			    esc_url( $question_url ),                   // URL for the comment link
			    esc_html( $comment_text )                   // Comment text
			);

        }
        // If it's a child comment (answer), prepend "Answer:"
        elseif ( $comment->comment_parent > 0 ) {
            $comment_text = sprintf(
			    /* translators: %s is the answer text */
			    '<strong>%1$s:</strong> %2$s',
			    esc_html__( 'Answer', 'your-text-domain' ), // Translatable "Answer" label
			    esc_html( $comment_text )                   // Comment text (escaped)
			);

        }
    }

    return $comment_text;
}
add_filter( 'comment_text', 'add_question_answer_labels_to_comments', 10, 2 );


// Handle the template redirect for the comment answers page
function comment_answers_template_redirect() {
    $comment_answers_id = get_query_var( 'comment_answers_id' );

    // Debugging the query var
    error_log( 'Comment Answers ID: ' . $comment_answers_id );

    // If the custom query variable is set, load the custom template
    if ( $comment_answers_id ) {
        // Get the parent comment (question)
        $parent_comment = get_comment( $comment_answers_id );

        // Debugging the comment object
        if ( $parent_comment ) {
            error_log( 'Parent Comment Found: ' . print_r( $parent_comment, true ) );
        }

        // Check if it's a valid parent comment of type 'discussion'
        if ( $parent_comment && $parent_comment->comment_parent == 0 && $parent_comment->comment_type == 'discussion' ) {
            // Include WordPress header
            get_header();

            // Include the custom template from the plugin folder (assuming plugin directory structure)
			$plugin_template_path = plugin_dir_path( __FILE__ ) . 'templates/discussion-template.php';
			if ( file_exists( $plugin_template_path ) ) {
			    include $plugin_template_path;
			} else {
			    // Fallback to the theme if the file does not exist in the plugin directory
			    get_template_part( 'comment-answers-template' );
			}


            // Include WordPress footer
            get_footer();
            exit;
        } else {
            // Redirect or show a message if it's not a valid parent comment of type 'discussion'
            wp_redirect( home_url() ); // Redirect to the homepage or any other page
            exit;
        }
    }
}
add_action( 'template_redirect', 'comment_answers_template_redirect', 999 );

// Ensure that the plugin rewrites are applied when activated
function pd_flush_rewrite_rules() {
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'pd_flush_rewrite_rules' );