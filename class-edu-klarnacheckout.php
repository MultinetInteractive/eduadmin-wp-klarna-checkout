<?php
defined( 'ABSPATH' ) || die( 'This plugin must be run within the scope of WordPress.' );

if ( ! class_exists( 'EDU_KlarnaCheckout' ) ) {
	class EDU_KlarnaCheckout extends EDU_Integration {
		public function __construct() {
			$this->id          = 'eduadmin-klarnacheckout';
			$this->displayName = __( 'Klarna Checkout', 'eduadmin-wp-klarna-checkout' );
			$this->description = '';

			$this->init_form_fields();
			$this->init_settings();

			add_action( 'eduadmin-checkpaymentplugins', array( $this, 'intercept_booking' ) );
			add_action( 'eduadmin-processbooking', array( $this, 'process_booking' ) );
			add_action( 'wp_loaded', array( $this, 'process_klarnaresponse' ) );
		}

		public function intercept_booking() {
			if ( 'no' === $this->get_option( 'enabled', 'no' ) ) {
				return;
			}
		}

		public function process_booking() {
			if ( 'no' === $this->get_option( 'enabled', 'no' ) ) {
				return;
			}
		}

		public function process_klarnaresponse() {
			if ( 'no' === $this->get_option( 'enabled', 'no' ) ) {
				return;
			}
		}

		public function init_form_fields() {
			$this->setting_fields = array(
				'enabled'  => array(
					'title'       => __( 'Enabled', 'edauadmin-wp-klarna-checkout' ),
					'type'        => 'checkbox',
					'description' => __( 'Enables/Disabled the integration with Klarna Checkout', 'eduadmin-wp-klarna-checkout' ),
					'default'     => 'no',
				),
				'username' => array(
					'title'       => __( 'Username (UID)', 'eduadmin-wp-klarna-checkout' ),
					'type'        => 'text',
					'description' => __( 'The API credential username from Klarna', 'eduadmin-wp-klarna-checkout' ),
					'default'     => '',
				),
				'password' => array(
					'title'       => __( 'Password', 'eduadmin-wp-klarna-checkout' ),
					'type'        => 'password',
					'description' => __( 'Password for the API credentials from Klarna', 'eduadmin-wp-klarna-checkout' ),
					'default'     => '',
				),
			);
		}
	}
}
