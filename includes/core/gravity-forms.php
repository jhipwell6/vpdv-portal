<?php

namespace FXUP_User_Portal\Core;

class Gravity_Forms
{
    protected static $instance;
	
	private static $list_data = array();

    /**
     * Initializes plugin variables and sets up WordPress hooks/actions.
     *
     * @return void
     */

    protected function __construct()
    {
		add_filter( 'gform_field_value', array( $this, 'prefill_grocery_list_fields' ), 10, 3 );
		add_filter( 'gform_field_content_7', array( $this, 'set_readonly_fields' ), 10, 5 );
		add_filter( 'gform_confirmation_anchor' , '__return_false' );
		add_filter( 'gform_merge_tag_filter', array( $this, 'trim_blank_grocery_lines' ), 10, 6 );
		add_filter( 'gform_pre_render', array( $this, 'populate_adult_guest_choices' ) );
		add_filter( 'gform_pre_validation', array( $this, 'populate_adult_guest_choices' ) );
		add_filter( 'gform_pre_submission_filter', array( $this, 'populate_adult_guest_choices' ) );
		add_filter( 'gform_admin_pre_render', array( $this, 'populate_adult_guest_choices' ) );
	}
    /**
     * Singleton factory Method
     * Forces that only on instance of the class exists
     *
     * @return $instance Object, Returns the current instance or a new instance of the class
     */

    public static function instance()
    {
        if ( ! isset( self::$instance ) ) {
            $className = __CLASS__;
            self::$instance = new $className();
        }
        return self::$instance;
    }
	
	public function prefill_grocery_list_fields( $value, $field, $name )
	{
		self::set_list_data();
		
		if ( array_key_exists( $field->inputName, self::$list_data ) ) {
			$value = $this->map_list_items( self::$list_data[ $field->inputName ] );
		}
		
		return $value;
	}
	
	public function set_readonly_fields( $content, $field, $value, $lead_id, $form_id )
	{
		if ( $field->type == 'list' ) {
			$content = str_replace( "aria-label='Item, Row '", "aria-label='Item' class='readonly-field' readonly", $content );
			$content = str_replace( "aria-label='Preferred Brand, Row '", "aria-label='Preferred Brand' class='readonly-field' readonly", $content );
			$content = str_replace( "aria-label='Estimated Price, Row '", "aria-label='Estimated Price' class='readonly-field price-field' readonly", $content );
		}
		
		return $content;
	}
	
	public function trim_blank_grocery_lines( $value, $merge_tag, $modifier, $field, $raw_value, $format )
	{
		if ( $field->type == 'list' && $merge_tag == 'all_fields' && isset( $field->formId ) && $field->formId == 7 ) {
			$values = unserialize( $raw_value );
			$new_values = array();
			if ( ! empty( $values ) ) {
				foreach ( $values as $line_value ) {
					if ( $line_value['Quantity'] != '' ) {
						$new_values[] = $line_value;
					}
				}
			}
			
			if ( ! empty( $new_values ) ) {
				$value = $field->get_value_entry_detail( $new_values, '', false, 'html', 'email' );
			} else {
				$value = 'N/A';
			}
		}
		return $value;
	}

	public function populate_adult_guest_choices( $form )
	{
		if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
			return $form;
		}

		$Itinerary = $this->get_itinerary_for_form( $form );
		$choices = $this->get_adult_guest_choices( $Itinerary );

		foreach ( $form['fields'] as &$field ) {
			if ( ! $this->is_adult_guests_field( $field ) ) {
				continue;
			}

			$field->choices = $choices;

			if ( property_exists( $field, 'placeholder' ) && empty( $field->placeholder ) ) {
				$field->placeholder = 'Select Parent';
			}
		}

		return $form;
	}
	
	public static function get_list_data()
	{
		if ( empty( self::$list_data ) ) {
			self::set_list_data();
		}
		
		return self::$list_data;
	}
	
	private static function set_list_data()
	{
		if ( empty( self::$list_data ) ) {
			$data = array();
			$raw_data = get_field( 'grocery_list_groups', 'option' );
			if ( ! empty( $raw_data ) ) {
				foreach ( $raw_data as $group ) {
					$slug = self::generate_slug( $group['title'] );
					$data[ $slug ] = $group['options'];
				}

				self::$list_data = $data;
			}
		}
	}
	
	private static function generate_slug( $text )
	{
		$text = strtolower( $text );
		$text = str_replace( ' ', '_', $text );
		$text = str_replace( array( 'and', '&' ), '', $text );
		$text = str_replace( '__', '_', $text );
		
		return $text;
	}
	
	private function map_list_items( $arr )
	{
		$list = array();
		foreach ( $arr as $item ) {
			$list[] = array(
				'Item' => $item['item'],
				'Preferred Brand' => $item['preferred_brand'],
				'Quantity' => '',
				'Notes' => '',
				'Estimated Price' => '',
			);
		}
		
		return $list;
	}

	private function is_adult_guests_field( $field )
	{
		if ( ! is_object( $field ) ) {
			return false;
		}

		if ( empty( $field->allowsPrepopulate ) || empty( $field->inputName ) ) {
			return false;
		}

		return 'adult_guests' === $field->inputName;
	}

	private function get_itinerary_for_form( $form )
	{
		$token = isset( $_GET['itin'] ) ? sanitize_text_field( wp_unslash( $_GET['itin'] ) ) : '';
		if ( '' !== $token ) {
			$Itinerary = \FXUP_User_Portal\Models\Itinerary::fromToken( $token );
			if ( $Itinerary ) {
				return $Itinerary;
			}
		}

		$itinerary_id = $this->get_submitted_itinerary_id( $form );
		if ( $itinerary_id > 0 ) {
			try {
				return new \FXUP_User_Portal\Models\Itinerary( $itinerary_id );
			} catch ( \Exception $e ) {
				return false;
			}
		}

		return false;
	}

	private function get_submitted_itinerary_id( $form )
	{
		if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
			return 0;
		}

		foreach ( $form['fields'] as $field ) {
			if ( ! is_object( $field ) || empty( $field->inputName ) ) {
				continue;
			}

			if ( 'itinerary_id' !== $field->inputName ) {
				continue;
			}

			$input_key = 'input_' . $field->id;
			$value = isset( $_POST[ $input_key ] ) ? wp_unslash( $_POST[ $input_key ] ) : '';

			return absint( $value );
		}

		return 0;
	}

	private function get_adult_guest_choices( $Itinerary )
	{
		$choices = array();

		if ( ! $Itinerary ) {
			return $choices;
		}

		foreach ( $Itinerary->getAdultGuests() as $Guest ) {
			$choices[] = array(
				'text' => $Guest->getFullName(),
				'value' => (string) $Guest->getPostID(),
			);
		}

		return $choices;
	}
}

Gravity_Forms::instance();
