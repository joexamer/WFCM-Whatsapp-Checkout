<?php
// Make sure we don't expose any info if called directly
if ( ! defined( 'ABSPATH' ) ) { 
    exit;
}
/**
 * Plugin Name: WFCM Whatsapp Checkout
 * Description: Whatsapp checkout plugin for WFCM.
 * Version: 1.1
 * Author: DigaTopia, Yousef Amer
 * Author URI: https://github.com/joexamer
 * Plugin URI: https://github.com/joexamer/WFCM-Whatsapp-Checkout
 * Requires at least Woocommerce : 4.1
 * Requires at least WCFM Front End Manager : 6.4
 * Requires at least WCFM Marketplace Multi Vendor : 3.4
 * Tested up to Wordpress : 5.5
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

 function WCFMWC_check_woocommece_active(){
	if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
		echo "<div class='error'><p><strong>WCFM Whatsapp Checkout</strong> requires <strong>WooCommerce plugin.</strong>&nbsp; Please <a href='https://wordpress.org/plugins/woocommerce' target=_blank>install</a> and activate it.</p></div>";
		}
	}
	add_action('admin_notices', 'WCFMWC_check_woocommece_active');

 function WCFMWC_check_wcmv_active(){
	if ( ! is_plugin_active( 'wc-multivendor-marketplace/wc-multivendor-marketplace.php' ) ) {
		echo "<div class='error'><p><strong>WCFM Whatsapp Checkout</strong> requires <strong>WC Multivendor Marketplace plugin.</strong>&nbsp; Please <a href='https://wordpress.org/plugins/wc-multivendor-marketplace' target=_blank>install</a> and activate it.</p></div>";
		}
	}
	add_action('admin_notices', 'WCFMWC_check_wcmv_active');

 function WCFMWC_check_wcfm_active(){
	if ( ! is_plugin_active( 'wc-frontend-manager/wc_frontend_manager.php' ) ) {
		echo "<div class='error'><p><strong>WCFM Whatsapp Checkout</strong> requires <strong>WC Multivendor Marketplace - Frontend Manager plugin.</strong>&nbsp; Please <a href='https://wordpress.org/plugins/wc-frontend-manager' target=_blank>install</a> and activate it.</p></div>";
		}
	}
	add_action('admin_notices', 'WCFMWC_check_wcfm_active');

add_filter( 'wcfm_marketplace_settings_fields_general', 'vendor_store_custom_fields' );
function vendor_store_custom_fields($settings_fields_general) {
	global $WCFM, $WCFMmp, $wp;
	if(isset($settings_fields_general['banner'])){
		return $settings_fields_general; 
	}
	if( current_user_can('manage_woocommerce') ) {
		$van_cur_url = add_query_arg( array(), $wp->request );
		$van_vendorid = substr( $van_cur_url, strrpos( $van_cur_url, '/' ) + 1 );
		$user_id = intval( $van_vendorid );
	}
	else {
		$user_id = apply_filters( 'wcfm_current_vendor_id', get_current_user_id() );
	}
	$store_whatsapp_opt = array( 'yes' => __( 'Yes', 'wc-frontend-manager' ), 'no' => __( 'No', 'wc-frontend-manager' ) );
	$vendor_data = get_user_meta( $user_id, 'wcfmmp_profile_settings', true );
	$store_whatsapp_show = isset( $vendor_data['store_whatsapp_show'] ) ? $vendor_data['store_whatsapp_show'] : 'no';
	$store_whatsapp = isset( $vendor_data['store_whatsapp_number'] ) ? $vendor_data['store_whatsapp_number'] : null;
	$settings_fields_general["store_whatsapp_number"] = array('label' => __('Whatsapp Number', 'wc-frontend-manager') , 'type' => 'text',  'class' => 'wcfm-text wcfm_ele ', 'label_class' => 'wcfm_title', 'value' => $store_whatsapp );
	$settings_fields_general["store_whatsapp_show"] = array('label' => __('Show Whatsapp button on Checkout', 'wc-frontend-manager') , 'type' => 'select', 'options' => $store_whatsapp_opt, 'class' => 'wcfm-select wcfm_ele', 'label_class' => 'wcfm_title', 'value' => $store_whatsapp_show );
	return $settings_fields_general;
}

add_action( 'after_wcfmmp_sold_by_info_product_page', 'cus_after_wcfmmp_sold_by_info_product_page' );
function cus_after_wcfmmp_sold_by_info_product_page( $vendor_id ) {
	$vendor_data = get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
	$whatsapp = isset($vendor_data['store_whatsapp_number'])?$vendor_data['store_whatsapp_number']:null;
	if( isset($vendor_data['store_whatsapp_show']) && $vendor_data['store_whatsapp_show'] == 'yes' && !empty($whatsapp)) {
		echo '<div class="wcfmmp_store_tab_info wcfmmp_store_info_address"><i class="wcfmfa fa-phone" aria-hidden="true"></i><span>' . $whatsapp . '</div>';
	}
}

add_action( 'woocommerce_before_thankyou', 'wfcm_add_assets_wa_checkout' );
add_filter( 'woocommerce_thankyou_order_received_text', 'wfcm_wa_thankyou', 10, 2 );

function wfcm_wa_thankyou($title, $order) {
	$data =[];
	$shipping_data =[];
	$judul = 'Thank you for your order.';
    $subtitle = 'Complete your checkout by pressing the Order by WA button below so that the order can be processed by the Seller.';
	
	$mode = ($order->get_billing_address_1() != $order->get_shipping_address_1() || $order->get_billing_first_name() != $order->get_shipping_first_name())?'shipping':'billing';
	$mode = ($order->get_billing_address_1() != $order->get_shipping_address_1() || $order->get_billing_first_name() != $order->get_shipping_first_name())?'shipping':'billing';
	$country =  WC()->countries->countries[ $order->{"get_".$mode."_country"}() ];
	$states = WC()->countries->get_states( $order->{"get_".$mode."_country"}() );
	$state_code = $order->{"get_".$mode."_state"}();
	$province = '';
	if ( ! empty( $state_code ) && isset( $states[ $state_code ] ) ) {
		$province = $states[ $state_code ];
	}
	$shipping_method_title = $order->get_shipping_method();
	foreach( $order->get_items( 'shipping' ) as $item_id => $shipping_item_obj ){
		$found=false;
		foreach($shipping_item_obj->get_meta_data() as $i=>$val){
			$d = $val->get_data();
			if($d['key']=='vendor_id'){
				$shipping_data[$d['value']] = [
					'title'=>$shipping_item_obj->get_method_title(),
					'total'=>$shipping_item_obj->get_total(),
				];
				$found = true;
				break;
			}
			if(!$found){
				$shipping_data[0] = [
					'title'=>$shipping_item_obj->get_method_title(),
					'total'=>$shipping_item_obj->get_total(),
				];
			}
		}
	}

	foreach($order->get_items() as $item){
		$vendor_id = $item->get_meta('_vendor_id');
		if(!isset($data[$vendor_id])){
			$vendor_data = get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
			$whatsapp_show = isset( $vendor_data['store_whatsapp_show'] ) ? $vendor_data['store_whatsapp_show'] : 'no';
			$whatsapp = isset( $vendor_data['store_whatsapp_number'] ) ? $vendor_data['store_whatsapp_number'] : null;
			$vendor_name =  get_user_meta( $vendor_id, 'store_name', true );
			if($whatsapp_show!='yes' || empty($whatsapp) ){
				continue;
			}
			$items = $item->get_quantity()."x - *".$item->get_name()."*\n";
	    	$items .= "URL: ".get_permalink( $item->get_product_id() ) ."\n";
			$data[$vendor_id]=[
				'whatsapp'=>$whatsapp,
				'vendor_name'=>$vendor_name,
				'items'=>$items,
				'total'=>$item->get_total(),
			];
		}else{
			$items = $item->get_quantity()."x - *".$item->get_name()."*\n";
	    	$items .= "Tautan: ".get_permalink( $item->get_product_id() ) ."\n";
			$data[$vendor_id]['items'] .= $items;
			$data[$vendor_id]['total'] += $item->get_total();
		}
	}
	
	if(empty($data)){
		return $title;
	}
	$html ='';
	foreach($data as $vendor_id=>$d){
		$msg = "*مرحبًا ، هذه تفاصيل طلبي:*\n\n";
		  	$msg .= $d['items'];
		  	$msg .="*كود الطلب*: ".$order->get_id()."\n";
		  	$msg .="*إجمالي تكلفة الطلب*: ".strip_tags(wc_price($d['total']))."\n";
		  	$msg .="*طريقة الدفع*: ".$order->get_payment_method_title()."\n";
		  	if(isset($shipping_data[$vendor_id])){
		  		$msg .="*طريقة الشحن*: ".$shipping_data[$vendor_id]['title']." ".strip_tags(wc_price($shipping_data[$vendor_id]['total']))."\n\n";
		  	}elseif(isset($shipping_data[0])){
		  		$msg .="*طريقة الشحن*: ".$shipping_data[0]['title']." ". strip_tags(wc_price($shipping_data[0]['total']))."\n\n";
		  	}
		  	
		  	$msg .="*تفاصيل الشحن*:\n";
		  	$msg .="الاسم: ".$order->get_billing_first_name()." ".$order->get_billing_last_name()."\n";
		  	$msg .="العنوان: ".$order->get_billing_address_1()."\n";
		  	$msg .="المدينة: ".$order->get_billing_city()."\n";
		$msg .="الدولة: ".$order->get_billing_country()."\n";
		  	if($mode=='shipping'){
		  		$email = (isset($order->shipping['email']))?$order->shipping['email']:$order->get_billing_email();
		  		$phone = (isset($order->shipping['phone']))?$order->shipping['phone']:$order->get_billing_phone();
		  	}else{
		  		$email = $order->get_billing_email();
		  		$phone = $order->get_billing_phone();
		  	}
		  	$msg .="البريد الإلكتروني: ".$email."\n";
		  	$msg .="رقم الهاتف: ".$phone."\n";
		  	$msg .= "ملاحظات الطلب: ".$order->get_customer_note()."\n\n";
		  	$msg .="شكراً لكم!\n\n";
		  	$msg .= "وقت وتاريخ الطلب: ".get_post_time( 'j-F-Y - H:i', false, $order->get_id(), true )."\n";
    	$whatsapp_link = 'https://api.whatsapp.com/send?phone='.$d['whatsapp'].'&text='.rawurlencode($msg);
    	break; 
 }

 if (empty($whatsapp_link)) {
  return $title; 
 }

 $js = "
 <script type='text/javascript'>
  setTimeout(function() {
   window.open('" . esc_url_raw($whatsapp_link) . "', '_blank');
  }, 3000); // 3000 milliseconds = 3 seconds
 </script>";

 return '<div class="thankyoucustom_wrapper">
    	           <h1 class="thankyoutitle">'.$judul.'</h1>
    	           <p class="subtitle">'.$subtitle.'</p>'.
    	           $js.
    	       '</div>';
}

function wfcm_add_assets_wa_checkout(){
	wp_register_style( 'wa_checkout_style',  plugin_dir_url( __FILE__ ) . 'style.css' );
	wp_enqueue_style( 'wa_checkout_style' );
}
