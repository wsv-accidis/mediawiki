<?php
/*
 * TMEIT Web Services
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class TmeitWsRegisterGcm extends TmeitWsPostService
{
	const RegistrationIdKey = 'registrationId';

	public function __construct()
	{
		parent::__construct();
	}

	protected function processRequest( $params )
	{
		$registrationId = @$params[self::RegistrationIdKey];
		if( empty( $registrationId ) )
			return $this->finishRequest( self::buildError( 'A required parameter is missing. Please pretend you have an API reference and use that.', self::HttpBadRequest ) );

		$this->doRegisterGcm( $this->userId, $registrationId );
		return $this->finishRequest( array( self::SuccessKey => true ) );
	}

	protected function doRegisterGcm( $userId, $registrationId )
	{
		$this->db->gcmRegister( $userId, $registrationId );
		$this->db->getDatabase()->commit();
	}
}