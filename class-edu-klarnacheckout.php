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
			add_action( 'eduadmin-bookingcompleted', array( $this, 'process_klarnaresponse' ) );
			add_action( 'wp_loaded', array( $this, 'process_paymentstatus' ) );

			add_shortcode( 'eduadmin-klarna-testpage', array( $this, 'test_page' ) );
		}

		/**
		 * @param $attributes
		 */
		public function test_page( $attributes ) {
			$attributes = shortcode_atts(
				array(
					'bookingid'          => 0,
					'programmebookingid' => 0,
				),
				normalize_empty_atts( $attributes ),
				'test_page'
			);

			if ( $attributes['bookingid'] > 0 ) {
				$event_booking = EDUAPI()->OData->Bookings->GetItem(
					$attributes['bookingid'],
					null,
					'Customer($select=CustomerId;),ContactPerson($select=PersonId;),OrderRows',
					false
				);
			} elseif ( $attributes['programmebookingid'] > 0 ) {
				$event_booking = EDUAPI()->OData->ProgrammeBookings->GetItem(
					$attributes['programmebookingid'],
					null,
					'Customer($select=CustomerId;),ContactPerson($select=PersonId;),OrderRows',
					false
				);
			}

			$_customer = EDUAPI()->OData->Customers->GetItem(
				$event_booking['Customer']['CustomerId'],
				null,
				null,
				false
			);

			$_contact = EDUAPI()->OData->Persons->GetItem(
				$event_booking['ContactPerson']['PersonId'],
				null,
				null,
				false
			);

			$ebi = new EduAdmin_BookingInfo( $event_booking, $_customer, $_contact );

			if ( ! empty( EDU()->session['klarna-order-id'] ) && ! empty( $_GET['klarna_order_id'] ) && EDU()->session['klarna-order-id'] === $_GET['klarna_order_id'] ) {
				do_action( 'eduadmin-bookingcompleted', $ebi );
			} else {
				do_action( 'eduadmin-processbooking', $ebi );
			}
		}

		/**
		 * @param EduAdmin_BookingInfo|null $ebi
		 */
		public function intercept_booking( $ebi = null ) {
			if ( 'no' === $this->get_option( 'enabled', 'no' ) ) {
				return;
			}

			if ( ! empty( $_POST['act'] ) && ( 'bookCourse' === $_POST['act'] || 'bookProgramme' === $_POST['act'] ) ) {
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

			if ( empty( $_GET['klarna_order_id'] ) || empty( EDU()->session['klarna-order-id'] ) ) {
				$checkout = $this->create_checkout( $ebi );

				$snippet = $checkout['gui']['snippet'];
				echo "<div>{$snippet}</div>";
			}
		}

		public function process_klarnaresponse() {
			if ( 'no' === $this->get_option( 'enabled', 'no' ) ) {
				return;
			}
			$checkout_url  = ! checked( $this->get_option( 'test_mode', 'no' ), '1', false ) ? Klarna_Checkout_Connector::BASE_URL : Klarna_Checkout_Connector::BASE_TEST_URL;
			$shared_secret = $this->get_option( 'shared_secret', '' );

			if ( ! empty( $_GET['klarna_order_id'] ) && ! empty( EDU()->session['klarna-order-id'] ) && EDU()->session['klarna-order-id'] === $_GET['klarna_order_id'] ) {
				try {
					$connector = Klarna_Checkout_Connector::create(
						$shared_secret,
						$checkout_url
					);

					$order_id = EDU()->session['klarna-order-id'];

					$order = new Klarna_Checkout_Order( $connector, $order_id );

					$order->fetch();

					$snippet = $order['gui']['snippet'];
					echo "<div>{$snippet}</div>";
					EDU()->session['klarna-order-id'] = null;

				} catch ( Klarna_Checkout_ApiErrorException $ex ) {
					EDU()->write_debug( $ex->getMessage() );
					EDU()->write_debug( $ex->getPayload() );
				}
			}
		}

		public function init_form_fields() {
			$this->setting_fields = array(
				'enabled'       => array(
					'title'       => __( 'Enabled', 'edauadmin-wp-klarna-checkout' ),
					'type'        => 'checkbox',
					'description' => __( 'Enables/Disabled the integration with Klarna Checkout', 'eduadmin-wp-klarna-checkout' ),
					'default'     => 'no',
				),
				'eid'           => array(
					'title'       => __( 'EID', 'eduadmin-wp-klarna-checkout' ),
					'type'        => 'text',
					'description' => __( 'The EID to connect to Klarna Checkout v2', 'eduadmin-wp-klarna-checkout' ),
					'default'     => '',
				),
				'shared_secret' => array(
					'title'       => __( 'Shared secret', 'eduadmin-wp-klarna-checkout' ),
					'type'        => 'password',
					'description' => __( 'The shared secret to connect to Klarna Checkout v2', 'eduadmin-wp-klarna-checkout' ),
					'default'     => '',
				),
				'termsurl'      => array(
					'title'       => __( 'Terms and Conditions URL', 'eduadmin-wp-klarna-checkout' ),
					'type'        => 'text',
					'description' => __( 'This URL is required for Klarna Checkout', 'eduadmin-wp-klarna-checkout' ),
					'default'     => '',
				),
				'test_mode'     => array(
					'title'       => __( 'Test mode', 'eduadmin-wp-klarna-checkout' ),
					'type'        => 'checkbox',
					'description' => __( 'Enables test mode, so you can test the integration', 'eduadmin-wp-klarna-checkout' ),
					'default'     => 'no',
				),
			);
		}

		/**
		 * @param EduAdmin_BookingInfo|null $ebi
		 *
		 * @return Klarna_Checkout_Order|null
		 */
		public function create_checkout( $ebi = null ) {

			$checkout_url  = ! checked( $this->get_option( 'test_mode', 'no' ), '1', false ) ? Klarna_Checkout_Connector::BASE_URL : Klarna_Checkout_Connector::BASE_TEST_URL;
			$shared_secret = $this->get_option( 'shared_secret', '' );

			$create = array();

			$create['locale']            = strtolower( str_replace( '_', '-', get_locale() ) );
			$create['purchase_country']  = 'SE';
			$create['purchase_currency'] = get_option( 'eduadmin-currency', 'SEK' );

			$merchant              = array();
			$merchant['id']        = $this->get_option( 'eid', '' );
			$merchant['terms_uri'] = $this->get_option( 'termsurl', '' );

			$current_url = esc_url( "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}" );

			$booking_id           = 0;
			$programme_booking_id = 0;

			$reference_id = 0;

			$_event = null;

			if ( ! empty( $ebi->EventBooking['BookingId'] ) ) {
				$booking_id   = intval( $ebi->EventBooking['BookingId'] );
				$reference_id = $booking_id;

				$_event = EDUAPI()->OData->Events->GetItem( $ebi->EventBooking['EventId'] );
			}

			if ( ! empty( $ebi->EventBooking['ProgrammeBookingId'] ) ) {
				$programme_booking_id = intval( $ebi->EventBooking['ProgrammeBookingId'] );
				$reference_id         = $programme_booking_id;

				$_event = EDUAPI()->OData->ProgrammeStarts->GetItem( $ebi->EventBooking['ProgrammeStartId'] );
			}

			$rowExtraInfo = "";

			if ( null != $_event ) {
				if ( ! empty( $_event['City'] ) ) {
					$rowExtraInfo .= ';' . $_event['City'];
				}

				if ( ! empty( $_event['StartDate'] ) ) {
					$rowExtraInfo .= ';' . date( "Y-m-d", strtotime( $_event['StartDate'] ) );
				}

				if ( ! empty( $_event['EndDate'] ) ) {
					$rowExtraInfo .= ';' . date( "Y-m-d", strtotime( $_event['EndDate'] ) );
				}
			}

			$confirmation_url = add_query_arg(
				array(
					'klarna_order_id'      => '{checkout.order.id}',
					'booking_id'           => $booking_id,
					'programme_booking_id' => $programme_booking_id,
					'edu-valid-form'       => wp_create_nonce( 'edu-booking-confirm' ),
					'act'                  => 'paymentCompleted',
				),
				$current_url
			);

			$push_url = add_query_arg(
				array(
					'klarna_order_id'      => '{checkout.order.id}',
					'booking_id'           => $booking_id,
					'programme_booking_id' => $programme_booking_id,
					'status'               => 'push',
				),
				$current_url
			);

			$merchant['checkout_uri']     = $current_url;
			$merchant['confirmation_uri'] = $confirmation_url;
			$merchant['push_uri']         = $push_url;

			$create['merchant'] = $merchant;

			$create['merchant_reference']             = array();
			$create['merchant_reference']['orderid1'] = $reference_id;
			$create['merchant_reference']['orderid2'] = $reference_id;

			$create['cart']          = array();
			$create['cart']['items'] = array();

			foreach ( $ebi->EventBooking['OrderRows'] as $order_row ) {
				$cart_item = array();

				$cart_item['reference'] = $order_row['ItemNumber'];
				$cart_item['name']      = $order_row['Description'] . $rowExtraInfo;
				$cart_item['quantity']  = intval( $order_row['Quantity'] );

				if ( ! $order_row['PriceIncVat'] ) {
					$price_per_unit = $order_row['PricePerUnit'] * ( 1 + ( $order_row['VatPercent'] / 100 ) ) * 100;
				} else {
					$price_per_unit = $order_row['PricePerUnit'] * 100;
				}

				$cart_item['unit_price']    = $price_per_unit;
				$cart_item['tax_rate']      = intval( $order_row['VatPercent'] * 100 );
				$cart_item['discount_rate'] = intval( $order_row['DiscountPercent'] * 100 );

				$create['cart']['items'][] = $cart_item;
			}

			try {
				$connector = Klarna_Checkout_Connector::create(
					$shared_secret,
					$checkout_url
				);

				$order = new Klarna_Checkout_Order( $connector );
				$order->create( $create );

				$order->fetch();

				$order_id                         = $order['id'];
				EDU()->session['klarna-order-id'] = $order_id;

				return $order;
			} catch ( Klarna_Checkout_ApiErrorException $ex ) {
				EDU()->write_debug( $ex->getMessage() );
				EDU()->write_debug( $ex->getPayload() );

				return null;
			}
		}

		public function process_paymentstatus() {
			if ( ! empty( $_GET['klarna_order_id'] ) && ! empty( $_GET['status'] ) ) {
				$checkout_url  = ! checked( $this->get_option( 'test_mode', 'no' ), '1', false ) ? Klarna_Checkout_Connector::BASE_URL : Klarna_Checkout_Connector::BASE_TEST_URL;
				$shared_secret = $this->get_option( 'shared_secret', '' );

				try {
					$connector = Klarna_Checkout_Connector::create(
						$shared_secret,
						$checkout_url
					);

					$order_id = $_GET['klarna_order_id'];

					$order = new Klarna_Checkout_Order( $connector, $order_id );

					$order->fetch();

					$booking_id           = intval( $_GET['booking_id'] );
					$programme_booking_id = intval( $_GET['programme_booking_id'] );


					if ( 'checkout_complete' === $order['status'] ) {

						$patch_booking       = new stdClass();
						$patch_booking->Paid = true;

						// We're setting this as a Card Payment, so that our service in the background will remove it if it doesn't get paid in time (15 minute slot)
						$patch_booking->PaymentMethodId = 2;

						if ( $booking_id > 0 ) {
							EDUAPI()->REST->Booking->PatchBooking(
								$booking_id,
								$patch_booking
							);
						}

						if ( $programme_booking_id > 0 ) {
							EDUAPI()->REST->ProgrammeBooking->PatchBooking(
								$programme_booking_id,
								$patch_booking
							);
						}

						$update           = array();
						$update['status'] = 'created';
						$order->update( $update );
					}
					exit( 0 );
				} catch ( Klarna_Checkout_ApiErrorException $ex ) {
					EDU()->write_debug( $ex->getMessage() );
					EDU()->write_debug( $ex->getPayload() );
					exit( 1 );
				}
			}
		}
	}
}
