<?php
/*
 * TMEIT Web Services
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

abstract class TmeitWsServiceBase
{
	const ErrorMessageKey = 'errorMessage';
	const JsonContentType = 'application/json';
	const HttpBadRequest = 400;
	const HttpCreated = 201;
	const HttpForbidden = 403;
	const HttpInternalServerError = 501;
	const HttpNotFound = 404;
	const ServiceAuthKey = 'serviceAuth';
	const SuccessKey = 'success';
	const UsernameKey = 'username';

	/** @var TmeitDb */
	protected $db;
	protected $userId;

	protected function __construct()
	{
		$this->db = new TmeitDb();
	}

	public abstract function handleRequest();

	protected function authenticateOrDie( $username, $serviceAuth )
	{
		if( empty( $username ) || empty( $serviceAuth ) )
			return $this->finishRequest( self::buildError( 'A required parameter is missing. Please pretend you have an API reference and use that.', self::HttpBadRequest ) ); // HELPFUL!

		if( 0 === ( $this->userId = $this->isValidServiceAuth( $username, $serviceAuth ) ) )
			return $this->finishRequest( self::buildError( 'Invalid username or authorization code. Go away.', self::HttpForbidden ) );

		return true;
	}

	protected abstract function processRequest( $params );

	protected static function buildError( $errorMessage, $httpStatus )
	{
		http_response_code( $httpStatus );
		return array(
			self::SuccessKey => false,
			self::ErrorMessageKey => $errorMessage
		);
	}

	protected static function buildMissingParameterError()
	{
		return self::buildError( 'A required parameter is missing. Please pretend you have an API reference and use that.', self::HttpBadRequest );
	}

	protected function finishRequest( $obj )
	{
		$json = json_encode( $obj );
		$gzip = gzencode( $json );

		header( 'Content-Type: ' . self::JsonContentType );
		header( 'Content-Encoding: gzip' );
		header( 'Content-Length: ' . strlen( $gzip ) );

		echo $gzip;

		exit();
		/** @noinspection PhpUnreachableStatementInspection */
		return false;
	}

	protected function isValidServiceAuth( $username, $serviceAuth )
	{
		return $this->db->userValidateServiceAuth( $username, $serviceAuth );
	}
}
