<?php

/**
 * FXUP_Template
 * loads the proper templates based on data
 *
 * @category Core
 * @author WebFX
 */
if ( ! defined( 'ABSPATH' ) )
	exit;

class FXUP_Template
{
	protected static $instance;
	public static $add_scripts = true;

	/**
	 * Initializes variables and sets up WordPress hooks/actions.
	 *
	 * @return void
	 */
	protected function __construct()
	{
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
		add_action( 'wp_head', array( $this, 'add_hotjar_tracking' ) );
		add_action( 'wp_footer', array( $this, 'add_chat_script' ) );
		add_action( 'send_headers', 'send_frame_options_header', 10, 0 );
	}

	/* Static Singleton Factory Method */

	public static function instance()
	{
		if ( ! isset( self::$instance ) ) {
			$className = __CLASS__;
			self::$instance = new $className;
		}
		return self::$instance;
	}

	public function register_scripts()
	{
		$version = filemtime( FXUP_USER_PORTAL()->plugin_path() . '/assets/js/user-portal.js' );
		
		wp_enqueue_style(
			'shepherd'
			, '//cdn.jsdelivr.net/npm/shepherd.js@10.0.1/dist/css/shepherd.css'
		);
		
		wp_enqueue_script(
			'fxup-tourconfig'
			, self::get_asset( 'fxup-tourconfig.js' )
			, array()
			, $version
			, true
		);
		
		wp_enqueue_script(
			'shepherd'
			, '//cdn.jsdelivr.net/npm/shepherd.js@10.0.1/dist/js/shepherd.min.js'
			, array( 'jquery', 'fxup-tourconfig' )
			, $version
			, true
		);

		wp_register_script(
			'user-portal'
			, self::get_asset( 'user-portal.js' )
			, array()
			, $version
			, true
		);

		wp_register_script(
			'exit-intent'
			, self::get_asset( 'vendor/jquery.exitintent.min.js' )
			, array()
			, false
			, true
		);

		if ( is_singular( 'itinerary' ) || is_page( 976 ) || is_page( 994 ) || is_page( 791 ) || is_page( 1162 ) || is_page( 1195 ) || is_page( 1305 ) || is_page( 1469 ) || is_page( 'transportation' ) || is_page( 'grocery-list' ) ) {
			wp_enqueue_script( 'exit-intent' );

			if ( is_page( 'transportation' ) ) {
				$token = isset( $_GET['itin'] ) ? sanitize_text_field( $_GET['itin'] ) : false;
				$Itinerary = \FXUP_User_Portal\Models\Itinerary::fromToken( $token );
				$data = array(
					'transportationSummaryLink' => $Itinerary->getTransportationSummaryLink(),
				);
				wp_localize_script( 'user-portal', 'fxupData', $data );
			}
			if ( is_page( 'grocery-list' ) ) {
				$data = \FXUP_User_Portal\Core\Gravity_Forms::get_list_data();
				wp_localize_script( 'user-portal', 'fxupData', $data );
			}
			if ( is_page( 'edit-guest-travel' ) ) {
				$token = isset( $_GET['itin'] ) ? sanitize_text_field( $_GET['itin'] ) : false;
				$Itinerary = \FXUP_User_Portal\Models\Itinerary::fromToken( $token );
				$data = array(
					'tripStart' => $Itinerary->getTripStartDate(),
					'tripEnd' => $Itinerary->getTripEndDate(),
				);
				wp_localize_script( 'user-portal', 'fxupData', $data );
			}
			if ( is_page( 'edit-guest-list' ) ) {
				$token = isset( $_GET['itin'] ) ? sanitize_text_field( $_GET['itin'] ) : false;
				$Itinerary = \FXUP_User_Portal\Models\Itinerary::fromToken( $token );
//				if ( $Itinerary->getGuests() ) {
//					$guest_data = array_map( function() {
//						
//					});
//				}
				$data = array(
					'guests' => $Itinerary->getGuestsForJson(),
				);
				wp_localize_script( 'user-portal', 'fxupData', $data );
			}
		}
		wp_enqueue_script( 'user-portal' );
	}

	public static function get_asset( $asset )
	{
		// figure out which folder to look in
		$folder = ( substr( $asset, -3 ) == '.js' ) ? 'js/' : 'css/';

		$file = FXUP_USER_PORTAL()->plugin_url() . '/assets/' . $folder . $asset;

		return $file;
	}

	public function add_hotjar_tracking()
	{
		?>
<!-- Hotjar Tracking Code for Client Login Site -->
<script>
    (function(h,o,t,j,a,r){
        h.hj=h.hj||function(){(h.hj.q=h.hj.q||[]).push(arguments)};
        h._hjSettings={hjid:3564860,hjsv:6};
        a=o.getElementsByTagName('head')[0];
        r=o.createElement('script');r.async=1;
        r.src=t+h._hjSettings.hjid+j+h._hjSettings.hjsv;
        a.appendChild(r);
    })(window,document,'https://static.hotjar.com/c/hotjar-','.js?sv=');
</script>
		<?php
	}
	
	public function add_chat_script()
	{
		if ( ! is_user_logged_in() ) {
			return;
		}
		
		$current_user = wp_get_current_user();
		?>
<div style="width: 0; height: 0;" id="VG_OVERLAY_CONTAINER"></div>
<style>
	.vg-render-container * {min-width:auto;min-height: auto;}
	.vg-render-container input {padding: 0 !important;border: none !important;}
</style>
<script defer>
    (function() {
        window.VG_CONFIG = {
            ID: "u1z75ckhrghnq1d",
            region: 'na',
            render: 'bottom-right',
            stylesheets: [
                "https://vg-bunny-cdn.b-cdn.net/vg_live_build/styles.css",
            ],
			user: {
				name: '<?php echo $current_user->display_name; ?>',
				email: '<?php echo $current_user->user_email; ?>',
				userID: '<?php echo $current_user->ID; ?>'
			},
			userID: '<?php echo $current_user->ID; ?>'
        }
        var VG_SCRIPT = document.createElement("script");
        VG_SCRIPT.src = "https://vg-bunny-cdn.b-cdn.net/vg_live_build/vg_bundle.js";
        VG_SCRIPT.defer = true;
        document.body.appendChild(VG_SCRIPT);
    })()
</script>
<?php
	}
}

FXUP_Template::instance();
