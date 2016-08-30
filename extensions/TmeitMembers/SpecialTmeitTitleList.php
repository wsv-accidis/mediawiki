<?php
/*
 * TMEIT Members extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class SpecialTmeitTitleList extends TmeitSpecialMemberPage
{
	private $saved = false;
	private $thisUrl;
	private $titles;

	public function __construct()
	{
		parent::__construct( 'TmeitTitleList', 'tmeitadmin' );
	}

	protected function prepare( $par )
	{
		$deleteId = $this->getIntField( 'delete' );
		if( $deleteId > 0 )
			$this->db->titleDelete( $deleteId );

		if( $this->wasPosted() )
		{
			if( $this->hasField( 'submit-update' ) )
			{
				$tempList = $this->db->titleGetList();
				foreach( $tempList as $id => $title )
				{
					$newEmail = $this->getTextField( 'email_'.$id );
					$newSortIdx = $this->getIntField( 'sort_idx_'.$id );

					if( $newEmail != $title['email'] || $newSortIdx != $title['sort_idx'] )
						$this->db->titleSetEmailAndSortIdx( $id, $newEmail, $newSortIdx );
				}
			}
			elseif( $this->hasField( 'submit-create' ) )
				$this->db->titleSaveNew( $this->getTextField( 'title_title' ) );

			$this->saved = true;
		}

		$this->thisUrl = $this->getTitle()->getFullURL();
		$this->titles = $this->db->titleGetList();
		return true;
	}

	protected function render()
	{
		$indexTip = "Anger vilka titlar som visas överst på medlemssidan. Används bara för styrelsen.";
		if( $this->saved ):
?>
<p class="tmeit-important-note">
	Sparad.
</p>
<?
		endif;
?>
<form id="tmeit-form" action="" method="post">
	<table class="wikitable tmeit-table tmeit-half-width">
		<tr>
			<td></td>
			<th>
				Titel
			</th>
			<th>
				E-post
			</th>
			<th>
				Index
				[<a href="javascript:alert('Index: <?=$indexTip."');"; ?>" title="<?=$indexTip; ?>">?</a>]
			</th>
		</tr>
<?
		foreach( $this->titles as $id => $title ):
?>
		<tr>
			<td class="action-column">
				<a href="#" onclick="promptDelete( '<?=$id; ?>' );">Ta bort</a>
			</td>
			<td class="main-column">
				<?=htmlspecialchars( $title['title'] ); ?>
			</td>
			<td class="field_column">
				<input type="text" class="medium-text" name="email_<?=$id; ?>" value="<?=htmlspecialchars( $title['email'] ); ?>" />
			</td>
			<td class="field_column">
				<input type="text" class="tiny-text" name="sort_idx_<?=$id; ?>" value="<?=htmlspecialchars( $title['sort_idx'] ); ?>" />
			</td>
		</tr>
<?
		endforeach;
?>
		<tr>
			<td></td>
			<td colspan="3" class="submit-column">
				<input type="submit" name="submit-update" value="Spara" />
			</td>
		</tr>
	</table>

	<h3>Lägg till en titel</h3>

	<table class="wikitable tmeit-table tmeit-third-width">
		<tr>
			<td class="caption-column">
				Titel
			</td>
			<td class="field-column">
				<input type="text" class="medium-text" name="title_title" value="" />
			</td>
		</tr>
		<tr>
			<td></td>
			<td class="submit-column">
				<input type="submit" name="submit-create" value="Lägg till" />
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
		if( confirm( 'Är du säker på att du vill ta bort titeln?' ) )
			location.href = '<?=$this->thisUrl; ?>?delete=' + id;
	}
</script>
<?
		}
}
?>