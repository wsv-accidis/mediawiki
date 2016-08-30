<?php
/*
 * TMEIT Members extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class SpecialTmeitTeamList extends TmeitSpecialMemberPage
{
	private $thisUrl;
	private $teams;

	public function __construct()
	{
		parent::__construct( 'TmeitTeamList', 'tmeitadmin' );
	}

	protected function prepare( $par )
	{
		$deleteId = $this->getIntField( 'delete' );
		if( $deleteId > 0 )
			$this->db->teamDelete( $deleteId );

		if( $this->wasPosted() )
			$this->db->teamSaveNew( $this->getTextField( 'team_title' ) );

		$this->thisUrl = $this->getTitle()->getFullURL();
		$this->teams = $this->db->teamGetList();
		return true;
	}

	protected function render()
	{
?>
<form id="tmeit-form" action="" method="post">
	<table class="wikitable tmeit-table tmeit-third-width">
		<tr>
			<td></td>
			<th>
				Namn
			</th>
		</tr>
<?
		foreach( $this->teams as $id => $team ):
?>
		<tr>
			<td class="action-column">
				<a href="#" onclick="promptDelete( '<?=$id; ?>' );">Ta bort</a>
			</td>
			<td class="main-column">
				<?=htmlspecialchars( $team['title'] ); ?>
			</td>
		</tr>
<?
		endforeach;
?>
	</table>

	<h3>Lägg till ett arbetslag</h3>

	<table class="wikitable tmeit-table tmeit-third-width">
		<tr>
			<td class="caption-column">
				Namn
			</td>
			<td class="field-column">
				<input type="text" class="medium-text" name="team_title" value="" />
			</td>
		</tr>
		<tr>
			<td></td>
			<td class="submit-column">
				<input type="submit" value="Lägg till" />
			</td>
		</tr>
	</table>
</form>

<h3>Länkar</h3>
<ul>
	<li><a href="<?=$this->listMembersUrl; ?>">Lista medlemmar</a></li>
	<li><a href="<?=$this->manageTeamsUrl; ?>">Hantera arbetslag</a></li>
	<li><a href="<?=$this->manageGroupsUrl; ?>">Hantera grupper</a></li>
	<li><a href="<?=$this->manageTitlesUrl; ?>">Hantera titlar</a></li>
</ul>

<script type="text/javascript">
	function promptDelete( id )
	{
		if( confirm( 'Är du säker på att du vill ta bort arbetslaget?' ) )
			location.href = '<?=$this->thisUrl; ?>?delete=' + id;
	}
</script>
<?
	}
}
?>