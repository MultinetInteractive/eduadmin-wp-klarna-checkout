<?php
defined( 'ABSPATH' ) || die( 'This plugin must be run within the scope of WordPress.' );

/*
 * Plugin Name: EduAdmin Booking - Klarna Checkout-plugin
 * Plugin URI:  https://www.eduadmin.se
 * Description: Plugin to EduAdmin Booking to enable Klarna Checkout-integration
 * Version:     1.1.1
 * GitHub Plugin Uri: https://github.com/MultinetInteractive/eduadmin-wp-klarna-checkout
 * Requires at least: 4.7
 * Tested up to: 5.2
 * Author: Chris Gårdenberg, MultiNet Interactive AB
 * Author URI: https://www.multinet.com
 * License: GPL3
 * Text Domain: eduadmin-wp-klarna-checkout
 */
/*
	EduAdmin Booking plugin
	Copyright (C) 2015-2018 Chris Gårdenberg, MultiNet Interactive AB
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

add_action( 'admin_init', function () {
	if ( is_admin() && current_user_can( 'activate_plugins' ) && ( ! is_plugin_active( 'eduadmin-booking/eduadmin.php' ) && ! is_plugin_active( 'eduadmin/eduadmin.php' ) ) ) {
		add_action( 'admin_notices', function () {
			?>
            <div class="error">
                <p><?php esc_html_e( 'This plugin requires the EduAdmin-WordPress-plugin to be installed and activated.', 'eduadmin-wp-klarna-checkout' ); ?></p>
            </div>
			<?php
		} );
		deactivate_plugins( plugin_basename( __FILE__ ) );
		if ( isset( $_GET['activate'] ) ) {
			unset( $_GET['activate'] );
		}
	}
} );

if ( ! class_exists( 'EDU_KlarnaCheckout_Loader' ) ) {
	final class EDU_KlarnaCheckout_Loader {
		public function __construct() {
			add_action( 'plugins_loaded', array( $this, 'init' ) );
		}

		public function init() {
			if ( ! class_exists( 'Klarna_Checkout_Connector' ) ) {
				require_once __DIR__ . '/klarna_kco_php_4.0.0/src/Klarna/Checkout.php';
			}

			if ( class_exists( 'EDU_Integration' ) ) {

				require_once __DIR__ . '/class-edu-klarnacheckout.php';

				add_filter( 'edu_integrations', array( $this, 'add_integration' ) );
			}
		}

		public function add_integration( $integrations ) {
			$integrations[] = 'EDU_KlarnaCheckout';

			return $integrations;
		}
	}

	$edu_klarnacheckout_loader = new EDU_KlarnaCheckout_Loader();
}
