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

			add_shortcode( 'eduadmin-klarna-testpage', array( $this, 'test_page' ) );
		}

		/**
		 * @param int $booking_id
		 */
		public function test_page( $attributes ) {
			$attributes = shortcode_atts(
				array(
					'bookingid' => 0,
				),
				normalize_empty_atts( $attributes ),
				'test_page'
			);

			$event_booking = EDUAPI()->OData->Bookings->GetItem(
				intval( $attributes['bookingid'] ),
				null,
				'Customer($select=CustomerId;),ContactPerson($select=PersonId;)'
			);
			$_customer     = EDUAPI()->OData->Customers->GetItem( $event_booking['Customer']['CustomerId'] );
			$_contact      = EDUAPI()->OData->Persons->GetItem( $event_booking['ContactPerson']['PersonId'] );

			$ebi = new EduAdmin_BookingInfo( $event_booking, $_customer, $_contact );

			do_action( 'eduadmin-processbooking', $ebi );
		}

		/**
		 * @param EduAdmin_BookingInfo|null $ebi
		 */
		public function intercept_booking( $ebi = null ) {
			if ( 'no' === $this->get_option( 'enabled', 'no' ) ) {
				return;
			}

			if ( ! empty( $_POST['act'] ) && 'bookCourse' === $_POST['act'] ) {
				$ebi->NoRedirect = true;
			}
		}

		/**
		 * @param EduAdmin_BookingInfo|null $ebi
		 */
		public function process_booking( $ebi = null ) {
			if ( 'no' === $this->get_option( 'enabled', 'no' ) ) {
				return;
			}

			$ebi->NoRedirect = true;

			$checkout = $this->create_checkout( $ebi );
			include_once 'checkout-page.php';
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
				'termsurl' => array(
					'title'       => __( 'Terms and Conditions URL', 'eduadmin-wp-klarna-checkout' ),
					'type'        => 'text',
					'description' => __( 'This URL is required for Klarna Checkout', 'eduadmin-wp-klarna-checkout' ),
					'default'     => '',
				),
			);
		}

		/**
		 * @param EduAdmin_BookingInfo|null $ebi
		 *
		 * @return Klarna_Checkout_Order
		 */
		public function create_checkout( $ebi = null ) {
			$create                  = array();
			$create['locale']        = strtolower( str_replace( '_', '-', get_locale() ) );
			$create['cart']          = array();
			$create['cart']['items'] = array();

			try {
				$connector = Klarna_Checkout_Connector::create(
					'',
					Klarna_Checkout_Connector::BASE_TEST_URL
				);

				$order = new Klarna_Checkout_Order( $connector );
				$order->create( $create );

				return $order;
			} catch ( Klarna_Checkout_ApiErrorException $ex ) {
				EDU()->write_debug( $ex->getMessage() );
				EDU()->write_debug( $ex->getPayload() );
				EDU()->write_debug( $create );
			}
		}
	}
}
