<?php
/*
 * TMEIT Web Services
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class TmeitWsGetMembers extends TmeitWsGetService
{
	private $faces;

	public function __construct()
	{
		parent::__construct();
		$this->faces = new TmeitFaces();
	}

	protected function processRequest( $params )
	{
		$activeGroupIds = array();
		$activeTitleIds = array();
		$activeTeamIds = array();

		$groups = $this->getGroups( $activeGroupIds );
		$users = $this->getUsers( $activeGroupIds, $activeTitleIds, $activeTeamIds );
		$titles = $this->getTitles( $activeTitleIds );
		$teams = $this->getTeams( $activeTeamIds );

		$json = array(
			'groups' => $groups,
			'teams' => $teams,
			'titles' => $titles,
			'users' => $users
		);

		$this->setCacheControl( self::CacheTwentyFourHours );
		return $this->finishRequest( $json );
	}

	private function getGroups( &$activeGroupIds )
	{
		$groups = array_filter( $this->db->groupGetList(), function ( $group )
		{
			return !$group['is_inactive'];
		} );

		$activeGroupIds = array_keys( $groups );
		return array_map( function ( $groupId ) use ( $groups )
		{
			return array( 'id' => $groupId, 'title' => $groups[$groupId]['title'] );
		}, $activeGroupIds );
	}

	private function getUsers( $activeGroupIds, &$activeTitleIds, &$activeTeamIds )
	{
		$users = $this->db->userGetListByGroups( $activeGroupIds );

		$activeTitleIds = array_unique( array_filter( array_map( function ( $user )
		{
			return $user['title_id'];
		}, $users ), function ( $titleId )
		{
			return ( 0 != $titleId );
		} ) );

		$activeTeamIds = array_unique( array_filter( array_map( function ( $user )
		{
			return $user['team_id'];
		}, $users ), function ( $teamId )
		{
			return ( 0 != $teamId );
		} ) );

		$userIds = array_keys( $users );
		$users = array_map( function ( $userId ) use ( $users )
		{
			$user = $users[$userId];
			$user['id'] = $userId;
			$user['faces'] = $this->getFaces( $user['username'] );

			$props = $this->db->userGetPropsById( $userId );
			$this->putStringIfExists( $props, TmeitDb::PropDatePrao, $user, 'date_prao' );
			$this->putStringIfExists( $props, TmeitDb::PropDateMars, $user, 'date_marskalk' );
			$this->putStringIfExists( $props, TmeitDb::PropDateVraq, $user, 'date_vraq' );

			$experience = $this->db->experienceGetCacheByUser( $userId );
			$user['experience_points'] = ( FALSE != $experience ? $experience['points'] : 0 );
			$user['experience_badges'] = $this->getBadges( $experience );

			return $user;
		}, $userIds );

		return $users;
	}

	private function getFaces( $username )
	{
		return array_map( function ( $photo ) use ( $username )
		{
			return $this->faces->getUrlOfPhoto( $username, $photo );
		}, $this->faces->findPhotos( $username ) );
	}

	private function putStringIfExists( &$props, $propKey, &$user, $outKey )
	{
		$value = $this->db->userGetPropString( $props, $propKey );
		if( FALSE != $value )
			$user[$outKey] = $value;
	}

	private function getBadges( $experience )
	{
		if( FALSE == $experience || empty( $experience['badges'] ) )
			return array();

		$points = $experience['points'];
		$result = array();

		foreach( $experience['badges'] as $badgeId )
		{
			$badge = TmeitBadges::getById( $badgeId );
			if( NULL !== $badge )
				$result[] = array(
					'title' => str_replace( '{EXP}', $points, $badge->getTitle() ),
					'src' => $badge->getUrl()
				);
		}

		return $result;
	}

	private function getTitles( $activeTitleIds )
	{
		$allTitles = $this->db->titleGetList();

		return array_values( array_map( function ( $titleId ) use ( $allTitles )
		{
			return array( 'id' => $titleId, 'title' => $allTitles[$titleId]['title'] );
		}, $activeTitleIds ) );
	}

	private function getTeams( $activeTeamIds )
	{
		$allTeams = $this->db->teamGetList();

		$result = array_values( array_map( function ( $teamId ) use ( $allTeams )
		{
			return array( 'id' => $teamId, 'title' => $allTeams[$teamId]['title'] );
		}, $activeTeamIds ) );

		usort( $result, function ( $a, $b )
		{
			return strcmp( $a['title'], $b['title'] );
		} );

		return $result;
	}
}