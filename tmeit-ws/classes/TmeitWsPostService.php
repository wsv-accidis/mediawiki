<?php
/*
 * TMEIT Web Services
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

abstract class TmeitWsPostService extends TmeitWsServiceBase
{
	const HttpPost = 'POST';

	public function handleRequest()
	{
		if( self::HttpPost !== $_SERVER['REQUEST_METHOD'] )
			return $this->finishRequest( self::buildError( 'Invalid method. Please use POST.', self::HttpBadRequest ) );
		if( substr( $_SERVER['CONTENT_TYPE'], 0, strlen( self::JsonContentType ) ) !== self::JsonContentType )
			return $this->finishRequest( self::buildError( 'Request must have JSON body and content type.', self::HttpBadRequest ) );

		$params = json_decode( file_get_contents( 'php://input' ), true );
		if( NULL === $params )
			return $this->finishRequest( self::buildError( 'Request body must be valid JSON.', self::HttpBadRequest ) );

		$username = @$params[self::UsernameKey];
		$serviceAuth = @$params[self::ServiceAuthKey];

		if( $this->authenticateOrDie( $username, $serviceAuth ) )
			return $this->processRequest( $params );

		return false;
	}
}