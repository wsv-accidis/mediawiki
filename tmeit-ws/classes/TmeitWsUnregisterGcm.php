<?php

/*
 * TMEIT Web Services
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class TmeitWsUnregisterGcm extends TmeitWsRegisterGcm
{
	public function __construct()
	{
		parent::__construct();
	}

	protected function doRegisterGcm( $userId, $registrationId )
	{
		$this->db->gcmUnregister( $userId, $registrationId );
		$this->db->getDatabase()->commit();
	}
}