<?php 

if( isset( $_POST['save'] ) ){
	if( isset( $_POST['old_styles'] ) ){
    	update_option( 'woocommerce_stock_old_styles', sanitize_text_field( $_POST['old_styles'] ) );
	}else{
		delete_option( 'woocommerce_stock_old_styles' );
	}
}

$old_styles = get_option( 'woocommerce_stock_old_styles' );
if( empty( $old_styles ) ){ $old_styles = 'no'; }
?>

<div class="wrap">

	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
  
	<div class="t-col-6">
  		<div class="toret-box box-info">
    		<div class="box-header">
      			<h3 class="box-title"><?php _e('Stock manager setting','woocommerce-stock-manager'); ?></h3>
    		</div>
  			<div class="box-body">
  			<div class="clear"></div>
    			<form method="post" action="" style="position:relative;">
      				<table class="table-bordered">
      					<tr>
      						<th><?php _e('Active ol styles','woocommerce-stock-manager'); ?></th>
      						<td><input type="checkbox" name="old_styles" value="ok" <?php if( $old_styles == 'ok' ){ echo 'checked="checked"'; } ?> /></td>
      					</tr>
      				</table>
      				<input type="submit" name="save" class="btn btn-danger" />
      			</form>
  			</div>
 		</div>
	</div>
</div>