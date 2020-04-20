<?php
/**
 * @package   WooCommerce Stock Manager
 * @author    Vladislav Musílek
 * @license   GPL-2.0+
 * @link      http://toret.cz
 * @copyright 2015 Toret.cz
 */

global $wpdb;

if( !empty( $_GET['history'] ) ){
    $product_id = sanitize_text_field( $_GET['history'] );
    $product = wc_get_product( $product_id );
}else{
    return;
}
?>


<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<div class="t-col-12">
  		<div class="toret-box box-info">
    		<div class="box-header">
      			<h3 class="box-title"><?php echo $product->get_name(); ?></h3>
    		</div>
  			<div class="box-body">
      
            <?php                
                    $data = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."stock_log WHERE product_id = '".$product_id."'");
                    if( !empty( $data ) ){
                        ?>
                        <div class="clear"></div>
      			        <table class="table-bordered">
        			        <tr>
                                <th><?php _e('Date','woocommerce-stock-manager'); ?></th>
                                <th><?php _e('Stock','woocommerce-stock-manager'); ?></th>						        
        			        </tr>
                        <?php
                        foreach( $data as $item ){
                            ?>
                            <tr>
                                <td><?php echo $item->date_created; ?></td>
                                <td><?php echo $item->qty; ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                        </table>
                        <?php
                    }else{
                        echo '<p>'.__( 'No result', 'woocommerce-stock-manager' ).'</p>';
                    } 
            ?>

            </div>
  		</div>
	</div>   	
</div>
