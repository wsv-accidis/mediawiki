<?php
/*
 * TMEIT Members extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class SpecialTmeitMemberList extends TmeitSpecialMemberPage
{
	const MODE_GROUPS = 0;
	const MODE_TEAMS = 1;
    const COOKIE_SHOW_INACTIVE = 'TmeitMemberListShowInactive';

	private $editMemberUrl;
	private $groups;
	private $listImagesUrl;
	private $memberImageUrl;
	private $mode;
    private $showInactive;
	private $teams;
	private $users = array();
	private $viewMemberUrl;

	public function __construct()
	{
		parent::__construct( 'TmeitMemberList', 'tmeit' );
	}

	protected function prepare( $par )
	{
		$this->mode = ( $this->getTextField( 'by', 'group' ) == 'team' ? self::MODE_TEAMS : self::MODE_GROUPS );

        $this->showInactive = ( $this->getTextField( 'inactive' ) == 'show'
            || ( $this->getTextField( 'inactive' ) != 'hide' && $this->getRequest()->getCookie( self::COOKIE_SHOW_INACTIVE ) == '1' ) );
        $this->getRequest()->response()->setcookie( self::COOKIE_SHOW_INACTIVE, $this->showInactive ? '1' : '0' );

		$this->groups = $this->db->groupGetList();
		$this->teams = $this->db->teamGetList();

		if( self::MODE_GROUPS == $this->mode )
		{
			foreach( $this->groups as $groupId => $group )
                if( $this->showInactive || !$group['is_inactive'] )
				    $this->users[$group['title']] = $this->db->userGetListByGroup( $groupId );
			$this->users['Ingen grupp'] = $this->db->userGetListByGroup( NULL );
		}
		elseif( self::MODE_TEAMS == $this->mode )
		{
			foreach( $this->teams as $teamId => $team )
				$this->users[$team['title']] = $this->db->userGetListByTeam( $teamId );

			$inactiveGroups = array();
			foreach( $this->groups as $groupId => $group )
                if( $group['is_inactive'] )
                    $inactiveGroups[] = $groupId;

			$noTeam = $noTeamActive = array();
			foreach( $this->db->userGetListByTeam( NULL ) as $user )
			{
                $userIsInactive = in_array( $user['group_id'], $inactiveGroups );
                if( $userIsInactive && !$this->showInactive )
                    continue;
                elseif( $userIsInactive )
                    $noTeam[] = $user;
                else
                    $noTeamActive[] = $user;
			}

			$this->users['Inget arbetslag (aktiva)'] = $noTeamActive;
			$this->users['Inget arbetslag (ej aktiva)'] = $noTeam;
		}

		return true;
	}

	protected function initSpecialUrls()
	{
        parent::initSpecialUrls();
		$this->editMemberUrl = SpecialPage::getTitleFor( 'TmeitMemberEdit' )->getFullURL().'/';
		$this->listImagesUrl = SpecialPage::getTitleFor( 'TmeitMemberImageList' )->getFullURL();
		$this->memberImageUrl = SpecialPage::getTitleFor( 'TmeitMemberImage' )->getFullURL().'/';
		$this->viewMemberUrl = SpecialPage::getTitleFor( 'TmeitMember' )->getFullURL().'/';

	}

	protected function render()
	{
        $urlSuffix = ( self::MODE_GROUPS == $this->mode ? "?by=group" : "?by=team" );
?>
<p>
	Gruppera: [<? if( self::MODE_GROUPS == $this->mode ): ?><b>grupp</b><? else: ?><a href="?by=group">grupp</a><? endif; ?>] [<? if( self::MODE_TEAMS == $this->mode ): ?><b>arbetslag</b><? else: ?><a href="?by=team">arbetslag</a><? endif; ?>]
    &nbsp;&nbsp;Inaktiva/Ex: [<? if( $this->showInactive ): ?><a href="<?=$urlSuffix; ?>&inactive=hide">dölj</a><? else: ?><a href="<?=$urlSuffix; ?>&inactive=show">visa</a><? endif; ?>]
</p>
<?
		foreach( $this->users as $header => $list )
			if( !empty( $list ) )
				$this->renderSingleTable( $header, $list );

		if( $this->isAdmin ):
?>
<h3>Länkar</h3>
<ul>
	<li><a href="<?=$this->editMemberUrl; ?>">Ny medlem</a></li>
	<li><a href="<?=$this->listImagesUrl; ?>">Lista medlemsfoton</a></li>
	<li><a href="<?=$this->manageTeamsUrl; ?>">Hantera arbetslag</a></li>
	<li><a href="<?=$this->manageGroupsUrl; ?>">Hantera grupper</a></li>
	<li><a href="<?=$this->manageTitlesUrl; ?>">Hantera titlar</a></li>
</ul>
<?
		endif;
	}

	private function renderSingleTable( $header, array $users )
	{
?>
<h3><?=htmlspecialchars( $header ); ?></h3>
<table class="wikitable tmeit-table tmeit-full-width tmeit-member-list">
	<tr>
		<td></td>
		<th>
			Användare
		</th>
		<th>
			Riktigt namn
		</th>
		<th>
			Telefonnummer
		</th>
		<th>
			E-post
		</th>
		<th>
<?
			if( self::MODE_GROUPS == $this->mode ):
?>
			Arbetslag
<?
			elseif( self::MODE_TEAMS == $this->mode ):
?>
			Grupp
<?
			endif;
?>
		</th>
		<th>
			STAD
		</th>
		<th>
			FEST
		</th>
		<th>
			Tillst.
		</th>
        <th>
            Körk.
        </th>
		<th>
			Admin
		</th>
	</tr>
<?
		foreach( $users as $user ):
?>
	<tr>
		<td class="action-column">
			<a href="<?=$this->viewMemberUrl.htmlspecialchars( $user['username'] ); ?>">Visa</a>
<?
			if( $this->isAdmin ):
?>
			| <a href="<?=$this->editMemberUrl.htmlspecialchars( $user['username'] ); ?>">Redigera</a>
<?
			else:
?>
			| <a href="<?=$this->memberImageUrl.htmlspecialchars( $user['username'] ); ?>">Foton</a>
<?
			endif;
?>
		</td>
		<td class="main-column">
			<?=htmlspecialchars( $user['username'] ); ?>
		</td>
		<td>
			<?=htmlspecialchars( $user['realname'] ); ?><? if( self::MODE_TEAMS == $this->mode && $user['is_team_admin'] ): ?>*<? endif; ?>
		</td>
		<td>
			<?=htmlspecialchars( $user['phone'] ); ?>
		</td>
		<td>
			<a href="mailto:<?=htmlspecialchars( $user['email'] ); ?>"><?=htmlspecialchars( $user['email'] ); ?></a>
		</td>
		<td>
<?
			if( self::MODE_GROUPS == $this->mode ):
?>
			<?=( $user['team_id'] > 0 && isset( $this->teams[$user['team_id']] )
				? htmlspecialchars( $this->teams[$user['team_id']]['title'] ).( $user['is_team_admin'] ? '*' : '' )
				: "(Inget)" ); ?>
<?
			elseif( self::MODE_TEAMS == $this->mode ):
?>
			<?=( $user['group_id'] > 0 && isset( $this->groups[$user['group_id']] ) ? htmlspecialchars( $this->groups[$user['group_id']]['title'] ) : "(Ingen)" ); ?>
<?
			endif;
?>
		</td>
		<td class="icon-column">
			<?=self::iconIfTrue( 'stad.png', $user['has_stad'] ); ?>
		</td>
		<td class="icon-column">
			<?=self::iconIfTrue( 'fest.png', $user['has_fest'] ); ?>
		</td>
		<td class="icon-column">
			<?=self::iconIfTrue( 'tillstand.png', $user['has_permit'] ); ?>
		</td>
        <td class="icon-column">
            <?=self::iconIfTrue( 'license.png', $user['has_license'] ); ?>
        </td>
		<td class="icon-column">
			<?=self::iconIfTrue( 'admin.png', $user['is_admin'] ); ?>
		</td>
	</tr>
<?
		endforeach;
?>
</table>
<?
	}

	private static function iconIfTrue( $icon, $var )
	{
		global $wgScriptPath;

		if( $var )
			return '<img src="'.$wgScriptPath.'/extensions/TmeitMembers/images/'.$icon.'" class="tmeit-icon" />';

		return '';
	}
}
?>