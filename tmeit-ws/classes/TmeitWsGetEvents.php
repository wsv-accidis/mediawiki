<?php
/*
 * TMEIT Web Services
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class TmeitWsGetEvents extends TmeitWsGetService
{
	public function __construct()
	{
		parent::__construct();
	}

	protected function processRequest( $params )
	{
		$this->setCacheControl( self::CacheFiveMinutes );
		return $this->finishRequest( array( 'events' => $this->getEvents() ) );
	}

	private function getEvents()
	{
		$events = $this->db->eventGetList();
		return array_map( function ( $eventId ) use ( $events )
		{
			$event = $events[$eventId];
			$event['id'] = $eventId;
			return $event;
		}, array_keys( $events ) );
	}
}
