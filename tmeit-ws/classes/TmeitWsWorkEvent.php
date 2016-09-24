<?php
/*
 * TMEIT Web Services
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class TmeitWsWorkEvent extends TmeitWsPostService
{
	// Note: Keep in sync with SpecialTmeitEventWork
	const MinHour = 8;
	const MaxHour = 29;

	const CommentKey = 'comment';
	const EventIdKey = 'event_id';
	const RangeEndKey = 'range_end';
	const RangeStartKey = 'range_start';
	const WorkingKey = 'working';

	public function __construct()
	{
		parent::__construct();
	}

	protected function processRequest( $params )
	{
		$eventId = (int) @$params[self::EventIdKey];
		if( 0 == $eventId )
			return $this->finishRequest( self::buildError( 'A required parameter is missing. Please pretend you have an API reference and use that.', self::HttpBadRequest ) );

		$event = $this->db->eventGetById( $eventId );
		if( FALSE === $event || $event['is_past'] )
			return $this->finishRequest( self::buildError( 'Event does not exist or changes are no longer allowed.', self::HttpForbidden ) );

		$workers = $this->db->eventGetWorkersById( $eventId );
		$isWorking = false;
		$numberOfWorkers = 0;

		foreach( $workers as $id => $worker )
		{
			if( $worker['working'] == TmeitDb::WorkYes )
			{
				++$numberOfWorkers;
				if( $id == $this->userId )
					$isWorking = true;
			}
		}

		$canWork = ( $isWorking || $numberOfWorkers < $event['workers_max'] );
		$comment = trim( @$params[self::CommentKey] );
		$working = (int) @$params[self::WorkingKey];
		$range = FALSE;

		if( $working == TmeitDb::WorkYes || $working == TmeitDb::WorkMaybe )
		{
			if( $working == TmeitDb::WorkYes && !$canWork )
				return $this->finishRequest( self::buildError( 'This event is full, you can\'t sign up to work.', self::HttpBadRequest ) );

			if( isset( $params[self::RangeStartKey] ) && isset( $params[self::RangeEndKey] ) )
			{
				$workFrom = (int) @$params[self::RangeStartKey];
				$workUntil = (int) @$params[self::RangeEndKey];

				if( $workFrom < self::MinHour )
					$workFrom = self::MinHour;
				if( $workUntil > self::MaxHour )
					$workUntil = self::MaxHour;

				if( $workFrom >= $workUntil )
					return $this->finishRequest( self::buildError( 'Range is less than one hour.', self::HttpBadRequest ) );

				$range = array( $workFrom, $workUntil );
			}
		}
		else
			$working = TmeitDb::WorkNo;

		$this->db->eventSaveWorker( $eventId, $this->userId, $working, $range, $comment );
		$this->db->getDatabase()->commit();

		return $this->finishRequest( array( self::SuccessKey => true ) );
	}
}