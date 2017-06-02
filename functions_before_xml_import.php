<?php

/**
 *  Funkcia
 *  - vyberie jednoduchy produkt ktory ma custom filed _warehouse_general_import
 *  s hodnotou $warehouse_name
 *  - odstrani hodnotu $warehouse_name z custom fieldu _warehouse_general_import
 *  - podla podmienky nastavi produktu stav:
 *  		- back order none/notify
 *  		- skladom/ nie je skladom,
 *  		- presunie do kosa
 * 
 * 
 **/
function check_and_change_Single_product_with_customfield($warehouse_name){

// args vyber simple (jednoduchy) product ktoreho custom filed _warehouse_general_import obsahuje premennu $warehouse_name (definuje sa v module wp_all_import shopline addon)
		$args = array(
	  'numberposts' => -1,
	  'nopaging'      => true,
	  'post_type'   => 'product',
	  'tax_query'           => array(
                                    array(
                                            'taxonomy' => 'product_type',
                                            'field'    => 'slug',
                                            'terms'    => 'simple',
                                        ),
                                    ),
	  'meta_query' => array (
            array (
              'key' => '_warehouse_general_import',
              'value' => $warehouse_name,
                          'compare' => 'LIKE'
            )

          )

	);

			// query
			$the_query = new WP_Query( $args );

			$product_parent_ids = array();
		if ( $the_query->have_posts() ) {
			//echo 'Skuska single product funkcia<ul>';
			while ( $the_query->have_posts() ) {
				echo "<hr>";
				$the_query->the_post();
				$product_id = get_the_id();
				$warehouse_import = (array) get_post_meta( get_the_id(), '_warehouse_general_import', true );
				echo '<li>'.get_the_title().'-'.get_the_id().'</li>';
				print_r($warehouse_import);


		/**
		 *  vymazat nazov dodavatela z produktu 
		 */
		
		//echo "vymazat nazov dodavatela z produktu <br>"; 

			// najde v array podla value key a potom $key z array odstrani 
			$key = array_search($warehouse_name, $warehouse_import);
			unset($warehouse_import[$key]);
			
			//echo "po zmazani ".$warehouse_name."<br>";
			print_r($warehouse_import);
			update_post_meta( $product_id, '_warehouse_general_import', $warehouse_import );


		/**
		 *  očekovat či po vymazani dodavatela tam nie je ešte jeden
		 */
			
			if(empty($warehouse_import)){
				//echo "Produkt nema ziadneho dodavatela<br>";
				// kolko ks produktu je skladom
				$skladom_ks  = get_post_meta( $product_id, '_stock', true );
				//echo "a skladom ma veci - $skladom_ks<br>";


				if($skladom_ks == 0){
					/**
					 *  ak uz nie je dodavatel a nie je nič skladom tak product do koša
					 *  a prepnut back odrder na 'no'
					 */
					//echo "Produktu nepovolit spetnu objednavku<br>";
						update_post_meta( $product_id, '_backorders', 'no' );
					//echo "Produkt hodeny do koša <br>";
					 wp_trash_post( $product_id );

					//echo "Produkt stav skladu - nie je na sklade<br>"; 
					 $out_of_stock_staus = 'outofstock';
					 update_post_meta( $product_id, '_stock_status', wc_clean( $out_of_stock_staus ) );


				}
				else {
					/**
					*  ak uz nie je dodavatel a je niečo skladom tak prepnut back odrder na 'no' 
					*/
				//echo "Produktu nepovolit spetnu objednavku<br>";
				//echo update_post_meta( $product_id, '_backorders', 'no' );

				}

			}
			else {
				/**
				 *   podmienka ak je este jeden dodavatel tak nemenit nič
				 */
				//echo "Produkt ma este nejakeho dodavatela";
			}
	
					} //end while
			//echo '</ul>';
		}
}



function delete_product_variation_by_customfield($warehouse_name){
	/*********************************************************************************
	 *  Zmaže všetky varianty ktore maju custom field so zadefinou hodnotou velkoskladu
	 */
	
	 // args
	$args = array(
	  'numberposts' => -1,
	  'nopaging'      => true,
	  'post_type'   => 'product_variation',
	  'meta_key'    => '_warehouse_variation_import',
	  'meta_value'  => $warehouse_name
	);

	// query
	$the_query = new WP_Query( $args ); 
	
		$product_parent_ids = array();
		if ( $the_query->have_posts() ) {
			echo '<ul><h3>Delete variation</h3>';
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				$variation_delete_id = get_the_id();
				echo '<li><hr>' . get_the_title() . '</li>';
				echo '<li>Variation delete ID -' . $variation_delete_id . '<hr></li>';				
				//echo '<li>' . wp_get_post_parent_id( $post_ID ) . '</li>';

				wp_delete_post( $variation_delete_id, true ); 


				$product_parent_ids[] = wp_get_post_parent_id( $post_ID );
			}
			echo '</ul>';
			/* Restore original Post Data */
			wp_reset_postdata();
		} else {
			// no posts found
		}

		$_unique_product_parent_ids = array_unique($product_parent_ids); // spoji vsetky duplicitne id do jedneho


		wp_reset_query();  // Restore global post data stomped by the_post(). 

		/***********************************************************************************************
		 *  Reupdate article from variant 
		 */
		
		foreach ($_unique_product_parent_ids as $_unique_product_parent_id) {
					
			$product_id = $_unique_product_parent_id	;	
			$post = get_post($product_id);

			$id =  $post->ID;

			$product_variations = new WC_Product_Variable( $id );
			$product_variations = $product_variations->get_available_variations();
		
			if (empty($product_variations) ){
				//echo "Produkt hidden s ID - ". $product_id;

				// produkty draft nehadzat do kosa ponechat draft
				$post_status = get_post_status( $product_id );	
				($post_status == 'draft')?$status = "draft":$status = "trash";// publish / pending / draft / trash
					
				$args = array( 
					'ID'				=> $product_id,
					'post_status'		=> $status,
					'post_modified '	=> current_time( 'mysql' ),
					'post_modified_gmt'	=> current_time( 'mysql', 1 )
					);
				wp_update_post($args);
		
			}

			$previously_attributes = array();
			//naimportuje prazdne pole pred importom atributov
			$attribute->size_tax = wc_attribute_taxonomy_name('velkost');
			wp_set_object_terms( $product_id, $previously_attributes  , $attribute->size_tax );
			foreach ($product_variations as $variation) {
								
								$previously_attributes[] = $variation['attributes'][attribute_pa_velkost];
			}
			

			wp_set_object_terms( $product_id, $previously_attributes  , $attribute->size_tax );
		}

}

function before_xml_import($import_id) {
    $import = new PMXI_Import_Record();
    $import->getById($import_id);
    echo "<pre>";
    print_r($import['options']['shopline_size_feed_import']);
    //print_r($import);
    echo "</pre>";
    if (!$import->isEmpty()) {

     }

     echo "<hr>dodavatel -";
     echo $warehouse_name = $import['options']['shopline_size_feed_import'][_warehouse_import];
     echo "<hr>";
     $sku_field = $import['options']['shopline_size_feed_import'][_sku_import];

     // vytiahne z wp all importu zo spusteneho imporu info o dodavatalovi
     if ($sku_field != "") {
     	$warehouse_name = $import['options']['shopline_size_feed_import'][_warehouse_import];
     	//$dodavatel_name = $import['options']['shopline_size_feed_import'][_dodavatel];

			echo "<h2>Funkcia before_xml_import spustena - </h2>";
			print_r($warehouse_name);
			echo "";
		delete_product_variation_by_customfield($warehouse_name);
		check_and_change_Single_product_with_customfield($warehouse_name);
     }
}

/**
 * https://pastebin.com/uQeW42er
 * pred importom 
 */
add_action('pmxi_before_xml_import', 'before_xml_import', 10, 1);