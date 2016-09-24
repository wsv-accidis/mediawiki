<?php
/*
 * TMEIT Web Services
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

abstract class TmeitWsGetService extends TmeitWsServiceBase
{
	const CacheOneMinute = 60;
	const CacheFiveMinutes = 300;
	const CacheOneHour = 3600;
	const CacheTwentyFourHours = 86400;
	const HttpGet = 'GET';
	const HeaderUsername = 'HTTP_X_TMEIT_USERNAME';
	const HeaderServiceAuth = 'HTTP_X_TMEIT_SERVICE_AUTH';

	public function handleRequest()
	{
		if( self::HttpGet !== $_SERVER['REQUEST_METHOD'] )
			return $this->finishRequest( self::buildError( 'Invalid method. Please use GET.', self::HttpBadRequest ) );

		$username = @$_SERVER[self::HeaderUsername];
		$serviceAuth = @$_SERVER[self::HeaderServiceAuth];
		$subPath = $this->getSubPath();

		if( $this->authenticateOrDie( $username, $serviceAuth ) )
			return $this->processRequest( array( 'subpath' => $subPath ) );

		return false;
	}

	private function getSubPath()
	{
		$requestUri = $_SERVER['REQUEST_URI'];
		$scriptName = $_SERVER['SCRIPT_NAME'];

		if( strlen( $requestUri ) - strlen( $scriptName ) > 1 )
			return substr( $requestUri, 1 + strlen( $scriptName ) );

		return FALSE;
	}

	protected function setCacheControl( $maxAge )
	{
		header( 'Cache-Control: private, max-age=' . $maxAge );
	}
}