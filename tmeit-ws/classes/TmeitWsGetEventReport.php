<?php

/*
 * TMEIT Web Services
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class TmeitWsGetEventReport extends TmeitWsGetService
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

		$report = $this->db->reportGetByEventId( $id );
		$hasReport = ( FALSE !== $report );
		$workers = $this->getWorkers( $hasReport ? $report['workers'] : $this->db->reportGetWorkersByEvent( $id ) );
		unset( $report['workers'] );

		$this->setCacheControl( self::CacheOneMinute );

		$response = [
			'is_reported' => $hasReport,
			'workers' => $workers
		];
		if( $hasReport )
			$response['report'] = $report;

		return $this->finishRequest( $response );
	}

	private function getWorkers( $workers )
	{
		$userIds = array_keys( $workers );

		$result = array_values( array_map( function( $userId ) use ( $workers )
		{
			$worker = $workers[$userId];
			$worker['id'] = $userId;
			return $worker;
		}, $userIds ) );

		return $result;
	}
}
