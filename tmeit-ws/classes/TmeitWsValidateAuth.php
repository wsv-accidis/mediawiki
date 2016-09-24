<?php
/*
 * TMEIT Web Services
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class TmeitWsValidateAuth extends TmeitWsPostService
{
	const UserIdKey = "id";

	public function __construct()
	{
		parent::__construct();
	}

	protected function processRequest( $params )
	{
		$this->db->userCleanupServiceAuth( $this->userId );
		$this->db->getDatabase()->commit();
		return $this->finishRequest( array( self::SuccessKey => true, self::UserIdKey => $this->userId ) );
	}
}