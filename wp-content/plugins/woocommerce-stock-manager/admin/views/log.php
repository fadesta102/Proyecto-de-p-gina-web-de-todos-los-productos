<?php
/**
 * @package   WooCommerce Stock Manager
 * @author    Vladislav Musílek
 * @license   GPL-2.0+
 * @link      http://toret.cz
 * @copyright 2015 Toret.cz
 */

$stock = $this->stock();

?>


<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<div class="t-col-12">
  		<div class="toret-box box-info">
    		<div class="box-header">
      			<h3 class="box-title"><?php _e('Stock manager','woocommerce-stock-manager'); ?></h3>
    		</div>
  			<div class="box-body">
      
      			<?php include('components/filter.php'); ?>
      
      			<div class="clear"></div>
      			<table class="table-bordered">
        			<tr>
						<th><?php _e('SKU','woocommerce-stock-manager'); ?></th>
						<th><?php _e('ID','woocommerce-stock-manager'); ?></th>
						<th><?php _e('Name','woocommerce-stock-manager'); ?></th>
						<th><?php _e('Product type','woocommerce-stock-manager'); ?></th>
						<th><?php _e('Parent ID','woocommerce-stock-manager'); ?></th>
						<th><?php _e('Stock','woocommerce-stock-manager'); ?></th>
						<th></th>
        			</tr>
      				
					<?php $products = $stock->get_products( $_GET ); 
      
      				if( !empty( $products->posts ) ){
        				foreach( $products->posts as $item ){ 
        					$item_product = wc_get_product( $item->ID );
        					$product_type = $item_product->get_type();
      				?>
        				<tr>
          					<td><?php echo $item_product->get_sku(); ?></td>
          					<td><?php echo $item_product->get_id(); ?></td>
          					<td>
		  						<a href="<?php echo admin_url().'post.php?post='.$item_product->get_id().'&action=edit'; ?>" target="_blank">
                    				<?php echo get_the_title( $item_product->get_id() ); ?>  
                				</a>
							</td>
          					<td class="td_center"><?php echo $product_type; ?></td>
          					<td></td>
          					<td class="td_center"><?php echo $item_product->get_stock_quantity(); ?></td>
							<td class="td_center">
								<?php if( $product_type != 'variable' ){ ?>
									<a class="btn btn-success" href="<?php echo admin_url().'admin.php?page=stock-manager-log&history='.$item_product->get_id(); ?>"><?php echo __( 'History', 'woocommerce-stock-manager' ); ?></a>
								<?php } ?>
							</td>
       					</tr>
        
        					<?php 
            				if($product_type == 'variable'){
                				$args = array(
	               					'post_parent' => $item->ID,
	               					'post_type'   => 'product_variation', 
	               					'numberposts' => -1,
	               					'post_status' => 'publish', 
                 					'order_by' => 'menu_order'
                				); 

                				$variations_array = $item_product->get_children();
                				foreach($variations_array as $vars){
             
        							$item_product = wc_get_product($vars);
        							$product_type = 'product variation' ;
        
      							?>
        							<tr>
          								<td><?php echo $item_product->get_sku(); ?></td>
          								<td><?php echo $item_product->get_id(); ?></td>
          								<td>
		  									<a href="<?php echo admin_url().'post.php?post='.$item_product->get_id().'&action=edit'; ?>" target="_blank">
                    							<?php echo get_the_title( $item_product->get_id() ); ?>  
                							</a>
										</td>
          								<td class="td_center"><?php echo $product_type; ?></td>
          								<td><?php echo $item->ID; ?></td>
          								<td class="td_center"><?php echo $item_product->get_stock_quantity(); ?></td>
										<td class="td_center">
											<a class="btn btn-success" href="<?php echo admin_url().'admin.php?page=stock-manager-log&history='.$item_product->get_id(); ?>"><?php echo __( 'History', 'woocommerce-stock-manager' ); ?></a>
										</td>
       								</tr>
        						<?php        
                				}
            				}
      					}
        			}
       			?>
      			</table>
      		<div class="clear"></div>
      		<?php echo $stock->pagination( $products ); ?>
  		</div>
	</div>   	
</div>
