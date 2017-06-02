<?php

/*
Plugin Name: Shopline import Addon pre wp allimport
Description: Rozšírene pre plugin WP All Import. Prida funkcianolitu pre vytvorenie variant z velkosti a pri jednoduchych zmeni skladovy stav
Version: 1.0
Author: Hidde
*/

include "rapid-addon.php";
include "custom-taxonomies.php";
include "custom-fields.php";
include "functions_before_xml_import.php";



add_action( 'init', 'addon_fields_function_run_init', 1000 );
function addon_fields_function_run_init() {
		 $dodavatel_terms = get_terms( array(
	    'taxonomy' => 'dodavatel',
	    'hide_empty' => false,
	) );
		
	//cyklus vytvori pole pre radio select 
	$dodavatel_option = array();
	foreach ($dodavatel_terms as $dodavatel) {

	$dodavatel_option[$dodavatel->slug] = $dodavatel->name;

	}


	$shopline_size_feed_import = new RapidAddon("Shopline size feed import", "shopline_size_feed_import");

	$shopline_size_feed_import->add_field('_sku_import', 'SKU', 'text');
	$shopline_size_feed_import->add_field('_size_import', 'Size', 'text');
	$shopline_size_feed_import->add_field('_stock_qty_import', 'Stock Qty', 'text');
	$shopline_size_feed_import->add_field(
	    '_warehouse_import',
	    'Dodávateľ',
	    'radio',
	    $dodavatel_option
	);


	$shopline_size_feed_import->set_import_function('addon_import_function');

	$shopline_size_feed_import->run();
}


function addon_import_function($post_id, $data, $import_options){
	/*update_post_meta( $post_id, "My Field", $data['my_text_field']);
	var_dump($post_id);
	var_dump($data);
	var_dump($import_options);*/
	if (!empty($data['_sku_import'])){
		$data['_size_import'];
		echo "<pre>";
		print_r($post_id);
		echo "</pre>";

		echo "<pre>";
		print_r($data);
		echo "</pre>";

		$product_id = $post_id;
		$_product = wc_get_product($product_id); //vypise informacie o produkte


		/**
		 * ked je produkt variabinly vytvara varianty pri jednoduchom meni len jeho skladove stavy a typ zobrazenie (kos, koncept ...)
		 */
		if( $_product->is_type( 'variable' ) ) {
			// do stuff for simple products
			echo "Produkt variable - ".$product_id."<br>";


					$variations = $_product->get_available_variations();
					$previously_attributes = wc_get_product_terms($product_id,'pa_velkost',array( 'fields' => 'names'));//atributy ktore produkt ma pred importom

				/*	echo "<pre>";
					print_r($_product);
					echo "</pre>";*/



				echo "regular_price-".$_product->get_regular_price()."<br>";
				echo "sale_price-".$_product->get_sale_price()."<br>";
				echo "price-".$_product->get_price()."<br>";
				echo "_sale_price_tmp-".get_post_meta( $post_id, '_sale_price_tmp', true )."<br>";
				echo "_regular_price_tmp-".get_post_meta( $post_id, '_regular_price_tmp', true )."<br>";
				echo "_price_tmp-".get_post_meta( $post_id, '_price_tmp', true )."<br>";

				$regular_price 	= $_product->get_regular_price();
				$sale_price 	= $_product->get_sale_price();
				$price 			= $_product->get_price();



					/*********************************************************************************
					 *   Create atribute
					 */
					echo $size_name[] = $data['_size_import'];
				   // In a class constructor
				     $attribute = new stdClass();
				      if (taxonomy_exists(wc_attribute_taxonomy_name('velkost'))) :
				        echo wc_attribute_taxonomy_name('velkost')."<br>";
				        $attribute->size_tax = wc_attribute_taxonomy_name('velkost');
				      endif; 


				            // Insert the attributes (I will be using size and color for variations)
				      $attributes = array(
				          $attribute->size_tax => array(
				              'name' => $attribute->size_tax,
				              'value' =>'',
				              'is_visible' => '1',
				              'is_variation' => '1',
				              'is_taxonomy' => '1'
				              //'position' => 1000
				          )

				      );

					update_post_meta( $product_id, '_product_attributes', $attributes ); //updatne atributy produktu 
					wp_set_object_terms( $product_id,  ( ! empty($previously_attributes)) ? array_merge($previously_attributes, $size_name) : $size_name , $attribute->size_tax );
					/**************************************************************************************
					 *  Create variation
					 */

					$parent_id = $product_id;

					  $variation = array(
					      'post_title'   => 'Product #' . $parent_id . ' Variation-'.$size,
					      'post_content' => '',
					      'post_status'  => 'publish',
					      'post_parent'  => $parent_id,
					      'post_type'    => 'product_variation'
					  );

					  $variation_id = wp_insert_post( $variation );
					  echo  $variation_id."<br>";
					  echo $size_name=strtolower($size_name[0]);//ak je ponechane velke pismo tak sa velkost nezobrazuje v admine
					  update_post_meta( $variation_id, 'attribute_' . $attribute->size_tax, $size_name );

					  // sku pre variant zlozene z vyrobne cislo --velkost-dodavatel
					  $parent_sku = get_post_meta( $parent_id, '_sku', true );
					  $variant_sku = $parent_sku."--".$size_name."--".$data[_warehouse_import];
					  update_post_meta( $variation_id, '_sku', $variant_sku);

					// správa skladu yes/no
					update_post_meta( $variation_id, '_manage_stock', 'yes' );

				      // pocet kusov na sklade
				     // update_post_meta( $variation_id, '_stock', $kusy );
				     
					// Povoliť nákup aj keď tovar nie je na sklade?
					update_post_meta( $variation_id, '_backorders', 'notify' );

				    update_post_meta( $variation_id, '_warehouse_variation_import', $data[_warehouse_import]);

				    update_post_meta( $variation_id, '_regular_price', $regular_price);
				    update_post_meta( $variation_id, '_sale_price', $sale_price);

		} 
		else {
			// do stuff for everything else
			echo "Produkt single a ostatne<br>";

			/**
			 *  Zobrat co je v custom fielde _warehouse_general_import a pridat do array - $data[_warehouse_import]
			 */
				$warehouse_import = (array) get_post_meta( $product_id, '_warehouse_general_import', true );
				$warehouse_import[]= $data[_warehouse_import];
				update_post_meta( $product_id, '_warehouse_general_import', $warehouse_import);
				echo "<pre>";
				print_r($warehouse_import);
				echo "</pre>";
			/**
			 *  Zmenit stav skladu - skladom
			 */
				echo "stav skladu - skladom<br>"; 
				$out_of_stock_staus = 'instock';
				update_post_meta( $product_id, '_stock_status', wc_clean( $out_of_stock_staus ) );
			/**
			 *  Back order nastavit na notify
			 */
				update_post_meta( $product_id, '_backorders', 'notify' );
		}
	}//end if data empty

}





