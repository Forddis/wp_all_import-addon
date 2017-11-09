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
	$shopline_size_feed_import->add_field(
    '_if_stock_import',
    'Skladom alebo dodavatel?',
    'radio',
    array(
        'warehouse' => 'Dodávateľ',
        'stock' => 'Skladom'
    )
);


	$shopline_size_feed_import->set_import_function('addon_import_function');

	$shopline_size_feed_import->run();
}


function addon_import_function($post_id, $data, $import_options){

	if (!empty($data['_sku_import'])){

		$data['_size_import'];
		echo "<pre>";
		print_r($post_id);
		echo "</pre>";

		echo "<pre>";
		print_r($data);
		echo "</pre>";

/*		echo "<pre>";
		print_r($import_options);
		echo "</pre>";*/

	$product_id = $post_id;
	$_product = wc_get_product($product_id); //vypise informacie o produkte
	$warehouse_slug = $data['_warehouse_import'];
	echo "<br>Sulg - velkoskladu - ".$warehouse_slug."<br>";
	// nastavi produkt (parent) na visible
	set_product_visible($product_id);


		/**
		 * ked je produkt variabinly vytvara varianty pri jednoduchom meni len jeho skladove stavy a typ zobrazenie (kos, koncept ...)
		 */
		if( $_product->is_type( 'variable' ) ) {
			// do stuff for simple products
			echo "Produkt variable - ".$product_id."<br>";


					$variations = $_product->get_available_variations();
					$previously_attributes = wc_get_product_terms($product_id,'pa_velkost',array( 'fields' => 'names'));//atributy ktore produkt ma pred importom

					echo "<pre>";
					print_r($previously_attributes);
					echo "</pre>";

				

				echo "regular_price-".$_product->get_regular_price()."<br>";
				echo "sale_price-".$_product->get_sale_price()."<br>";
				echo "price-".$_product->get_price()."<br>";
				echo "_sale_price_tmp-".get_post_meta( $post_id, '_sale_price_tmp', true )."<br>";
				echo "_regular_price_tmp-".get_post_meta( $post_id, '_regular_price_tmp', true )."<br>";
				echo "_price_tmp-".get_post_meta( $post_id, '_price_tmp', true )."<br>";

				$regular_price 	= $_product->get_regular_price();
				$sale_price 	= $_product->get_sale_price();
				$price 			= $_product->get_price();



				// Najdi Variantu produktu podla jeho ID ako ktora ma oznacenie podla velkoskladu a hodnoty z taxonomie pa_velkost ktora je brana podla mena z velkosti z xml

$size_name[] = str_replace(',', '.', wc_clean($data['_size_import']));// zmeni ciarku na bodku
echo "size_name[0] - ".$size_name[0]."<br>";
$taxonomy = 'pa_velkost';
echo "taxonomy - ".$taxonomy."<br>";
//$meta = get_post_meta($variation_id, 'attribute_'.$taxonomy, true);
$term = get_term_by('name', $size_name[0], $taxonomy);
echo "<pre>";
echo "term - ";
print_r($term);
echo "<pre>";

				$args_prod_var = array(
						'posts_per_page' => 1,
						//'nopaging'      => true,
						'post_status'  => array('publish','private'),
						'post_parent'	=> $product_id,
						'post_type'   => 'product_variation',
						'return' => 'ids',

						'meta_query' => array(
								'relation' => 'AND',
								array(
									'key'		=> '_warehouse_variation_import',
									'value'		=> $warehouse_slug
								
								),
								array(
									'key'		=> 'attribute_pa_velkost',
									'value'		=> $term->slug

								)
							)
					);
				//$used_variation_query = new WP_Query( $args_prod_var );
				$used_variation_query = get_posts( $args_prod_var );
					

echo "<pre>";
echo "query - ";
//print_r($used_variation_query);
echo "<pre>";


				?>
				<ol>
				<?php 

			// AK je varianta uz vytvorena tak jue treba nastavit staus na publish
				if ( $used_variation_query ) {
						
						foreach ( $used_variation_query as $post ) {				
								
							$variant_sku = get_post_meta( $post->ID, '_sku', true );
							$variation_id = $post->ID;
						echo "<li>";
						echo $variant_sku."-----ID--".$post->ID;
						echo "</li>"; 

							

			//Ak je import do skladu 				
							if ($data['_if_stock_import']=='stock') {

								echo "<br>Na sklad<br>";
								$stock_qty = $data['_stock_qty_import'];
								set_stock_variations_how_publish($variation_id,$stock_qty);
							}
			//Ked nie je do skladu - ext. dodavatel
							else{
								
								echo "Nie na sklad<br>";
								set_variations_how_publish($variation_id);
							}

						}
			wp_reset_postdata();
			//wp_reset_query();  // Restore global post data stomped by the_post(). 
				}
			// AK varianta este nie je vytvorena 
				else{





								/*********************************************************************************
								 *   Create atribute
								 */
								
								
						//echo $size_name[] = str_replace(',', '.', wc_clean($data['_size_import']));// zmeni ciarku na bodku
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
							              'is_visible' => '0',
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

								  $variation_id = wp_insert_post( $variation );//vytvoy post(variant) so zadefinovanymi atributmi
								  echo  $variation_id."<br>";
								/************** get atribute slug by name ***************/					 
								  $taxonomy = $attribute->size_tax;
									$meta = get_post_meta($variation_id, 'attribute_'.$taxonomy, true);
									$term = get_term_by('name', $size_name[0], $taxonomy);
									
									echo "term -slug ".$term->slug;
									echo "<hr>";
								/**************************************/

								 $size_name= $term->name;
								 $size_slug= $term->slug;//pre vytvorenie atributu do variant je nutne pouzit slug atributu       
								  update_post_meta( $variation_id, 'attribute_' . $taxonomy, $size_slug );

								  // sku pre variant zlozene z vyrobne cislo --velkost-dodavatel
								  $parent_sku = get_post_meta( $parent_id, '_sku', true );
								  $variant_sku = $parent_sku."--".$size_name."--".$data[_warehouse_import];
								  update_post_meta( $variation_id, '_sku', $variant_sku);

								// správa skladu yes/no
								update_post_meta( $variation_id, '_manage_stock', 'yes' );

								// If import item update to stock
								if ($data['_if_stock_import']=='stock') {

									//pocet kusov na sklade
							     	update_post_meta( $variation_id, '_stock', $data['_stock_qty_import'] );

									// Povoliť nákup aj keď tovar nie je na sklade?
									update_post_meta( $variation_id, '_backorders', 'no' );
								}
								else{
									// Povoliť nákup aj keď tovar nie je na sklade?
									update_post_meta( $variation_id, '_backorders', 'notify' );
								}

							     


							    update_post_meta( $variation_id, '_warehouse_variation_import', $data[_warehouse_import]);

							    update_post_meta( $variation_id, '_regular_price', $regular_price);
							    update_post_meta( $variation_id, '_sale_price', $sale_price);
				}
		} 
		else {
			/******************************** Product Single********************************************/
			echo "Produkt single a ostatne<br>";

			/**
			 *  Zobrat co je v custom fielde _warehouse_general_import a pridat do array - $data[_warehouse_import]
			 */
				$previous_import_warehouse = (array) get_post_meta( $product_id, '_warehouse_general_import', true );
				$warehouse_import = $previous_import_warehouse;
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


				// If import item update to stock
				if ($data['_if_stock_import']=='stock') {

					//pocet kusov na sklade
			     	update_post_meta( $product_id, '_stock', $data['_stock_qty_import'] );

			     	// ak nie je dodavatel tak nepovolit backorder
			     	if (empty($previous_import_warehouse)) {
						// Povoliť nákup aj keď tovar nie je na sklade?
			     		update_post_meta( $product_id, '_backorders', 'no' );
			     	}
			     	else {
			     	/**
					 *  Back order nastavit na notify
					 */
					update_post_meta( $product_id, '_backorders', 'notify' );
			     	}

					
				}
				else{

					/**
					 *  Back order nastavit na notify
					 */
					update_post_meta( $product_id, '_backorders', 'notify' );
				}


		}
	}//end if !empty($data['_sku_import'])

}





