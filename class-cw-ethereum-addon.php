<?php
/**
 * Plugin Name: Ethereum CryptoWoo Add-on
 * Plugin URI: https://github.com/WeProgramIT/cryptowoo-ethereum-addon
 * GitHub Plugin URI: WeProgramIT/cryptowoo-ethereum-addon
 * Description: Accept Ethereum payments in WooCommerce. Requires CryptoWoo main plugin.
 * Version: 1.0.0
 * Author: We Program IT | legal company name: OS IT Programming AS | Company org nr: NO 921 074 077
 * Author URI: https://weprogram.it
 * License: GPLv2
 * Text Domain: cryptowoo-ethereum-addon
 * Domain Path: /lang
 * WC tested up to: 3.5.4
 *
 * @package CryptoWoo Ethereum Addon
 */

// Make sure we don't expose any info if called directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( CW_Ethereum_Addon::class ) ) {

	/**
	 * Class CW_Ethereum_Addon
	 */
	class CW_Ethereum_Addon {

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->init();
		}

		/** Get the currency name
		 *
		 * @return string
		 */
		private function get_currency_name() : string {
			return 'Ethereum';
		}

		/** Get the currency protocol name
		 *
		 * @return string
		 */
		private function get_currency_protocol_name() : string {
			return 'ethereum';
		}

		/** Get the currency code
		 *
		 * @return string
		 */
		private function get_currency_code() : string {
			return 'ETH';
		}

		/** Get the list of exchanges
		 *
		 * @return array
		 */
		private function get_exchanges_list() : array {
			return array( 'CoinGecko', 'Binance', 'Bitfinex', 'Bitstamp', 'Bittrex', 'Coinbase', 'Kraken', 'Livecoin', 'Poloniex', 'ShapeShift' );
		}

		/** Get the default number of decimals
		 *
		 * @return int
		 */
		private function get_default_decimals() : int {
			return 4;
		}

		/**
		 * Initialize plugin
		 */
		public function init() {

			include_once ABSPATH . 'wp-admin/includes/plugin.php';

			if ( ! $this->plugin_is_installed( 'cryptowoo' ) ) {
				add_action( 'admin_notices', array( $this, 'cw_not_installed_notice' ) );
			} elseif ( ! $this->plugin_is_activated( 'cryptowoo' ) ) {
				add_action( 'admin_notices', array( $this, 'cw_inactive_notice' ) );
			} else {
				$this->activate();
			}
		}

		/** Check if a plugin is installed
		 *
		 * @param string $plugin_id Plugin id name.
		 *
		 * @return bool
		 */
		private function plugin_is_installed( string $plugin_id ) : bool {
			return file_exists( WP_PLUGIN_DIR . '/' . $plugin_id );
		}

		/** Check if a plugin is activated
		 *
		 * @param string $plugin_id Plugin id name.
		 *
		 * @return bool
		 */
		private function plugin_is_activated( string $plugin_id ) : bool {
			return is_plugin_active( "$plugin_id/$plugin_id.php" );
		}

		/**
		 * Display CryptoWoo not installed notice
		 */
		public function cw_not_installed_notice() {
			$this->addon_not_installed_notice( 'CryptoWoo' );
		}

		/**
		 * Display CryptoWoo inactive notice
		 */
		public function cw_inactive_notice() {
			$this->addon_inactive_notice( 'CryptoWoo' );
		}

		/** Display addon inactive notice
		 *
		 * @param string $addon_name Addon name.
		 */
		private function addon_inactive_notice( string $addon_name ) {
			$this->require_cw_admin_notice();

			$addon_id = strtolower( str_replace( [ 'CryptoWoo', ' ' ], [ 'cw', '_' ], $addon_name ) );

			CW_Admin_Notice::generate( CW_Admin_Notice::NOTICE_ERROR )
			->add_message( "{$this->get_plugin_name()} " . __( 'error' ) )
			->add_message( "$addon_name " . __( 'plugin is inactive' ) )
			->add_message( __( 'Activate the addon and go to the CryptoWoo checkout settings to make sure the settings are correct.' ) )
			->add_button_plugin_activate( __( 'Activate', 'cryptowoo' ) . ' CryptoWoo ' . __( 'Payment Gateway', 'cryptowoo' ), __( 'Activate', 'cryptowoo' ) . ' CryptoWoo ' . __( 'Payment Gateway', 'cryptowoo' ), 'cryptowoo' )
			->make_dismissible( "{$this->get_currency_short_name()}_{$addon_id}_not_active" )
			->print();
		}

		/** Display CryptoWoo HD Wallet add-on not installed notice
		 *
		 * @param string $addon_name test Addon name.
		 * TODO: Add link to CryptoWoo and HD Wallet Addon.
		 */
		private function addon_not_installed_notice( string $addon_name ) {
			$this->require_cw_admin_notice();

			CW_Admin_Notice::generate( CW_Admin_Notice::NOTICE_ERROR )
			->add_message( "{$this->get_plugin_name()} " . __( 'error' ) )
			->add_message( "$addon_name " . __( 'plugin has not been installed' ) )
			->add_message( "{$this->get_plugin_name()} " . __( 'will only work in combination with' ) . " $addon_name." )
			->make_dismissible( "{$this->get_currency_short_name()}_hd_wallet_not_installed" )
			->print();
		}

		/** Require CW_Admin_Notice class */
		private function require_cw_admin_notice() {
			if ( ! class_exists( CW_Admin_Notice::class ) ) {
				if ( ! $this->plugin_is_installed( 'cryptowoo' ) ) {
					require_once __DIR__ . 'admin/class-cw-admin-notice.php';
				}

				require_once WP_PLUGIN_DIR . '/cryptowoo/admin/class.cw-admin-notice.php';
			}
		}

		/**
		 * Activate plugin
		 */
		public function activate() {
			// Check if Ethereum Addon is enabled.
			add_filter( 'cw_coins_enabled', array( $this, 'coins_enabled_override' ), 10, 3 );

			// Coin symbol and name.
			add_filter( 'woocommerce_currencies', array( $this, 'woocommerce_currencies' ), 10, 1 );
			add_filter( 'cw_get_currency_symbol', array( $this, 'get_currency_symbol' ), 10, 2 );
			add_filter( 'cw_get_enabled_currencies', array( $this, 'add_coin_identifier' ), 10, 1 );

			// BIP32 prefixes.
			add_filter( 'address_prefixes', array( $this, 'address_prefixes' ), 10, 1 );

			// Custom block explorer URL.
			add_filter( 'cw_link_to_address', array( $this, 'link_to_address' ), 10, 4 );

			// Options page validations.
			add_filter( 'validate_custom_api_genesis', array( $this, 'validate_custom_api_genesis' ), 10, 2 );
			add_filter( 'validate_custom_api_currency', array( $this, 'validate_custom_api_currency' ), 10, 2 );
			add_filter( 'cryptowoo_is_ready', array( $this, 'cryptowoo_is_ready' ), 10, 3 );
			add_filter( 'cw_misconfig_notice', array( $this, 'cw_misconfig_notice' ), 10, 2 );

			// HD wallet management.
			add_filter( 'index_key_ids', array( $this, 'index_key_ids' ), 10, 1 );
			//add_filter( 'mpk_key_ids', array( $this, 'mpk_key_ids' ), 10, 1 );
			add_filter( 'get_mpk_data_mpk_key', array( $this, 'get_mpk_data_mpk_key' ), 10, 3 );
			add_filter( 'get_mpk_data_network', array( $this, 'get_mpk_data_network' ), 10, 3 );
			add_filter( 'cw_discovery_notice', array( $this, 'add_currency_to_array' ), 10, 1 );

			// Currency params.
			add_filter( 'cw_get_currency_params', array( $this, 'get_currency_params' ), 10, 2 );

			// Order sorting and prioritizing.
			add_filter( 'cw_sort_unpaid_addresses', array( $this, 'sort_unpaid_addresses' ), 10, 2 );
			add_filter( 'cw_prioritize_unpaid_addresses', array( $this, 'prioritize_unpaid_addresses' ), 10, 2 );
			add_filter( 'cw_filter_batch', array( $this, 'filter_batch' ), 10, 2 );

			// Exchange rates.
			add_filter( 'cw_force_update_exchange_rates', array( $this, 'force_update_exchange_rates' ), 10, 2 );
			add_filter( 'cw_cron_update_exchange_data', array( $this, 'cron_update_exchange_data' ), 10, 2 );

			// Wallet config.
			add_filter( 'wallet_config', array( $this, 'wallet_config' ), 10, 3 );
			add_filter( 'cw_get_processing_config', array( $this, 'processing_config' ), 10, 3 );

			// Options page.
			add_action( 'plugins_loaded', array( $this, 'add_fields' ), 10 );

			// Change currency icon color.
			add_action( 'wp_head', array( $this, 'coin_icon_color' ) );

			// Add to crypto store check.
			add_filter( 'is_cryptostore', array( $this, 'is_cryptostore' ), 10, 2 );

			// Override Kraken to have XETHXXBT instead of XETHZBTC as ticker.
			add_action( 'plugins_loaded', array( $this, 'override_exchanges' ), 10 );

			// get payment address.
			add_filter( "cw_create_payment_address_{$this->get_currency_code()}", array( $this, 'get_payment_address' ), 10, 3 );

			// Add address validation.
			add_filter( 'cw_validate_address_' . $this->get_currency_code(), array( $this, 'validate_address' ) );
		}

		/** Validate the ethereum address.
		 *
		 * @param string $address  Address to check.
		 *
		 * @return bool
		 */
		public function validate_address( $address ) {
			return true; // TODO: Proper validation.
		}

		/**
		 * Override Kraken to have XETHXXBT instead of XETHZBTC as ticker
		 */
		public function override_exchanges() {
			//include_once plugin_dir_path( __FILE__ ) . 'exchanges/class-cw-exchange-kraken-eth.php';
		}

		/**
		 * Get the plugin name
		 */
		private function get_plugin_name() : string {
			return "CryptoWoo {$this->get_currency_name()} Addon";
		}

		/** Get the currency short name (currency code lower cased)
		 *
		 * @return string
		 */
		private function get_currency_short_name() : string {
			return strtolower( $this->get_currency_code() );
		}

		/**
		 * Get the plugin name
		 */
		private function get_plugin_domain() : string {
			return "cryptowoo-{$this->get_currency_short_name()}-addon";
		}

		/** Get the processing api id for CryptoWoo option
		 *
		 * @return string
		 */
		private function get_processing_api_id() : string {
			return "processing_api_{$this->get_currency_short_name()}";
		}

		/** Get the processing api id for CryptoWoo option
		 *
		 * @return string
		 */
		private function get_custom_processing_api_id() : string {
			return "custom_api_{$this->get_currency_short_name()}";
		}

		/** Get the processing api id for CryptoWoo option
		 *
		 * @return string
		 */
		private function get_preferred_block_explorer_api_id() : string {
			return "preferred_block_explorer_{$this->get_currency_short_name()}";
		}

		/** Get the exchange api id for CryptoWoo option
		 *
		 * @return string
		 */
		private function get_preferred_exchange_api_id() : string {
			return "preferred_exchange_{$this->get_currency_short_name()}";
		}

		/** Get the processing api id for CryptoWoo option
		 *
		 * @return string
		 */
		private function get_custom_block_explorer_api_id() : string {
			return "custom_block_explorer_{$this->get_currency_short_name()}";
		}

		/** Get the processing fallback url id for CryptoWoo option
		 *
		 * @return string
		 */
		private function get_processing_fallback_url_id() : string {
			return "processing_fallback_url_{$this->get_currency_short_name()}";
		}

		/** Get the mpk id for CryptoWoo option.
		 *
		 * @return string
		 */
		private function get_mpk_id() : string {
			return "cryptowoo_{$this->get_currency_short_name()}_mpk";
		}

		/** Get the index id for CryptoWoo option.
		 *
		 * @return string
		 */
		private function get_index_id() : string {
			return "cryptowoo_{$this->get_currency_short_name()}_index";
		}

		/** Get the index id for CryptoWoo option.
		 *
		 * @return string
		 */
		private function get_multiplier_id() : string {
			return "multiplier_{$this->get_currency_short_name()}";
		}

		/** Get the exchanges list with key -> value.
		 *
		 * @return array
		 */
		private function get_exchanges_list_with_keys() : array {
			$exchanges_list_with_keys = array();
			$exchanges_list           = $this->get_exchanges_list();

			foreach ( $exchanges_list as $exchange_name ) {
				$exchanges_list_with_keys[ strtolower( $exchange_name ) ] = $exchange_name;
			}

			return $exchanges_list_with_keys;
		}

		/** Get the default exchange id.
		 *
		 * @return string
		 */
		private function get_default_exchange_id() : string {
			$exchanges_list = $this->get_exchanges_list();

			return strtolower( reset( $exchanges_list ) );
		}

		/**
		 * Override currency params in public master key validation
		 *
		 * @param array  $currency_params Currency parameters.
		 * @param string $field_id        Name of the master public key field.
		 *
		 * @return object
		 */
		public function get_currency_params( $currency_params, $field_id ) {
			if ( strcmp( $field_id, $this->get_mpk_id() ) === 0 ) {
				$currency_params            = new stdClass();
				$currency_params->currency  = $this->get_currency_code();
				$currency_params->index_key = $this->get_index_id();
			}

			return $currency_params;
		}

		/**
		 * Get BitWasp Network mapping
		 */
		public function get_bitwasp_network() {
			require_once 'bitwasp/class-eth.php';

			return new \BitWasp\Bitcoin\Network\Networks\ETH();
		}

		/**
		 * Font color for aw-cryptocoins
		 * see cryptowoo/assets/fonts/aw-cryptocoins/cryptocoins-colors.css
		 */
		public function coin_icon_color() {
			?>
			<style type="text/css">
				i.cc.<?php echo esc_attr( $this->get_currency_code() ); ?> {
					color: #3C3C3D;
				}
			</style>
			<?php
		}

		/** Add minimum confidence and "raw" zeroconf settings to processing config
		 *
		 * @param array  $pc_conf  Processing configuration.
		 * @param string $currency Currency code.
		 * @param array  $options  CryptoWoo options.
		 *
		 * @return array
		 */
		public function processing_config( $pc_conf, $currency, $options ) {
			if ( $this->get_currency_code() === $currency ) {
				$min_conf_id  = "cryptowoo_{$this->get_currency_short_name()}_min_conf";
				$zero_conf_id = "cryptowoo_{$this->get_currency_short_name()}_raw_zeroconf";
				// Maybe accept "raw" zeroconf.
				$pc_conf['min_confidence'] = isset( $options[ $min_conf_id ] ) && 0 === (int) $options[ $min_conf_id ] && isset( $options[ $zero_conf_id ] ) && (bool) $options[ $zero_conf_id ] ? 0 : $pc_conf['min_confidence'];
			}

			return $pc_conf;
		}

		/**
		 * Processing API configuration error
		 *
		 * @param array $enabled Array of enabled cryptocurrencies.
		 * @param array $options CryptoWoo options.
		 *
		 * @return mixed
		 */
		public function cw_misconfig_notice( $enabled, $options ) {
			$enabled[ $this->get_currency_code() ] = 'disabled' === $options[ $this->get_processing_api_id() ] && ( (bool) CW_Validate::check_if_unset( $this->get_mpk_id(), $options ) );

			return $enabled;
		}

		/**
		 * Add currency name
		 *
		 * @param array $currencies Array of Woocommerce currencies.
		 *
		 * @return mixed
		 */
		public function woocommerce_currencies( $currencies ) {
			$currencies[ $this->get_currency_code() ] = $this->get_currency_name();

			return $currencies;
		}


		/** Add currency symbol
		 *
		 * @param string $currency_symbol Currency symbol.
		 * @param string $currency Currency code.
		 *
		 * @return string
		 */
		public function get_currency_symbol( $currency_symbol, $currency ) {
			return $currency === $this->get_currency_code() ? $this->get_currency_code() : $currency_symbol;
		}


		/** Add coin identifier
		 *
		 * @param array $coin_identifiers currency codes.
		 *
		 * @return array
		 */
		public function add_coin_identifier( $coin_identifiers ) {
			$coin_identifiers[ $this->get_currency_code() ] = $this->get_currency_short_name();

			return $coin_identifiers;
		}


		/** Add address prefix
		 *
		 * @param array $prefixes Cryptocurrency address prefixes.
		 *
		 * @return array
		 */
		public function address_prefixes( $prefixes ) {
			$prefixes[ $this->get_currency_code() ]               = strtolower( $this->get_bitwasp_network()->getAddressByte() ); // P2PKH base58 prefix .
			$prefixes[ $this->get_currency_code() . '_MULTISIG' ] = strtolower( $this->get_bitwasp_network()->getP2shByte() ); // P2SH base58 prefix .

			return $prefixes;
		}


		/**
		 * Add wallet config
		 *
		 * @param array  $wallet_config Cryptocurrency wallet configuration.
		 * @param string $currency      Currency name.
		 * @param array  $options       CryptoWoo options.
		 *
		 * @return array
		 */
		public function wallet_config( $wallet_config, $currency, $options ) {
			if ( $this->get_currency_code() === $currency ) {
				$wallet_config                     = array(
					'coin_client'  => $this->get_currency_protocol_name(),
					'request_coin' => $this->get_currency_code(),
					'multiplier'   => (float) $options[ $this->get_multiplier_id() ],
					'safe_address' => false,
					'decimals'     => $this->get_default_decimals(),
				);
				$wallet_config['hdwallet']         = false; //CW_Validate::check_if_unset( $this->get_mpk_id(), $options, false );
				$wallet_config['coin_protocols'][] = $this->get_currency_protocol_name();
				$wallet_config['fwd_addr_key']     = false;
			}

			return $wallet_config;
		}

		/**
		 * Add Ethereum to enabled currencies array if it is enabled.
		 *
		 * @param string[] $coins Currencies.
		 * @param string[] $coin_identifiers Currency identifiers.
		 * @param array    $options CryptoWoo options.
		 *
		 * @return mixed
		 */
		public function coins_enabled_override( $coins, $coin_identifiers, $options ) {
			if ( is_array( $coin_identifiers ) && isset( $coin_identifiers[ $this->get_currency_code() ] ) ) {
				if ( CW_Validate::check_if_unset( $this->get_mpk_id(), $options ) ) {
					$coins[ $this->get_currency_code() ] = $this->get_currency_name();
				}
			}

			return $coins;
		}

		/** Get the next payment address using hd-wallet-derive.
		 *
		 * @param string   $payment_address Payment address.
		 * @param WC_Order $order Woocommerce order object.
		 * @param array    $options CryptoWoo options.
		 *
		 * @return mixed|string
		 */
		public function get_payment_address( $payment_address, $order, $options ) {
			return CW_Validate::check_if_unset( 'cryptowoo_eth_address', $options );

			/* TODO: Add hd derivation.
			require_once __DIR__ . '/includes/hd-wallet-derive/vendor/autoload.php';
			$wallet_derive = new \App\WalletDerive( [ 'coin'       => 'ETH',
													  'startindex' => (int) $options[ $this->get_index_id() ],
													  'numderive'  => 1,
													  'addr-type'  => 'auto'
			] );

			return $wallet_derive->derive_keys( 'xpub6CgbPpYZVrc87ByqtFcKYpSyQZVzCDMPAWUwauoBvyzpemptpnsccaw5YLzKd3o7e1EhjcedMpgyTTbesaxE4cAKM22orqDB33cyjYTdRwv' );*/
		}

		/** Override links to payment addresses
		 *
		 * @param string $url      URL.
		 * @param string $address  Crypto address.
		 * @param string $currency Currency code.
		 * @param array  $options  CryptoWoo options.
		 *
		 * @return string
		 */
		public function link_to_address( $url, $address, $currency, $options ) {
			if ( $this->get_currency_code() === $currency ) {
				$api_url = $options [ $this->get_preferred_block_explorer_api_id() ] ?: 'autoselect';

				if ( 'autoselect' === $api_url ) {
					$api_url = $options[ $this->get_processing_api_id() ];
				}

				$api_path = 'address';
				$url      = "http://$api_url/$api_path/$address"; // TODO: Change to https when accessible.
			}

			return $url;
		}

		/** Override genesis block
		 *
		 * @param string $genesis Genesis block id.
		 * @param string $field_id Processing api field.
		 *
		 * @return string
		 */
		public function validate_custom_api_genesis( $genesis, $field_id ) {
			if ( in_array( $field_id, array( $this->get_custom_processing_api_id(), $this->get_processing_fallback_url_id() ), true ) ) {
				$genesis = '0';
			}

			return $genesis;
		}


		/** Override custom API currency
		 *
		 * @param string $currency Currency code.
		 * @param string $field_id Processing API ID.
		 *
		 * @return string
		 */
		public function validate_custom_api_currency( $currency, $field_id ) {
			if ( in_array( $field_id, array( $this->get_custom_processing_api_id(), $this->get_processing_fallback_url_id() ), true ) ) {
				$currency = $this->get_currency_code();
			}

			return $currency;
		}


		/** Add currency to cryptowoo_is_ready
		 *
		 * @param array $enabled Currencies that are enabled.
		 * @param array $options CryptoWoo options.
		 * @param array $changed_values Changed values from transient.
		 *
		 * @return array
		 */
		public function cryptowoo_is_ready( $enabled, $options, $changed_values ) {
			$enabled[ "{$this->get_currency_code()}_mpk" ]           = (bool) CW_Validate::check_if_unset( $this->get_mpk_id(), $options, false );
			$enabled[ "{$this->get_currency_code()}_mpk_transient" ] = (bool) CW_Validate::check_if_unset( $this->get_mpk_id(), $changed_values, false );

			return $enabled;
		}


		/** Add currency to is_cryptostore check
		 *
		 * @param bool   $cryptostore If the Woocoommerce store currency is a cryptocurrency.
		 * @param string $woocommerce_currency Woocommerce store currency code.
		 *
		 * @return bool
		 */
		public function is_cryptostore( $cryptostore, $woocommerce_currency ) {
			return (bool) $cryptostore ?: $woocommerce_currency === $this->get_currency_code();
		}


		/** Add HD index key id for currency
		 *
		 * @param array $index_key_ids HD Wallet index key ids.
		 *
		 * @return array
		 */
		public function index_key_ids( $index_key_ids ) {
			$index_key_ids[ $this->get_currency_code() ] = $this->get_index_id();

			return $index_key_ids;
		}


		/** Add HD mpk key id for currency
		 *
		 * @param array $mpk_key_ids HD Wallet master public key ids.
		 *
		 * @return array
		 */
		public function mpk_key_ids( $mpk_key_ids ) {
			$mpk_key_ids[ $this->get_currency_code() ] = "cryptowoo_{$this->get_currency_code()}_address";

			/* TODO: Add hd derivation.
			$mpk_key_ids[ $this->get_currency_code() ] = $this->get_mpk_id();
			*/

			return $mpk_key_ids;
		}


		/** Override mpk_key
		 *
		 * @param string $mpk_key Master public key options id.
		 * @param string $currency Currency code.
		 * @param array  $options CryptoWoo options.
		 *
		 * @return string
		 */
		public function get_mpk_data_mpk_key( $mpk_key, $currency, $options ) {
			if ( $currency === $this->get_currency_code() ) {
				$mpk_key = $this->get_mpk_id();
			}

			return $mpk_key;
		}


		/** Override mpk_data->network
		 *
		 * @param stdClass $mpk_data Master public key data.
		 * @param string   $currency Currency code.
		 * @param array    $options CryptoWoo options.
		 *
		 * @return object
		 * @throws Exception BitWasp exception.
		 */
		public function get_mpk_data_network( $mpk_data, $currency, $options ) {
			if ( $currency === $this->get_currency_code() ) {
				require_once 'bitwasp/class-eth.php';
				require_once 'bitwasp/class-eth-network-factory.php';
				$mpk_data->network        = BitWasp\Bitcoin\Network\ETH_Network_Factory::ETH();
				$mpk_data->network_config = new \BitWasp\Bitcoin\Key\Deterministic\HdPrefix\NetworkConfig( $mpk_data->network, [
					$mpk_data->slip132->p2pkh( $mpk_data->bitcoinPrefixes ),
				] );
			}

			return $mpk_data;
		}

		/** Add currency force exchange rate update button
		 *
		 * @param array $results Exchange rates api result.
		 *
		 * @return array
		 */
		public function force_update_exchange_rates( $results ) {
			$results[ $this->get_currency_code() ] = CW_ExchangeRates::processing()->update_coin_rates( $this->get_currency_code(), false, true );

			return $results;
		}

		/** Add currency to background exchange rate update
		 *
		 * @param array $data Exchange rates api result data.
		 * @param array $options CryptoWoo options.
		 *
		 * @return array
		 */
		public function cron_update_exchange_data( $data, $options ) {
			$ethereum = CW_ExchangeRates::processing()->update_coin_rates( $this->get_currency_code(), $options );

			// Maybe log exchange rate updates.
			if ( (bool) $options['logging']['rates'] ) {
				if ( 'not updated' !== $ethereum['status'] || strpos( $ethereum['status'], 'disabled' ) ) {
					$data[ $this->get_currency_code() ] = strpos( $ethereum['status'], 'disabled' ) ? $ethereum['status'] : $ethereum['last_update'];
				} else {
					$data[ $this->get_currency_code() ] = $ethereum;
				}
			}

			return $data;
		}

		/** Add currency to currencies array
		 *
		 * @param string[] $currencies Currency codes.
		 *
		 * @return array
		 */
		public function add_currency_to_array( $currencies ) {
			$currencies[] = $this->get_currency_code();

			return $currencies;
		}

		/**
		 * Add addresses to sort unpaid addresses
		 *
		 * @param array    $top_n Sorting levels.
		 * @param stdClass $address Address data.
		 *
		 * @return array
		 */
		public function sort_unpaid_addresses( $top_n, $address ) {
			if ( strcmp( $address->payment_currency, $this->get_currency_code() ) === 0 ) {
				$top_n[3][ $this->get_currency_code() ][] = $address;
			}

			return $top_n;
		}

		/**
		 * Add addresses to prioritize unpaid addresses
		 *
		 * @param array    $top_n Sorting levels.
		 * @param stdClass $address Address data.
		 *
		 * @return array
		 */
		public function prioritize_unpaid_addresses( $top_n, $address ) {
			if ( strcmp( $address->payment_currency, $this->get_currency_code() ) === 0 ) {
				$top_n[3][] = $address;
			}

			return $top_n;
		}

		/**
		 * Add addresses to address_batch
		 *
		 * @param array    $address_batch Addresses for processing.
		 * @param stdClass $address Address data.
		 *
		 * @return array
		 */
		public function filter_batch( $address_batch, $address ) {
			if ( strcmp( $address->payment_currency, $this->get_currency_code() ) === 0 ) {
				$address_batch[ $this->get_currency_code() ][] = $address->address;
			}

			return $address_batch;
		}

		/**
		 * Add Redux options
		 */
		public function add_fields() {
			$woocommerce_currency = get_option( 'woocommerce_currency' );

			/** Payment processing section start */

			Redux::setField( 'cryptowoo_payments', array(
				'section_id' => 'wallets-address_list',
				'id'=>'address_list_eth',
				'type' => 'multi_text',
				'ajax_save' => true,
				'title' => sprintf(__('%s Addresses', 'cryptowoo'), 'Ethereum'),
				'validate_callback' => 'redux_validate_address_list',
				'subtitle' => sprintf(__('Current unused %1$s addresses: %2$s%3$s%4$s', 'cryptowoo'), 'Ethereum',
					CW_AddressList::get_address_list_details('ETH'), '<br>',
					CW_AddressList::get_delete_list_button('ETH')),
				'desc' => '',
				'hint' => array(
					'title' => 'Please Note:',
					'content' => __("Only add addresses for one currency at a time. Do not forget to click 'Save Changes' after you added the addresses.", 'cryptowoo'),
				)
			) );

			/*
			 * Required confirmations with explorer.ethereum.com.
			 */
			Redux::setField( 'cryptowoo_payments', array(
				'section_id' => 'processing-confirmations',
				'id'         => "cryptowoo_{$this->get_currency_short_name()}_min_conf",
				'type'       => 'spinner',
				'title'      => sprintf( __( '%s Minimum Confirmations', 'cryptowoo' ), $this->get_currency_code() ),
				'desc'       => sprintf( __( 'Minimum number of confirmations for <strong>%s</strong> transactions - %s Confirmation Threshold', 'cryptowoo' ), $this->get_currency_code(), $this->get_currency_code() ),
				'default'    => 1,
				'min'        => 0,
				'step'       => 1,
				'max'        => 100,
				'required'   => array(
					array( $this->get_processing_api_id(), 'equals', 'explorer.ethereum.com' ),
				),
			) );

			// Enable raw zeroconf.
			Redux::setField( 'cryptowoo_payments', array(
				'section_id' => 'processing-confirmations',
				'id'         => "cryptowoo_{$this->get_currency_short_name()}_raw_zeroconf",
				'type'       => 'switch',
				'title'      => $this->get_currency_code() . __( ' "Raw" Zeroconf', 'cryptowoo' ),
				'subtitle'   => __( 'Accept unconfirmed transactions as soon as they are seen on the network.', 'cryptowoo' ),
				'desc'       => sprintf( __( '%sThis practice is generally not recommended. Only enable this if you know what you are doing!%s', 'cryptowoo' ), '<strong>', '</strong>' ),
				'default'    => false,
				'required'   => array(
					array( "cryptowoo_{$this->get_currency_short_name()}_min_conf", '=', 0 ),
				),
			) );

			// Zeroconf order amount threshold.
			Redux::setField( 'cryptowoo_payments', array(
				'section_id' => 'processing-zeroconf',
				'id'         => "cryptowoo_max_unconfirmed_{$this->get_currency_short_name()}",
				'type'       => 'slider',
				'title'      => sprintf( __( '%s zeroconf threshold (%s)', 'cryptowoo' ), $this->get_currency_name(), $woocommerce_currency ),
				'desc'       => '',
				'required'   => array( "cryptowoo_{$this->get_currency_short_name()}_min_conf", '<', 1 ),
				'default'    => 100,
				'min'        => 0,
				'step'       => 10,
				'max'        => 500,
			) );

			Redux::setField( 'cryptowoo_payments', array(
				'section_id' => 'processing-zeroconf',
				'id'         => "cryptowoo_{$this->get_currency_short_name()}_zconf_notice",
				'type'       => 'info',
				'style'      => 'info',
				'notice'     => false,
				'required'   => array( "cryptowoo_{$this->get_currency_short_name()}_min_conf", '>', 0 ),
				'icon'       => 'fa fa-info-circle',
				'title'      => sprintf( __( '%s Zeroconf Threshold Disabled', 'cryptowoo' ), $this->get_currency_name() ),
				'desc'       => sprintf( __( 'This option is disabled because you do not accept unconfirmed %s payments.', 'cryptowoo' ), $this->get_currency_name() ),
			) );

			/*
			 * Processing API
			 */
			Redux::setField( 'cryptowoo_payments', array(
				'section_id'        => 'processing-api',
				'id'                => $this->get_processing_api_id(),
				'type'              => 'select',
				'title'             => sprintf( __( '%s Processing API', 'cryptowoo' ), $this->get_currency_name() ),
				'subtitle'          => sprintf( __( 'Choose the API provider you want to use to look up %s payments.', 'cryptowoo' ), $this->get_currency_name() ),
				'options'           => array(
					'blockcypher' => 'BlockCypher.com',
					'custom'                  => 'Custom (insight)',
					'disabled'                => 'Disabled',
				),
				'desc'              => '',
				'default'           => 'disabled',
				'ajax_save'         => false, // Force page load when this changes.
				'validate_callback' => 'redux_validate_processing_api',
				'select2'           => array( 'allowClear' => false ),
			) );


			/*
			 * Processing API custom URL warning
			 */
			Redux::setField( 'cryptowoo_payments', array(
				'section_id' => 'processing-api',
				'id'         => "{$this->get_processing_api_id()}_info",
				'type'       => 'info',
				'style'      => 'critical',
				'icon'       => 'el el-warning-sign',
				'required'   => array(
					array( $this->get_processing_api_id(), 'equals', 'custom' ),
					array( $this->get_custom_processing_api_id(), 'equals', '' ),
				),
				'desc'       => sprintf( __( 'Please enter a valid URL in the field below to use a custom %s processing API', 'cryptowoo' ), $this->get_currency_name() ),
			) );

			/*
			 * Custom processing API URL
			 */
			Redux::setField( 'cryptowoo_payments', array(
				'section_id'        => 'processing-api',
				'id'                => $this->get_custom_processing_api_id(),
				'type'              => 'text',
				'title'             => sprintf( __( '%s Insight API URL', 'cryptowoo' ), $this->get_currency_name() ),
				'subtitle'          => sprintf( __( 'Connect to any %sInsight API%s instance.', 'cryptowoo' ), '<a href="https://github.com/bitpay/insight-api/" title="Insight API" target="_blank">', '</a>' ),
				'desc'              => sprintf( __( 'The root URL of the API instance:%sLink to address:%sinsight.bitpay.com/ext/getaddress/%sRoot URL: %sinsight.bitpay.com%s', $this->get_plugin_domain() ), '<p>', '<code>', '</code><br>', '<code>', '</code></p>' ),
				'placeholder'       => 'explorer.ethereum.com',
				'required'          => array( $this->get_processing_api_id(), 'equals', 'custom' ),
				'validate_callback' => 'redux_validate_custom_api',
				'ajax_save'         => false,
				'msg'               => __( 'Invalid', 'cryptowoo' ) . " {$this->get_currency_code()} Insight API URL",
				'default'           => '',
				'text_hint'         => array(
					'title'   => 'Please Note:',
					'content' => __( 'Make sure the root URL of the API has a trailing slash ( / ).', 'cryptowoo' ),
				),
			) );

			// Re-add blockcypher token field (to make sure it is last).
			$field = Redux::getField( 'cryptowoo_payments', 'blockcypher_token' );
			Redux::removeField( 'cryptowoo_payments', 'blockcypher_token' );
			unset( $field['priority'] );
			Redux::setField( 'cryptowoo_payments', $field );

			// API Resource control information.
			Redux::setField( 'cryptowoo_payments', array(
				'section_id'        => 'processing-api-resources',
				'id'                => $this->get_processing_fallback_url_id(),
				'type'              => 'text',
				'title'             => sprintf( '%s ' . __( 'API Fallback', 'cryptowoo' ), $this->get_currency_code() ),
				'subtitle'          => sprintf( __( 'Fallback to any %sInsight API%s instance in case the explorer.ethereum.com API fails. Retry upon beginning of the next hour. Leave empty to disable.', 'cryptowoo' ), '<a href="https://github.com/bitpay/insight-api/" title="Insight API" target="_blank">', '</a>' ),
				'desc'              => sprintf( __( 'The root URL of the API instance:%sLink to address:%sinsight.bitpay.com/ext/getaddress/XtuVUju4Baaj7YXShQu4QbLLR7X2aw9Gc8%sRoot URL: %sinsight.bitpay.com%s', $this->get_plugin_domain() ), '<p>', '<code>', '</code><br>', '<code>', '</code></p>' ),
				'placeholder'       => 'explorer.ethereum.com',
				'required'          => array( $this->get_processing_api_id(), 'equals', 'blockcypher' ),
				'validate_callback' => 'redux_validate_custom_api',
				'ajax_save'         => false,
				'msg'               => __( 'Invalid', 'cryptowoo' ) . " {$this->get_currency_code()} Insight API URL",
				'default'           => 'explorer.ethereum.com',
				'text_hint'         => array(
					'title'   => 'Please Note:',
					'content' => __( 'Make sure the root URL of the API has a trailing slash ( / ).', 'cryptowoo' ),
				),
			) );

			/** Payment processing section end */


			/** Pricing section start */

			/*
			 * Preferred exchange rate provider
			 */
			Redux::setField( 'cryptowoo_payments', array(
				'section_id'        => 'rates-exchange',
				'id'                => $this->get_preferred_exchange_api_id(),
				'type'              => 'select',
				'title'             => "{$this->get_currency_name()} Exchange ({$this->get_currency_code()}/BTC)",
				'subtitle'          => sprintf( __( "Choose the exchange you prefer to use to calculate the %s{$this->get_currency_name()} to Bitcoin exchange rate%s", 'cryptowoo' ), '<strong>', '</strong>.' ),
				'desc'              => sprintf( __( 'Cross-calculated via BTC/%s', 'cryptowoo' ), $woocommerce_currency ),
				'options'           => $this->get_exchanges_list_with_keys(),
				'default'           => $this->get_default_exchange_id(),
				'ajax_save'         => false, // Force page load when this changes.
				'validate_callback' => 'redux_validate_exchange_api',
				'select2'           => array( 'allowClear' => false ),
			) );

			/*
			 * Exchange rate multiplier
			 */
			Redux::setField( 'cryptowoo_payments', array(
				'section_id'    => 'rates-multiplier',
				'id'            => $this->get_multiplier_id(),
				'type'          => 'slider',
				'title'         => sprintf( '%s ' . __( 'exchange rate multiplier', 'cryptowoo' ), $this->get_currency_name() ),
				'subtitle'      => sprintf( __( 'Extra multiplier to apply when calculating prices for', 'cryptowoo' ) . '%s.', $this->get_currency_code() ),
				'desc'          => '',
				'default'       => 1,
				'min'           => .01,
				'step'          => .01,
				'max'           => 2,
				'resolution'    => 0.01,
				'validate'      => 'comma_numeric',
				'display_value' => 'text',
			) );

			// Re-add discount notice (to make sure it is last).
			$field = Redux::getField( 'cryptowoo_payments', 'discount_notice' );
			Redux::removeField( 'cryptowoo_payments', 'discount_notice' );
			unset( $field['priority'] );
			Redux::setField( 'cryptowoo_payments', $field );

			/*
			 * Preferred blockexplorer
			 */
			Redux::setField( 'cryptowoo_payments', array(
				'section_id' => 'rewriting',
				'id'         => $this->get_preferred_block_explorer_api_id(),
				'type'       => 'select',
				'title'      => sprintf( '%s ' . __( 'Block Explorer', 'cryptowoo' ), $this->get_currency_name() ),
				'subtitle'   => __( 'Choose the block explorer you want to use for links to the blockchain.', 'cryptowoo' ),
				'desc'       => '',
				'options'    => array(
					'autoselect'                    => __( 'Autoselect by processing API', 'cryptowoo' ),
					'explorer.ethereum.com'           => 'explorer.ethereum.com',
					'custom'                        => __( 'Custom (enter URL below)' ),
				),
				'default'    => 'autoselect',
				'select2'    => array( 'allowClear' => false ),
			) );

			Redux::setField( 'cryptowoo_payments', array(
				'section_id' => 'rewriting',
				'id'         => "preferred_block_explorer_{$this->get_currency_short_name()}_info",
				'type'       => 'info',
				'style'      => 'critical',
				'icon'       => 'el el-warning-sign',
				'required'   => array(
					array( $this->get_preferred_block_explorer_api_id(), '=', 'custom' ),
					array( $this->get_custom_block_explorer_api_id(), '=', '' ),
				),
				'desc'       => sprintf( __( 'Please enter a valid URL in the field below to use a custom %s block explorer', 'cryptowoo' ), $this->get_currency_code() ),
			) );

			Redux::setField( 'cryptowoo_payments', array(
				'section_id'        => 'rewriting',
				'id'                => $this->get_custom_block_explorer_api_id(),
				'type'              => 'text',
				'title'             => sprintf( __( 'Custom %s Block Explorer URL', 'cryptowoo' ), $this->get_currency_name() ),
				'subtitle'          => __( 'Link to a block explorer of your choice.', 'cryptowoo' ),
				'desc'              => sprintf( __( 'The URL to the page that displays the information for a single address.%sPlease add %s{{ADDRESS}}%s as placeholder for the cryptocurrency address in the URL.%s', 'cryptowoo' ), '<br><strong>', '<code>', '</code>', '</strong>' ),
				'placeholder'       => 'explorer.ethereum.com/ext/getaddress/{$address}',
				'required'          => array( $this->get_preferred_block_explorer_api_id(), '=', 'custom' ),
				'validate_callback' => 'redux_validate_custom_blockexplorer',
				'ajax_save'         => false,
				'msg'               => __( 'Invalid custom block explorer URL', 'cryptowoo' ),
				'default'           => '',
			) );

			/** Pricing section end */


			/** Display settings section start */

			/*
			 * Currency Switcher plugin decimals
			 */
			Redux::setField( 'cryptowoo_payments', array(
				'section_id' => 'pricing-decimals',
				'id'         => "decimals_{$this->get_currency_code()}",
				'type'       => 'select',
				'title'      => sprintf( __( '%s amount decimals', 'cryptowoo' ), $this->get_currency_name() ),
				'subtitle'   => '',
				'desc'       => __( 'This option overrides the decimals option of the WooCommerce Currency Switcher plugin.', 'cryptowoo' ),
				'required'   => array( 'add_currencies_to_woocs', '=', true ),
				'options'    => array(
					2 => '2',
					4 => '4',
					6 => '6',
					8 => '8',
				),
				'default'    => $this->get_default_decimals(),
				'select2'    => array( 'allowClear' => false ),
			) );

			/** Display settings section end */

			/** HD wallet section start */

			Redux::setField( 'cryptowoo_payments', array(
				'section_id' => 'wallets-hdwallet',
				'id'         => "wallets-hdwallet-{$this->get_currency_short_name()}",
				'type'       => 'section',
				'title'      => $this->get_currency_name(),
				'icon'       => "cc-{$this->get_currency_code()}",
				'indent'     => true,
			) );

			/*
			 * Extended public key
			 */
			Redux::setField( 'cryptowoo_payments', array(
				'section_id'        => 'wallets-hdwallet',
				'id'                => $this->get_mpk_id(),
				'type'              => 'text',
				'ajax_save'         => false,
				'username'          => false,
				'title'             => sprintf( __( '%sprefix%s', 'cryptowoo-hd-wallet-addon' ), '<b>' . $this->get_currency_name() . ' "02..." ', '</b>' ),
				'desc'              => "{$this->get_currency_name()} HD Wallet Extended Public Key (02...)",
				'validate_callback' => 'redux_validate_mpk',
				'placeholder'       => '02...',
				'text_hint'         => array(
					'title'   => 'Please Note:',
					'content' => sprintf( __( 'If you enter a used key you will have to run the address discovery process after saving this setting.%sUse a dedicated HD wallet (or at least a dedicated key) for your store payments to prevent address reuse.', 'cryptowoo-hd-wallet-addon' ), '<br>' ),
				),
			) );

			Redux::setField( 'cryptowoo_payments', array(
				'section_id'        => 'wallets-hdwallet',
				'id'                => "derivation_path_{$this->get_currency_short_name()}",
				'type'              => 'select',
				'subtitle'          => '',
				'title'             => sprintf( __( '%s Derivation Path', 'cryptowoo-hd-wallet-addon' ), $this->get_currency_code() ),
				'desc'              => __( 'Change the derivation path to match the derivation path of your wallet client.', 'cryptowoo-hd-wallet-addon' ),
				'validate_callback' => 'redux_validate_derivation_path',
				'options'           => array(
					'44/60/' => __( 'm/44/60/i (e.g. Electrum Standard Wallet)', 'cryptowoo-hd-wallet-addon' ),
					'm'  => __( 'm/i (BIP44 Account)', 'cryptowoo-hd-wallet-addon' ),
				),
				'default'           => '0/',
				'select2'           => array( 'allowClear' => false ),
			) );

			// Re-add Bitcoin testnet section (to make sure it is last).
			$section = Redux::getField( 'cryptowoo_payments', 'wallets-hdwallet-testnet' );
			$field1  = Redux::getField( 'cryptowoo_payments', 'cryptowoo_btc_test_mpk' );
			$field2  = Redux::getField( 'cryptowoo_payments', 'derivation_path_btctest' );
			unset( $section['priority'] );
			unset( $field1['priority'] );
			unset( $field2['priority'] );
			Redux::removeField( 'cryptowoo_payments', 'wallets-hdwallet-testnet' );
			Redux::removeField( 'cryptowoo_payments', 'wallets-cryptowoo_btc_test_mpk-testnet' );
			Redux::removeField( 'cryptowoo_payments', 'wallets-hdwallet-derivation_path_btctest' );
			Redux::setField( 'cryptowoo_payments', $section );
			Redux::setField( 'cryptowoo_payments', $field1 );
			Redux::setField( 'cryptowoo_payments', $field2 );

			Redux::setField( 'cryptowoo_payments', array(
				'section_id'        => 'wallets-other',
				'id'                => "cryptowoo_{$this->get_currency_code()}_address",
				'type'              => 'text',
				'title'             => sprintf( __( '%sprefix%s', $this->get_plugin_domain() ), '<b>' . $this->get_currency_code(). ' "0x.."', '</b>' ),
				'desc'              => __( "{$this->get_currency_name()} ({$this->get_currency_code()}) address", $this->get_plugin_domain() ),
				'validate_callback' => '',
				'placeholder'       => '0x..',
			) );

			/** HD wallet section end */
		}
	}

	new CW_Ethereum_Addon();
}
