<?php
/*
 * TMEIT Events extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

abstract class TmeitSpecialEventPage extends TmeitSpecialPage
{
	protected function mayEditEventOrThrow( $event )
	{
		if( !$this->mayEditEvent( $event ) )
			throw new PermissionsError( 'tmeitadmin' );
	}

	protected function mayEditEvent( $event, $userTeamAdmin = -1 )
	{
		if( $this->isAdmin ) // admins can always edit events
			return true;
		if( 0 == $event['team_id'] ) // if there is no team assigned then no edit rights for team admins
			return false;

		global $wgUser;
		if( $userTeamAdmin < 0 )
			$userTeamAdmin = $this->db->userGetTeamAdminByMediaWikiUserId( $wgUser->getId() );

		// team admin can edit event
		return $userTeamAdmin == $event['team_id'];
	}

	protected function mayEditReport( $event, $userTeamAdmin )
	{
		return ( $this->isAdmin || ( $event['team_id'] != 0 && $event['team_id'] == $userTeamAdmin ) );
	}

	protected function mayWorkEvent( $event )
	{
		return !$event['is_past'] && ( $event['workers_max'] > 0 || $event['workers_count'] > 0 );
	}
}