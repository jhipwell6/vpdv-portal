var FXUP = ( function ( FXUP, $ ) {

	// This is to prevent the user from putting the same guest into more than 1 room.
	FXUP.RoomBookingValidation = {
		roomList: null,
		default: 'Select a Guest',
		selects: null,
		mapSelects: null,
		guests: null,
		sortedGuestsIDs: null,
		assignedGuests: null, // Currently, this is keyed and stored by name, not Guest post id. Also includes the "Select a guest" default.
		unassignedGuests: null,
		init() {
			this.roomList = document.querySelector( '#room-list' );
			if ( this.roomList ) {
				//  To Do: remove this debug logic
				this.bind();
			}
		},
		bind() {

			this.selects = this.roomList.querySelectorAll( 'select.room-guest-assignment' );
			if ( ! ( this.mapSelects instanceof Map ) ) {
				this.mapSelects = new Map();
			}
			if ( ! ( this.guests instanceof Map ) ) {
				this.guests = new Map();
			}
			if ( ! ( this.assignedGuests instanceof Set ) ) {
				this.assignedGuests = new Set();
			}
			if ( ! ( this.unassignedGuests instanceof Set ) ) {
				this.unassignedGuests = new Set();
			}
			// Get all Guest Names
			// Get all Assigned Guest Names
			this.selects.forEach( select => {
				let selected = select.value;
				this.mapSelects.set( select, selected );
				this.assignedGuests.add( selected );
				let options = Array.from( select.options );
				options.forEach( option => {
					let guestID = option.value;
					let guestName = option.innerHTML;
					this.guests.set( guestID, guestName );
				} );
				// Sort guest IDs by their names 
				if ( ! ( this.sortedGuestsIDs instanceof Array ) ) {
					this.sortedGuestsIDs = [ ...this.guests ]
							.sort( ( guestArrayOne, guestArrayTwo ) => {
								// Compare on name
								return guestArrayOne[1] < guestArrayTwo[1] ? - 1 : ( guestArrayOne[1] > guestArrayTwo[1] ? 1 : 0 );
							} )
							// Only retain the ID
							.map( guestArray => guestArray[0] )
				}
				// Have to use jQuery to bind this event - because of selectric.
				$( select ).on( 'change', ( e ) => {
					this.onSelectChange( select );
				} );
				// this.assignedGuests = [...new Set(this.assignedGuests.push(selected))];
				// let options = Array.from(select.options).map(option => option.value);
				// this.guests = [...new Set([...this.guests, ...options])];
			} );
			// Figure out which ones are Unassigned by comparison to Assigned
			this.unassignedGuests = new Set( Array.from( this.guests.keys() ).filter( guestID => ! this.assignedGuests.has( guestID ) ) );

			this.refreshSelects();

			// Handle these in FXUP.RoomArrangements so that animation is executed first.
			// $('.js-guest-fields input:radio').on('ifClicked', (event) => this.toggleChildFields(event));
			// $('.js-add-guest-check').on('ifUnchecked', (event) => this.onRemoveAdditionalGuests(event)); 

		},
		toggleChildFields( event ) {
			let radioInput = event.target;
			if ( radioInput.value == 1 ) {
				this.onGuestIsAChild( radioInput )
			}
		},
		onGuestIsAChild( radioInput ) {
			let wrapper = radioInput.closest( '.js-guest-wrap' );
			let select = wrapper.querySelector( 'select.room-guest-assignment' );
			select.value = this.default;
			this.onSelectChange( select );
		},
		onRemoveAdditionalGuests( event ) {
			let checkbox = event.target;
			let wrapper = checkbox.closest( '.room-config-group' );
			let selects = wrapper.querySelectorAll( '.js-additional-guests-container select.room-guest-assignment' );
			// Set to default
			selects.forEach( select => {
				select.value = this.default;
			} );
			this.onSelectChange( ...selects );
		},
		changeSelectValue( select, value ) {
			select.value = value;
			this.onSelectChange( select );
			// $(select).val(value);
			// $(select).trigger('change'); // Trigger the refresh ('change') jQuery event to free up the Guest for other selects.
		},
		onSelectChange( ...selects ) {
			selects.forEach( select => {
				let newValue = select.value;
				let oldValue = this.mapSelects.get( select );
				// Set the new value
				this.mapSelects.set( select, newValue );
				this.assignedGuests.add( newValue );
				this.unassignedGuests.delete( newValue );

				this.assignedGuests.delete( oldValue );
				this.unassignedGuests.add( oldValue );

			} );
			this.refreshSelects( ...selects );
		},
		refreshSelects( ...changedSelects ) {

			if ( changedSelects.length ) {
				// If a select was changed, prioritize refreshing this one so the experience is more interactive
				changedSelects.forEach( changedSelect => {
					let changedSelectValue = changedSelect.value;
					this.refreshSelect( changedSelect, changedSelectValue );
				} )
			}

			// Iterate through selects
			for ( const [ select, value ] of this.mapSelects.entries() ) {
				if ( changedSelects.includes( select ) ) {
					continue; } // Don't bother refreshing the one that was changed.
				setTimeout( () => this.refreshSelect.bind( this )( select, value ), 0 ); // Makes the experience feel faster because pushes to EventLoop queue.
				// this.refreshSelect(select, value);
			}
		},
		refreshSelect( select, value ) {
			if ( FXUP.RoomArrangements.isAnimating ) {
				setTimeout( () => this.refreshSelect.bind( this )( select, value ), 0 );
				return;
			}
			// Add the currently chosen value to the unsorted unassignedGuestNames, remove the default option (will appear on top), and sort them by name.
			let validGuestIDs = [ value, ...this.unassignedGuests ].filter( guestID => guestID !== this.default ).sort( ( guestIDOne, guestIDTwo ) => {
				sortPositionOne = this.sortedGuestsIDs.indexOf( guestIDOne );
				sortPositionTwo = this.sortedGuestsIDs.indexOf( guestIDTwo );
				return sortPositionOne < sortPositionTwo ? - 1 : ( sortPositionOne > sortPositionTwo ? 1 : 0 );
			} ); // Strips the default
			let newOptions = validGuestIDs.map( guestID => {
				let optionElem = document.createElement( 'option' );
				optionElem.value = guestID;
				optionElem.innerHTML = this.guests.get( guestID ); // Get the associated Guest Name
				return optionElem;
			} );
			// Add the default option to the top of the dropdown. Note: The default may have been the currently selected value. If so, it will be selected.
			let defaultOption = document.createElement( 'option' );
			defaultOption.value = this.default;
			defaultOption.innerHTML = this.default;
			newOptions.unshift( defaultOption );
			// Not IE supported
			// select.replaceChildren(...newOptions);

			// IE supported
			while ( select.firstElementChild ) {
				select.firstElementChild.remove();
			}
			newOptions.forEach( option => {
				select.insertAdjacentElement( 'beforeend', option );
			} )
			select.value = value; // Might be the default.
			$( select ).selectric( 'refresh' );
		}
	};

	FXUP.SharedSaveStatusBar = {

		statusUpdate: function ( event ) {

			// First get rid of any SUBMITTED message from the last save
			if ( $( '.js-itin-save-status-submitted' ).length > 0 ) {
				$( '.js-itin-save-status-submitted' ).slideUp();
			}

			// First get rid of any SAVED message from the last save
			if ( $( '.js-itin-save-status-saved' ).length > 0 ) {
				$( '.js-itin-save-status-saved' ).slideUp();
			}

			// Next, add a reminder to save.
			if ( $( '.js-itin-save-status' ).length > 0 ) {
				window.onbeforeunload = function () {
					return true;
				};
				$( '.js-itin-save-status' ).slideDown();
			}

		},

		closeStatus: function ( event ) {
			$( this ).closest( '.itin-save-status' ).slideUp();
		},

		displayPopup: function ( event ) {
			event.preventDefault();
			$( '.itin-add-popup' ).fadeIn();
		},

		closePopup: function ( event ) {
			event.preventDefault();
			$( this ).closest( '.popup-confirm-wrapper' ).fadeOut();
		},

		exitPopup: function ( event ) {
			if ( $( '.js-itin-save-status' ).length > 0 ) {
				window.onbeforeunload = null;
				$( '.js-itin-save-status' ).slideUp();
			}
		},
	};

	FXUP.RoomArrangements = {
		isAnimating: null,
		init: function () {
			var $roomDetails = $( '#room-list' );
			if ( $roomDetails.length > 0 ) {
				this.bind();
			}
		},

		bind: function () {

			$( '.js-add-guest-check' ).on( 'ifChecked', this.showAddGuest );
			$( '.js-add-guest-check' ).on( 'ifUnchecked', this.hideAddGuest ); // Also call FXUP.RoomBookingValidation.onRemoveAdditionalGuests
			$( '.js-rooms-save' ).on( 'click', this.saveRooms );
			$( '.js-itin-save' ).on( 'click', this.saveRooms );

			$( '.js-rooms-submit' ).on( 'click', this.saveRooms );

			$( 'body' ).on( 'change', '#room-list select', FXUP.SharedSaveStatusBar.statusUpdate ); // Once value is selected
			$( 'body' ).on( 'input', '#room-list input, #room-list textarea', FXUP.SharedSaveStatusBar.statusUpdate ); // As soon as input is changed, even if still in focus
			$( 'body' ).on( 'ifChanged', FXUP.SharedSaveStatusBar.statusUpdate ); // For iCheck boxes

			$( '.js-close-status' ).on( 'click touch', FXUP.SharedSaveStatusBar.closeStatus );
			$( '.js-add-itinerary-item' ).on( 'click', FXUP.SharedSaveStatusBar.displayPopup );
			$( 'body' ).on( 'click', '.js-itin-popup-close', FXUP.SharedSaveStatusBar.closePopup );
			$( '.js-popup-exit' ).on( 'click', FXUP.SharedSaveStatusBar.exitPopup );
		},

		toggleChildFields: function ( event ) {
			// Guest slots now use a unified guest selector for adults and children.
			// Keep method for backwards compatibility with legacy templates.
		},

		showAddGuest: function () {
			FXUP.RoomArrangements.isAnimating = true;
			$( this ).closest( '.js-additional-guest' ).next().slideDown( 400, function () {
				FXUP.RoomArrangements.isAnimating = false;
			} );
		},

		hideAddGuest: function ( event ) {
			FXUP.RoomArrangements.isAnimating = true;
			$this = $( this );
			$additionalGuest = $this.closest( '.js-additional-guest' );
			$additionalGuestSelectsContainer = $additionalGuest.next();
			$additionalGuestSelectsContainer
					.slideUp( 400, function () {
						FXUP.RoomArrangements.isAnimating = false;
						$additionalGuestSelectsContainer.find( '.js-guest-child input' ).val( '' );
						$additionalGuestSelectsContainer.find( '.js-guest-adult select' ).prop( 'selectedIndex', 0 );  // .selectric('refresh');
						FXUP.RoomBookingValidation.onRemoveAdditionalGuests( event );
					} )
			// .slideUp(400)
			// .delay(100)
			// .slideUp(0, function() {
			// FXUP.RoomArrangements.isAnimating = false;
			// $additionalGuestSelectsContainer.find('.js-guest-child input').val('');    
			// $additionalGuestSelectsContainer.find('.js-guest-adult select').prop('selectedIndex', 0);  // .selectric('refresh');
			// FXUP.RoomBookingValidation.onRemoveAdditionalGuests(event);
			// });  
		},

		saveRooms: function ( event ) {
			event.preventDefault();
			var roomDetails = $( '#room-list' ).serialize(),
					submit = false;

			if ( $( this ).hasClass( 'js-rooms-submit' ) ) {
				submit = 'submitted';
			}

			$.ajax( {
				type: 'post',
				url: FX.ajaxurl,
				data: {
					action: 'save_room_list',
					room_list: roomDetails,
					submit_final: submit
				},
				beforeSend: function () {
					$( '.ajax-loading' ).fadeIn();
				},
				success: function ( results ) {
					$( '.ajax-loading' ).fadeOut();

					setTimeout( function () {
						$( '.js-itin-save-status' ).slideUp();
						$( '.js-itin-save-status-submitted, .js-itin-save-status-saved' ).slideUp();
						if ( submit ) {
							$( '.js-itin-save-status-submitted' ).slideDown().delay( 2500 ).slideUp();
						} else {
							$( '.js-itin-save-status-saved' ).slideDown().delay( 2500 ).slideUp();
						}

					}, 500 );
				}
			} )
		}
	};

	FXUP.GuestTravel = {

		recentlySavedGuestTravel: null,

		init() {
			if ( document.querySelector( '#travel-list' ) ) {
				this.bind();
			}

			if ( $( '.page-template-page-guest-travel' ).length ) {
				this.initLitepicker();
			}
		},

		bind() {
			$( 'body' ).on( 'submit', '.js-save-travel-details', $.proxy( this.saveTravelSingle, this ) );

			$( '.js-close-status' ).on( 'click touch', FXUP.SharedSaveStatusBar.closeStatus );

			$( 'body' ).on( 'click', '.js-fxup-send-guest-travel-reminder-notification', $.proxy( this.sendTravelReminder, this ) );
			$( 'body' ).on( 'click', '.js-fxup-send-single-guest-travel-reminder-notification', $.proxy( this.sendSingleTravelReminder, this ) );

			$.fn.extend( {
				toggleText: function ( a, b ) {
					return this.text( this.text() == b ? a : b );
				}
			} );
			
			$( 'body' ).on( 'ifChecked', '[name^="requires_arrival_transportation"],[name^="requires_departure_transportation"]', this.maybeConfirmTransportationSetting );

			// Hide the loading spinner ()
			this.hideLoaderSpinner();
		},
		
		maybeConfirmTransportationSetting( e ) {
			if ( $( this ).val() == '0' ) {
				$( this ).jConfirm( {
					'show_now': true,
					'hide_on_click': false,
					size: 'tiny',
					position: 'right',
				} ).on( 'deny', function(e) {
					var name = $( this ).attr( 'name' );
					$( this ).iCheck( 'uncheck' );
					$( '[name="' + name + '"][value="1"]' ).iCheck( 'check' );
				} );
			}
		},

		saveTravelSingle( e ) {
			e.preventDefault();
			const preTravelDetails = $( e.target ).serializeArray();

			this.resetInvalidInputs( preTravelDetails );
			const invalidInputs = this.getInvalidInputs( preTravelDetails );
			if ( invalidInputs.length ) {
				this.markInvalidInputs( invalidInputs );
				alert( 'Please fill in all required fields.' );
				return false;
			}

			const travelDetails = $( e.target ).serialize();

			$.ajax( {
				type: 'post',
				url: FX.ajaxurl,
				data: {
					action: 'save_travel_list',
					travel_list: travelDetails
				},
				beforeSend: function () {
					$( '.ajax-loading' ).fadeIn();
				},
				success: function ( results ) {
					$( '.ajax-loading' ).fadeOut();

					setTimeout( function () {
						$( '.js-itin-save-status' ).slideUp();
						$( '.js-itin-save-status-submitted' ).slideDown().delay( 2500 ).slideUp();
					}, 500 );
				}
			} );
		},

		getInvalidInputs( travelDetails ) {
			let invalidInputs = [ ];
			invalidInputs = travelDetails.filter( ( input ) => {
				if ( ! this.shouldValidateInput( input.name ) ) {
					return false;
				}

				if ( input.value == '' ) {
					return true;
				}

				return false;
			} );

			return invalidInputs;
		},

		resetInvalidInputs( inputs ) {
			inputs.forEach( ( input ) => {
				$( '[name="' + input.name + '"]' ).removeClass( 'invalid' );
			} );
		},

		markInvalidInputs( invalidInputs ) {
			if ( ! invalidInputs.length ) {
				return false;
			}

			invalidInputs.forEach( ( input ) => {
				$( '[name="' + input.name + '"]' ).addClass( 'invalid' );
			} );
		},

		shouldValidateInput( name ) {
			if (
					name.includes( 'passport_number' ) ||
					name.includes( 'travel_notes' ) ||
					name.includes( 'requires_arrival_transportation' ) ||
					name.includes( 'requires_departure_transportation' ) ||
					name.includes( 'guest_travel_status' )
					) {
				return false;
			}
			return true;
		},

		addRecentlySavedGuestTravel( guest ) {
			let guests = this.getRecentlySavedGuestTravel();
			guests.push( guest );
			this.setRecentlySavedGuestTravel( guests );
		},

		removeRecentlySavedGuestTravel( guest ) {
			let id = guest.target ? $( guest.target ).val() : guest.ID;
			let guests = this.getRecentlySavedGuestTravel().filter( ( g ) => g.ID != id );
			this.setRecentlySavedGuestTravel( guests );
		},

		getRecentlySavedGuestTravel( forceUpdate = false ) {
			if ( null === this.recentlySavedGuestTravel || forceUpdate ) {
				this.recentlySavedGuestTravel = JSON.parse( localStorage.getItem( 'vpdvRecentlySavedGuestTravel' ) || "[]" );
			}
			return this.recentlySavedGuestTravel;
		},

		setRecentlySavedGuestTravel( guest ) {
			this.recentlySavedGuestTravel = guest;
			localStorage.setItem( 'vpdvRecentlySavedGuestTravel', JSON.stringify( guest ) );
			return this.recentlySavedGuestTravel;
		},

		hasRecentlySavedGuestTravel( ID = null ) {
			if ( null === ID ) {
				return this.getRecentlySavedGuestTravel().length;
			}
			return this.getRecentlySavedGuestTravel().some( ( guest ) => guest.ID == ID );
		},

		hideLoaderSpinner: function () {
			let loaderSpinner = document.querySelector( '.loader-spinner' );
			if ( loaderSpinner ) {
				loaderSpinner.classList.add( 'hide-loader' );
			}
		},

		sendTravelReminder( e ) {
			const ID = $( e.target ).attr( 'data-id' );
			$.ajax( {
				type: 'post',
				url: FX.ajaxurl,
				data: {
					action: 'fxup_send_guest_reminder',
					itinerary_post_id: ID,
					guest_id: false,
				},
				beforeSend: function () {
					$( '.ajax-loading' ).fadeIn();
				},
				success: function ( results ) {
					$( '.ajax-loading' ).fadeOut();

					setTimeout( function () {
						$( '.js-itin-travel-reminder-sent' ).slideDown().delay( 2500 ).slideUp();
					}, 500 );
				}
			} );
		},

		sendSingleTravelReminder( e ) {
			const ID = $( e.target ).attr( 'data-id' );
			const Guest = $( e.target ).attr( 'data-guest' );
			$.ajax( {
				type: 'post',
				url: FX.ajaxurl,
				data: {
					action: 'fxup_send_guest_reminder',
					itinerary_post_id: ID,
					guest_id: Guest,
				},
				beforeSend: function () {
					$( '.ajax-loading' ).fadeIn();
				},
				success: function ( results ) {
					$( '.ajax-loading' ).fadeOut();

					setTimeout( function () {
						$( '.js-itin-travel-reminder-sent' ).slideDown().delay( 2500 ).slideUp();
					}, 500 );
				}
			} );
		},

		litepickerSelect: new Event( 'litepickerSelect', { bubbles: true, cancelable: false } ),

		initLitepicker() {
			$( '.activity-accordion' ).each( ( i, el ) => {
				var $elem = $( el );
				setTimeout( () => {
					var element = $elem.find( '#guest-trip-length-' + i ).get( 0 );
					var minDate = new Date( fxupData.tripStart.date );
					var maxDate = new Date( fxupData.tripEnd.date );
					minDate.setDate( minDate.getDate() - 2 );
					maxDate.setDate( maxDate.getDate() + 2 );
					var picker = new Litepicker(
							{
								element: element,
								format: 'MM-DD-YYYY',
								singleMode: false,
								useResetBtn: true,
								minDate: minDate,
								maxDate: maxDate,
								dropdowns: {
									minYear: 2020,
									maxYear: 2030,
									months: true,
									years: true
								},
								onSelect: ( date1, date2 ) => {
									$( '.arrival-date-' + i + ' input' ).val( this.getFormattedDate( date1 ) );
									$( '.departure-date-' + i + ' input' ).val( this.getFormattedDate( date2 ) );
									element.dispatchEvent( this.litepickerSelect );
								}
							}
					);

					// Set the Litepicker's start and end dates if user has previously chosen these
					const arrivalDateRaw = $elem.find( `div.arrival-date-${i}` ).find( 'input' ).first().val();
					const departureDateRaw = $elem.find( `div.departure-date-${i}` ).find( 'input' ).first().val();

					const arrivalDate = this.getDateFromRaw( arrivalDateRaw );
					const departureDate = this.getDateFromRaw( departureDateRaw );

					if ( 'Date' === arrivalDate.constructor.name && 'Date' === departureDate.constructor.name ) {
						picker.setDateRange( arrivalDate, departureDate );
					}

				}, 500 );
			} );
		},

		getDateFromRaw( raw ) {
			let date = false;
			const slashesFormatExpression = /^\d{2}\/\d{2}\/\d{4}$/;
			if ( 'string' === typeof raw && slashesFormatExpression.test( raw ) ) {
				const partsArray = raw.split( '/' );
				const month = Number( partsArray[0] ) - 1; // JavaScript Date months start at index 0
				const day = partsArray[1];
				const year = partsArray[2];
				date = new Date();
				date.setMonth( month );
				date.setDate( day );
				date.setFullYear( year );
			}
			return date;
		},

		getFormattedDate( date ) {
			var year = date.getFullYear();

			var month = ( 1 + date.getMonth() ).toString();
			month = month.length > 1 ? month : '0' + month;

			var day = date.getDate().toString();
			day = day.length > 1 ? day : '0' + day;

			return month + '/' + day + '/' + year;
		}

	};

	FXUP.GuestTravelInfo = {

		init: function () {
			if ( document.querySelector( '#guest-travel-info' ) ) {
				this.bind();
			}
		},

		bind: function () {

			$( 'body' ).on( 'submit', '#guest-travel-info', $.proxy( this.saveGuestTravel, this ) );
			$( 'body' ).on( 'change', '#guest-travel-info input, #guest-travel-info select, #guest-travel-info textarea', FXUP.SharedSaveStatusBar.statusUpdate );
			$( 'body' ).on( 'ifChanged', FXUP.SharedSaveStatusBar.statusUpdate ); // For iCheck boxes
			$( '.js-close-status' ).on( 'click touch', FXUP.SharedSaveStatusBar.closeStatus );
			$( 'body' ).on( 'ifChecked', 'input[name^="guest_travel_status_"]', this.toggleGuestDetails );
			$( 'body' ).on( 'ifChecked', '[name^="requires_arrival_transportation"],[name^="requires_departure_transportation"]', this.maybeConfirmTransportationSetting );

			$.fn.extend( {
				toggleText: function ( a, b ) {
					return this.text( this.text() == b ? a : b );
				}
			} );

		},

		saveGuestTravel( e ) {
			e.preventDefault();
			const preTravelDetails = $( '#guest-travel-info' ).serializeArray();

			this.resetInvalidInputs( preTravelDetails );
			const invalidInputs = this.getInvalidInputs( preTravelDetails );
			if ( invalidInputs.length ) {
				this.markInvalidInputs( invalidInputs );
				alert( 'Please fill in all required fields.' );
				return false;
			}

			const guestTravelDetails = $( '#guest-travel-info' ).serialize();

			$.ajax( {
				type: 'post',
				url: FX.ajaxurl,
				data: {
					action: 'save_guest_travel_list',
					guest_travel_list: guestTravelDetails
				},
				success: function ( results ) {
					setTimeout( function () {
						$( '.js-itin-save-status-submitted' ).slideDown();
					}, 500 );
				}
			} );
		},

		toggleGuestDetails( event ) {
			if ( $( event.target ).val() == 'ready' ) {
				$( '#guest-details' ).show();
			} else {
				$( '#guest-details' ).hide();
			}
		},
		
		maybeConfirmTransportationSetting( e ) {
			if ( $( this ).val() == '0' ) {
				$( this ).jConfirm( {
					'show_now': true,
					'hide_on_click': false,
					size: 'tiny',
					position: 'right',
				} ).on( 'deny', function(e) {
					var name = $( this ).attr( 'name' );
					$( this ).iCheck( 'uncheck' );
					$( '[name="' + name + '"][value="1"]' ).iCheck( 'check' );
				} );
			}
		},
		
		getInvalidInputs( travelDetails ) {
			let invalidInputs = [ ];
			invalidInputs = travelDetails.filter( ( input ) => {
				if ( ! this.shouldValidateInput( input.name ) ) {
					return false;
				}

				if ( input.value == '' ) {
					return true;
				}

				return false;
			} );

			return invalidInputs;
		},

		resetInvalidInputs( inputs ) {
			inputs.forEach( ( input ) => {
				$( '[name="' + input.name + '"]' ).removeClass( 'invalid' );
			} );
		},

		markInvalidInputs( invalidInputs ) {
			if ( ! invalidInputs.length ) {
				return false;
			}

			invalidInputs.forEach( ( input ) => {
				$( '[name="' + input.name + '"]' ).addClass( 'invalid' );
			} );
		},

		shouldValidateInput( name ) {
			if (
					name.includes( 'passport_number' ) ||
					name.includes( 'travel_notes' ) ||
					name.includes( 'requires_arrival_transportation' ) ||
					name.includes( 'requires_departure_transportation' ) ||
					name.includes( 'guest_travel_status' ) ||
					name.includes( 'arrival_date' ) ||
					name.includes( 'departure_date' )
					) {
				return false;
			}
			return true;
		}

	};

	FXUP.GuestList = {

		init: function () {
			if ( document.querySelector( '#gform_4' ) || document.querySelector( '#gform_5' ) ) {
				this.bind();
				window.updateTableCell = this.updateTableCell;
				// window.serializeGuestRowData = this.serializeGuestRowData;
				window.getForm = this.getForm;
				window.submitFormToAdmin = this.submitFormToAdmin;
				window.applyFormToTable = this.applyFormToTable;
				window.showFormField = this.showFormField;
				window.updateFormInput = this.updateFormInput;
			}
		},

		bind: function () {
			// $('.js-guest-list-save').on('click touch', this.save);
			// $('body').on('click', '.js-delete-guest', this.delete);
			// $('body').on('click', '.js-delete-confirm-guest', this.confirmDelete);
			// $('body').on('click', '.js-add-guest-item', this.addOne);
			// $('body').on('click', '.js-add-guest', this.addGuest);
			// $('body').on('click', '.js-delete-single-guest', this.deleteGuest);
			$( 'body' ).on( 'click', '.js-delete-guest-cancel, .js-onsite-change-cancel', this.deleteCancel );
			// $('body').on('click', '.js-delete-confirm-single-guest', this.confirmDeleteGuest);
			// $('body').on('click', '.js-add-new-guest', this.userAddGuest);
			// $('body').on('click', '.js-user-delete-single-guest', this.userDeleteGuest);
			// $('body').on('click', '.js-user-add-guest-group', this.userInsertGuestGroup);
			// $('body').on('submit', '.js-user-guest-list', this.userInsertGuestGroup);
			// $('body').on('change', '#guest-list input, #guest-list select, #guest-list textarea', this.statusUpdate);
			$( 'body' ).on( 'click', '.js-remove-guest', this.removePopup );
			$( 'body' ).on( 'click', '.js-onsite-change', this.changePopup );
			$( 'body' ).on( 'click', '.js-update-onsite', this.updateOnsite );
			$( 'body' ).on( 'click', '.js-delete-confirm-single-guest', this.deleteGuestConfirm );
			$( '.js-close-status' ).on( 'click touch', FXUP.SharedSaveStatusBar.closeStatus );
			$( '.guest-dietary-restrictions-checkboxes input[value="Other"]' ).on( 'ifChanged', this.maybeShowOtherDietaryRestrictions );
			$( 'body' ).on( 'submit', '.js-import-guests', this.importGuests );
			$( 'body' ).on( 'click', '.js-export-guests', this.exportGuests );
			$( 'body' ).on( 'click', '.js-edit-guest', this.editGuestPopup );
			$( 'body' ).on( 'click', '.js-edit-guest-cancel', this.editCancel );
			$( 'body' ).on( 'change ifChanged', '#guest_dietary_restrictions_Other', this.toggleOtherDietInput );
			$( 'body' ).on( 'change', '.js-onsite_stay_select', this.toggleStayLocationInput );
			$( 'body' ).on( 'change', '.js-stay_location_select', this.toggleOtherLocationInput );
			$( 'body' ).on( 'submit', '.js-edit-guest-form', this.editGuestSubmit );

			$popupSubmitConfirm = $( '.js-popup-submit-confirm' );
			if ( $popupSubmitConfirm.length ) {
				this.setUpPopup( $popupSubmitConfirm, this.submitGuestList );
				$( 'body' ).on( 'click', '.js-glist-save', () => {
					this.showPopup.bind( this, $popupSubmitConfirm )();
				} );
			}

			$( 'body' ).on( 'click touchstart', '.js-guest-field-edit-pen', this.editPen );
			$( 'body' ).on( 'click touchstart', '.js-edit-guest-list-row-submit', this.confirmPopup );
			// Prevent form from submitting when a popup input is "entered" - should just close the popup
			$( 'body' ).on( 'keydown', '.popup-confirm-wrapper input', ( event ) => {
				if ( event.keyCode == 13 ) {
					event.preventDefault();
					this.confirmPopup.bind( event.target )()
					return false;
				}
			} );

			$.fn.extend( {
				toggleText: function ( a, b ) {
					return this.text( this.text() == b ? a : b );
				}
			} );

			$( '.js-add-new-guest-button' ).on( 'click', function ( event ) {
				$( '.js-add-new-guest-form' ).slideToggle();
				$( this ).toggleText( 'Add Additional Guest', 'Hide Guest Form' );
			} );

			$( document ).on( 'gform_confirmation_loaded', function ( event, formId ) {
				if ( formId == 5 ) {
					// location.reload();
					// Remove any hash
					let newLocation = window.location.href.replace( window.location.hash, '' );
					if ( newLocation.charAt( newLocation.length - 1 ) === '#' ) {
						newLocation = newLocation.substr( 0, newLocation.length - 1 );
					}
					// Prevent the default "Changes may not be saved" dialog
					window.onbeforeunload = null;
					// Reload the page
					window.location.href = newLocation;
				}
			} );

		},

		editGuestPopup() {
			var self = FXUP.GuestList;
			const guestID = $( this ).attr( 'data-id' );
			const form = $( '.js-edit-guest-form' );
			const Guest = fxupData.guests.find( ( guest ) => guest.guest_id == guestID );

			Object.entries( Guest ).forEach( ( [ key, value ] ) => {
				switch ( key ) {
					case 'guest_dietary_restrictions':
						if ( value.length ) {
							value.forEach( ( val ) => {
								$( '[name="' + key + '[]"][value="' + val + '"]', form ).iCheck( 'check' );
							} );
						}
						break;
					case 'onsite_stay':
						$( '[name="' + key + '"]', form ).val( value ? 'Yes' : 'No' ).selectric( 'refresh' );
						break;
					case 'guest_allergies':
						$( '[name="' + key + '"]', form ).val( ! value ? '' : value );
						break;
					default:
						$( '[name="' + key + '"]', form ).val( value );
			}
			} );

			self.toggleOtherDietInput();
			self.toggleStayLocationInput();
			self.toggleOtherLocationInput();
			$( '[name="stay_location"]' ).selectric( 'refresh' );

			$( '.js-popup-edit-guest' ).fadeIn();
		},

		editGuestSubmit( event ) {
			event.preventDefault();
			const rawData = $( this ).serialize();
			const guestID = $( '[name="guest_id"]', this ).val();
			const data = new FormData();
			data.append( 'action', 'edit_guest' );
			data.append( 'guest_id', guestID );
			data.append( 'guest_data', rawData );

			fetch( FX.ajaxurl, {
				method: 'POST',
				body: data
			} ).then( ( response ) => {
				return response.json()
			} ).then( ( result ) => {
				Object.entries( result.data.Guest ).forEach( ( [ key, value ] ) => {
					switch ( key ) {
						case 'guest_dietary_restrictions':
							if ( value.length ) {
								$( '[data-row-guest-id=' + result.data.guest_id + '] [data-field-name="dietary_restrictions"] .field-value' ).text( value.join( ', ' ) );
							}
							break;
						case 'guest_dietary_restriction_other':
							$( '[data-row-guest-id=' + result.data.guest_id + '] [data-field-name="dietary_restrictions_other"] .field-value' ).text( value );
							break;
						case 'onsite_stay':
							$( '[data-row-guest-id=' + result.data.guest_id + '] [data-field-name="' + key + '"] .field-value' ).text( value ? 'Yes' : 'No' );
							break;
						case 'stay_location':
							if ( result.data.Guest.onsite_stay ) {
								$( '[data-row-guest-id=' + result.data.guest_id + '] [data-field-name="stay_location"] .field-value' ).text( '' );
							}
							break;
						case 'stay_location_other':
							if ( value && ! result.data.Guest.onsite_stay && result.data.Guest.stay_location == 'Other' ) {
								$( '[data-row-guest-id=' + result.data.guest_id + '] [data-field-name="stay_location"] .field-value' ).text( value );
							}
							break;
						case 'guest_allergies':
							$( '[data-row-guest-id=' + result.data.guest_id + '] [data-field-name="' + key + '"] .field-value' ).text( ! value ? '' : value );
							break;
						default:
							$( '[data-row-guest-id=' + result.data.guest_id + '] [data-field-name="' + key + '"] .field-value' ).text( value );
				}
				} );
				let Guest = fxupData.guests.find( ( guest, i ) => {
					if ( guest.guest_id == result.data.guest_id ) {
						fxupData.guests[i] = result.data.Guest;
						return true;
					}
				} );
				FXUP.GuestList.editCancel();
			} );
		},

		toggleOtherDietInput( event = null ) {
			if ( $( '#guest_dietary_restrictions_Other' ).is( ':checked' ) ) {
				$( '.js-guest_dietary_restriction_other' ).removeClass( 'hidden' );
			} else {
				$( '.js-guest_dietary_restriction_other' ).addClass( 'hidden' );
			}
		},

		toggleStayLocationInput( event = null ) {
			if ( $( '.js-onsite_stay_select' ).val() == 'No' ) {
				$( '.js-stay_location' ).removeClass( 'hidden' );
			} else {
				$( '.js-stay_location' ).addClass( 'hidden' );
				$( '.js-stay_location_other' ).addClass( 'hidden' );
			}
			FXUP.GuestList.toggleOtherLocationInput();
		},

		toggleOtherLocationInput( event = null ) {
			if ( $( '.js-stay_location_select' ).val() == 'Other' && $( '.js-onsite_stay_select' ).val() == 'No' ) {
				$( '.js-stay_location_other' ).removeClass( 'hidden' );
			} else {
				$( '.js-stay_location_other' ).addClass( 'hidden' );
		}
		},

		editCancel( event = null ) {
			if ( event ) {
				event.preventDefault();
			}
			$( '.js-popup-edit-guest' ).fadeOut();

			const form = $( '.js-edit-guest-form' );
			$( '[name][type="checkbox"]', form ).iCheck( 'uncheck' );
			$( '[name]', form ).not( '[type="checkbox"]' ).val( '' );
		},

		setUpPopup( $popup, callback ) {
			$popup.on( 'click touchstart', '.js-submit-confirm', function ( event ) {
				callback( event ); // 'this' will be the clicked element
				$popup.fadeOut();
			} );
			$popup.on( 'click touchstart', '.js-itin-popup-close', () => $popup.fadeOut() );
		},

		showPopup( $popup ) {
			$popup.fadeIn();
		},

		confirmPopup() {
			let $this = $( this );
			let $wrapper = $this.closest( '.popup-confirm-wrapper' );
			let $form = $this.closest( 'form.edit-row-form' );
			// let guestID = $form.data('form-guest-id');
			let formElem = $form[0]; // Turn back into HTMLFormElement

			$wrapper.fadeOut();

			let redraw = 'full-hold'; // Redraws rows in their current position
			FXUP.GuestList.applyFormToTable( formElem, redraw );
			FXUP.GuestList.submitFormToAdmin( formElem );
		},

		editPen() {
			let $this = $( this );
			let $fieldElement = $this.closest( '[data-button-field]' );
			let field = $fieldElement.data( 'button-field' );
			let $row = $this.closest( '[data-button-guest-id]' );
			let guestID = $row.data( 'button-guest-id' );
			FXUP.GuestList.showFormField( guestID, field );
		},

		showFormField( guestID, field ) {
			let form = FXUP.GuestList.getForm( guestID );
			let input = form.querySelector( `[name="${field}"]` );
			let $input = $( input );
			let $wrapper = $input.closest( '.popup-confirm-wrapper' );
			$wrapper.fadeIn();
		},

		getForm( guestID ) {

			if ( ! guestID ) {
				return;
			}

			const existingFormKey = `#guest_list_edit_row_${guestID}`;
			let existingForm = document.querySelector( existingFormKey );

			// Use form that has already been requested and appended to document if it exists
			if ( ! existingForm ) {
				// Grab a new form and append to document if not already appended
				$.ajax( {
					async: false,
					type: 'post',
					url: FX.ajaxurl,
					data: {
						action: 'edit_guest_list_row',
						guest_id: guestID,
					},
					success: function ( response ) {

						let results = JSON.parse( response );
						let markup = results.form_markup;

						// Check whether container for guest forms exists - create if it does not
						let container = document.querySelector( '#guest_list_edit_row_form_container' );
						if ( ! container ) {
							container = document.createElement( 'div' );
							container.id = 'guest_list_edit_row_form_container';
							document.body.insertAdjacentElement( 'beforeend', container );
						}
						container.insertAdjacentHTML( 'beforeend', markup );
						addedForm = container.querySelector( existingFormKey );
						existingForm = addedForm;
					}
				} );

			}

			return existingForm;
		},

		applyFormToTable( formElem, finalDrawPaging = true ) {
			let formData = new FormData( formElem );
			let guestID = formData.get( 'guest_id' );
			let lastUpdatedCellField = null;
			for ( let [ field, value ] of formData ) {
				lastUpdatedCellField = FXUP.GuestList.updateTableCell( guestID, field, value, 'page' ); // Pass argument to prevent from drawing until all fields are updated
			}
			lastUpdatedCellField.draw( finalDrawPaging ); // Finally, call draw with full-hold to reset the search filters. By default, will refresh
		},

		submitFormToAdmin( formElem ) {
			// this is the form
			let formData = new FormData( formElem );
			let guestID = formData.get( 'guest_id' );
			let onsite = formData.get( 'onsite_stay' );

			let dataParams = new URLSearchParams( formData );
			let dataString = dataParams.toString();

			// let testObject = {"undefined":"value undefined","guest_first_name":"value guest_first_name","guest_last_name":"value guest_last_name","guest_email":"value guest_email","guest_children":"value guest_children","onsite_stay":"value onsite_stay","stay_location":"value stay_location","villa_id":"value villa_id","room_name":"value room_name","guest_notes":"value guest_notes","dietary_restrictions":"value dietary_restrictions","guest_allergies":"value guest_allergies"};
			// let testQueryParams = new URLSearchParams(testObject);
			// let testQueryString = testQueryParams.toString();

			$.ajax( {
				type: 'post',
				url: FX.ajaxurl,
				data: {
					action: 'submit_guest_list_row_edit',
					guest_id: guestID,
					guest_row_form_data: dataString
				},
				success: function ( results ) {
					console.log( results );
				}
			} );
		},

		serializeGuestRowData( guestID ) {

			const row = { };

			const $table = $( '#guest_list' );
			const $DataTable = $table.DataTable();
			const $tableDataGuestID = $table.find( 'td [data-guestid="' + guestID + '"]' );
			const $tableRow = $tableDataGuestID.closest( 'tr[role="row"' );
			const $tableRowFields = $tableRow.find( 'td' );

			$tableRowFields.each( ( index, element ) => {

			} );


		},

		updateFormInput( guestID, field, value ) {
			let form = FXUP.GuestList.getForm( guestID );
			form[field].value = value;
		},

		updateTableCell( guestID, field, value, paging = true ) {

			// field example: first-name
			// classField example: '.guest-list__first-name'

			const $table = $( '#guest_list' );
			const $DataTable = $table.DataTable();

			// const $tableDataGuestID = $table.find('td [data-guestid="'+guestID+'"]');

			const $tableRow = $table.find( '[data-row-guest-id="' + guestID + '"]' );

			const $tableDataField = $tableRow.find( `[data-field-name="${field}"]` ); // Lookup table data field using custom attributes

			const $tableDataFieldValue = $tableDataField.find( '.field-value' );
			$tableDataFieldValue.html( value );

			// $tableDataField.html(value); // Update the inner element inside the td to flip the flag

			const cellField = $DataTable.cell( $tableDataField );

			cellField.data( $tableDataField.html() ); // Must access/pass the FULL updated td inner HTML to cell's data to update DataTable state


			cellField.draw( paging ); // For paginated tables, wait to call draw() until all fields on page have been updated

			return cellField;
		},

		removePopup: function ( event ) {
			event.preventDefault();
			var guestID = $( this ).attr( 'data-id' );
			$( '.js-delete-confirm-single-guest' ).attr( 'data-guestdelete', guestID );
			$( '.js-popup-delete-single-guest' ).fadeIn();
		},

		changePopup: function ( event ) {
			event.preventDefault();

			var guestID = $( this ).attr( 'data-guestid' );
			$( '.js-update-onsite' ).attr( 'data-guestupdate', guestID );
			$( '.js-popup-change-onsite-status' ).fadeIn();
		},

		updateOnsite: function ( event ) {
			event.preventDefault();
			var guestID = $( this ).attr( 'data-guestupdate' ),
					onsite = $( this ).attr( 'data-onsite' ) === '1',
					table = $( '#guest_list' ).DataTable();

			onsiteText = onsite ? 'Yes' : 'No';
			draw = 'full-hold'; // Adhere to filters by defualt
			// If not onsite, then we do not even refresh the filters because there are future updates to make to the row before it is potentially hidden from the results
			if ( ! onsite ) {
				draw = 'page';
			}

			const $tableData = $( 'td [data-guestid="' + guestID + '"]' ).closest( 'td' );
			const cell = table.cell( $tableData );

			$tableData.find( '.js-onsite-status' ).html( onsiteText ); // Update the inner element inside the td to flip the flag

			cell.data( $tableData.html() ); // Must access/pass the FULL updated td inner HTML to cell's data to update DataTable state
			cell.draw( draw ); // Page only - the page is not moved

			// IMPORTANT: Update the hidden form with the new consite value. If "offsite", a Location popup will display and then secretly submit the entire form (including this onsite_stay field);
			FXUP.GuestList.updateFormInput( guestID, 'onsite_stay', onsiteText );

			$.ajax( {
				type: 'post',
				url: FX.ajaxurl,
				data: {
					action: 'update_guest_onsite_status',
					guest_id: guestID,
					onsite: onsiteText
				},
				success: function ( results ) {
					$( '.js-popup-change-onsite-status' ).fadeOut();
					if ( ! onsite ) {
						// If user is offsite, show the Location form popup and remove their Villa and Room fields                       
						// Set Villa and Room form fields to blanks
						FXUP.GuestList.updateFormInput( guestID, 'villa_id', '' ); // Villa ID
						FXUP.GuestList.updateFormInput( guestID, 'room_name', '' );
						// Set Villa and Room table fields to blanks
						FXUP.GuestList.updateTableCell( guestID, 'villa_name', '', draw ); // Villa NAME -- Do not call draw yet - wait untl stay_location is updated.
						FXUP.GuestList.updateTableCell( guestID, 'room_name', '', draw ); // Now we can move the page
						// Display Location popup
						FXUP.GuestList.showFormField( guestID, 'stay_location' );
					}
				}
			} );

		},

		deleteGuestConfirm: function ( event ) {
			var guestID = $( this ).attr( 'data-guestdelete' ),
					itineraryID = $( '#input_5_17' ).val(),
					table = $( '#guest_list' ).DataTable();

			table
					.row( $( 'td [data-id="' + guestID + '"]' ).parents( 'tr' ) )
					.remove()
					.draw();

			$.ajax( {
				type: 'post',
				url: FX.ajaxurl,
				data: {
					action: 'user_remove_guest_item',
					guest_id: guestID,
					itin_id: itineraryID
				},
				success: function ( results ) {
					$( '.js-popup-delete-single-guest' ).fadeOut();
				}
			} );
		},

		submitGuestList: function ( event ) {
			event.preventDefault();
			var $itinElem = $( '[data-itin]' ).first();
			var itin = $itinElem.attr( 'data-itin' );

			$.ajax( {
				type: 'post',
				url: FX.ajaxurl,
				data: {
					action: 'submit_guest_list',
					itin_id: itin
				},
				success: function ( results ) {
					const $submitSuccessBox = $( '.js-itin-save-status-saved.itin-save-status.saved' );
					$submitSuccessBox.slideDown().delay( 2500 ).slideUp();
				}
			} );
		},

		importGuests( e ) {
			e.preventDefault();
			const $input = $( e.target ).find( '.js-import-guests-file' );
			const itinID = $( e.target ).attr( 'data-itinerary' );
			const fileName = 'fxup_guest_import_file';
			const rawData = {
				action: 'fxup_import_guests',
				itinerary_id: itinID,
				file_name: fileName,
				[fileName]: $input.prop( 'files' )[0]
			};

			const data = new FormData();
			Object.entries( rawData ).forEach( ( [ key, value ] ) => {
				data.append( key, value );
			} );

			fetch( FX.ajaxurl, {
				method: 'POST',
				body: data
			} ).then( ( response ) => {
				return response.json()
			} ).then( ( result ) => {
				let newLocation = window.location.href.replace( window.location.hash, '' );
				if ( newLocation.charAt( newLocation.length - 1 ) === '#' ) {
					newLocation = newLocation.substr( 0, newLocation.length - 1 );
				}
				// Prevent the default "Changes may not be saved" dialog
				window.onbeforeunload = null;
				// Reload the page
				window.location.href = newLocation;
			} );
		},
		
		exportGuests( e ) {
			e.preventDefault();
			const itinID = $( e.target ).attr( 'data-itinerary' );
			const rawData = {
				action: 'fxup_export_guests',
				itinerary_id: itinID
			};
			const data = new FormData();
			Object.entries( rawData ).forEach( ( [ key, value ] ) => {
				data.append( key, value );
			} );

			fetch( FX.ajaxurl, {
				method: 'POST',
				body: data
			} ).then( ( response ) => {
				return response.blob()
			} ).then( ( result ) => {
				FXUP.GuestList.saveFile( result, 'itinerary-' + itinID + '-guests.csv' );
			} );
		},
		
		saveFile( blob, fileName ) {
			const a = document.createElement( "a" );
			document.body.appendChild( a );
			a.style = "display: none";
			const url = window.URL.createObjectURL( blob );
			a.href = url;
			a.download = fileName;
			a.click();
			window.URL.revokeObjectURL( url );
			a.remove();
		},

		save: function ( event ) {
			event.preventDefault();

			guestList = $( '#guest-list' ).serialize();
			$( '.guest-error' ).remove();
			$.ajax( {
				type: 'post',
				url: FX.ajaxurl,
				data: {
					action: 'save_guest_list',
					guest_list: guestList
				},
				success: function ( results ) {
					guestInsert = JSON.parse( results );
					if ( 'errors' in guestInsert ) {
						$.each( guestInsert.errors, function ( i, v ) {
							$( 'input[name="' + i + '"' ).after( '<span class="guest-error">' + v + '</span>' );
						} );
					} else {
						if ( $( '.js-itin-save-status' ).length > 0 ) {
							window.onbeforeunload = null;
							$( '.js-itin-save-status' ).slideUp();
						}
						$( '.js-itin-save-status-saved' ).slideDown();
					}
				}
			} );
		},

		addOne: function ( event ) {
			event.preventDefault();
			guestList = $( '#guest-list' ).serialize();

			$.ajax( {
				type: 'post',
				url: FX.ajaxurl,
				data: {
					action: 'add_guest_list_item',
					guest_list: guestList
				},
				success: function ( results ) {
					var listInsert = JSON.parse( results );

					$( listInsert.target ).after( listInsert.html );

					if ( $( '.js-itin-save-status' ).length > 0 ) {
						window.onbeforeunload = null;
						$( '.js-itin-save-status' ).slideDown();
					}
				}
			} );
		},

		userInsertGuestGroup: function ( event ) {
			event.preventDefault();

			guestList = $( '#guest-list' ).serialize(),
					itineraryID = $( '#guest-list input[name="itinerary_id"]' ).val();
			$( '.guest-error' ).remove();

			$.ajax( {
				type: 'post',
				url: FX.ajaxurl,
				data: {
					action: 'user_insert_guest_list_item',
					guest_list: guestList,
					itinerary_id: itineraryID
				},
				success: function ( results ) {
					guestInsert = JSON.parse( results );

					if ( 'errors' in guestInsert ) {
						$.each( guestInsert.errors, function ( i, v ) {
							if ( i == 'imahuman' ) {
								$( '.js-user-add-guest-group' ).after( '<span class="guest-error">' + v + '</span>' );
							} else {
								$( 'input[name="' + i + '"' ).after( '<span class="guest-error">' + v + '</span>' );
							}
						} );
					} else {
						window.location.replace( guestInsert.redirect );
					}
				}
			} );
		},

		addGuest: function ( event ) {
			event.preventDefault();

			guestList = $( '#guest-list' ).serialize();
			guestGroup = $( this ).attr( 'data-group' );

			$.ajax( {
				type: 'post',
				url: FX.ajaxurl,
				data: {
					action: 'add_guest_item',
					guest_list: guestList,
					guest_group: guestGroup
				},
				success: function ( results ) {
					var guestInsert = JSON.parse( results );
					$( guestInsert.target + ' .js-add-guest' ).before( guestInsert.html );
					if ( $( '.js-itin-save-status' ).length > 0 ) {
						window.onbeforeunload = null;
						$( '.js-itin-save-status' ).slideDown();
					}
				}
			} );
		},

		userAddGuest: function ( event ) {
			event.preventDefault();

			guestList = $( '#guest-list' ).serialize();

			$.ajax( {
				type: 'post',
				url: FX.ajaxurl,
				data: {
					action: 'user_add_guest_item',
					guest_list: guestList
				},
				success: function ( results ) {
					var guestInsert = JSON.parse( results );
					$( '.js-guest-insert' ).before( guestInsert.html );
					$( '.js-guest-insert' ).prev().find( 'select' ).selectric( {
						arrowButtonMarkup: '<b class="button"></b>'
					} );
				}
			} );

		},

		userDeleteGuest: function ( event ) {
			event.preventDefault();

			targetDelete = $( this ).closest( '.guest-list-item' );

			setTimeout( function () {
				$( targetDelete ).closest( '.guest-list-item' ).remove();
			}, 500 );
		},

		deleteGuest: function ( event ) {
			event.preventDefault();
			var groupDelete = $( this ).closest( '.js-accordion-wrapper' ).data( 'group' ),
					guestDelete = $( this ).attr( 'data-guest' );

			$( '.js-delete-confirm-single-guest' ).attr( 'data-groupdelete', groupDelete ).attr( 'data-guestdelete', guestDelete );
			$( '.js-popup-delete-single-guest' ).fadeIn();

		},

		deleteCancel: function ( event ) {
			event.preventDefault();
			$( '.js-popup-delete-guest, .js-popup-delete-single-guest, .js-popup-change-onsite-status' ).fadeOut();
		},

		delete: function ( event ) {
			event.preventDefault();

			var groupDelete = $( this ).closest( '.js-accordion-wrapper' ).data( 'group' );
			$( '.js-delete-confirm-guest' ).attr( 'data-groupdelete', groupDelete );
			$( '.js-popup-delete-guest' ).fadeIn();
		},

		confirmDelete: function ( event ) {
			event.preventDefault();

			targetGroup = $( this ).attr( 'data-groupdelete' );
			setTimeout( function () {
				$( '.js-group-' + targetGroup ).remove();
			}, 500 );
			$( '.js-popup-delete-guest' ).fadeOut();
			if ( $( '.js-itin-save-status' ).length > 0 ) {
				window.onbeforeunload = null;
				$( '.js-itin-save-status' ).slideDown();
			}
		},

		confirmDeleteGuest: function ( event ) {
			event.preventDefault();

			targetGroup = $( this ).attr( 'data-groupdelete' ),
					targetGuest = $( this ).attr( 'data-guestdelete' );

			setTimeout( function () {
				$( '.js-group-' + targetGroup + ' .js-guest-' + targetGuest ).remove();
			}, 500 );
			$( '.js-popup-delete-single-guest' ).fadeOut();
			if ( $( '.js-itin-save-status' ).length > 0 ) {
				window.onbeforeunload = null;
				$( '.js-itin-save-status' ).slideDown();
			}
		},

		statusUpdate: function ( event ) {
			if ( $( '.js-itin-save-status' ).length > 0 ) {
				window.onbeforeunload = function () {
					return true;
				};
				$( '.js-itin-save-status' ).slideDown();
			}
		},

		maybeShowOtherDietaryRestrictions: function () {
			// const isOtherChecked = $('.guest-dietary-restrictions-checkboxes').find('input[value="Other"]').closest('.icheckbox').hasClass('checked');
			const $otherTextArea = $( '.guest-dietary-restrictions-other-textarea' );
			$otherTextArea.toggleClass( 'gfield_visibility_hidden' );
		},

	};
	
	FXUP.FormItinerary = {

		init: function () {
			this.bind();
			const tripDatesInput = document.querySelector( '#trip-dates' );
			if ( null !== tripDatesInput ) {
				this.createLitepicker( tripDatesInput, this.tripDatesOnSelect );
			}
		},

		bind: function () {
			$( '#form-itinerary' ).on( 'submit', this.submit );
		},

		submit: function ( event ) {
			event.preventDefault();
			const form = $( event.target );
			const formData = form.serialize();
			$.ajax( {
				type: 'post',
				url: FX.ajaxurl,
				data: {
					action: 'user_create_new_itinerary',
					form_data: formData,
				},
				success: function ( response ) {
					const parsedResponse = JSON.parse( response );
					// Clear errors
					$( '[data-input-errors]' ).text( '' );
					if ( parsedResponse.hasOwnProperty( 'errors' ) ) {
						const errors = parsedResponse.errors;
						for ( const key in errors ) {
							if ( errors.hasOwnProperty( key ) ) {
								$( `[data-input-errors="${key}"]` ).text( `${errors[key]}` );
								if ( key === 'trip_start_date' || key === 'trip_end_date' ) {
									$( `[data-input-errors="trip_dates"]` ).text( `${errors[key]}` );
								}
							}
						}
					}

					if ( parsedResponse.hasOwnProperty( 'message' ) && 'String' === parsedResponse.message.constructor.name ) {
					}

					if ( parsedResponse.hasOwnProperty( 'redirect' ) && parsedResponse.message.constructor.name ) {
						window.location.replace( parsedResponse.redirect );
					}
				}
			} );
		},

		getFormattedDate: function ( date ) {
			var year = date.getFullYear();

			var month = ( 1 + date.getMonth() ).toString();
			month = month.length > 1 ? month : '0' + month;

			var day = date.getDate().toString();
			day = day.length > 1 ? day : '0' + day;

			return month + '/' + day + '/' + year;
		},

		createLitepicker: function ( textInputElement, callback ) {
			const picker = new Litepicker(
					{
						element: textInputElement,
						format: 'MM-DD-YYYY',
						singleMode: false,
						useResetBtn: true,
						dropdowns: {
							minYear: 2019,
							maxYear: 2030,
							months: true,
							years: true
						},
						onSelect: callback,
					}
			);
		},

		tripDatesOnSelect: function ( date1, date2 ) {
			const formattedDate1 = FXUP.FormItinerary.getFormattedDate( date1 );
			const formattedDate2 = FXUP.FormItinerary.getFormattedDate( date2 );
			$( '#trip-start-date' ).val( formattedDate1 );
			$( '#trip-end-date' ).val( formattedDate2 );
		},
	};
	
	FXUP.Itinerary = {

		init: function () {
			// Make sure this is only using the save logic for the Itinerary edit view, not the other edit views
			itinForm = $( '#itinerary-form' );
			if ( itinForm.length > 0 ) {
				this.bind();
			}
		},

		bind: function () {
			$( '.js-itin-save' ).on( 'click', this.save );
			$( '.js-submit-confirm' ).on( 'click', this.saveSubmit );
			$( '.js-itin-submit' ).on( 'click', this.submit );

			$( 'body' ).on( 'change', '#itinerary-form select:not(.js-jump-dropdown)', FXUP.SharedSaveStatusBar.statusUpdate ); // Once value is selected
			$( 'body' ).on( 'input', '#itinerary-form input, #itinerary-form textarea', FXUP.SharedSaveStatusBar.statusUpdate ); // As soon as input is changed, even if still in focus
			$( 'body' ).on( 'ifChanged', FXUP.SharedSaveStatusBar.statusUpdate ); // For iCheck boxes

			$( '.js-close-status' ).on( 'click touch', this.closeStatus );
			$( '.js-add-itinerary-item' ).on( 'click', this.displayPopup );
			$( 'body' ).on( 'click', '.js-itin-popup-close', this.closePopup );
			$( '.js-popup-exit' ).on( 'click', this.exitPopup );
			$( 'body' ).on( 'change', '.js-total-adults, .js-total-children', this.updatePrice );
			$( document ).on( 'ifChanged', '#fxup-editable-manual-override', this.saveManualOverride );
		},

		isConcierge: function () {
			return ! ! $( 'input[name=concierge-user]' ).val();
		},

		save: function ( event ) {
			event.preventDefault();
			if ( typeof tinyMCE != 'undefined' ) {
				tinyMCE.triggerSave();
			}

			itinForm = $( '#itinerary-form' ).serialize();
			itinUser = $( 'input[name=itin-user]' ).val();
			concierge = $( 'input[name=concierge-user]' ).val();

			$.ajax( {
				type: 'post',
				url: FX.ajaxurl,
				data: {
					action: 'save_itinerary',
					itin_form: itinForm,
					concierge: concierge,
					itin_user: itinUser
				},
				beforeSend: function () {
					$( '.ajax-loading' ).fadeIn();
				},
				success: function () {
					// Remove any hash
					let newLocation = window.location.href.replace( window.location.hash, '' );
					if ( newLocation.charAt( newLocation.length - 1 ) === '#' ) {
						// Remove trailing '#' if still attached.
						newLocation = newLocation.substr( 0, newLocation.length - 1 );
					}
					// $(window).off('beforeunload');
					// Prevent the "Changes may not be saved" dialog
					window.onbeforeunload = null;
					// Reload the page
					//window.location = newLocation;
					$( '.ajax-loading' ).fadeOut();

					setTimeout( function () {
						$( '.js-itin-save-status' ).slideUp();
						$( '.js-itin-save-status-saved' ).slideUp();
						$( '.js-itin-save-status-saved' ).slideDown().delay( 2500 ).slideUp();

					}, 500 );
				}
			} );
		},

		submit: function ( event ) {
			event.preventDefault();

			$( '.js-popup-confirm' ).hide();
			$( '.js-popup-exit' ).hide();
			$( '.js-popup-submit-confirm' ).fadeIn();
		},

		saveSubmit: function ( event ) {
			event.preventDefault();

			itinForm = $( '#itinerary-form' ).serialize();
			itinUser = $( 'input[name=itin-user]' ).val();


			$.ajax( {
				type: 'post',
				url: FX.ajaxurl,
				data: {
					action: 'save_submit_itinerary',
					itin_form: itinForm,
					itin_user: itinUser,
				},
				beforeSend: function () {
					$( '.ajax-loading' ).fadeIn();
				},
				success: function () {
					$( '.ajax-loading' ).fadeOut();
					$( '.js-popup-submit-confirm' ).hide();
					$( '.js-popup-confirm' ).hide();
					$( '.js-popup-exit' ).hide();
					if ( $( '.js-itin-save-status' ).length > 0 ) {
						window.onbeforeunload = null;
						$( '.js-itin-save-status' ).slideUp();
					}
					if ( $( '.js-itin-save-status-saved' ).length > 0 ) {
						window.onbeforeunload = null;
						$( '.js-itin-save-status-saved' ).slideUp();
					}
					$( '.js-itin-save-status-submitted' ).slideDown().delay( 2500 ).slideUp();
					$( '.itinerary-status' ).html( '<h5>Itinerary Status: Pending Concierge Approval<i class="itin-tooltip far fa-question-circle" title="Your itinerary is Pending Concierge Approval"></i></h5>' );
					$( '.itin-tooltip-text .tooltip' ).text( 'We are taking a look and will confirm everything shortly!' );
					$( '.js-itin-submit i' ).removeClass( 'fa-file-import' ).addClass( 'fa-check' );
					// $('.js-popup-submit-confirmed').fadeIn()
					FXUP.Itinerary.updateActivityApprovalStatus();
					FXUP.Itinerary.updateActivityBookedStatus();
					FXUP.Itinerary.updatePrice();
				}
			} );
		},

		saveManualOverride: function ( event ) {
			var itin = $( '[name="itinerary_id"]' ).val();
			$.ajax( {
				type: 'post',
				url: FX.ajaxurl,
				data: {
					action: 'save_manual_override',
					itin: itin
				},
				beforeSend: function () {
					$( '.ajax-loading' ).fadeIn();
				},
				success: function () {
					$( '.ajax-loading' ).fadeOut();
					window.onbeforeunload = null;
					setTimeout( () => {
						window.location.reload();
					}, 100 );
				}
			} );
		},

		closeStatus: function ( event ) {
			$( this ).closest( '.itin-save-status' ).slideUp();
		},

		displayPopup: function ( event ) {
			event.preventDefault();
			$( '.itin-add-popup' ).fadeIn();
			// Grab the TripDay identifier attribute from the element that was clicked
			var day = $( this ).attr( 'data-day' ); // The clicked element on the popup has a data attribute for the day to add this activity to.
			FXUP.Activities.setTripDay( day );
		},

		closePopup: function ( event ) {
			event.preventDefault();
			$( this ).closest( '.popup-confirm-wrapper' ).fadeOut();
		},

		exitPopup: function ( event ) {
			if ( $( '.js-itin-save-status' ).length > 0 ) {
				window.onbeforeunload = null;
				$( '.js-itin-save-status' ).slideUp();
			}
		},

		updatePrice: function ( event ) {
			// Only do this if user is concierge
			if ( '1' === $( 'input[name="concierge-user"]' ).val() ) {

				// Get all activities that are booked
				const $checkedBookedCheckboxes = $( '.activity-booked-checkbox:checked' );
				const $bookedActivityAccordions = $checkedBookedCheckboxes.closest( '.activity-accordion' ); // Accordions for booked activities
				// Get the final costs for those activities
				const $exactFinalCostInputs = $bookedActivityAccordions.find( '.exact-final-cost-input' );

				// Sum up the final costs for each activity
				let summedAmountInteger = 0;
				$exactFinalCostInputs.each( function ( index ) {
					// Final Cost value
					const inputValue = $( this ).val();
					if ( ! isNaN( inputValue ) ) {
						const inputValueInteger = Math.round( inputValue * 100 );
						summedAmountInteger = Math.round( summedAmountInteger + inputValueInteger ); // Sum
					}
				} );
				// Convert back to fixed decimal
				let summedFixedDecimal = ( summedAmountInteger / 100 ).toFixed( 2 );

				// Update .js-itinerary-price-value innerHTML with the sum
				$( '.js-itinerary-price-value' ).html( summedFixedDecimal );

				// Get all activities that are not booked
				const $uncheckedBookedCheckboxes = $( '.activity-booked-checkbox:not(:checked)' );

				if ( $uncheckedBookedCheckboxes && $uncheckedBookedCheckboxes.length > 0 ) {
					// If any activities are not booked, set .activity-total.estimated-total to display and .activity-total.final-total to display none
					$( '.activity-total.estimated-total' ).show();
					$( '.activity-total.final-total' ).hide();
				} else {
					// Else set .activity-total.estimated-total to display none and .activity-total.final-total to display
					$( '.activity-total.final-total' ).show();
					$( '.activity-total.estimated-total' ).hide();
				}

			}

		},

		updateActivityApprovalStatus: function () {
			// Find all checked activity approvals
			const $checkedActivityApprovals = $( '.activity-approval-confirm:checked' ).closest( '.activity-approval-warning' );
			// Set their related .activity-approval-already-confirm input value to '1'
			$checkedActivityApprovals.find( 'input.activity-approval-already-confirm' ).val( '1' );
			// Make sure their message is the confirmed message
			$checkedActivityApprovals.find( '.activity-approval-warning-message' ).hide();
			$checkedActivityApprovals.find( '.activity-approval-confirmed-message' ).show();
			// If user is not concierge
			if ( '1' !== $( 'input[name="concierge-user"]' ).val() ) {
				// Hide the checkbox input
				$checkedActivityApprovals.find( '.activity-approval-confirm-container' ).hide();
			}
			// Find all unchecked activity approvals
			const $uncheckedActivityApprovals = $( '.activity-approval-confirm:not(:checked)' ).closest( '.activity-approval-warning' );
			// Do NOT change their .activity-approval-already-confirm input value
			// Make sure their message is the warning message
			$uncheckedActivityApprovals.find( '.activity-approval-confirmed-message' ).hide();
			$uncheckedActivityApprovals.find( '.activity-approval-warning-message' ).show();
		},

		updateActivityBookedStatus: function () {
			// Only do this if user is concierge...
			if ( '1' === $( 'input[name="concierge-user"]' ).val() ) {
				// Find all checked booking boxes
				const $checkedBookedCheckboxes = $( '.activity-booked-checkbox:checked' );
				// Get their related activities
				const $bookedActivityAccordions = $checkedBookedCheckboxes.closest( '.activity-accordion' );
				$bookedActivityAccordions.each( function ( index ) {
					$accordion = $( this );
					// Set the memo in the activity heading to display
					$accordion.find( '.activity-booked-memo' ).show();
					// If Time Booked is set, set .exact-time-booked-value
					const $exactTimeBookedSelect = $accordion.find( '.exact-time-booked-select' );
					const $exactTimeBookedSpan = $accordion.find( '.exact-time-booked-memo-value' );
					if ( $exactTimeBookedSelect && $exactTimeBookedSpan ) {
						const exactTimeBookedValue = $exactTimeBookedSelect.val();
						if ( exactTimeBookedValue && exactTimeBookedValue !== 'Select a time' ) {
							$exactTimeBookedSpan.html( exactTimeBookedValue );
							$exactTimeBookedSpan.closest( '.exact-time-booked-memo' ).show();
						} else {
							$exactTimeBookedSpan.closest( '.exact-time-booked-memo' ).hide();
						}
					}
					// If Exact Final Cost is set, set .exact-final-cost-value
					const $exactFinalCostInput = $accordion.find( '.exact-final-cost-input' );
					const $exactFinalCostSpan = $accordion.find( '.exact-final-cost-memo-value' );
					if ( $exactFinalCostInput && $exactFinalCostSpan ) {
						const exactFinalCostValue = $exactFinalCostInput.val();
						if ( exactFinalCostValue ) {
							$exactFinalCostSpan.html( exactFinalCostValue );
							$exactFinalCostSpan.closest( '.exact-final-cost-memo' ).show();
						} else {
							$exactFinalCostSpan.closest( '.exact-final-cost-memo' ).hide();
						}
					}
					// Set their related info container to display
					$accordion.find( '.activity-booked-info' ).slideDown();
				} );

				// Find all unchecked booking boxes
				const $uncheckedBookedCheckboxes = $( '.activity-booked-checkbox:not(:checked)' );
				// Get their related activities
				const $notBookedActivityAccordions = $uncheckedBookedCheckboxes.closest( '.activity-accordion' );
				$notBookedActivityAccordions.each( function ( index ) {
					$accordion = $( this );
					// Hide the memo in the activity heading
					$accordion.find( '.activity-booked-memo' ).hide();
					// Hide their related info container
					$accordion.find( '.activity-booked-info' ).slideUp();
				} );

			}
		},
	};
	
	FXUP.Notifications = {
		init() {
			if ( $( '#edit-notification-settings' ).length ) {
				this.bind();
			}
		},

		bind() {
			$( document ).on( 'submit', '#edit-notification-settings', $.proxy( this.saveSettings, this ) );
			$( document ).on( 'click', '.js-notification-settings', $.proxy( this.openSettings, this ) );
			$( document ).on( 'click', '.js-itin-popup-close', this.closeSettings );
		},

		saveSettings( event ) {
			event.preventDefault();

			const notificationSettings = $( '#edit-notification-settings' ).serialize();

			$.ajax( {
				type: 'post',
				url: FX.ajaxurl,
				data: {
					action: 'save_notification_settings',
					notification_settings: notificationSettings
				},
				beforeSend: function () {
					$( '.ajax-loading' ).fadeIn();
				},
				success: function () {
					// Reload the page
					$( '.ajax-loading' ).fadeOut();
					$( '.js-popup-notification-settings' ).fadeOut();
					$( '.js-itin-save-status.unsaved' ).slideUp();
					$( '#edit-notification-settings' ).data( 'dirty', false );

					setTimeout( function () {
						$( '.js-itin-save-status-saved' ).slideDown().delay( 2500 ).slideUp();
					}, 500 );
				}
			} );

			return false;
		},

		openSettings( event ) {
			event.preventDefault();
			$( '.js-popup-notification-settings' ).fadeIn();
		},

		closeSettings( event ) {
			event.preventDefault();
			$( this ).closest( '.popup-confirm-wrapper' ).fadeOut();
		},
	};

	FXUP.Transportation = {

		tables: '',

		init() {
			// Make sure this is only using the save logic for the Transportation edit view, not the other edit views
			const transportForm = $( '#transport-form' );
			if ( transportForm.length > 0 ) {
				this.bind();
				this.initTable();
				FXUP.General.initTooltips();
				// Refresh missing list
				this.refreshMissingList( 'arrival' );
				this.refreshMissingList( 'departure' );
			}
		},

		bind() {
			$( document ).on( 'click', '.js-transport-save', this.save );
			$( document ).on( 'click', '.js-transport-regenerate', this.regenerate );
			$( document ).on( 'click', '.js-regenerate-confirm', this.regenerateConfirm );
			$( document ).on( 'click', '.js-itin-popup-close', this.closePopup );
			$( document ).on( 'click', '.js-transport-add', this.addEmptyTransport );
			$( document ).on( 'click', '.js-transport-remove', this.removeTransport );
			$( document ).on( 'click', '.js-remove-transport-confirm', $.proxy( this.removeConfirm, this ) );
			$( document ).on( 'click', '.js-share-transportation', $.proxy( this.openShareDialog, this ) );
			$( document ).on( 'click', '.js-share-transportation-popup-close', this.closeShareDialog );
			$( document ).on( 'keyup change', '.js-transport-status', $.proxy( this.updateFieldValue, this ) );
			$( document ).on( 'keyup change', '.js-transport-date', $.proxy( this.updateFieldValue, this ) );
			$( document ).on( 'keyup change', '.js-transport-company', $.proxy( this.updateFieldValue, this ) );
			$( document ).on( 'keyup change', '.js-transport-pickup-time', $.proxy( this.updateFieldValue, this ) );
			$( document ).on( 'keyup change', '.js-transport-pickup-location', $.proxy( this.updateFieldValue, this ) );
			$( document ).on( 'keyup change', '.js-transport-dropoff-location', $.proxy( this.updateFieldValue, this ) );
			$( document ).on( 'keyup change', '.js-transport-mode', $.proxy( this.updateFieldValue, this ) );
			$( document ).on( 'keyup change', '.js-transport-notes', $.proxy( this.updateFieldValue, this ) );
			$( document ).on( 'keyup change', '.js-transport-cost', $.proxy( this.updateFieldValue, this ) );
			$( document ).on( 'click', '.js-transport-remove-guest', $.proxy( this.removeGuest, this ) );
			$( document ).on( 'change', '.js-transport-add-guest', $.proxy( this.addGuest, this ) );
			$( document ).on( 'submit', '#share-transportation', $.proxy( this.generateShareLink, this ) );
			$( document ).on( 'change', '#share-transportation select', $.proxy( this.clearShareLink, this ) );
		},

		isConcierge() {
			return ! ! $( 'input[name=concierge-user]' ).val();
		},

		initTable() {
			this.tables = $( '[id^=transport_list]' ).DataTable( {
				paging: false,
				order: [ ],
				responsive: true
			} );
		},

		generateShareLink( event ) {
			event.preventDefault();
			const shareTransportForm = $( '#share-transportation :input[value!=""]' ).serialize();
			const link = fxupData.transportationSummaryLink + '&' + shareTransportForm;
			$( '.js-share-transportation-link' ).val( link );
			$( '.js-share-transportation-link-wrap' ).fadeIn();
		},

		clearShareLink( event ) {
			event.preventDefault();
			$( '.js-share-transportation-link' ).val( '' );
			$( '.js-copy-value' ).text( 'copy' );
		},

		openShareDialog( event ) {
			event.preventDefault();
			$( '.js-popup-share-transportation' ).fadeIn();
		},

		closeShareDialog( event ) {
			event.preventDefault();
			$( this ).closest( '.popup-confirm-wrapper' ).fadeOut();
		},

		save( event ) {
			event.preventDefault();

			const transportForm = $( '#transport-form' ).serialize();
			const itinUser = $( 'input[name=itin-user]' ).val();
			const currentUser = $( 'input[name=current_user]' ).val();
			const concierge = $( 'input[name=concierge-user]' ).val();

			$.ajax( {
				type: 'post',
				url: FX.ajaxurl,
				data: {
					action: 'save_transportation',
					transport_form: transportForm,
					concierge: concierge,
					itin_user: itinUser,
					current_user: currentUser
				},
				beforeSend: function () {
					$( '.ajax-loading' ).fadeIn();
				},
				success: function () {
					// Reload the page
					$( '.ajax-loading' ).fadeOut();

					setTimeout( function () {
						$( '.js-itin-save-status-saved' ).slideDown().delay( 2500 ).slideUp();
					}, 500 );
				}
			} );
		},

		regenerate( event ) {
			event.preventDefault();
			$( '.js-popup-regenerate-confirm' ).fadeIn();
		},

		regenerateConfirm( event ) {
			event.preventDefault();

			const transportForm = $( '#transport-form' ).serialize();
			const itinUser = $( 'input[name=itin-user]' ).val();
			const concierge = $( 'input[name=concierge-user]' ).val();

			$.ajax( {
				type: 'post',
				url: FX.ajaxurl,
				data: {
					action: 'regenerate_transportation',
					transport_form: transportForm,
					concierge: concierge,
					itin_user: itinUser
				},
				beforeSend() {
					$( '.ajax-loading' ).fadeIn();
				},
				success( response ) {
					// Reload the page
					$( '.ajax-loading' ).fadeOut();

					// Insert updated markup
					const results = response;
					const markup = results.form_markup;
					$( '#transportation-form-content' ).html( markup );

					// refresh js elements
					FXUP.Transportation.refreshElements();

					// Refresh missing list
					FXUP.Transportation.refreshMissingList( 'arrival' );
					FXUP.Transportation.refreshMissingList( 'departure' );

					setTimeout( function () {
						$( '.js-popup-regenerate-confirm' ).fadeOut();
						$( '.js-itin-save-status-saved' ).slideDown().delay( 2500 ).slideUp();
					}, 500 );
				}
			} );
		},

		addEmptyTransport( event ) {
			event.preventDefault();
			const button = $( event.currentTarget );
			const type = button.data( 'type' );
			const lastTransport = $( '.js-transport-wrapper[data-type="' + type + '"]' ).last();
			const index = lastTransport.data( 'row' );
			const itinerary = $( 'input[name=itinerary_id]' ).val();
			const itinUser = $( 'input[name=itin-user]' ).val();
			const concierge = $( 'input[name=concierge-user]' ).val();

			$.ajax( {
				type: 'post',
				url: FX.ajaxurl,
				data: {
					action: 'add_transport',
					transport_type: type,
					concierge: concierge,
					itinerary: itinerary,
					index: index,
					itin_user: itinUser
				},
				beforeSend() {
					$( '.ajax-loading' ).fadeIn();
				},
				success( response ) {
					// Reload the page
					$( '.ajax-loading' ).fadeOut();

					// Update the view
					lastTransport.after( response );

					// Refresh missing list
					FXUP.Transportation.refreshMissingList( 'arrival' );
					FXUP.Transportation.refreshMissingList( 'departure' );

					// refresh js elements
					FXUP.Transportation.refreshElements();
				}
			} );
		},

		removeTransport( event ) {
			event.preventDefault();
			const button = $( event.currentTarget );
			const type = button.attr( 'data-type' );
			const row = button.attr( 'data-row' );
			$( '.js-popup-remove-transport-confirm' ).fadeIn().find( '.js-remove-transport-confirm' )
					.attr( 'data-type', type )
					.attr( 'data-row', row )
					.data( 'type', type )
					.data( 'row', row );
		},

		removeConfirm( event ) {
			event.preventDefault();
			const button = $( event.currentTarget );
			const type = button.attr( 'data-type' );
			const row = button.attr( 'data-row' );
			const toDelete = JSON.parse( $( 'input[name="to_delete"]' ).val() );
			const transportDataInput = this.getTransportInput( button );
			let transportData = JSON.parse( transportDataInput.val() );

			if ( ! toDelete[ type ].includes( row ) ) {
				toDelete[ type ].push( row );
			}

			$( 'input[name="to_delete"]' ).val( JSON.stringify( toDelete ) );

			setTimeout( function () {
				$( '.js-popup-remove-transport-confirm' ).fadeOut();
			}, 500 );

			transportData.guests.forEach( ( guest_id ) => {
				let Guest = this.getGuest( guest_id, type, row );
				this.addToMissingList( Guest, type );
			} );

			$( '.js-transport-wrapper[data-type="' + type + '"][data-row="' + row + '"]' ).remove();
		},

		updateFieldValue( event ) {
			event.preventDefault();
			const input = $( event.target );
			const key = input.data( 'field' );
			const transportDataInput = this.getTransportInput( input );
			let transportData = JSON.parse( transportDataInput.val() );

			// update the value
			transportData[ key ] = input.val();

			// store the value
			transportDataInput.val( JSON.stringify( transportData ) );
		},

		addGuest( event ) {
			event.preventDefault();
			const input = $( event.currentTarget );
			const transportDataInput = this.getTransportInput( input );
			const Guest = this.getGuest( input.val(), input.data( 'type' ), input.data( 'row' ) );
			let transportData = JSON.parse( transportDataInput.val() );

			// update the value
			transportData.guests.push( parseInt( input.val() ) );

			// store the value
			transportDataInput.val( JSON.stringify( transportData ) );

			// update table
			const tableID = input.data( 'table' );
			let rowNode = this.tables.table( '#' + tableID ).row.add( this.toGuestTable( Guest, input.data( 'type' ) ) ).draw( false ).node();
			$( rowNode )
					.attr( 'data-row-guest-id', Guest.ID )
					.attr( 'data-type', input.data( 'type' ) )
					.attr( 'data-row', input.data( 'row' ) )
					.attr( 'data-table', input.data( 'table' ) )
					.find( 'td' ).last().addClass( 'text-center' );

			this.adjustGuestCount( transportData.guests.length, input.data( 'type' ), input.data( 'row' ) );

			// update add guest select box
			this.removeFromMissingList( Guest, input.data( 'type' ) );
		},

		removeGuest( event ) {
			event.preventDefault();
			const button = $( event.currentTarget );
			const tableRow = button.closest( 'tr' );
			const transportDataInput = this.getTransportInput( tableRow );
			const Guest = this.getGuest( tableRow.data( 'row-guest-id' ), tableRow.data( 'type' ), tableRow.data( 'row' ) );
			let transportData = JSON.parse( transportDataInput.val() );

			// update the value
			transportData.guests = transportData.guests.filter( ( guestID ) => guestID != Guest.ID );

			// store the value
			transportDataInput.val( JSON.stringify( transportData ) );

			// update table
			const tableID = tableRow.data( 'table' );
			this.tables.table( '#' + tableID ).row( button.parents( 'tr' ) )
					.remove()
					.draw( false );

			// update add guest select box
			this.addToMissingList( Guest, tableRow.data( 'type' ) );

			this.adjustGuestCount( transportData.guests.length, tableRow.data( 'type' ), tableRow.data( 'row' ) );
		},

		getTransportInput( el ) {
			const type = el.data( 'type' );
			const row = el.data( 'row' );

			return $( '[name="transport_' + type + '[]"][data-row="' + row + '"]' );
		},

		getGuest( ID, type, row ) {
			let Guest = null;
			const groups = Array.isArray( FXUPTransportGuests[ type ] ) ? FXUPTransportGuests[ type ] : Object.values( FXUPTransportGuests[ type ] );
			groups.every( ( group ) => {
				Guest = group.find( ( guest ) => guest.ID == ID );
				if ( Guest ) {
					return false;
				}
				return true;
			} );
			if ( typeof Guest == 'undefined' || Guest == null ) {
				Guest = FXUPTransportGuests.missing.arrival.find( ( guest ) => guest.ID == ID );
			}
			if ( typeof Guest == 'undefined' || Guest == null ) {
				Guest = FXUPTransportGuests.missing.departure.find( ( guest ) => guest.ID == ID );
			}
			return Guest;
		},

		addToMissingList( Guest, type ) {
			let missing = FXUPTransportGuests.missing[type];
			missing.push( Guest );
			FXUPTransportGuests.missing[type] = missing;

			this.refreshMissingList( type );
		},

		removeFromMissingList( Guest, type ) {
			let missing = FXUPTransportGuests.missing[type];
			missing = missing.filter( ( g ) => {
				return g.ID != Guest.ID;
			} );
			FXUPTransportGuests.missing[type] = missing;

			this.refreshMissingList( type );
		},

		refreshMissingList( type ) {
			if ( typeof FXUPTransportGuests.missing === 'undefined' ) {
				FXUPTransportGuests.missing = {
					arrival: [ ],
					departure: [ ]
				};
			}

			$( '.js-transport-add-guest[data-type="' + type + '"]' ).each( function () {
				$( this ).empty();
				$( this ).append( '<option value="-1">Select a guest to add</option>' );
				if ( FXUPTransportGuests.missing[type].length ) {
					FXUPTransportGuests.missing[type].forEach( ( Guest ) => {
						$( this ).append( '<option value="' + Guest.ID + '">' + Guest.full_name + '</option>' );
					} );
				}
				$( this ).selectric( 'refresh' );
			} );
		},

		toGuestTable( Guest, type ) {
			const flightGetter = type + '_flight';
			const timeGetter = type + '_time';
			return [
				Guest.first_name,
				Guest.last_name,
				Guest[ flightGetter ],
				Guest[ timeGetter ],
				Guest.children,
				Guest.villa_name,
				this.removeButtonHTML
			];
		},

		adjustGuestCount( count, type, row ) {
			const countContainer = $( '.js-transport-wrapper[data-type="' + type + '"][data-row="' + row + '"]' ).find( '.js-transport-count' );
			const newCount = count == 1 ? count + ' Guest' : count + ' Guests';

			countContainer.text( newCount );
		},

		removeButtonHTML() {
			return '<span class="field-value"><button class="js-transport-remove-guest"><i class="fas fa-times-circle"></i></button></span>';
		},

		closePopup: function ( event ) {
			event.preventDefault();
			$( this ).closest( '.popup-confirm-wrapper' ).fadeOut();
		},

		refreshElements() {
			this.refreshTables();
			this.refreshSelectric();
			FXUP.General.initTooltips();
		},

		refreshTables() {
			this.tables.destroy();
			this.initTable();
		},

		refreshSelectric() {
			$( 'select' ).selectric( {
				arrowButtonMarkup: '<b class="button"></b>'
			} ).selectric( 'refresh' );
		}
	};

	FXUP.Summary = {

		init() {
			this.bind();
		},

		bind() {
			$( document ).on( 'click', '.js-share-summary', $.proxy( this.shareSummary, this ) );
			$( document ).on( 'click', '.js-share-summary-confirm', $.proxy( this.shareSummaryConfirm, this ) );
			$( document ).on( 'click', '.js-itin-popup-close', this.closePopup );
			$( document ).on( 'click', '.js-copy-value', this.copySummaryLink );
		},

		shareSummary( event ) {
			event.preventDefault();
			$( '.js-popup-share-summary' ).fadeIn();
		},

		shareSummaryConfirm( event ) {
			event.preventDefault();
			const itin = $( 'input[name="itinerary_id"]' ).val();

			$.ajax( {
				type: 'post',
				url: FX.ajaxurl,
				data: {
					action: 'share_summary',
					itin: itin
				},
				beforeSend() {
					$( '.ajax-loading' ).fadeIn();
				},
				success( response ) {
					// Reload the page
					$( '.ajax-loading' ).fadeOut();
					$( '.js-popup-share-summary' ).fadeOut();

					setTimeout( function () {
						$( '.js-popup-share-summary-confirmed' ).fadeIn();
					}, 500 );
				}
			} );
		},

		closePopup( event ) {
			event.preventDefault();
			$( this ).closest( '.popup-confirm-wrapper' ).fadeOut();
		},

		copySummaryLink( event ) {
			const link = $( this ).closest( '.input-group' ).find( 'input' ).val();
			const result = FXUP.Summary.copyToClipboard( link );
			if ( result ) {
				$( this ).text( 'Copied' );
			}
		},

		copyToClipboard( str ) {
			if ( navigator && navigator.clipboard && navigator.clipboard.writeText )
				return navigator.clipboard.writeText( str );
			return false;
		}
	};

	FXUP.General = {

		init() {
			this.initTooltips();
		},

		initTooltips() {
			$( document ).on( 'hover touch', '.itin-tooltip', function ( event ) {
				$( this ).find( '.itin-tooltip-text' ).toggleClass( 'hidden' );
			} );
		}
	};
	
	FXUP.Activities = {
		tripDay: null,
		itineraryPostIDElement: null,
		itineraryPostID: null,
		$popupContainer: null,
		$dayWrappers: null,
		$flyoutsContainer: null,
		flyoutTypesAdded: null,
		init: function () {
			this.bind();
		},

		bind: function () {
			this.itineraryPostIDElement = document.querySelector( 'input[name="itinerary_id"]' );
			this.itineraryPostID = this.itineraryPostIDElement ? this.itineraryPostIDElement.value : null;
			this.$dayWrappers = $( '.itinerary-day-wrapper' );
			this.$popupContainer = $( '.itin-add-popup' );
			this.$flyoutsContainer = $( '#flyouts-container' );
			// Loaded on page load
			this.$popupContainer.find( '.js-add-activity' ).on( 'click', this.addActivity );
			this.$popupContainer.find( '.js-add-service' ).on( 'click', this.addService );
			this.$popupContainer.find( '.js-add-wedding' ).on( 'click', this.addWedding );
			this.$popupContainer.find( '.js-add-activity-item' ).on( 'click', this.activityDayOptions ); // Add to Itinerary - (Custom, Celebration). Some are async (Post Types), others are loaded on page load (Custom, Celebration).
			this.$dayWrappers.each( this.updateTitleIndex );

			// Async
			this.$flyoutsContainer.on( 'click', '.js-add-activity-item', this.activityDayOptions ); // Add to Itinerary - Post Types. Some are async (Post Types), others are loaded on page load (Custom, Celebration).
			this.$flyoutsContainer.on( 'click', '.activity-toggle', this.activityToggleDescription ); // View Activity Details
			this.$flyoutsContainer.on( 'change', '.js-act-filter', this.activityFilter ); // Filter By Category
			//  $(".js-toggle-addclass").on( 'click', this.addClass); // MAY NEED TO DELEGATE THIS - the listener in main.js is bound to elements on page load.
			this.$flyoutsContainer.on( 'click', '.flyout-close', function ( event ) {
				$( this ).closest( '.flyout' ).removeClass( 'active' );
			} );

			$( 'body' ).on( 'click', '.js-delete', this.deleteActivity );
			$( 'body' ).on( 'click', '.js-delete-confirm', this.activityConfirmDelete );
			$( 'body' ).on( 'click', '.js-delete-cancel', this.deleteCancel );
			$( 'body' ).on( 'click', '.js-add-activity-confirm', this.addActivityConfirm );
			$( '.activity-message-options' ).on( 'change', this.updateMessage );

			$( 'body' ).on( 'input blur', 'input[name^="custom_activity_title"]', this.updateCustomTitleName );
			$( 'body' ).on( 'change', 'select[name^="activity_time_booked"], select.exact-time-booked-select', this.updateTitleTime );
			$( 'body' ).on( 'ifChanged', '.activity-booked-checkbox', this.updateTitleTime ); // For iCheck boxes
			$( 'body' ).on( 'ifChanged', '.activity-booked-checkbox', this.updateTitleBooked ); // For iCheck boxes

			$( 'body' ).on( 'click', '.js-tab-button', this.toggleTab );
		},

		toggleTab( e ) {
			e.preventDefault();

			const button = $( this );
			const tabId = $( this ).attr( 'data-tab' );
			const group = button.closest( '.js-tab-group' );

			/* toggle active button */
			group.find( '.js-tab-button' ).removeClass( 'active' );
			button.addClass( 'active' );

			/* toggle active tab content */
			group.find( '.tab-content' ).removeClass( 'active' );
			group.find( '.tab-content[data-tab="' + tabId + '"]' ).addClass( 'active' );
		},

		setTripDay( day ) {
			this.tripDay = parseInt( day );
		},

		updateMessage: function ( event ) {
			const $select = $( event.target );
			const optionValue = $select.find( 'option:selected' ).val();
			const $textArea = $select.closest( '.activity-message-options-container' ).next( '.activity-message-container' ).find( 'textarea.activity-message' );
			$textArea.val( optionValue );
		},

		maybeGetFlyout( type ) {
			if ( ! ( this.flyoutTypesAdded instanceof Set ) ) {
				this.flyoutTypesAdded = new Set();
			}
			if ( ! this.flyoutTypesAdded.has( type ) ) {
				this.getFlyout( type );
			}
		},

		getFlyout( type ) {
			$.ajax( {
				type: 'POST',
				url: FX.ajaxurl,
				data: {
					action: 'get_event_type_flyout',
					itinerary_post_id: FXUP.Activities.itineraryPostID,
					event_post_type: type,
				},
				success: function ( response ) {
					const responseJSON = JSON.parse( response );
					let markup = responseJSON.markup;
					FXUP.Activities.appendFlyout( markup );
				},
				// dataType: dataType,
				async: false
			} );
		},

		appendFlyout( markup ) {
			this.$flyoutsContainer.append( markup );
		},

		addActivity: function ( event ) {
			event.preventDefault();
			FXUP.Activities.maybeGetFlyout( 'activity' );
			$( '.itin-add-popup' ).fadeOut();
			$( '#flyout-activities' ).addClass( 'active' );
		},

		addService: function ( event ) {
			event.preventDefault();
			FXUP.Activities.maybeGetFlyout( 'service' );
			$( '.itin-add-popup' ).fadeOut();
			$( '#flyout-services' ).addClass( 'active' );
		},

		addWedding: function ( event ) {
			event.preventDefault();
			FXUP.Activities.maybeGetFlyout( 'wedding' );
			$( '.itin-add-popup' ).fadeOut();
			$( '#flyout-weddings' ).addClass( 'active' );
		},

		activityToggleDescription: function ( event ) {
			event.preventDefault();

			if ( $( this ).hasClass( 'fa-plus' ) ) { // activity is open
				$( '.activity-toggle' ).addClass( 'fa-minus' ).removeClass( 'fa-plus' );
				$( '.js-activity-description' ).slideUp();
				// $(this).closest('.villa-smbtn').find('.js-activity-description').slideToggle();
			} else if ( $( this ).hasClass( 'fa-minus' ) ) {
				$( '.activity-toggle' ).addClass( 'fa-minus' ).removeClass( 'fa-plus' );
				$( this ).removeClass( 'fa-minus' ).addClass( 'fa-plus' );
				$( '.js-activity-description' ).slideUp();
				$( this ).closest( '.villa-smbtn' ).find( '.js-activity-description' ).slideToggle();
			} else {
				$( '.activity-toggle' ).addClass( 'fa-minus' ).removeClass( 'fa-plus' );
				$( this ).addClass( 'fa-plus' );
				$( '.js-activity-description' ).slideUp();
				$( this ).closest( '.villa-smbtn' ).find( '.js-activity-description' ).slideToggle();
			}

		},

		activityFilter: function ( event ) {
			var filterVal = $( this ).find( ':selected' ).val();
			if ( filterVal == 'All' ) {
				$( '.js-activity-row' ).show();
			} else {
				$( '.js-activity-row' ).hide();
				$( '.js-act-cat' + filterVal ).show();
			}
		},

		activityDayOptions: function ( event ) {
			event.preventDefault();
			// debugger;

			// On the popup
			// var day = $(this).attr('data-day'); // The clicked element on the popup has a data attribute for the day to add this activity to.
			$( '.js-add-activity-item' ).removeClass( 'js-day-selected' );

			// On the flyout
			$( '.activity-confirm-days input' ).each( function () {
				$( this ).attr( 'checked', false );
			} );

			if ( FXUP.Activities.tripDay != 0 ) {
				$( '.activity-confirm-days input[value="' + FXUP.Activities.tripDay + '"]' ).each( function () {
					$( this ).attr( 'checked', true );
				} );
				$( '.js-add-activity-item' ).addClass( 'js-day-selected' );
			}

			// The whole js-day-selected thing is for functionality that is not currently live - ability to choose day dynamically.
			if ( $( this ).hasClass( 'js-day-selected' ) ) {
				$( this ).siblings( '.activity-confirm-days' ).find( '.js-add-activity-confirm' ).trigger( 'click', { day: FXUP.Activities.tripDay } );
			} else {
				$( this ).siblings( '.activity-confirm-days' ).slideToggle();
			}
		},

		deleteActivity: function ( event ) {
			event.preventDefault();

			var activityDelete = $( this ).closest( '.js-accordion-wrapper' ).data( 'activity' );
			$( '.js-delete-confirm' ).attr( 'data-actdelete', activityDelete );
			$( '.js-popup-delete' ).fadeIn();
		},

		activityConfirmDelete: function ( event ) {
			event.preventDefault();
			var activityDelete = $( this ).attr( 'data-actdelete' ),
					activityRows = $( '*[data-activity=' + activityDelete + ']' ).closest( '.sec-itinerary-accordion' ).find( '.js-accordion-wrapper' ),
					activityCount = activityRows.size() - 1;

			$( '*[data-activity=' + activityDelete + ']' ).slideUp();
			activityCountInsert = 'No Activities Added Yet';

			if ( activityCount == 1 ) {
				activityCountInsert = activityCount + ' Itinerary Item';
			} else if ( activityCount > 1 ) {
				activityCountInsert = activityCount + ' Itinerary Items';
			}

			const $activityCountMessageContainer = $( '*[data-activity=' + activityDelete + ']' ).closest( '.itinerary-day-wrapper' ).find( '.js-act-count' );

			// If no activities left
			if ( 1 > activityCount ) {
				// Hide the View/Edit Day Details button
				$activityCountMessageContainer.closest( '.itinerary-day-wrapper' ).find( '.js-itinerary-toggle' ).first().hide();
				// Roll up the accordion
				activityRows.closest( '.itinerary-day-activities' ).slideUp();
				// Grey out the activity count if no activities left
				$activityCountMessageContainer.addClass( 'activity-count-no-activities' );
			}

			$activityCountMessageContainer.html( activityCountInsert );

			setTimeout( function () {
				$( '*[data-activity=' + activityDelete + ']' ).remove();
			}, 500 );
			$( '.js-popup-delete' ).fadeOut();



			var activityPrices = $( '.js-total-price' ),
					itineraryTotalPrice = 0;

			$.each( activityPrices, function ( i, v ) {
				costRaw = $( this ).html();
				costFormatted = costRaw.replace( '$', '' );
				itineraryTotalPrice += parseFloat( costFormatted );
			} );

			itineraryPriceInsert = itineraryTotalPrice.toFixed( 2 ).replace( /\d(?=(\d{3})+\.)/g, '$&,' );
			// $('.js-total-itinerary-price').html('$' + itineraryPriceInsert);

			$( '.js-itin-save-status' ).slideDown();
			if ( $( '.js-reload-warning' ).length > 0 ) {
				window.onbeforeunload = function () {
					return true;
				};
				$( '.js-reload-warning' ).show();
			}
		},

		deleteCancel: function ( event ) {
			event.preventDefault();
			$( '.js-popup-delete' ).fadeOut();
		},

		addActivityConfirm: function ( event, data ) {
			event.preventDefault();
			var selectedDays = $( this ).closest( '.activity-confirm-days' ).find( 'input[name="selected_days"]:checked' ),
					selectedValues = [ ],
					pid = $( this ).data( 'pid' ),
					itinUser = $( 'input[name=concierge-user]' ).val(),
					itin = $( 'input[name=itinerary_id]' ).val(),
					action = 'add_activity_days',
					celebration = false;

			// 9.21.22 fix
			if ( selectedDays.length == 0 ) {
				var day = data.day;
				selectedDays = $( this ).closest( '.activity-confirm-days' ).find( 'input[name="selected_days"][value="' + day + '"]' )
			}

			if ( $( this ).data( 'custom' ) == true ) {
				action = 'add_custom_activity_days';
			}

			if ( $( this ).data( 'celebration' ) == true ) {
				celebration = true;
			}


			$.each( selectedDays, function ( i, v ) {

				const selectedVal = $( this ).val();
				const dayActivities = $( '.js-day-' + selectedVal ).find( '.js-accordion-wrapper' );
				let dayActVal = [ ];

				$.each( dayActivities, function () {
					dayActivity = $( this ).find( 'input[name=activity_counter]' ).val();
					dayActVal.push( parseInt( dayActivity ) );
				} );

				selectedValues[i] = {
					day: selectedVal,
					activities: dayActVal
				}
			} );

			$.ajax( {
				type: 'post',
				url: FX.ajaxurl,
				data: {
					action: action,
					pid: pid,
					selected_days: selectedValues,
					user: itinUser,
					itin: itin,
					celebration: celebration
				},
				success: function ( results ) {
					var activityInsert = JSON.parse( results );
					$.each( activityInsert, function () {

						/*                         if( parseInt(this.activity) == 1 ) {
						 $('.js-day-'+this.day).find('.itinerary-day-heading').after(this.html);
						 $('.js-day-'+this.day).find('.js-act-count').html(this.activity + ' Activity');
						 $('.js-day-'+this.day).find('.js-itinerary-toggle').show();
						 $('.js-day-'+this.day).find('.js-add-itinerary-item').appendTo('.js-day-'+this.day+' .sec-itinerary-accordion');                            
						 } else { */
						// const day = $('.js-day-'+this.day);
						$( this.html ).prependTo( '.js-day-' + this.day + ' .sec-itinerary-accordion' ); // Add to the DOM
						const $day = $( '.js-day-' + this.day );
						let activityCount = $day.find( '[data-activity]' ).length;
						let activityCountHTML = '1 Activity';
						if ( activityCount > 1 ) {
							activityCountHTML = activityCount + ' Activities';
						}
						const $activity = $( `[data-activity="day-${this.day}-activity-${this.activity}"]` );
						$( '.js-day-' + this.day ).find( '.js-itinerary-toggle' ).show();
						$( '.js-day-' + this.day ).find( '.js-act-count' ).html( activityCountHTML ).removeClass( 'activity-count-no-activities' );
						// $('.js-day-'+this.day).find('.js-add-itinerary-item').appendTo('.js-day-'+this.day+' .sec-itinerary-accordion');   
						//}
						$( '.js-day-' + this.day ).find( 'select[name=activity_time_booked-day-' + this.day + '-activity-' + this.activity + ']' ).selectric( {
							arrowButtonMarkup: '<b class="button"></b>'
						} );

						$( '.js-day-' + this.day ).find( 'select[name=custom_activity_time-day-' + this.day + '-activity-' + this.activity + ']' ).selectric( {
							arrowButtonMarkup: '<b class="button"></b>'
						} );

						$( '.js-day-' + this.day ).find( 'select[name=exact_time_booked-day-' + this.day + '-activity-' + this.activity + ']' ).selectric( {
							arrowButtonMarkup: '<b class="button"></b>'
						} );

						const $messageOptionsSelect = $activity.find( '.activity-message-options' );
						$messageOptionsSelect.on( 'change', FXUP.Activities.updateMessage );

						$( '.js-day-' + this.day ).find( 'select[name=activity_message_options-day-' + this.day + '-activity-' + this.activity + ']' ).selectric( {
							arrowButtonMarkup: '<b class="button"></b>'
						} );

						$( '.js-day-' + this.day ).find( '.js-guests' ).multiSelect();

						// $('.js-day-'+this.day).find('.js-guests').selectric();

						$( '.js-day-' + this.day + ' input' ).iCheck();

						$( '.js-day-' + this.day ).find( '.itinerary-day-activities' ).slideDown();
						$( '.js-day-' + this.day ).find( '.js-itinerary-toggle i' ).addClass( 'fa-chevron-up' );

						FXUP.Activities.updateTitleIndex( this.day - 1 );

						$( '.itin-add-popup' ).hide();
						$( '.js-itin-save-status' ).slideDown();

						$( '.flyout' ).removeClass( 'active' );
						// reset all flyout fields/accordions
						resetTarget = $( '.js-activity-row' );
						$.each( resetTarget, function () {
							$( this ).find( '.fas' ).not( '.fa-arrow-left, .fa-file-import, .fa-chevron-down, .fa-chevron-up, .fa-book-reader, .fa-exclamation-circle, .please-do-not-turn-me-into-a-math-symbol' ).removeClass( 'fa-minus' ).addClass( 'fa-plus' );
							$( this ).find( '.js-activity-description' ).hide();
							$( this ).find( '.activity-confirm-days' ).hide();


							resetDayTarget = $( this ).find( '.activity-confirm-days input[name=selected_days]' );
							$.each( resetDayTarget, function () {
								$( this ).prop( 'checked', false );
							} );
						} );

					} );

					if ( $( '.js-reload-warning' ).length > 0 ) {
						window.onbeforeunload = function () {
							return true;
						};
						$( '.js-reload-warning' ).show();
					}
				}
			} );
		},
		getWrapper( elem ) {
			$elem = $( elem );
			let $wrapper;
			if ( $elem.hasClass( 'js-accordion-wrapper' ) ) {
				$wrapper = $elem;
			} else {
				$wrapper = $elem.closest( '.js-accordion-wrapper' );
			}
			return $wrapper;
		},
		isBooked( elem ) {
			let isBooked = false;
			$wrapper = FXUP.Activities.getWrapper( elem );
			if ( FXUP.Itinerary.isConcierge() ) {
				let $bookedInput = $wrapper.find( 'input.activity-booked-checkbox' );
				if ( $bookedInput.attr( 'checked' ) ) {
					isBooked = true;
				}
			} else {
				let $bookedSpan = $wrapper.find( 'span.activity-booked.activity-booked-memo' );
				if ( 'none' !== $bookedSpan.css( 'display' ) ) {
					isBooked = true;
				}
			}
			return isBooked;
		},
		getRequestedTime( elem ) {
			let requestedTime;
			$wrapper = FXUP.Activities.getWrapper( elem );
			$input = $wrapper.find( 'select[name^="activity_time_booked"]' );
			if ( $input.val() && $input.val() !== 'Select a time' ) {
				requestedTime = $input.val();
			}
			return requestedTime;
		},
		getBookedTime( elem ) {
			let bookedTime;
			$wrapper = FXUP.Activities.getWrapper( elem );
			// Concierge has access to input, non-concierge does not
			if ( FXUP.Itinerary.isConcierge() ) {
				$input = $wrapper.find( 'select.exact-time-booked-select' );
				if ( $input.val() && $input.val() !== 'Select a time' ) {
					bookedTime = $input.val();
				}
			} else {
				$span = $wrapper.find( '.exact-time-booked-memo-value' );
				if ( $span.text() ) {
					bookedTime = span.text();
				}
			}
			return bookedTime;
		},
		updateCustomTitleName( e ) {
			// As the user types their new title, update the title which displays in the Activity row to match
			let $input = $( this );
			let $wrapper = FXUP.Activities.getWrapper( this );
			let $title = $wrapper.find( '.activity--title-name' );
			$title.text( $input.val() );
		},
		updateTitleTime( e ) {
			let newTime;
			let $wrapper = FXUP.Activities.getWrapper( this );
			let $time = $wrapper.find( '.activity--title-timePrefix' );
			let $timeSeparator = $wrapper.find( '.activity--title-timeSeparator' );
			// Try to find a valid booked time
			if ( FXUP.Activities.isBooked( this ) ) {
				newTime = FXUP.Activities.getBookedTime( $wrapper );
			}
			// If no valid booked time found, try to find a valid requested time
			if ( ! newTime ) {
				newTime = FXUP.Activities.getRequestedTime( $wrapper );
			}
			// Only update the time text if we have a newTime to use
			if ( newTime ) {
				$time.text( newTime );
				$timeSeparator.css( 'display', 'unset' );
			} else {
				$time.text( '' );
				$timeSeparator.css( 'display', 'none' );
			}
		},
		updateTitleBooked( e ) {
			let $wrapper = FXUP.Activities.getWrapper( this );
			let $bookedSpan = $wrapper.find( '.activity-booked-memo' );
			if ( FXUP.Activities.isBooked( $wrapper ) ) {
				$bookedSpan.css( 'display', 'unset' );
			} else {
				$bookedSpan.css( 'display', 'none' );
			}
		},
		updateTitleIndex( i ) {
			const index = i + 1;
			const $day = $( '.js-day-' + index );
			const $wrappers = $day.find( '.js-accordion-wrapper' );
			let titles = [ ];
			$wrappers.each( function () {
				let $title = $( this ).find( '.activity--title-name' );
				$title.removeAttr( 'data-occurrence' );
				let titleText = $title.text();

				titles.push( titleText );

				let occurrences = titles.reduce( function ( acc, curr ) {
					return acc[curr] ? ++ acc[curr] : acc[curr] = 1, acc
				}, { } );
				let titleOccurrence = occurrences.hasOwnProperty( titleText ) ? parseInt( occurrences[titleText] ) : 0;

				if ( titleOccurrence > 1 ) {
					$title.attr( 'data-occurrence', titleOccurrence );
				}
			} )
		}
	};

	FXUP.GroceryList = {
		total: 0,
		init() {
			this.bind();
			this.setDataPriceAttrs();
			this.calculateTotals();
		},
		bind() {
			$( document ).on( 'change keyup', 'td[class*="cell3 "] input,td[class$="cell3"] input', $.proxy( this.calculateLinePrice, this ) );
			$( document ).on( 'gform_page_loaded', this.setDataPriceAttrs );
			$( document ).on( 'gform_page_loaded', this.calculateTotals );
			$( document ).on( 'click', '.gf_step', this.goToStep );
			$( document ).on( 'click', '.js-grocery-search', $.proxy( this.search, this ) );
			$( document ).on( 'keypress', '.js-grocery-search-input', $.proxy( this.maybeSearch, this ) );
		},
		calculateLinePrice( e ) {
			const qtyInput = $( e.target );
			const price = this.getLinePrice( qtyInput );
			const qty = parseFloat( qtyInput.val() );
			const lineTotal = ( price * qty ).toFixed( 2 );
			const priceInput = qtyInput.closest( '.gfield_list_group' ).find( 'td[class*="cell5 "] input,td[class$="cell5"] input' );

			// update input in the view
			priceInput.val( this.formatAsCurrency( lineTotal ) );
			priceInput.attr( 'data-price', lineTotal );

			// calculate all totals
			this.calculateTotals();
		},
		setDataPriceAttrs() {
			$( '.price-field' ).each( function () {
				$( this ).attr( 'data-price', $( this ).val().replace( /\$/, '' ) );
			} );
		},
		calculateTotals() {
			let total = 0;
			$( '.price-field' ).each( function () {
				const price = parseFloat( $( this ).attr( 'data-price' ) );
				if ( price > 0 && ! isNaN( price ) ) {
					total = total + price;
				}
			} );
			this.total = total.toFixed( 2 );

			this.storeTotal();
		},
		storeTotal() {
			const total = this.formatAsCurrency( this.total );
			$( '#estimated-total' ).text( total );
			$( '#input_7_43' ).val( total );
		},
		formatAsCurrency( price ) {
			const dollars = new Intl.NumberFormat( 'en-US', {
				style: 'currency',
				currency: 'USD'
			} ).format( price );

			return isNaN( price ) ? 'N/A' : dollars;
		},
		getLinePrice( input ) {
			const group = this.generateSlug( input.closest( '.gfield' ).find( '.gfield_label' ).text() );
			const item = input.closest( '.gfield_list_group' ).find( 'td[class*="cell1 "] input,td[class$="cell1"] input' ).val();

			return this.priceLookup( group, item );
		},
		priceLookup( group, item ) {
			if ( group in fxupData ) {
				const match = fxupData[ group ].filter( ( line ) => line.item == item );
				if ( match.length ) {
					return parseFloat( match[0].price );
				}
			}
			return 0;
		},
		generateSlug( str ) {
			let slug = str.toLowerCase();
			slug = slug.replace( / and /ig, ' ' );
			slug = slug.replace( / & /ig, ' ' );
			slug = slug.replace( /__/ig, '_' );
			slug = slug.replace( / /ig, '_' );

			return slug;
		},
		goToStep( e ) {
			const prefix = 'gf_step_7_';
			const stepID = $( e.currentTarget ).attr( 'id' );
			const step = stepID.substring( prefix.length );

			$( '#gform_target_page_number_7' ).val( step );
			$( '#gform_7' ).trigger( 'submit', [ true ] );
		},
		search() {
			let match = false;
			const query = $( '.js-grocery-search-input' ).val().toLowerCase();
			$( '.gfield_list_cell input' ).each( ( i, input ) => {
				if ( $( input ).val().toLowerCase().includes( query ) ) {
					$( input ).closest( '.gform_page' ).prev().find( '.gform_next_button' ).click();
					match = true;
					return false;
				}
			} );
			if ( ! match ) {
				alert( 'Not found.' );
			}
		},
		maybeSearch( e ) {
			var keycode = ( e.keyCode ? e.keyCode : e.which );
			if ( keycode == '13' ) {
				this.search();
			}
		}
	};

	FXUP.Tour = {

		id: 'fxup-tour',
		instance: null,

		init() {
			this.instance = new Shepherd.Tour( FXUP_TourConfig );
			this.bind();

			if ( this.shouldStart() ) {
				this.start();
			}
		},

		bind() {
			$( document ).on( 'click', '.js-start-tour', $.proxy( this.start, this ) );
			this.instance.on( 'cancel', $.proxy( this.dismiss, this ) );
			this.instance.on( 'complete', $.proxy( this.dismiss, this ) );
		},

		start( e ) {
			if ( e ) {
				e.preventDefault();
			}
			this.instance.start();
		},

		shouldStart() {
			return false;
//			return ! localStorage.getItem( this.id );
		},

		dismiss() {
			if ( ! localStorage.getItem( this.id ) ) {
				localStorage.setItem( this.id, 'yes' );
			}
		}

	};

	const onDocReady = [
		() => {
			$.exitIntent( 'enable' );
		},
		() => {
			FXUP.GuestList.init();
		},
		() => {
			FXUP.GuestTravel.init();
		},
		() => {
			FXUP.GuestTravelInfo.init();
		},
		() => {
			FXUP.FormItinerary.init();
		},
		() => {
			FXUP.Itinerary.init();
		},
		() => {
			FXUP.General.init();
		},
		() => {
			FXUP.Activities.init();
		},
		() => {
			FXUP.RoomArrangements.init();
		},
		() => {
			FXUP.RoomBookingValidation.init();
		},
		() => {
			FXUP.Notifications.init();
		},
		() => {
			FXUP.Transportation.init();
		},
		() => {
			FXUP.Summary.init();
		},
		() => {
			FXUP.GroceryList.init();
		},
		() => {
			FXUP.Tour.init();
		}
	];

// RECOMMENDED
// Add 1 function to jQuery ready, but that function will iterate through callbacks and move each callback separately to event queue
	$( function () {
		onDocReady.forEach( callback => {
			setTimeout( callback, 0 );
		} );
	} );


	return FXUP;

} )( FXUP || { }, jQuery )
