<?php 


 /*************************************Cutom filed to products********************************************************/
/**
 * 	http://www.remicorson.com/mastering-woocommerce-products-custom-fields/
 *  http://jeroensormani.com/adding-custom-woocommerce-product-fields/
 */



// Display Fields
add_action( 'woocommerce_product_options_general_product_data', 'woo_add_custom_general_fields' );

// Save Fields
add_action( 'woocommerce_process_product_meta', 'woo_add_custom_general_fields_save' );


function woo_add_custom_general_fields() {

  global $woocommerce, $post;
  
  echo '<div class="options_group">';
  
  // Custom fields will be created here...

		// Product multiple Select
?>
<?php
$warehouse_import = (array) get_post_meta( $post->ID, '_warehouse_general_import', true );
print_r($warehouse_import);
	 $dodavatel_terms = get_terms( array(
										    'taxonomy' => 'dodavatel',
										    'hide_empty' => false,
									) );

	 //var_dump($dodavatel_terms);

?><p class='form-field _warehouse_general_import'>
	<label for='_warehouse_general_import'><?php _e( 'Dodávateľ', 'woocommerce' ); ?></label>
	<select name='_warehouse_general_import[]' class='wc-enhanced-select' multiple='multiple' style='width: 80%;'>


<?php 	foreach ($dodavatel_terms as $dodavatel ) : ?>

 <option <?php selected( in_array(  $dodavatel->slug , $warehouse_import ) ); ?> value='<?= $dodavatel->slug ?>'><?= $dodavatel->name ?></option>

<?php endforeach ?>	
 	</select>
	<img class='help_tip' data-tip="<?php _e( 'Vyber dodávateľa. Ak nie je v zozname treba ho vytvoriť Product -> Dodávateľ', 'woocommerce' ); ?>" src='<?php echo esc_url( WC()->plugin_url() ); ?>/assets/images/help.png' height='16' width='16'>
</p>

<?php
  
  echo '</div>';
	
}

function woo_add_custom_general_fields_save( $post_id ){
	
	// multiselect
	$redeem_in_stores = (array) $_POST['_warehouse_general_import'];
	update_post_meta( $post_id, '_warehouse_general_import', $redeem_in_stores );
	
}




 
 /*************************************Cutom filed to variations********************************************************/
/*
* http://www.remicorson.com/woocommerce-custom-fields-for-variations/
*/
 
 
 
 
// Add Variation Settings
add_action( 'woocommerce_product_after_variable_attributes', 'variation_settings_fields', 10, 3 );
// Save Variation Settings
add_action( 'woocommerce_save_product_variation', 'save_variation_settings_fields', 10, 2 );
/**
 * Create new fields for variations
 *
*/
function variation_settings_fields( $loop, $variation_data, $variation ) {
	// _warehouse_import Field
	woocommerce_wp_text_input( 
		array( 
			'id'          => '_warehouse_variation_import[' . $variation->ID . ']', 
			'label'       => __( 'Velkosklad', 'woocommerce' ), 
			'placeholder' => '',
			'desc_tip'    => 'true',
			'description' => __( 'Vloz nazov velkoskladu  bez diakritky ', 'woocommerce' ),
			'value'       => get_post_meta( $variation->ID, '_warehouse_variation_import', true )
		)
	);
	
}
/**
 * Save new fields for variations
 *
*/
function save_variation_settings_fields( $post_id ) {
	// Text Field
	$text_field = $_POST['_warehouse_variation_import'][ $post_id ];
	if( ! empty( $text_field ) ) {
		update_post_meta( $post_id, '_warehouse_variation_import', esc_attr( $text_field ) );
	}

}