<?php
namespace FXUP_User_Portal\Core;

class Email_Service
{
	use Traits\Sends_Emails {
		Traits\Sends_Emails::__construct as sends_emails;
	}
	
	private $Itinerary;
	private static $ItineraryClass = 'FXUP_User_Portal\Models\Itinerary';
	private static $templates;
	protected $emails;
	
//	private static $db_exists = false;
	
	public function __construct()
	{
		$this->sends_emails();
		// allow chaining
		return $this;
	}
	
	public function init( $Itinerary )
	{
		$this->setItinerary( $Itinerary );
		$this->define_templates();
		return $this;
	}
	
	public function register_actions()
	{
		add_action( 'init', array( $this, 'set_digest_schedule' ) );
		add_action( 'fxup/process_email_queue', array( $this, 'send' ) );
	}
	
	public function define_templates()
	{
		if ( self::$templates === null ) {
			$email_types = $this->get_email_type_defaults();
			foreach ( $email_types as $type => $data ) {
				self::$templates[ $type ] = FXUP_USER_PORTAL()->plugin_path() . '/includes/views/emails/' . $type . '.php';
			}
		}
	}
	
	public function send( $Email = false )
	{
		// single (non-digest) emails don't need to be saved; just send them
		if ( ! boolval( $Email ) || $this->is_digest( $Email ) ) {
			$this->send_digest();
		} else {
			return $this->send_single( $Email );
		}
	}
	
	private function send_single( $Email )
	{
		if ( ! $this->getItinerary() )
			return false;
		
		if ( $this->getItinerary()->getDisableAllNotifications() && ! $Email->get_is_concierge() )
			return false;
		
		$Email->set_message( $this->generate_message( $Email ) );
		return $Email->send();
	}
	
	private function send_digest()
	{
		$this->define_templates();
		$emails = $this->get_unsent();
		if ( ! empty( $emails ) ) {
			
			// get all the digest types
			foreach ( $this->get_email_type_defaults() as $type => $data ) {
				if ( ! $data['is_digest'] )
					continue;
				
				// group emails by digest type
				$digest = $this->filter( $emails, array( 'type' => $type ) );
				$EmailDigest = new \FXUP_User_Portal\Models\EmailDigest( $digest, $type, $data );
				$EmailDigest->set_message( $this->generate_message( $EmailDigest ) );
				
				if ( ! $EmailDigest->get_to() || $EmailDigest->get_to() == '' ) {
					$recipients = wp_list_pluck( $digest, 'to' );
					if ( ! empty( $recipients ) ) {
						$unique_recipients = array_unique( $recipients );
						
						foreach ( $unique_recipients as $recipient ) {
							$recipient_digest = $this->filter( $digest, array( 'to' => $recipient ) );
							$RecipientDigest = new \FXUP_User_Portal\Models\EmailDigest( $recipient_digest, $type, $data );
							$RecipientDigest->set_to( $recipient );
							$RecipientDigest->send();
						}
					}
				} else {
					$EmailDigest->send();
				}
			}
		}
	}
	
	public function set_digest_schedule()
	{
		if ( ! FXUP_USER_PORTAL()->queue()->get_next('fxup/process_email_queue') ) {
			FXUP_USER_PORTAL()->queue()->schedule_recurring( strtotime( 'tomorrow 3 am' ), DAY_IN_SECONDS, 'fxup/process_email_queue', array(), 'fxup-process-email-queue' );
		}
	}
	
	public function get( $Email = false )
	{
		$Email = $this->get_object( $Email );

		// bail if no item exists
        if ( ! $Email ) {
            return false;
        }
		
		return $Email;
	}
	
	public function filter( $emails, $args = array() )
	{		
		$filtered_emails = array_filter( $emails, function( $Email ) use ( $args ) {
			$prop = key( $args );
			return $Email->where( $prop, $args[ $prop ] );
		} );
		
		return ! empty( $filtered_emails ) ? $filtered_emails : false;
	}
	
	public function create( $args = array() )
	{		
		$Email = $this->get();
		if ( ! empty( $args ) ) {
			$Email->set_props( $this->get_email_data( $args ) );
		}
		
		if ( $this->is_digest( $Email ) ) {
			if ( $this->getItinerary()->getDisableAllNotifications() && ! $Email->get_is_concierge() ) {
				return $Email;
			}
			
			$Email = $Email->create();
		}
		
		$this->emails[] = $Email;
		
		return $Email;
	}
	
	public function get_all()
	{
		if ( null === $this->emails ) {
			global $wpdb;
			$db_table = $wpdb->prefix . 'fx_emails';
			$key = 'itinerary_id';
			$value = $this->getItinerary()->getPostID();
			$sql = $wpdb->prepare( "SELECT id FROM {$db_table} WHERE {$key} = %s", $value );
			
			// query emails
			$this->emails = $this->query( $sql );
		}
		
		return $this->emails;
	}
	
	public function get_unsent( $by_type = '', $by_to_email = '' )
	{
		global $wpdb;
		$db_table = $wpdb->prefix . 'fx_emails';
		$sql = "SELECT id FROM {$db_table} WHERE is_sent = 0";
		if ( $by_type !== '' ) {
			$sql .= " AND type = {$by_type}";
		}
		if ( $by_to_email !== '' ) {
			$sql .= " AND to = {$by_to_email}";
		}
		
		// query emails
		return $this->query( $sql );
	}
	
	private function query( $sql )
	{
		global $wpdb;
		$emails = array();
		
		// query emails
		$results = $wpdb->get_results( $sql, ARRAY_A );
		// loop through and use $this->get to set Email objects
		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				$emails[] = $this->get( $result['id'] );
			}
		}
		
		return $emails;
	}
	
	private function get_object( $Email )
    {
		$email_id = $this->get_email_id( $Email );
		$Email = new \FXUP_User_Portal\Models\Email( $email_id );
		if ( ! $Email->get_itinerary_id() && $this->getItinerary() ) {
			$Email->set_itinerary_id( $this->getItinerary()->getPostID() );
		}
		
        return $Email;
    }
	
	/**
	 * Get the post ID depending on what was passed.
	 *
	 * @return int|bool false on failure
	 */
	private function get_email_id( $Email )
	{
		if ( is_numeric( $Email ) ) {
			return $Email;
		} elseif ( $Email instanceof \FXUP_User_Portal\Models\Email ) {
			return $Email->get_id();
		} else {
			return false;
		}
	}
	
	private function is_digest( $Email )
	{
		if ( ! boolval( $Email ) )
			return false;
		
		$data = $this->get_email_type_defaults( $Email->get_type() );
		return (bool) $data['is_digest'];
	}
	
	private function generate_message( $Email )
	{
		extract( $Email->get_data() );
		ob_start();
        include $this->get_template( $Email->get_type() );
        return ob_get_clean();
	}
	
	public function get_template( $type )
	{
		return self::$templates[ $type ];
	}
	
	private function get_email_data( $args = array() )
	{
		$defaults = $this->get_email_type_defaults( $args['type'] );
		return wp_parse_args( $args, $defaults );
	}
	
	private function get_email_type_defaults( $type = null )
	{
		// allow overriding
		$data = array(
			'email-concierge-digest-report' => array(
				'to' => $this->email_concierge_notifications_to,
				'subject' => $this->email_concierge_digest_report_subject,
				'is_digest' => true,
				'is_concierge' => true,
			),
			'email-concierge-newly-confirmed-activities' => array(
				'to' => $this->email_concierge_notifications_to,
				'subject' => $this->email_concierge_newly_confirmed_activities_subject,
				'is_digest' => false,
				'is_concierge' => true,
			),
			'email-digest-concierge-newly-confirmed-activities' => array(
				'to' => $this->email_concierge_notifications_to,
				'subject' => $this->email_concierge_newly_confirmed_activities_subject,
				'is_digest' => true,
				'is_concierge' => true,
			),
			'email-concierge-guest-travel-arrangements-submitted' => array(
				'to' => $this->email_concierge_notifications_to,
				'subject' => $this->email_concierge_guest_travel_arrangements_submitted_subject,
				'is_digest' => true,
				'is_concierge' => true,
			),
			'email-concierge-guest-removed' => array( // deprecated (replaced with digest)
				'to' => $this->email_concierge_notifications_to,
				'is_digest' => false,
				'is_concierge' => true,
			),
			'email-digest-concierge-guest-added' => array( // done
				'to' => $this->email_concierge_notifications_to,
				'subject' => 'Guests Added Digest Report',
				'is_digest' => true,
				'is_concierge' => true,
			),
			'email-digest-concierge-guest-removed' => array( // done
				'to' => $this->email_concierge_notifications_to,
				'subject' => 'Guests Removed Digest Report',
				'is_digest' => true,
				'is_concierge' => true,
			),
			'email-concierge-deadline-check' => array( // todo (not critical)
				'to' => $this->email_concierge_notifications_to . ',' . $this->email_on_site_staff_notifications_to,
				'subject' => $this->email_concierge_deadline_check_subject,
				'is_digest' => false,
				'is_concierge' => true,
			),
			'email-account-creation-reminder' => array( // done
				'to' => '', // client
				'subject' => $this->email_account_creation_reminder_subject,
				'is_digest' => false,
				'is_concierge' => false,
			),
			'email-client-itinerary-deadline-reminder' => array( // done
				'to' => '', // client
				'subject' => $this->email_client_itinerary_deadline_reminder_subject,
				'is_digest' => false,
				'is_concierge' => false,
			),
			'email-client-itinerary-submitted' => array(  // done
				'to' => '', // client
				'subject' => $this->email_client_itinerary_submitted_subject,
				'is_digest' => false,
				'is_concierge' => false,
			),
			'email-client-concierge-update' => array( // done
				'to' => '', // client
				'subject' => $this->email_client_itinerary_updated_subject,
				'is_digest' => false,
				'is_concierge' => false,
			),
			'email-client-concierge-update-digest' => array( // done
				'to' => '', // client
				'subject' => $this->email_client_itinerary_updated_subject,
				'is_digest' => true,
				'is_concierge' => false,
			),
			'email-guest-travel-deadline-reminder' => array( // done
				'to' => '', // guests
				'subject' => $this->email_guest_travel_deadline_reminder_subject,
				'is_digest' => false,
				'is_concierge' => false,
			),
			'email-concierge-summary-links' => array( // done
				'to' => $this->email_summary_notifications_to,
				'subject' => 'Villa Punto de Vista - Upcoming Itinerary Summaries',
				'is_digest' => false,
				'is_concierge' => true,
			),
		);
		
		if ( $type !== null && array_key_exists( $type, $data ) ) {
			return $data[ $type ];
		}
		
		return $data;
	}
	
	public function setItinerary( $Itinerary )
	{
        if ( $Itinerary instanceof self::$ItineraryClass ) {
            $this->Itinerary = $Itinerary;
		} elseif ( is_numeric( $Itinerary ) && $Itinerary > 0 ) {
			$this->Itinerary = new \FXUP_User_Portal\Models\Itinerary( $Itinerary );
        } else {
            $this->Itinerary = false;
        }
        return $this->Itinerary;
    }

    public function getItinerary()
	{
        if ( null === $this->Itinerary ) {
            $this->setItinerary( false );
        }
        return $this->Itinerary;
    }
	
	public static function install()
	{
		$mu_installed = get_option('fx_emails_installed');
		
        if ( ! $mu_installed ) {
            self::create_db_table();
            update_option( 'fx_emails_installed', true );
        }
	}
	
	private static function create_db_table()
	{
		global $wpdb;
		$db_table = $wpdb->prefix . 'fx_emails';
		
		$sql = "CREATE TABLE `$db_table` (
			`id` INT NOT NULL AUTO_INCREMENT,
			`itinerary_id` INT DEFAULT NULL,
			`to` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
			`type` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
			`data` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
			`is_sent` BOOLEAN NOT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB;";
		
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
}