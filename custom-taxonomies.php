<?php
function register_my_taxes_dodavatel() {

    /**
     * Taxonomy: Dodávatelia.
     */

    $labels = array(
        "name" => __( 'Dodávatelia', 'Shopline WP ALL Import add-on' ),
        "singular_name" => __( 'Dodávateľ', 'Shopline WP ALL Import add-on' ),
    );

    $args = array(
        "label" => __( 'Dodávatelia', 'Shopline WP ALL Import add-on' ),
        "labels" => $labels,
        "public" => true,
        "hierarchical" => false,
        "label" => "Dodávatelia",
        "show_ui" => true,
        "show_in_menu" => true,
        "show_in_nav_menus" => true,
        "query_var" => true,
        "rewrite" => array( 'slug' => 'dodavatel', 'with_front' => true,  'hierarchical' => true, ),
        "show_admin_column" => true,
        "show_in_rest" => true,
        "rest_base" => "",
        "show_in_quick_edit" => true,
    );
    register_taxonomy( "dodavatel", array( "product"), $args );
}

add_action( 'init', 'register_my_taxes_dodavatel' );

?>