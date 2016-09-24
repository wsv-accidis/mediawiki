<?php

/*
 * TMEIT Web Services
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class TmeitWsGetEventDetails extends TmeitWsGetService
{
	public function __construct()
	{
		parent::__construct();
	}

	protected function processRequest( $params )
	{
		if( 0 == ( $id = (int) $params['subpath'] ) )
			return $this->finishRequest( self::buildError( 'Missing event ID in URL.', self::HttpBadRequest ) );

		if( FALSE == ( $eventDetails = $this->db->eventGetById( $id ) ) )
			return $this->finishRequest( self::buildError( 'Event not found', self::HttpNotFound ) );

		$user = $this->db->userGetByName( $this->db->userGetUsernameById( $this->userId ) );
		$userTeamAdmin = $user['is_team_admin'] ? $user['team_id'] : 0;
		$eventDetails['may_edit_report'] = $this->db->reportMayEdit( $eventDetails, $user['is_admin'], $userTeamAdmin );

		$workers = $this->getWorkers( $this->db->eventGetWorkersById( $id ) );

		$this->setCacheControl( self::CacheOneMinute );
		return $this->finishRequest( array(
			'event' => $eventDetails,
			'workers' => $workers
		) );
	}

	private function getWorkers( $workers )
	{
		$userIds = array_keys( $workers );

		$result = array_values( array_map( function( $userId ) use ( $workers )
		{
			$worker = $workers[$userId];
			$worker['id'] = $userId;

			$range = $worker['range'];
			if( !empty( $range ) )
			{
				$worker['has_range'] = true;
				$worker['range_start'] = $range[0];
				$worker['range_end'] = $range[1];
			}
			else
				$worker['has_range'] = false;

			unset( $worker['range'] );

			return $worker;
		}, $userIds ) );

		return $result;
	}
}