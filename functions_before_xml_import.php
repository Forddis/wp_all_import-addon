<?php
// WP wp_update_post funkcia nastavi staus variantu na private/publish
function change_post_status($post_id,$status){
    $current_post = get_post( $post_id, 'ARRAY_A' );
    $current_post['post_status'] = $status;
    wp_update_post($current_post);
}   
//tato funkcia je o 2/3 rychlesja ako s objekotm ale nedokaze v rodicovi vymazat variant child_vysible z objektu produktu 
function set_variations_how_private($variation_id){

		$status 		= wc_clean( 'private' );// Options: 'private', 'publish'
		$stock_status 	= wc_clean( 'outofstock' );// 'outofstock', 'instock'
		$backorders 	= wc_clean( 'no' );// 'no', 'yes', 'notify'

		change_post_status($variation_id, $status); 
		update_post_meta( $variation_id, '_stock', '' );
		update_post_meta( $variation_id, '_stock_status',$stock_status );
		update_post_meta( $variation_id, '_backorders',$backorders);
		do_action( 'pmxi_product_variation_saved', $variation_id );
}

//------------------
function set_variations_how_publish($variation_id){

		$status 		= wc_clean( 'publish' );// Options: 'private', 'publish'
		$stock_status 	= wc_clean( 'instock' );// 'outofstock', 'instock'
		$backorders 	= wc_clean( 'notify' );// 'no', 'yes', 'notify'

		change_post_status($variation_id, $status); 
		update_post_meta( $variation_id, '_stock', '' );
		update_post_meta( $variation_id, '_stock_status',$stock_status );
		update_post_meta( $variation_id, '_backorders',$backorders);
		do_action( 'pmxi_product_variation_saved', $variation_id );
}
//------------------
function set_stock_variations_how_publish($variation_id,$stock_qty){

		$status 		= wc_clean( 'publish' );// Options: 'private', 'publish'
		$stock_status 	= wc_clean( 'instock' );// 'outofstock', 'instock'
		$backorders 	= wc_clean( 'no' );// 'no', 'yes', 'notify'

		change_post_status($variation_id, $status); 
		update_post_meta( $variation_id, '_stock', $stock_qty );
		update_post_meta( $variation_id, '_stock_status',$stock_status );
		update_post_meta( $variation_id, '_backorders',$backorders);
		do_action( 'pmxi_product_variation_saved', $variation_id );
}

//  toto je nepouzita funkcia lebo je dost pomala set_variations_how_private($variation_id) je o 2/3 rychlejsia
function set_variation_how_private_object($variation_id){

				$product_object = wc_get_product($variation_id); //vypise informacie o produkte

				$status 		= wc_clean( 'private' );// Options: 'private', 'publish'
			 	$stock_status 	= wc_clean( 'outofstock' );// 'outofstock', 'instock'
			 	$backorders 	= wc_clean( 'no' );// 'no', 'yes', 'notify'
				 	
				$product_object->set_stock_quantity('');
				$product_object->set_backorders( $backorders );
				$product_object->set_stock_status($stock_status);
				$product_object->set_status( $status );
				$product_object->save();

 //do_action( 'woocommerce_api_save_product_variation', $product_object );

}

function set_product_hidden($product_id){
				// produkty draft nehadzat do kosa ponechat draft
				$post_status = get_post_status( $product_id );	
				($post_status == 'draft')?$status = "draft":$status = "trash";// publish / pending / draft / trash

					$_product = wc_get_product($product_id); //object product
				// 	$_product = new WC_Product_Variable($product_id);
				 	$visibility 	= wc_clean( 'hidden' );// Options: 'hidden', 'visible', 'search' and 'catalog'.
				 	$stock_status 	= wc_clean( 'outofstock' );// 'outofstock', 'instock'
				 	$backorders 	= wc_clean( 'no' );// 'no', 'yes', 'notify'
				 	//$date 			= new DateTime();
				 	$date 			= date_create();


					$_product->set_catalog_visibility($visibility);
				 	//$_product->set_date_modified($date->getTimestamp());
				 	$_product->set_date_modified(date_timestamp_get($date));
					$_product->set_stock_quantity( '' );
					$_product->set_backorders( $backorders );
					$_product->set_stock_status($stock_status);//bez quantity '' a backorders no sa neprepne

   					$_product->save();
}

	/**
	 * set product (parent)
	 * catalog_visibilit 	: visible
	 * stock_status 		: instock
	 * set_backorders		: notify
	 */
function set_product_visible($product_id){
	$_product = wc_get_product($product_id); //vypise informacie o produkte

	$visibility 	= wc_clean( 'visible' );// Options: 'hidden', 'visible', 'search' and 'catalog'.
 	$stock_status 	= wc_clean( 'instock' );// 'outofstock', 'instock'
 	
 	$_product->set_catalog_visibility($visibility );	 			
	$_product->set_backorders( 'notify' );
	$_product->set_stock_status($stock_status);

	$_product->save();
}
// ocekuje ci ma produkt visible variant
function set_parent_hidden_if_hasnt_variation($product_id){

	$product_variations = new WC_Product_Variable( $product_id );

	// dostupne varianty - pri metode nastavenia statusu variany len update post a nie $variant->save() sa pri pordukte variant stale zobrazuje, ale nema hodnotu  [variation_is_visible]=>1   
	$product_variations = $product_variations->get_available_variations();

	// vrati 1 ak najde v poli $product_variations aspon jednu value-1 pri key-variation_is_visible
	$found_key = strlen(array_search(1, array_column($product_variations, 'variation_is_visible')));

	if (empty($found_key) ){

		set_product_hidden($product_id);
		echo "Product Hidden ID--".$product_id;
		//$count_hidden_product++;
	}

}

/**
 *  Funkcia updatne cenu u variant  podla ceny u rodica
 */
function update_product_variation_price($variation_id){
	$product_id = wp_get_post_parent_id( $variation_id );

	echo $regular_price = get_post_meta( $product_id, '_regular_price', true);
	echo "<br>";
	echo $sale_price = get_post_meta( $product_id, '_sale_price', true);
	echo "<br>";

	update_post_meta( $variation_id, '_regular_price', $regular_price);
	update_post_meta( $variation_id, '_sale_price', $sale_price);
	do_action( 'pmxi_product_variation_saved', $variation_id );
}





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
function check_and_change_Single_product_with_customfield($warehouse_name,$import_to_stock){

// args vyber simple (jednoduchy) product ktoreho custom filed _warehouse_general_import obsahuje premennu $warehouse_name (definuje sa v module wp_all_import shopline addon)
		$args = array(
	  'posts_per_page' => -1,
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
				//echo "<hr>";
				$the_query->the_post();
				$product_id = get_the_id();
				$warehouse_import = (array) get_post_meta( get_the_id(), '_warehouse_general_import', true );
				//echo '<li>'.get_the_title().'-'.get_the_id().'</li>';
				//print_r($warehouse_import);


		/**
		 *  ak sa importuje do skladu tak najprv vynulovat stav skladovych zasob
		 */
		
			if ($import_to_stock == 'stock') {
				update_post_meta( $product_id, '_stock', '0' );
			}


		/**
		 *  vymazat nazov dodavatela z produktu 
		 */
		
		//echo "vymazat nazov dodavatela z produktu <br>"; 

			// najde v array podla value($warehouse_name) všetky keys  
			$removeKeys = array_keys( $warehouse_import ,$warehouse_name);

			$removeKeysEmpty = array_keys( $warehouse_import ,'');
			
			//a potom podla $keys z array odstrani vsetky values
			$warehouse_import = array_diff_key($warehouse_import, array_flip($removeKeysEmpty));
			$warehouse_import = array_diff_key($warehouse_import, array_flip($removeKeys));

			update_post_meta( $product_id, '_warehouse_general_import', $warehouse_import );


		/**
		 *  očekovat či po vymazani dodavatela tam nie je ešte jeden
		 */
			
			if(empty($warehouse_import)){
				//echo "Produkt nema ziadneho dodavatela<br>";
				// kolko ks produktu je skladom
				$skladom_ks  = get_post_meta( $product_id, '_stock', true );
				$product_id."-skladom ma veci - $skladom_ks<br>";


				if($skladom_ks == 0){
					/**
					 *  ak uz nie je dodavatel a nie je nič skladom tak product hidden
					 *  
					 */
					
						set_product_hidden($product_id);

					 
					 //wp_trash_post( $product_id );


				}
				else {
					/**
					*  ak uz nie je dodavatel a je niečo skladom tak prepnut back odrder na 'no' 
					*/
				//echo "Produktu nepovolit spetnu objednavku<br>";
				update_post_meta( $product_id, '_backorders', 'no' );

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



function delete_product_variation_by_customfield($warehouse_name,$import_to_stock){
	/*********************************************************************************
	 * Ked je import do skladu zmaze vsetky varianty kotore nemaju polozku 0
	 *  Nastavi hodnotu status na private pre všetky varianty ktore maju custom field so zadefinou hodnotou velkoskladu
	 */
	$time_start = microtime(true); 
	$args = array(
		'posts_per_page' => -1,
		'nopaging'      => true,
		'post_status'  => 'publish',
		'post_type'   => 'product_variation',
		'meta_key'    => '_warehouse_variation_import',
		'meta_value'  => $warehouse_name,

		'no_found_rows' => true, 
		'update_post_meta_cache' => false, 
		'update_post_term_cache' => false, 
		'return' => 'ids'
	);
	

		// query
		$the_query = new WP_Query( $args ); 
	
		$product_parent_ids = array();
		if ( $the_query->have_posts() ) {
			//echo '<ul><h3>Delete variation</h3>';
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				$variation_id = get_the_id(); 

				set_variations_how_private($variation_id);
				//wp_delete_post( $variation_id, true ); 

				// pole vsetkych id produktov ktych varianty boli upravene 
				$product_parent_ids[] = wp_get_post_parent_id( $variation_id );

			}
			//echo '</ul>';
			/* Restore original Post Data */
			wp_reset_postdata();
			wp_reset_query();  // Restore global post data stomped by the_post(). 
		} else {
			// no posts found
		}

		

		$_unique_product_parent_ids = array_unique($product_parent_ids); // spoji vsetky duplicitne id produktov do jedneho

		// loop ocekuje ktore produkty uz nemaju publish(visible) variant a nastavia mu hodnotu v katalogu hidden
         

		echo "Upravenych produktov - ".count($_unique_product_parent_ids);
		echo "<br>";
		//echo "Vypnutych produktov  - ".$count_hidden_product;
		echo "<br>";
	$time_end = microtime(true);

	//dividing with 60 will give the execution time in minutes other wise seconds
	$execution_time = ($time_end - $time_start)/60;

	//execution time of the script
	echo '<b>Total Execution Time - delete_product_variation_by_customfield :</b> '.$execution_time.' Mins';


}

function before_xml_import($import_id) {
    $import = new PMXI_Import_Record();
    $import->getById($import_id);
    //echo "<pre>";
    //print_r($import['options']['shopline_size_feed_import']);
    //print_r($import);
    //echo "</pre>";
    if (!$import->isEmpty()) {

     }

     echo "<hr>dodavatel -";
     echo $warehouse_name = $import['options']['shopline_size_feed_import']['_warehouse_import'];
     echo "<br>";
     echo $import_to_stock = $import['options']['shopline_size_feed_import']['_if_stock_import']; // stock/warehouse
     echo "<hr>";
     $sku_field = $import['options']['shopline_size_feed_import']['_sku_import'];

     // vytiahne z wp all importu zo spusteneho imporu info o dodavatalovi
     if ($sku_field != "") {
     	$warehouse_name = $import['options']['shopline_size_feed_import']['_warehouse_import'];
     	//$dodavatel_name = $import['options']['shopline_size_feed_import'][_dodavatel];

			echo "<h2>Funkcia before_xml_import spustena - </h2>";
			echo "<br>";
		delete_product_variation_by_customfield($warehouse_name,$import_to_stock);
		check_and_change_Single_product_with_customfield($warehouse_name,$import_to_stock);
     }
}

/**
 * https://pastebin.com/uQeW42er
 * pred importom 
 */
add_action('pmxi_before_xml_import', 'before_xml_import', 10, 1);


function after_xml_import($import_id) {
	$args_visible = array(

			//'numberposts' => 100,
			'posts_per_page' => -1,
			'nopaging'      => true,
			'post_status'  => 'publish',
			//'post_parent'	=> $post_parent,
			'post_type'   => 'product',
			'no_found_rows' => true, 
			'update_post_meta_cache' => false, 
			'update_post_term_cache' => false, 
			'return' => 'ids',

			'tax_query' => array(
				'relation' => 'AND',
	                      array(
	                            'taxonomy' => 'product_visibility',
	                            'field'    => 'name',                            
	                            'terms'    => 'exclude-from-catalog',
	                            'operator' => 'NOT IN',

	                      ),
	                      array(
	                            'taxonomy' => 'product_type',
	                            'field'    => 'name',                            
	                            'terms'    => 'variable',
	                            'operator' => 'IN',

	                      ),
	                  ) 
		);

	$query_visible = get_posts( $args_visible );
	

		if ( $query_visible ) {
			echo "Vypnute produkty:<br>";
			foreach ( $query_visible as $post ){				
					

					$product_id = $post->ID;
						$product_variations = new WC_Product_Variable( $product_id );

	// dostupne varianty - pri metode nastavenia statusu variany len update post a nie $variant->save() sa pri pordukte variant stale zobrazuje, ale nema hodnotu  [variation_is_visible]=>1   
	$product_variations = $product_variations->get_available_variations();

	// vrati 1 ak najde v poli $product_variations aspon jednu value-1 pri key-variation_is_visible
$found_key = strlen(array_search(1, array_column($product_variations, 'variation_is_visible')));

	if (empty($found_key) ){

		set_product_hidden($product_id);
		echo "Product Hidden ID--".$product_id;
		echo "<br>";
		//$count_hidden_product++;
	}


		//flush();
		//ob_flush();
				}
			wp_reset_postdata();
			//wp_reset_query();
		}

		else echo "Nevypnuty ziaden produkt<br>";

}


add_action('pmxi_after_xml_import', 'after_xml_import', 10, 1);