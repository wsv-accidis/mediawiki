<?php
/*
 * TMEIT Web Services
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class TmeitWsReportEvent extends TmeitWsPostService
{
	const EventIdKey = 'event_id';
	const WorkersKey = 'workers';

	public function __construct()
	{
		parent::__construct();
	}

	protected function processRequest( $params )
	{
		$eventId = (int) @$params[self::EventIdKey];
		if( 0 == $eventId )
			return $this->finishRequest( self::buildMissingParameterError() );

		$event = $this->db->eventGetById( $eventId );
		if( FALSE === $event )
			return $this->finishRequest( self::buildError( 'Event does not exist.', self::HttpNotFound ) );

		$adminRights = $this->db->userGetAdminRightsById( $this->userId );
		if( !$this->db->reportMayEdit( $event, $adminRights['is_admin'], $adminRights['is_admin_of_team'] ) )
			return $this->finishRequest( self::buildError( 'You are not allowed to report this event.', self::HttpForbidden ) );

		$workers = @$params[self::WorkersKey];
		if( FALSE == $workers || !is_array( $workers ) )
			$workers = array();

		$report = array();
		foreach( $workers as $worker )
		{
			$workerId = (int) $worker['id'];
			$report[$workerId] = array( 'multi' => (int) $worker['multi'], 'comment' => $worker['comment'] );
		}

		$this->db->reportCreateOrUpdate( $eventId, $this->userId, $report );
		$this->db->getDatabase()->commit();

		return $this->finishRequest( array( self::SuccessKey => true ) );
	}
}
