<?php
/*
 * TMEIT Web Services
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class TmeitWsAttendExternalEvent extends TmeitWsPostService
{
	const AttendingKey = 'attending';
	const DobKey = 'dob';
	const DrinkPrefsKey = 'drink_prefs';
	const EventIdKey = 'event_id';
	const FoodPrefsKey = 'food_prefs';
	const NotesKey = 'notes';

	public function __construct()
	{
		parent::__construct();
	}

	protected function processRequest( $params )
	{
		$eventId = (int) @$params[self::EventIdKey];
		if( 0 == $eventId )
			return $this->finishRequest( self::buildMissingParameterError() );

		$event = $this->db->extEventGetById( $eventId );
		if( FALSE === $event || $event['is_past'] )
			return $this->finishRequest( self::buildError( 'Event does not exist or changes are no longer allowed.', self::HttpNotFound ) );

		$attending = @$params[self::AttendingKey];
		if( FALSE == $attending || !is_array( $attending ) )
		{
			$this->db->extEventUnsetAttendee( $eventId, $this->userId, false );
			$this->db->getDatabase()->commit();

			return $this->finishRequest( array( self::SuccessKey => true ) );
		}

		$dob = @$attending[self::DobKey];
		$drinkPrefs = @$attending[self::DrinkPrefsKey];
		$foodPrefs = @$attending[self::FoodPrefsKey];
		$notes = @$attending[self::NotesKey];

		if( FALSE === ( $dob = TmeitUtil::validateDate( $dob ) ) )
			return $this->finishRequest( self::buildMissingParameterError() );

		$this->db->extEventAddOrUpdateAttendee( $eventId, $this->userId, $dob, $foodPrefs, $drinkPrefs, $notes );
		$this->db->getDatabase()->commit();

		return $this->finishRequest( array( self::SuccessKey => true ) );
	}
}