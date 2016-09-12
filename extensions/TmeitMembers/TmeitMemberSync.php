<?php
/*
 * TMEIT Members extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class TmeitMemberSync
{
	private $db;

	public function __construct()
	{
		$this->db = new TmeitDb();
	}

    /*
     * =================================================================================================================
     *  MediaWiki
     * =================================================================================================================
     */

	public function initUserInMediaWiki( User $user )
	{
		$mwUserId = $user->getId();

		if( $mwUserId > 0 )
		{
			$tmeitUser = $this->db->userGetByName( strtolower( $user->getName() ) );
			if( FALSE !== $tmeitUser )
			{
				$this->setMediaWikiUserId( $tmeitUser, $mwUserId );
				$this->setMediaWikiGroups( $tmeitUser, $mwUserId );
				$this->setMediaWikiRealName( $tmeitUser, $mwUserId, $user->getRealName() );
			}
		}
	}

	public function initTmeitUserInMediaWiki( $tmeitUser )
	{
		$mwUserId = $tmeitUser['mediawiki_user_id'];
		if( 0 == $mwUserId )
			$mwUserId = $this->db->mwGetUserIdByName( $tmeitUser['username'] );

		if( $mwUserId > 0 )
		{
			$this->setMediaWikiUserId( $tmeitUser, $mwUserId );
			$this->setMediaWikiGroups( $tmeitUser, $mwUserId );

			$mwRealName = $this->db->mwGetRealNameById( $mwUserId );
			$this->setMediaWikiRealName( $tmeitUser, $mwUserId, $mwRealName );
		}
	}

	private function setMediaWikiUserId( $tmeitUser, $mwUserId )
	{
		if( $mwUserId != $tmeitUser['mediawiki_user_id'] )
			$this->db->userSetMediaWikiUserId( $tmeitUser['id'], $mwUserId );
	}

	private function setMediaWikiGroups( $tmeitUser, $mwUserId )
	{
        $inactive = $this->db->groupGetIsInactive( $tmeitUser['group_id'] );
		$this->db->mwSetTmeitUserGroups( $mwUserId, !$inactive, $tmeitUser['is_admin'] );
	}

	private function setMediaWikiRealName( $tmeitUser, $mwUserId, $mwRealName )
	{
		if( 0 != strcmp( $tmeitUser['realname'], $mwRealName ) )
			$this->db->mwSetUserRealName( $mwUserId, $tmeitUser['realname'] );
	}
}
