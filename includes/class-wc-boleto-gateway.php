<?php
/**
 * WC Boleto Gateway Class.
 *
 * Built the Boleto method.
 */
class WC_Boleto_Parcelado_Gateway extends WC_Payment_Gateway {

	/**
	 * Gateway's Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->id           = 'boleto-parcelado';
		$this->icon         = apply_filters( 'wcboleto_icon', plugins_url( 'assets/images/boleto.png', plugin_dir_path( __FILE__ ) ) );
		$this->has_fields   = true;
		$this->method_title = __( 'Banking Ticket Installments', 'woocommerce-boleto-parcelado' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user settings variables.
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->boleto_time = $this->get_option( 'boleto_time' );
		$this->boleto_first_time = $this->get_option( 'boleto_first_time' );
		$this->boleto_second_time = $this->get_option( 'boleto_second_time' );
		$this->min_value   = intval($this->get_option( 'boleto_minimum' ));
		$this->max_plots   = intval($this->get_option( 'boleto_max_plots' ));
		$this->rate        = intval($this->get_option( 'boleto_rate' ));

		// Actions.
		add_action( 'woocommerce_thankyou_boleto', array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 2 );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// Display admin notices.
		$this->admin_notices();
	}

	/**
	 * Backwards compatibility with version prior to 2.1.
	 *
	 * @return object Returns the main instance of WooCommerce class.
	 */
	protected function woocommerce_instance() {
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1', '>=' ) ) {
			return WC();
		} else {
			global $woocommerce;
			return $woocommerce;
		}
	}

	/**
	 * Displays notifications when the admin has something wrong with the configuration.
	 *
	 * @return void
	 */
	protected function admin_notices() {
		if ( is_admin() ) {
			// Checks that the currency is supported
			if ( ! $this->using_supported_currency() ) {
				add_action( 'admin_notices', array( $this, 'currency_not_supported_message' ) );
			}
		}
	}

	/**
	 * Returns a bool that indicates if currency is amongst the supported ones.
	 *
	 * @return bool
	 */
	protected function using_supported_currency() {
		return ( 'BRL' == get_woocommerce_currency() );
	}

	/**
	 * Returns a value indicating the the Gateway is available or not. It's called
	 * automatically by WooCommerce before allowing customers to use the gateway
	 * for payment.
	 *
	 * @return bool
	 */
	public function is_available() {
		// Test if is valid for use.
		if('yes' == $this->get_option( 'enabled' ) && $this->using_supported_currency() && $this->get_order_total() >= $this->min_value){
			return 'yes';
		}
		else{
			return null;
		}
	}

	/**
	 * Admin Panel Options.
	 *
	 * @return string Admin form.
	 */
	public function admin_options() {
		echo '<h3>' . __( 'Banking Ticket', 'woocommerce-boleto-parcelado' ) . '</h3>';
		echo '<p>' . __( 'Enables payments via Banking Ticket.', 'woocommerce-boleto-parcelado' ) . '</p>';

		// Generate the HTML For the settings form.
		echo '<table class="form-table">';
		$this->generate_settings_html();
		echo '</table>';
		echo '<script type="text/javascript" src="' . plugins_url( 'assets/js/admin.js', plugin_dir_path( __FILE__ ) ) . '"></script>';
	}

	/**
	 * Start Gateway Settings Form Fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$shop_name = get_bloginfo( 'name' );

		$first = array(
			'enabled' => array(
				'title'   => __( 'Enable/Disable', 'woocommerce-boleto-parcelado' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Banking Ticket', 'woocommerce-boleto-parcelado' ),
				'default' => 'yes'
			),
			'title' => array(
				'title'       => __( 'Title', 'woocommerce-boleto-parcelado' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woocommerce-boleto-parcelado' ),
				'desc_tip'    => true,
				'default'     => __( 'Banking Ticket', 'woocommerce-boleto-parcelado' )
			),
			'description' => array(
				'title'       => __( 'Description', 'woocommerce-boleto-parcelado' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce-boleto-parcelado' ),
				'desc_tip'    => true,
				'default'     => __( 'Pay with Banking Ticket', 'woocommerce-boleto-parcelado' )
			),
			'boleto_details' => array(
				'title' => __( 'Ticket Details', 'woocommerce-boleto-parcelado' ),
				'type'  => 'title'
			),
			'boleto_time' => array(
				'title'       => __( 'Deadline to pay the Ticket', 'woocommerce-boleto-parcelado' ),
				'type'        => 'text',
				'description' => __( 'Number of days to pay.', 'woocommerce-boleto-parcelado' ),
				'desc_tip'    => true,
				'default'     => 30
			),
			'boleto_first_time' => array(
				'title'       => __( 'Deadline to pay the First Ticket', 'woocommerce-boleto-parcelado' ),
				'type'        => 'text',
				'description' => __( 'Number of days to pay.', 'woocommerce-boleto-parcelado' ),
				'desc_tip'    => true,
				'default'     => 5
			),
			'boleto_second_time' => array(
				'title'       => __( 'Deadline to pay the Second Ticket', 'woocommerce-boleto-parcelado' ),
				'type'        => 'text',
				'description' => __( 'Number of days to pay.', 'woocommerce-boleto-parcelado' ),
				'desc_tip'    => true,
				'default'     => 35
			),
			'boleto_minimum' => array(
				'title'       => __( 'Minimum value for ticket appear', 'woocommerce-boleto-parcelado' ),
				'type'        => 'text',
				'desc_tip'    => true,
			),
		    'boleto_max_plots' => array(
				'title'       => __( 'Maximum plots', 'woocommerce-boleto-parcelado' ),
				'type'        => 'text',
				'desc_tip'    => true,
			),
			'boleto_rate' => array(
				'title'       => __( 'Tax rate in each plots', 'woocommerce-boleto-parcelado' ),
				'type'        => 'text',
				'desc_tip'    => true,
			),
			'boleto_logo' => array(
				'title'       => __( 'Ticket Logo', 'woocommerce-boleto-parcelado' ),
				'type'        => 'text',
				'description' => __( 'Logo with 147px x 46px.', 'woocommerce-boleto-parcelado' ),
				'desc_tip'    => true,
				'default'     => plugins_url( 'assets/images/logo_empresa.png', plugin_dir_path( __FILE__ ) )
			),
			'bank_details' => array(
				'title' => __( 'Bank Details', 'woocommerce-boleto-parcelado' ),
				'type'  => 'title'
			),
			'bank' => array(
				'title'       => __( 'Bank', 'woocommerce-boleto-parcelado' ),
				'type'        => 'select',
				'desc_tip'    => true,
				'description' => __( 'Choose the bank for Ticket.', 'woocommerce-boleto-parcelado' ),
				'default'     => '0',
				'options'     => array(
					'0'          => '--',
					'bb'         => __( 'Banco do Brasil', 'woocommerce-boleto-parcelado' ),
					'bradesco'   => __( 'Bradesco', 'woocommerce-boleto-parcelado' ),
					'cef'        => __( 'Caixa Economica Federal - SR (SICOB)', 'woocommerce-boleto-parcelado' ),
					'cef_sigcb'  => __( 'Caixa Economica Federal - SIGCB', 'woocommerce-boleto-parcelado' ),
					'cef_sinco'  => __( 'Caixa Economica Federal - SINCO', 'woocommerce-boleto-parcelado' ),
					'hsbc'       => __( 'HSBC', 'woocommerce-boleto-parcelado' ),
					'itau'       => __( 'Itau', 'woocommerce-boleto-parcelado' ),
					'nossacaixa' => __( 'Nossa Caixa', 'woocommerce-boleto-parcelado' ),
					'real'       => __( 'Real', 'woocommerce-boleto-parcelado' ),
					'santander'  => __( 'Santander', 'woocommerce-boleto-parcelado' ),
					'unibanco'   => __( 'Unibanco', 'woocommerce-boleto-parcelado' ),
					'bancoob'    => __( 'Bancoob', 'woocommerce-boleto-parcelado')
				)
			)
		);

		$last = array(
			'extra_details' => array(
				'title' => __( 'Optional Data', 'woocommerce-boleto-parcelado' ),
				'type'  => 'title'
			),
			'quantidade' => array(
				'title'       => __( 'Quantity', 'woocommerce-boleto-parcelado' ),
				'type'        => 'text'
			),
			'valor_unitario' => array(
				'title'       => __( 'Unitary value', 'woocommerce-boleto-parcelado' ),
				'type'        => 'text'
			),
			'aceite' => array(
				'title'       => __( 'Acceptance', 'woocommerce-boleto-parcelado' ),
				'type'        => 'text'
			),
			'especie' => array(
				'title'       => __( 'Currency', 'woocommerce-boleto-parcelado' ),
				'type'        => 'text',
				'default'     => 'R$'
			),
			'especie_doc' => array(
				'title'       => __( 'Kind of document', 'woocommerce-boleto-parcelado' ),
				'type'        => 'text'
			),
			'especie' => array(
				'title'       => __( 'Currency', 'woocommerce-boleto-parcelado' ),
				'type'        => 'text',
				'default'     => 'R$'
			),
			'demonstrative' => array(
				'title' => __( 'Demonstrative', 'woocommerce-boleto-parcelado' ),
				'type'  => 'title'
			),
			'demonstrativo1' => array(
				'title'       => __( 'Line 1', 'woocommerce-boleto-parcelado' ),
				'type'        => 'text',
				'description' => __( 'Use [number] to show the Order ID.', 'woocommerce-boleto-parcelado' ),
				'desc_tip'    => true,
				'default'     => sprintf( __( 'Payment for purchase in %s', 'woocommerce-boleto-parcelado' ), $shop_name )
			),
			'demonstrativo2' => array(
				'title'       => __( 'Line 2', 'woocommerce-boleto-parcelado' ),
				'type'        => 'text',
				'description' => __( 'Use [number] to show the Order ID.', 'woocommerce-boleto-parcelado' ),
				'desc_tip'    => true,
				'default'     => __( 'Payment referred to the order [number]', 'woocommerce-boleto-parcelado' )
			),
			'demonstrativo3' => array(
				'title'       => __( 'Line 3', 'woocommerce-boleto-parcelado' ),
				'type'        => 'text',
				'description' => __( 'Use [number] to show the Order ID.', 'woocommerce-boleto-parcelado' ),
				'desc_tip'    => true,
				'default'     => $shop_name . ' - ' . home_url()
			),
			'instructions' => array(
				'title' => __( 'Instructions', 'woocommerce-boleto-parcelado' ),
				'type'  => 'title'
			),
			'instrucoes1' => array(
				'title'       => __( 'Line 1', 'woocommerce-boleto-parcelado' ),
				'type'        => 'text',
				'default'     => __( '- Mr. Cash, charge a fine of 2% after maturity', 'woocommerce-boleto-parcelado' )
			),
			'instrucoes2' => array(
				'title'       => __( 'Line 2', 'woocommerce-boleto-parcelado' ),
				'type'        => 'text',
				'default'     => __( '- Receive up to 10 days past due', 'woocommerce-boleto-parcelado' )
			),
			'instrucoes3' => array(
				'title'       => __( 'Line 3', 'woocommerce-boleto-parcelado' ),
				'type'        => 'text',
				'default'     => sprintf( __( '- For questions please contact us: %s', 'woocommerce-boleto-parcelado' ), get_option( 'woocommerce_email_from_address' ) )
			),
			'instrucoes4' => array(
				'title'       => __( 'Line 4', 'woocommerce-boleto-parcelado' ),
				'type'        => 'text',
				'default'     => ''
			),
			'shop_details' => array(
				'title' => __( 'Shop Details', 'woocommerce-boleto-parcelado' ),
				'type'  => 'title'
			),
			'cpf_cnpj' => array(
				'title'       => __( 'CPF/CNPJ', 'woocommerce-boleto-parcelado' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'Document number.', 'woocommerce-boleto-parcelado' ),
			),
			'endereco' => array(
				'title'       => __( 'Address', 'woocommerce-boleto-parcelado' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'Shop Address.', 'woocommerce-boleto-parcelado' ),
			),
			'cidade_uf' => array(
				'title'       => __( 'City/State', 'woocommerce-boleto-parcelado' ),
				'type'        => 'text',
				'desc_tip'    => true,
				'description' => __( 'Example <code>S&atilde;o Paulo/SP</code>.', 'woocommerce-boleto-parcelado' ),
			),
			'cedente' => array(
				'title' => __( 'Corporate Name', 'woocommerce-boleto-parcelado' ),
				'type'  => 'text',
			),
		);

		$this->form_fields = array_merge( $first, $this->get_bank_fields(), $last );
	}

	/**
	 * Gets bank fields.
	 *
	 * @return array Current bank fields.
	 */
	protected function get_bank_fields() {

		switch ( $this->get_option( 'bank' ) ) {
			case 'bb':
				$fields = array(
					'agencia' => array(
						'title'       => __( 'Agency', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Agency number without digit.', 'woocommerce-boleto-parcelado' )
					),
					'conta' => array(
						'title'       => __( 'Account', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Account number without digit.', 'woocommerce-boleto-parcelado' )
					),
					'convenio' => array(
						'title'       => __( 'Agreement number', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Agreements with 6, 7 or 8 digits.', 'woocommerce-boleto-parcelado' )
					),
					'contrato' => array(
						'title' => __( 'Contract number', 'woocommerce-boleto-parcelado' ),
						'type'  => 'text'
					),
					'carteira' => array(
						'title' => __( 'Wallet code', 'woocommerce-boleto-parcelado' ),
						'type'  => 'text'
					),
					'variacao_carteira' => array(
						'title'       => __( 'Wallet variation (optional)', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Wallet variation with dash.', 'woocommerce-boleto-parcelado' )
					),
					'formatacao_convenio' => array(
						'title'       => __( 'Agreement format', 'woocommerce-boleto-parcelado' ),
						'type'        => 'select',
						'default'     => '6',
						'options'     => array(
							'6' => __( 'Agreement with 6 digits', 'woocommerce-boleto-parcelado' ),
							'7' => __( 'Agreement with 7 dígitos', 'woocommerce-boleto-parcelado' ),
							'8' => __( 'Agreement with 8 dígitos', 'woocommerce-boleto-parcelado' ),
						)
					),
					'formatacao_nosso_numero' => array(
						'title'       => __( 'Our number formatting', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Used only for agreement with 6 digits (enter 1 for Our Number is up to 5 digits or 2 for option up to 17 digits).', 'woocommerce-boleto-parcelado' )
					)
				);
				break;
			case 'bancoob':
				$fields = array(
	                    'agencia' => array(
	                        'title'       => __( 'Agency', 'woocommerce-boleto-parcelado' ),
	                        'type'        => 'text',
	                        'description' => __( 'Agency number without digit.', 'woocommerce-boleto-parcelado' )
	                    ),
	                    'conta' => array(
	                        'title'       => __( 'Account', 'woocommerce-boleto-parcelado' ),
	                        'type'        => 'text',
	                        'description' => __( 'Account number without digit.', 'woocommerce-boleto-parcelado' )
	                    ),
	                    'convenio' => array(
	                        'title'       => __( 'Agreement number', 'woocommerce-boleto-parcelado' ),
	                        'type'        => 'text',
	                        'description' => __( 'Agreements with 6, 7 or 8 digits.', 'woocommerce-boleto-parcelado' )
	                    ),
	                    'carteira' => array(
	                        'title' => __( 'Wallet code', 'woocommerce-boleto-parcelado' ),
	                        'type'  => 'text'
	                    ),
	                    'numero_parcela' => array(
	                        'title' => __( 'Monthly Payment', 'woocommerce-boleto-parcelado' ),
	                        'type'  => 'text',
	                        'description' => __( 'Monthly Payment requires 3 digits, Default is 001', 'woocommerce-boleto-parcelado' )
	                    ),
	                    'modalidade_cobranca' => array(
	                        'title' => __( 'Billing Mode', 'woocommerce-boleto-parcelado' ),
	                        'type'  => 'text',
	                        'description' => __( 'Billing Mode requires 2 digits, default is 02', 'woocommerce-boleto-parcelado' )
	                    ),
	                    'taxa_boleto' => array(
	                        'title' => __( 'Tax Rate', 'woocommerce-boleto-parcelado' ),
	                        'type' => 'text',
	                        'description' => __( 'Insert tax rate of each payment. Default is 0', 'woocommerce-boleto-parcelado' )
	                    )
	                );
				break;
			case 'bradesco':
				$fields = array(
					'agencia' => array(
						'title'       => __( 'Agency', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Agency number without digit.', 'woocommerce-boleto-parcelado' ),
					),
					'agencia_dv' => array(
						'title' => __( 'Agency digit', 'woocommerce-boleto-parcelado' ),
						'type'  => 'text'
					),
					'conta' => array(
						'title'       => __( 'Account', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Account number without digit.', 'woocommerce-boleto-parcelado' ),
					),
					'conta_dv' => array(
						'title' => __( 'Account digit', 'woocommerce-boleto-parcelado' ),
						'type'  => 'text'
					),
					'conta_cedente' => array(
						'title'       => __( 'Transferor account', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Transferor account without digit (only numbers).', 'woocommerce-boleto-parcelado' ),
					),
					'conta_cedente_dv' => array(
						'title' => __( 'Transferor account digit', 'woocommerce-boleto-parcelado' ),
						'type'  => 'text'
					),
					'carteira' => array(
						'title'   => __( 'Wallet code', 'woocommerce-boleto-parcelado' ),
						'type'    => 'select',
						'default' => '03',
						'options' => array(
							'03' => '03',
							'06' => '06',
							'09' => '09',
							'25' => '25'
						)
					)
				);
				break;
			case 'cef':
				$fields = array(
					'agencia' => array(
						'title'       => __( 'Agency', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Agency number without digit.', 'woocommerce-boleto-parcelado' )
					),
					'conta' => array(
						'title'       => __( 'Account', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Account number without digit.', 'woocommerce-boleto-parcelado' )
					),
					'conta_dv' => array(
						'title' => __( 'Account digit', 'woocommerce-boleto-parcelado' ),
						'type'  => 'text'
					),
					'conta_cedente' => array(
						'title'       => __( 'Transferor account', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Transferor account without digit, use only numbers', 'woocommerce-boleto-parcelado' )
					),
					'conta_cedente_dv' => array(
						'title' => __( 'Transferor account digit', 'woocommerce-boleto-parcelado' ),
						'type'  => 'text'
					),
					'carteira' => array(
						'title'       => __( 'Wallet code', 'woocommerce-boleto-parcelado' ),
						'type'        => 'select',
						'description' => __( 'Confirm this information with your manager.', 'woocommerce-boleto-parcelado' ),
						'default'     => 'SR',
						'options'     => array(
							'SR' => __( 'Without registry', 'woocommerce-boleto-parcelado' ),
							'CR' => __( 'With registry', 'woocommerce-boleto-parcelado' )
						)
					),
					'inicio_nosso_numero' => array(
						'title'       => __( 'Beginning of the Our Number', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Use <code>80, 81 or 82</code> for <strong>Without registry</strong> or <code>90</code> for <strong>With registry</strong>. Confirm this information with your manager.', 'woocommerce-boleto-parcelado' ),
						'default'     => '80'
					)
				);
				break;
			case 'cef_sigcb':
				$fields = array(
					'agencia' => array(
						'title'       => __( 'Agency', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Agency number without digit.', 'woocommerce-boleto-parcelado' )
					),
					'conta' => array(
						'title'       => __( 'Account', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Account number without digit.', 'woocommerce-boleto-parcelado' )
					),
					'conta_dv' => array(
						'title' => __( 'Account digit', 'woocommerce-boleto-parcelado' ),
						'type'  => 'text'
					),
					'conta_cedente' => array(
						'title'       => __( 'Transferor account', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Transferor account with 6 digits, use only numbers.', 'woocommerce-boleto-parcelado' )
					),
					'carteira' => array(
						'title'       => __( 'Wallet code', 'woocommerce-boleto-parcelado' ),
						'type'        => 'select',
						'description' => __( 'Confirm this information with your manager.', 'woocommerce-boleto-parcelado' ),
						'default'     => 'SR',
						'options'     => array(
							'SR' => __( 'Without registry', 'woocommerce-boleto-parcelado' ),
							'CR' => __( 'With registry', 'woocommerce-boleto-parcelado' )
						)
					)
				);
				break;
			case 'cef_sinco':
				$fields = array(
					'agencia' => array(
						'title'       => __( 'Agency', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Agency number without digit.', 'woocommerce-boleto-parcelado' )
					),
					'conta' => array(
						'title'       => __( 'Account', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Account number without digit.', 'woocommerce-boleto-parcelado' ),
					),
					'conta_dv' => array(
						'title' => __( 'Account digit', 'woocommerce-boleto-parcelado' ),
						'type'  => 'text'
					),
					'conta_cedente' => array(
						'title'       => __( 'Transferor account', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Transferor account without digit, use only numbers', 'woocommerce-boleto-parcelado' )
					),
					'conta_cedente_dv' => array(
						'title' => __( 'Transferor account digit', 'woocommerce-boleto-parcelado' ),
						'type'  => 'text'
					),
					'carteira' => array(
						'title'       => __( 'Wallet code', 'woocommerce-boleto-parcelado' ),
						'type'        => 'select',
						'description' => __( 'Confirm this information with your manager.', 'woocommerce-boleto-parcelado' ),
						'default'     => 'SR',
						'options'     => array(
							'SR' => __( 'Without registry', 'woocommerce-boleto-parcelado' ),
							'CR' => __( 'With registry', 'woocommerce-boleto-parcelado' )
						)
					),
				);
				break;
			case 'hsbc':
				$fields = array(
					'codigo_cedente' => array(
						'title'       => __( 'Transferor code', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Transferor code with only 7 digits.', 'woocommerce-boleto-parcelado' )
					),
					'carteira' => array(
						'title'       => __( 'Wallet code', 'woocommerce-boleto-parcelado' ),
						'type'        => 'select',
						'description' => __( 'Accepts only CNR.', 'woocommerce-boleto-parcelado' ),
						'default'     => 'CNR',
						'options'     => array(
							'CNR' => 'CNR'
						)
					)
				);
				break;
			case 'itau':
				$fields = array(
					'agencia' => array(
						'title'       => __( 'Agency', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Agency number.', 'woocommerce-boleto-parcelado' ),
					),
					'conta' => array(
						'title'       => __( 'Account', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Account number without digit.', 'woocommerce-boleto-parcelado' )
					),
					'conta_dv' => array(
						'title' => __( 'Account digit', 'woocommerce-boleto-parcelado' ),
						'type'  => 'text'
					),
					'carteira' => array(
						'title'   => __( 'Wallet code', 'woocommerce-boleto-parcelado' ),
						'type'    => 'select',
						'default' => '104',
						'options' => array(
							'104' => '104',
							'109' => '109',
							'157' => '157',
							'174' => '174',
							'175' => '175',
							'178' => '178'
						)
					)
				);
				break;
			case 'nossacaixa':
				$fields = array(
					'agencia' => array(
						'title'       => __( 'Agency', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Agency number without digit.', 'woocommerce-boleto-parcelado' )
					),
					'conta_cedente' => array(
						'title'       => __( 'Transferor account', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Transferor account without digit and with only 6 numbers.', 'woocommerce-boleto-parcelado' )
					),
					'conta_cedente_dv' => array(
						'title' => __( 'Transferor account digit', 'woocommerce-boleto-parcelado' ),
						'type'  => 'text'
					),
					'carteira' => array(
						'title'   => __( 'Wallet code', 'woocommerce-boleto-parcelado' ),
						'type'    => 'select',
						'default' => '1',
						'options' => array(
							'1' => __( 'Simple Billing (1)', 'woocommerce-boleto-parcelado' ),
							'5' => __( 'Direct Billing (5)', 'woocommerce-boleto-parcelado' )
						)
					),
					'modalidade_conta' => array(
						'title'       => __( 'Account modality', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Account modality with two positions (example: 04).', 'woocommerce-boleto-parcelado' )
					)
				);
				break;
			case 'real':
				$fields = array(
					'agencia' => array(
						'title'       => __( 'Agency', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Agency number without digit.', 'woocommerce-boleto-parcelado' )
					),
					'conta' => array(
						'title'       => __( 'Account', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Account number without digit.', 'woocommerce-boleto-parcelado' )
					),
					'carteira' => array(
						'title' => __( 'Wallet code', 'woocommerce-boleto-parcelado' ),
						'type'  => 'text'
					)
				);
				break;
			case 'santander':
				$fields = array(
					'codigo_cliente' => array(
						'title'       => __( 'Customer code', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Customer code (PSK) with only 7 digits.', 'woocommerce-boleto-parcelado' )
					),
					'ponto_venda' => array(
						'title'       => __( 'Sale point (Agency)', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Agency number.', 'woocommerce-boleto-parcelado' )
					),
					'carteira' => array(
						'title'       => __( 'Wallet code', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Simple collection - Without registration.', 'woocommerce-boleto-parcelado' )
					),
					'carteira_descricao' => array(
						'title'   => __( 'Wallet description', 'woocommerce-boleto-parcelado' ),
						'type'    => 'text',
						'default' => 'COBRANÇA SIMPLES - CSR'
					)
				);
				break;
			case 'unibanco':
				$fields = array(
					'agencia' => array(
						'title'       => __( 'Agency', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Agency number without digit.', 'woocommerce-boleto-parcelado' )
					),
					'conta' => array(
						'title'       => __( 'Account', 'woocommerce-boleto-parcelado' ),
						'type'        => 'text',
						'description' => __( 'Account number without digit.', 'woocommerce-boleto-parcelado' )
					),
					'conta_dv' => array(
						'title' => __( 'Account digit', 'woocommerce-boleto-parcelado' ),
						'type'  => 'text'
					),
					'codigo_cliente' => array(
						'title' => __( 'Customer code', 'woocommerce-boleto-parcelado' ),
						'type'  => 'text'
					),
					'carteira' => array(
						'title' => __( 'Wallet code', 'woocommerce-boleto-parcelado' ),
						'type'  => 'text'
					)
				);
				break;

			default:
				$fields = array();
				break;
		}

		return $fields;
	}
	/**
	 * Validate field
	 *
	 */
	public function validate_fields(){
		if(intval($_POST['woocommerce-boleto-parcelado-value']) > $this->max_plots || intval($this->get_order_total()) < $this->min_value){
			return false;
		}
		return true;
	}
	/**
	 * Fields
	 *
	 */
	public function payment_fields(){
		$price = intval($this->get_order_total());

		_e('<label>Select number of plots</label>','woocommerce-boleto-parcelado');
		echo '<select name="woocommerce-boleto-parcelado-value">';
		for ($i=1; $i <= $this->max_plots; $i++) {
			$item_price = $price / $i;
			$item_price = round($item_price);

			if(!empty($this->rate) && $i != 1){
				$rate = str_replace('%', '', $this->rate);
				$rate = intval($rate);
				$value = ($rate / 100) * $item_price;
				$item_price = $item_price + $value;
				$tax = $rate . '%';
				$tax = sprintf(__('%s interest','woocommerce-boleto-parcelado'),$tax);
			}
			elseif(!empty($this->rate) && $i == 1){
				$tax = __('No interest','woocommerce-boleto-parcelado');
			}
			$item_price = wc_price($item_price);

			echo '<option value="'.$i.'">';
			echo sprintf(__('%sx of %s (%s)','woocommerce-boleto-parcelado'),$i,$item_price,$tax);
			echo '</option>';
		}
		echo '</select>';
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int    $order_id Order ID.
	 *
	 * @return array           Redirect.
	 */
	public function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );

		// Mark as on-hold (we're awaiting the ticket).
		$order->update_status( 'on-hold', __( 'Awaiting boleto payment', 'woocommerce-boleto-parcelado' ) );

		// Generates ticket data.
		$this->generate_boleto_data( $order );


		// Reduce stock levels.
		$order->reduce_order_stock();

		// Remove cart.
		$this->woocommerce_instance()->cart->empty_cart();

		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1', '>=' ) ) {
			$url = $order->get_checkout_order_received_url();
		} else {
			$url = add_query_arg( 'key', $order->order_key, add_query_arg( 'order', $order_id, get_permalink( woocommerce_get_page_id( 'thanks' ) ) ) );
		}

		// Return thankyou redirect.
		return array(
			'result'   => 'success',
			'redirect' => $url
		);
	}

	/**
	 * Output for the order received page.
	 *
	 * @return string Thank You message.
	 */
	public function thankyou_page() {
		$html = '<div class="woocommerce-message">';
		$html .= sprintf( '<a class="button" href="%s" target="_blank">%s</a>', WC_Boleto_Parcelado::get_boleto_url( $_GET['key'] ), __( 'Pay the Ticket &rarr;', 'woocommerce-boleto-parcelado' ) );

		$message = sprintf( __( '%sAttention!%s You will not get the ticket by Correios.', 'woocommerce-boleto-parcelado' ), '<strong>', '</strong>' ) . '<br />';
		$message .= __( 'Please click the following button and pay the Ticket in your Internet Banking.', 'woocommerce-boleto-parcelado' ) . '<br />';
		$message .= __( 'If you prefer, print and pay at any bank branch or lottery retailer.', 'woocommerce-boleto-parcelado' ) . '<br />';

		$html .= apply_filters( 'wcboleto_thankyou_page_message', $message );

		//$html .= '<strong style="display: block; margin-top: 15px; font-size: 0.8em">' . sprintf( __( 'Validity of the Ticket: %s.', 'woocommerce-boleto-parcelado' ), date( 'd/m/Y', time() + ( absint( $this->boleto_time ) * 86400 ) ) ) . '</strong>';

		$html .= '</div>';

		echo $html;
	}

	/**
	 * Generate ticket data.
	 *
	 * @param  object $order order object.
	 *
	 * @return void
	 */
	public function generate_boleto_data( $order ) {
		$plots = intval($_POST['woocommerce-boleto-parcelado-value']);
		$data = array();
		$boleto_time = $boleto_time = new DateTime();
		for ($i=1; $i <= $plots; $i++) {
			$data[$i] = array();
			$item_price = intval($this->get_order_total()) / $plots;
			$item_price = round($item_price);
			if(!empty($this->rate) && $plots != 1){
				$rate = str_replace('%', '', $this->rate);
				$rate = intval($rate);
				$value = ($rate / 100) * $item_price;
				$item_price = $item_price + $value;
			}
			$boleto_time->modify('+'.$this->boleto_time.' days');

			$data[$i]['valor'] = $item_price;
			$data[$i]['nosso_numero'] = apply_filters( 'wcboleto_our_number', $order->id );
			$data[$i]['numero_documento'] = apply_filters( 'wcboleto_document_number', $order->id );
			if($i == 1){
				$data[$i]['data_vencimento'] = date( 'd/m/Y', time() + ( absint( $this->boleto_first_time ) * 86400 ) );
			}
			elseif($i == 2){
				$data[$i]['data_vencimento'] = date( 'd/m/Y', time() + ( absint( $this->boleto_second_time ) * 86400 ) );
			}
			else{
				$data[$i]['data_vencimento'] = $boleto_time->format('d/m/Y');
			}
			$data[$i]['data_documento'] = date( 'd/m/Y' );
		    $data[$i]['data_processamento'] = date( 'd/m/Y' );
		}
		update_post_meta( $order->id, 'wc_boleto_data', $data );
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @param  object $order         Order object.
	 * @param  bool   $sent_to_admin Send to admin.
	 *
	 * @return string                Billet instructions.
	 */
	function email_instructions( $order, $sent_to_admin ) {
		if ( $sent_to_admin || 'on-hold' !== $order->status || 'boleto' !== $order->payment_method ) {
			return;
		}

		$html = '<h2>' . __( 'Payment', 'woocommerce-boleto-parcelado' ) . '</h2>';

		$html .= '<p class="order_details">';

		$message = sprintf( __( '%sAttention!%s You will not get the ticket by Correios.', 'woocommerce-boleto-parcelado' ), '<strong>', '</strong>' ) . '<br />';
		$message .= __( 'Please click the following button and pay the Ticket in your Internet Banking.', 'woocommerce-boleto-parcelado' ) . '<br />';
		$message .= __( 'If you prefer, print and pay at any bank branch or lottery retailer.', 'woocommerce-boleto-parcelado' ) . '<br />';

		$html .= apply_filters( 'wcboleto_email_instructions', $message );

		$html .= '<br />' . sprintf( '<a class="button" href="%s" target="_blank">%s</a>', WC_Boleto_Parcelado::get_boleto_url( $order->order_key ), __( 'Pay the Ticket &rarr;', 'woocommerce-boleto-parcelado' ) ) . '<br />';

		//$html .= '<strong style="font-size: 0.8em">' . sprintf( __( 'Validity of the Ticket: %s.', 'woocommerce-boleto-parcelado' ), date( 'd/m/Y', time() + ( absint( $this->boleto_time ) * 86400 ) ) ) . '</strong>';

		$html .= '</p>';

		echo $html;
	}

	/**
	 * Gets the admin url.
	 *
	 * @return string
	 */
	protected function admin_url() {

		return admin_url( 'admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_Boleto_Gateway' );
	}

	/**
	 * Adds error message when an unsupported currency is used.
	 *
	 * @return string
	 */
	public function currency_not_supported_message() {
		echo '<div class="error"><p><strong>' . __( 'Boleto Disabled', 'woocommerce-boleto-parcelado' ) . '</strong>: ' . sprintf( __( 'Currency <code>%s</code> is not supported. Works only with <code>BRL</code> (Brazilian Real).', 'woocommerce-boleto-parcelado' ), get_woocommerce_currency() ) . '</p></div>';
	}

}
