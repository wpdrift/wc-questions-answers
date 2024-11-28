<?php

// Modify the comment link to point to the new page for answers under product pages
function modify_comment_link( $link, $comment ) {
    // Ensure it's a parent comment of type 'discussion'
    if ( $comment->comment_parent == 0 && $comment->comment_type == 'discussion' ) {
        // Get the product associated with the comment
        $product = get_post( $comment->comment_post_ID );
        if ( $product && 'product' === $product->post_type ) {
            // Generate the link for the discussion page under the product
            $link = home_url( '/' . $product->post_name . '/discussion/' . $comment->comment_ID . '/' );
        }
    }
    return $link;
}
//add_filter( 'get_comment_link', 'modify_comment_link', 10, 2 );

// Register custom rewrite rules for the comment answers page under the product
function comment_answers_rewrite_rule() {
    // Add a custom rewrite rule: /product-name/discussion/comment-id
    add_rewrite_rule(
        '^([^/]+)/discussion/([0-9]+)/?', // URL structure: product-name/discussion/comment-id
        'index.php?product_slug=$matches[1]&comment_answers_id=$matches[2]', // Map to query vars
        'top'
    );
}
add_action( 'init', 'comment_answers_rewrite_rule', 999 );

// Add custom query vars for product slug and comment answers ID
function add_comment_answers_query_vars( $query_vars ) {
    $query_vars[] = 'product_slug';           // Add 'product_slug' query var
    $query_vars[] = 'comment_answers_id';     // Add 'comment_answers_id' query var
    return $query_vars;
}
add_filter( 'query_vars', 'add_comment_answers_query_vars' );