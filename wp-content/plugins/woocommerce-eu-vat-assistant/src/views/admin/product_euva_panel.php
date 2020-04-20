<?php
// This view is used to render the UI to manage prices by country
use Aelia\WC\EU_VAT_Assistant\Definitions;

?>
<!-- Simple product - EU VAT Assistant settings -->
<div id="euva_data" class="panel woocommerce_options_panel euva product_settings">
	<div class="wc-metaboxes-wrapper">
		<div class="woocommerce_euva wc-metaboxes"><?php
			include('product_euva_section.php');
		?></div>
	</div>
</div>
