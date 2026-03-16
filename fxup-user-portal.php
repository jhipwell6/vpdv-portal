<?php
/*
Plugin Name: FXUP_USER_PORTAL
Version: 0.3
Description: Custom User Portal for user/itinerary interaction
Author: WebFX
Author URI: https://webfx.com
Plugin URI: https://webfx.com
Text Domain: up-user-portal
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'FXUP_USER_PORTAL' ) ) :

final class FXUP_USER_PORTAL
{
    /**
     * @var string
     */
    public $version = '0.3';
	
	/**
     * Service for processing emails
     * @var null
     */
    private $email_service = null;

    /**
     * @var FXUP_USER_PORTAL The single instance of the class
     * @since 0.1
     */
    protected static $_instance = null;

    public function debug_log()
    {
        $log_location = $this->plugin_path() . '/fx-debug.log';
        $datetime = new DateTime('NOW');
        $timestamp = $datetime->format('Y-m-d H:i:s');
        $args = func_get_args();
        $formatted = array_map(function($item) {return print_r($item, true);}, $args);
        array_unshift($formatted, $timestamp);
        $joined = implode(' ', $formatted) . "\n";
        error_log($joined, 3, $log_location);
    }

   /**
     * Main Instance
     *
     * Ensures only one instance of Memory Book is loaded or can be loaded.
     *
     * @since 0.1
     * @static
     * @return FXUP_USER_PORTAL - Main instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

   /**
     * Constructor
     */
    public function __construct()
    {
    	$this->define_constants();
    	
    	// require autoload class here if exists
    	
        /**
         * Listen for activation
         */
        register_activation_hook( __FILE__, array( $this, 'activate' ) );

        /**
         * Once plugins are loaded, initialize
         */
        add_action('plugins_loaded', array( $this, 'setup' ), -10 );
    }
	
	/**
	 * Setup needed includes and actions  for plugin
	 * @hooked plugins_loaded
	 */
    public function setup()
    {
        $this->includes();
        $this->init_hooks();
        $this->init_services();
    }

	/**
	 * Function fired immediatly after plugin is activated
	 * initializes plugins install "actions"
	 */
    public function activate()
    {
        PLUGIN_INSTALL_CLASS::install();
    }

    /**
     * Define WC Constants
     */
    private function define_constants()
    {
        $upload_dir = wp_upload_dir();
        $this->define( 'PREFIX_PLUGIN_FILE', __FILE__ );
        $this->define( 'PREFIX_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
        $this->define( 'PREFIX_VERSION', $this->version );
        $this->define( 'FXUP_USER_PORTAL_VERSION', $this->version );
    }

    /**
     * Define constant if not already set
     * @param  string $name
     * @param  string|bool $value
     */
    private function define( $name, $value )
    {
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    }

    /**
     * What type of request is this?
     * string $type ajax, frontend or admin
     * @return bool
     */
    public function is_request( $type )
    {
        switch ( $type ) {
            case 'admin' :
                return is_admin();
            case 'ajax' :
                return defined( 'DOING_AJAX' );
            case 'cron' :
                return defined( 'DOING_CRON' );
            case 'frontend' :
                return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
        }
    }
	
    /**
     * Include required core files used in admin and on the frontend.
     */
    public function includes()
    {
        // Traits
		include_once $this->plugin_path() . '/includes/core/traits/sends-emails.php';
        // Models
        // FXUP_User_Portal\Models\Itinerary
        include_once $this->plugin_path() . '/includes/models/Itinerary.php';
        // FXUP_User_Portal\Models\Villa
        include_once $this->plugin_path() . '/includes/models/Villa.php';
        // FXUP_User_Portal\Models\TripDay
        include_once $this->plugin_path() . '/includes/models/TripDay.php';
        // FXUP_User_Portal\Models\Activity
        include_once $this->plugin_path() . '/includes/models/Activity.php';
        // FXUP_User_Portal\Models\Guest
        include_once $this->plugin_path() . '/includes/models/Guest.php';
        // FXUP_User_Portal\Models\Room
        include_once $this->plugin_path() . '/includes/models/Room.php';
		// FXUP_User_Portal\Models\Transport
        include_once $this->plugin_path() . '/includes/models/Transport.php';
		// FXUP_User_Portal\Models\Email
        include_once $this->plugin_path() . '/includes/models/Email.php';
		// FXUP_User_Portal\Models\EmailDigest
        include_once $this->plugin_path() . '/includes/models/EmailDigest.php';
		// FXUP_User_Portal\Migrations\ChildGuestMigration
		include_once $this->plugin_path() . '/includes/migrations/ChildGuestMigration.php';
		
		// Helpers
		include_once $this->plugin_path() . '/includes/helpers/form-handler.php';
        
        // IMPORTANT - Controllers must be included after Models.
        // This is because cron actions hooked/defined in Controllers will fire as soon as the Controller is included and the add_action() with cron hook name is called.
        // If the Controller uses a Model in the Cron action callback, that Model will NOT have been included yet.
        // Controllers
        include_once $this->plugin_path() . '/includes/controllers/fxup-itinerary.php';
        include_once $this->plugin_path() . '/includes/controllers/fxup-account-registration.php';
        include_once $this->plugin_path() . '/includes/controllers/fxup-notifications.php';
        include_once $this->plugin_path() . '/includes/controllers/fxup-importer.php';
        include_once $this->plugin_path() . '/includes/controllers/fxup-admin.php';

        // Helpers
        include_once $this->plugin_path() . '/includes/helpers/fxup-template-helpers.php';
        include_once $this->plugin_path() . '/includes/helpers/fxup-itinerary-change-logger.php';

        // Template
        include_once $this->plugin_path() . '/includes/fxup-template.php';

        // Core
        include_once $this->plugin_path() . '/includes/core/acf-fields.php';
		include_once $this->plugin_path() . '/includes/core/gravity-forms.php';
		include_once $this->plugin_path() . '/includes/core/action-queue.php';
		include_once $this->plugin_path() . '/includes/core/email-service.php';
		include_once $this->plugin_path() . '/includes/core/wp-cli.php';
		
		// Libraries
		include_once $this->plugin_path() . '/vendor/autoload.php';
		include_once $this->plugin_path() . '/libraries/action-scheduler/action-scheduler.php';
    }
	
	/**
      * registers hooks to listen for during plugins_loaded
      */
	public function init_hooks()
	{
	}
	
	/**
      * Create services for querying and building new model instances
      */
	public function init_services()
	{
		$this->email_service = new \FXUP_User_Portal\Core\Email_Service;
		$this->email_service->register_actions();
		\FXUP_User_Portal\Core\Email_Service::install();
	}
	
	public function email( $Itinerary )
    {
		return $this->email_service->init( $Itinerary );
    }
	
	/**
	 * Get queue instance.
	 *
	 * @return Action_Queue
	 */
	public function queue() {
		return \FXUP_User_Portal\Core\Action_Queue::instance();
	}
	
    /**
     * Get the plugin url.
     * @return string
     */
    public function plugin_url() 
    {
        return untrailingslashit( plugins_url( '/', __FILE__ ) );
    }

    /**
     * Get the plugin path.
     * @return string
     */
    public function plugin_path() 
    {
        return untrailingslashit( plugin_dir_path( __FILE__ ) );
    }

    /**
     * Get Ajax URL.
     * @return string
     */
    public function ajax_url() 
    {
        return admin_url( 'admin-ajax.php', 'relative' );
    }
	
	/**
     * print array information to view
     * @param  string|array $log [description]
     * @return void
     */
    public function debug( $log )
	{
		if ( is_array( $log ) || is_object( $log ) ) {
			echo '<pre>'; print_r( $log ); echo '</pre>';
		} else {
			echo $log;
		}
    }
}

endif;


/**
 * Returns the main instance of PLUGIN to prevent the need to use globals.
 *
 * @since  0.1
 * @return FXUP_USER_PORTAL
 */
function FXUP_USER_PORTAL() {
    return FXUP_USER_PORTAL::instance();
}

FXUP_USER_PORTAL();