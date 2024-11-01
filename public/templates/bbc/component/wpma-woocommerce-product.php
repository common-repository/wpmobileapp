<?php

// Filter of WPMA
$iNumberProduct = 6;

$aFilter     = [
  'allowFormCaching' => 1,
  'cache_image'      => 0,
  'cache_permalink'  => 0,
];

// Create Woocommerce API
global $WPMA;
$sJsonProduct = $WPMA->api->createWoocommerceUrl('products/151', $aFilter);
?>

<!-- Start: component woocommerce product -->
<wpma-woocommerce-product amount="<?php echo $iNumberProduct; ?>" class="wpma-el" name="products" url="<?php echo $sJsonProduct ?>" inline-template>
	<div>
		<!-- Testing -->
	</div>
</wpma-woocommerce-product>
<!-- End: component woocommerce product -->