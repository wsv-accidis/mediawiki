<?php
/*
 * TMEIT Web Services
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class TmeitWsGetNotifications extends TmeitWsPostService
{
	const NewSince = 'newSince';
	const MaxCount = 'maxCount';
	const DefaultMaxCount = 100;

	public function __construct()
	{
		parent::__construct();
	}

	protected function processRequest( $params )
	{
		$newSince = @$params[self::NewSince];
		$maxCount = @$params[self::MaxCount];

		if( empty( $newSince ) || empty( $maxCount ) )
			return $this->finishRequest( self::buildError( 'A required parameter is missing. Please pretend you have an API reference and use that.', self::HttpBadRequest ) );
		if( FALSE == ( $newSinceDt = DateTime::createFromFormat( DateTime::ISO8601, $newSince ) ) )
			return $this->finishRequest( $this->buildError( 'The newSince parameter does not specify a valid ISO-8601 formatted date and time.', self::HttpBadRequest ) );

		$maxCount = (int) $maxCount;
		if( $maxCount <= 0 || $maxCount > self::DefaultMaxCount )
			$maxCount = self::DefaultMaxCount;

		$notifications = $this->db->notifGetNewSince( $newSinceDt, $maxCount );
		$json = array();
		foreach( $notifications as $id => $notif )
		{
			$json[] = array(
				'id' => $id,
				'body' => $notif['body'],
				'url' => $notif['url'],
				'created' => ( new DateTime( $notif['created'] ) )->format( DateTime::ISO8601 )
			);
		}

		return $this->finishRequest( array( 'notifications' => $json ) );
	}
}
