<?php

/*
 * TMEIT Web Services
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class TmeitWsGetExternalEventDetails extends TmeitWsGetService
{
	public function __construct()
	{
		parent::__construct();
	}

	protected function processRequest( $params )
	{
		if( 0 == ( $id = (int) $params['subpath'] ) )
			return $this->finishRequest( self::buildError( 'Missing external event ID in URL.', self::HttpBadRequest ) );

		if( FALSE == ( $eventDetails = $this->db->extEventGetById( $id ) ) )
			return $this->finishRequest( self::buildError( 'External event not found', self::HttpNotFound ) );

		$attendees = $this->getAttendees( $eventDetails['attendees'] );
		$currentAttendee = $this->getCurrentAttendee( $eventDetails['attendees'] );
		if( FALSE === $currentAttendee )
			$currentAttendee = $this->db->extEventGetBlankAttendee( $this->userId );

		unset( $eventDetails['attendees'] );
		unset( $eventDetails['logs'] );

		$this->setCacheControl( self::CacheOneMinute );
		return $this->finishRequest( array(
			'event' => $eventDetails,
			'attendee' => $currentAttendee,
			'attendees' => $attendees
		) );
	}

	private function getCurrentAttendee( $attendees )
	{
		if( !isset( $attendees[$this->userId] ) )
			return FALSE;

		return $attendees[$this->userId];
	}

	private function getAttendees( $attendees )
	{
		$userIds = array_keys( $attendees );

		$result = array_values( array_map( function ( $userId ) use ( $attendees )
		{
			$attendee = $attendees[$userId];
			unset( $attendee['notes'] );
			$attendee['id'] = $userId;
			return $attendee;
		}, $userIds ) );

		return $result;
	}
}