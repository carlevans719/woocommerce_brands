<?php

// $nonce = $_POST['_wpnonce_name'];
// if (empty($_POST)  !wp_verify_nonce($nonce, 'wcb-nonce') ) die("Nonce invalid");

define('WP_USE_THEMES', false);
require_once('../../../../../wp-blog-header.php');

$args = array(
  'post_type' => 'product',
  'posts_per_page' => -1
);

$loop = new WP_Query( $args );

$return = $tmp = array();

// Run the loop
while ( $loop->have_posts() ): $loop->the_post();
  global $product;

  $tmp = get_post_meta($product->id);
  $return[] = array(0=>$product, 1=>$tmp);

endwhile;

wp_send_json($return);


/*
The above code is coupled with
$.getJSON(
  '/wc/wp-content/plugins/woocommerce-brands/widget/reactive/get_products.php',
  function(data){
    // do stuff
    console.log(data);
  }
);





*/


?>
