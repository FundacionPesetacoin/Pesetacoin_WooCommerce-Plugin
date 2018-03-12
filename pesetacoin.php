<?php

/*
Plugin Name: Pesetacoin Gateway WooCommerce
Plugin URI: https://www.nutecoweb.com
Description: Pasarela de pago de PesetaCoin para Woocommerce
Version: 0.9.2
Author: Nuteco Web
Author URI: https://www.nutecoweb.com
*/

add_action('plugins_loaded', 'pesetacoin_init');


function pesetacoin_init()
{
  
  define('PLUGIN_DIR', plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__)).'/');

  
  
  
  global $woocommerce;

 
class WC_Gateway_PTC extends WC_Payment_Gateway {


	public $locale;
	
	
	public function __construct() {
		global $woocommerce;

		$this->id                 = 'ptc';
		$this->icon               = apply_filters( 'woocommerce_bacs_icon', '' );
		$this->has_fields         = false;
		$this->method_title       = __( 'PTC', 'woocommerce' );
		$this->method_description = __( 'Pagos con PesetaCoin.', 'woocommerce' );

		
		$this->init_form_fields();
		$this->init_settings();

		
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions' );

	
		$this->account_details = get_option( 'woocommerce_ptc_hashs',
			array(
				array(
					'hash_name'   => $this->get_option( 'hash_name' ),
					
				),
			)
		);

	
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'save_account_details' ) );
		//add_action( 'woocommerce_thankyou_bacs', array( $this, 'thankyou_page' ) );

		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	
	public function init_form_fields() {

		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Activar PesetaCoin', 'woocommerce' ),
				'default' => 'no',
			),
			'title' => array(
				'title'       => __( 'PesetaCoin', 'woocommerce' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce' ),
				'default'     => __( 'PesetaCoin', 'woocommerce' ),
				'desc_tip'    => true,
			),
			
			'description' => array(
				'title'       => __( 'Description', 'woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description that the customer will see on your checkout.', 'woocommerce' ),
				'default'     => __( 'Realiza tu pago directamente con PesetaCoin.', 'woocommerce' ),
				'desc_tip'    => true,
			),
			
			'account_details' => array(
				'type'        => 'account_details',
				'description' => __( 'Se aconseja poner varias direcciones (hash) para recibir el pago.', 'woocommerce' ),
				'desc_tip'    => true,
			),
		);

	}
	
	
	
	
	
	public function get_icon() {
		$icon_html = '';

//precio en PesetaCoins
global $woocommerce;
$euros= $woocommerce->cart->total;	
	$xaxa= "http://nodos.pesetacoin.info/api/api.php";
	$data = file_get_contents($xaxa);
$pesetas = json_decode($data, true);
	$valor_ptc= $pesetas['ptc_eur'];
		$ptc= $euros/$valor_ptc;
		$ptc= round($ptc, 2);
//precio en PesetaCoins


$dir_ptc = plugin_dir_url( __FILE__ );

	
			$icon_html .= '<img src="'.$dir_ptc.'/ptc.png" alt="PesetaCoin"/> ';
		

		$icon_html .=  '  <div style="font-size: 13px">Total en PesetaCoin: <b>'.$ptc.' PTC</b></div>';

		return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
	}


	public function generate_account_details_html() {

		ob_start();

		$country 	= WC()->countries->get_base_country();
		$locale		= $this->get_country_locale();

		
		$sortcode = isset( $locale[ $country ]['sortcode']['label'] ) ? $locale[ $country ]['sortcode']['label'] : __( 'Sort code', 'woocommerce' );

		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php _e( 'Hash de pago de PTC', 'woocommerce' ); ?>: 

            </th>
          
			<td class="forminp" id="bacs_accounts">
				<table class="widefat wc_input_table sortable" cellspacing="0">
					<thead>
						<tr>
							<th class="sort">&nbsp;</th>
							<th>Direcciones</th>
							<?php
		
		?>
							
						</tr>
					</thead>
					<tbody class="accounts">
						<?php
						$i = -1;
						if ( $this->account_details ) {
							foreach ( $this->account_details as $account ) {
								$i++;

								echo '<tr class="account">
									<td class="sort"></td>
									<td><input type="text" value="' . esc_attr( wp_unslash( $account['hash_name'] ) ) . '" name="ptc_hashs[' . $i . ']" /></td>';
									
								
							}
						}
	
						?>
					</tbody>
					<tfoot>
						<tr>
							<th colspan="2"><a href="#" class="add button"><?php _e( '+ Añadir Hash', 'woocommerce' ); ?></a> <a href="#" class="remove_rows button"><?php _e( 'Borrar seleccionados', 'woocommerce' ); ?></a></th>
						</tr>
					</tfoot>
				</table>
				<script type="text/javascript">
					jQuery(function() {
						jQuery('#bacs_accounts').on( 'click', 'a.add', function(){

							var size = jQuery('#bacs_accounts').find('tbody .account').length;
							

							jQuery('<tr class="account">\
									<td class="sort"></td>\
									<td><input type="text" name="ptc_hashs[' + size + ']" /></td>\
									</tr>').appendTo('#bacs_accounts table tbody');
								
							

							return false;
						});
					});
				</script>
			</td>
		</tr>
		<?php
		return ob_get_clean();

	}


	public function save_account_details() {

		$accounts = array();

		if ( isset( $_POST['ptc_hashs'] ) ) {

			$hash_names   = array_map( 'wc_clean', $_POST['ptc_hashs'] );
			
			foreach ( $hash_names as $i => $name ) {
				if ( ! isset( $hash_names[ $i ] ) ) {
					continue;
				}

				$accounts[] = array(
					'hash_name'   => $hash_names[ $i ],
					
				);
			}
		}
		
		

		update_option( 'woocommerce_ptc_hashs', $accounts );

	}

	
	
	
	
	 public function thankyou()
    {

    }
	
	
	public function thankyou_page()
    {
 
     
    }
	
	
	
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		//precio en PesetaCoins
global $woocommerce;
$euros= $woocommerce->cart->total;	
	$xaxa= "http://nodos.pesetacoin.info/api/api.php";
	$data = file_get_contents($xaxa);
$pesetas = json_decode($data, true);
	$valor_ptc= $pesetas['ptc_eur'];
		$ptc= $euros/$valor_ptc;
		$ptc= round($ptc, 2);
//precio en PesetaCoins

	$pagos= array();
		
		$metodo= $order->get_payment_method();
	
						$i = -1;
						foreach ( $this->account_details as $account ) {
								$i++;
							$pagos[$i]= 	
								$pagos[$i]= esc_attr( wp_unslash( $account['hash_name'] ) );
						}

$cont= rand(0, $i);
		
		if($metodo == "ptc") {
		$description= "<span style='font-size:14px'>Para completar el pedido, debe enviar la cantidad <b>".$ptc."</b> de Pesetacoin a la siguiente dirección: <b>";
		$description.= $pagos[$cont];
		$description.="</b><br>Una vez se reciba la transacción se enviará el pedido.</span>";
        echo wpautop(wptexturize($description));
     
				
		}
		

	}
	
	
	


	public function process_payment( $order_id ) {

		$order = wc_get_order( $order_id );

	
		$order->update_status( 'on-hold', __( 'Awaiting BACS payment', 'woocommerce' ) );

		wc_reduce_stock_levels( $order_id );

		WC()->cart->empty_cart();

		
		return array(
			'result'    => 'success',
			'redirect'  => $this->get_return_url( $order ),
		);

	}

	public function get_country_locale() {

		if ( empty( $this->locale ) ) {

		}

		//return $this->locale;
		

	}
	
	
	
}


function add_pesetacoin_gateway($methods)
  {
    $methods[] = 'WC_Gateway_PTC';

    return $methods;
  }

add_filter('woocommerce_payment_gateways', 'add_pesetacoin_gateway');





}





