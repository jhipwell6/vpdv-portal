<?php 

namespace FXUP_User_Portal\Controllers;

class FXUP_Account_Registration
{
	
	private $default_pass;
	
    protected static $instance;

    /**
     * Initializes plugin variables and sets up WordPress hooks/actions.
     *
     * @return void
     */

    protected function __construct()
    {
		$this->default_pass = $this->generate_strong_password();
		
		add_filter( 'um_registration_for_loggedin_users', '__return_true' );
        add_action( 'um_after_save_registration_details', array( $this, 'create_user_itinerary' ), 10, 2 );
        add_filter( 'um_submit_form_error', array( $this, 'um_login_custom_error' ), 10, 2 );
        add_action( 'um_submit_form_errors_hook_', array( $this, 'um_custom_form_validation' ), 999, 1 );
        add_action( 'um_submit_form_errors_hook_logincheck', array( $this, 'um_custom_form_validation' ), 999, 1 );
        // add_action( 'um_user_register', array($this, 'user_registration_check' ));
        // add_action( 'register_new_user', array( $this, 'user_registration_check' ));
        // We are exiting in the middle of Ultimate Member's registration process - before it can log the user in.
        add_action( 'um_post_registration_approved_hook', array( $this, 'user_registration_check' ));
        // add_action( 'um_before_save_registration_details', array( $this, 'fx_before_save_registration_details'), 10, 2 );

//		add_filter( 'um_user_password_form_edit_field', array( $this, 'um_remove_password_field' ), 1000, 2 );
//		add_filter( 'um_confirm_user_password_form_edit_field', array( $this, 'um_remove_password_field' ), 1000, 2 );
		add_filter( 'um_edit_user_password_field_value', array( $this, 'set_user_password_for_concierge' ), 10, 2 );
		add_filter( 'um_edit_confirm_user_password_field_value', array( $this, 'set_user_password_for_concierge' ), 10, 2 );
		
		add_filter( 'gettext_ultimate-member', array( $this, 'set_reset_password_text' ), 10, 3 );
    }
	
	public function um_remove_password_field( $output, $mode ) {

		$current_user = wp_get_current_user();
		$is_concierge = in_array( 'um_concierge', $current_user->roles );
		
		if ( $mode == 'register' && $is_concierge ) {
			$output = '';
		}
		
		return $output;
	}
	
	public function set_user_password_for_concierge( $default, $key ) {
		
		$current_user = wp_get_current_user();
		$is_concierge = in_array( 'um_concierge', $current_user->roles );
		if ( $is_concierge ) {
			$default = $this->default_pass;
		}
		
		return $default;
	}

    public function user_registration_check() {
        /*
            We are exiting/redirecting before Ultimate Member can call this:
            // UM()->user()->auto_login( $user_id );
        */
        if (! is_admin() ) {
            // Redirect to /concierge page
            exit( wp_safe_redirect( get_permalink( 93 ) ) ) ;
        }
    }

    /**
     * Singleton factory Method
     * Forces that only on instance of the class exists
     *
     * @return $instance Object, Returns the current instance or a new instance of the class
     */

    public static function instance()
    {
        if (!isset(self::$instance)) {
            $className = __CLASS__;
            self::$instance = new $className;
        }
        return self::$instance;
    }

    // Create an itinerary for the user at signup and associate their account with it
    public function create_user_itinerary( $user_id, $submitted ) {

        $user_last = $submitted['last_name'];
        $itin_group_name = sanitize_text_field( $submitted['group_name'] );
        $itin_villa_option = $submitted['villa_option']; // Passed as Post Title - need to convert to Post ID

        $itin_dates = $submitted['trip_dates']; // '01-01-2021 - 01-16-2021'
        $itin_dates_array = explode( ' - ', $itin_dates ); // array(01-01-2021, 01-16-2021)
        $itin_start_date_raw = explode( '-', $itin_dates_array[0]); // array(01, 01, 2021)
        $itin_end_date_raw = explode( '-', $itin_dates_array[1]); // array(01, 16, 2021)

        $itin_account_id = sanitize_text_field( $submitted['account_id']);


        $itin_start_date = $itin_start_date_raw[2] . $itin_start_date_raw[0] . $itin_start_date_raw[1]; // '20210101'
        $itin_end_date = $itin_end_date_raw[2] . $itin_end_date_raw[0] . $itin_end_date_raw[1]; // '20210116'
        $itin_start_datetime = \DateTime::createFromFormat('Ymd', $itin_start_date); // DateTime
        $itin_end_datetime = \DateTime::createFromFormat('Ymd', $itin_end_date); // DateTime
        // $itin_wedding_date = $submitted['wedding_date'];



        $itin_temp_title = sanitize_text_field( $itin_group_name ) . ' - ' . $itin_villa_option;

        $villa_args = array( 
            'post_type' => 'villa',
            'posts_per_page' => 1,
            'title' => $itin_villa_option
        );

        $selected_villa = new \WP_Query( $villa_args );
        $villa_insert = $selected_villa->posts[0];
        $villa_id = $villa_insert->ID;

        $FXUP_Itinerary_Process = \FXUP_User_Portal\Controllers\FXUP_Itinerary_Process::instance();
        // Requires:
        /*
            villa_id
            group_name
            trip_start_date // DateTime
            trip_end_date // DateTime
            user_id
        */
        // Optional
        /*
            account_id
        */
        $data = array(
            'villa_id' => $villa_id,
            'group_name' => $itin_group_name,
            'trip_start_date' => $itin_start_datetime, // DateTime
            'trip_end_date' => $itin_end_datetime, // DateTime
            'user_id' => $user_id,
            'account_id' => $itin_account_id,
        );
        $FXUP_Itinerary_Process->create_new_itinerary_object($data);

        /*
        $itin_post = array(
            'post_title' => $itin_temp_title,
            'post_status' => 'publish',
            'post_author' => 9,
            'post_type' => 'itinerary'
        );

        // Save new itinerary and update relevant fields
        $new_itin = wp_insert_post( $itin_post );
        update_field( 'itinerary_user', $user_id,  $new_itin );
        update_field( 'villa_option', $villa_insert, $new_itin );
        update_field( 'trip_start_date', $itin_start_date, $new_itin ); // '20210101'
        update_field( 'trip_end_date', $itin_end_date, $new_itin ); // '20210116'
        // update_field( 'wedding_date', $itin_wedding_date, $new_itin );
        update_field( 'group_name', $itin_group_name, $new_itin );
        update_field( 'approval_status', 'Pending Client Submission', $new_itin );
        update_field( 'account_id', $itin_account_id, $new_itin );

        $share_link = $this->get_itin_token(20);
        update_field( 'share_link_token', $share_link, $new_itin );

        // Update activity repeater based on selected dates
        $trip_start = strtotime( $itin_start_date  ); // '20210101' to 1609459200
		$trip_end = strtotime( $itin_end_date );

		$trip_range = array();
		for( $current_date = $trip_start; $current_date <= $trip_end; $current_date += (86400) ){
			$cd = date( 'F j, Y', $current_date ); // 1609459200 to January 1, 2021
			$trip_range[] = $cd;
		}

        // Set a limit of 90 repeater fields
        // ** this is just to prevent users from accidentally creating hundreds of rows **
        if( count($trip_range) <= 90  ) {
            foreach( $trip_range as $day ) {
                $row = array(
                    'trip_day' => $day, // January 1, 2021
                    'trip_day_activities' => array()
                );

                $i = add_row( 'field_5cfab89e652d7', $row, $new_itin );
            }
            $range_value = get_field( 'field_5cfab89e652d7', $new_itin );		
            update_field( 'field_5cfab89e652d7', $range_value, $new_itin );
        }

        */

    }

    // Custom validation message for login
    public function um_login_custom_error( $error, $key ) {
        if( $error === 'Password is incorrect. Please try again.' ) {
            $error = "Sorry, we couldn't find that account or you've entered an incorrect password, please try again or reset your password";
        }
        return $error;
    }

    // Custom validation for UM forms
    public function um_custom_form_validation( $args ) {
        if( isset( $args['imahuman'] ) && $args['imahuman'] !== '16749697' ) {        
            UM()->form()->add_error( 'imahuman', 'There has been an issue submitting the form. Please try again.' );
        }
    }

    public function get_itin_token($length) {
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet.= "0123456789";
        $max = strlen($codeAlphabet);

        for ($i=0; $i < $length; $i++) {
            $token .= $codeAlphabet[random_int(0, $max-1)];
        }

        return $token;
    }

	private function generate_strong_password( $length = 12, $add_dashes = false, $available_sets = 'luds' ) {
		$sets = array();
		if(strpos($available_sets, 'l') !== false)
			$sets[] = 'abcdefghjkmnpqrstuvwxyz';
		if(strpos($available_sets, 'u') !== false)
			$sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
		if(strpos($available_sets, 'd') !== false)
			$sets[] = '23456789';
		if(strpos($available_sets, 's') !== false)
			$sets[] = '!@#$%&*?';

		$all = '';
		$password = '';
		foreach($sets as $set)
		{
			$password .= $set[array_rand(str_split($set))];
			$all .= $set;
		}

		$all = str_split($all);
		for($i = 0; $i < $length - count($sets); $i++)
			$password .= $all[array_rand($all)];

		$password = str_shuffle($password);

		if(!$add_dashes)
			return $password;

		$dash_len = floor(sqrt($length));
		$dash_str = '';
		while(strlen($password) > $dash_len)
		{
			$dash_str .= substr($password, 0, $dash_len) . '-';
			$password = substr($password, $dash_len);
		}
		$dash_str .= $password;
		return $dash_str;
	}

	public function set_reset_password_text( $translation, $text, $domain )
	{
		switch ( $text ) {
			case 'To reset your password, please enter your email address or username below.':
				$translation = 'To set your password, please enter your email address or username below.';
				break;
			
			case 'Reset my password':
				$translation = 'Set my password';
				break;
		}
		
		return $translation;
	}
}

FXUP_Account_Registration::instance();
