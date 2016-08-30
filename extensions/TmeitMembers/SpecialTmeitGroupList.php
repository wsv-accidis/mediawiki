<?php
/*
 * TMEIT Members extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class SpecialTmeitGroupList extends TmeitSpecialMemberPage
{
	private $thisUrl;
	private $groups;
	private $saved = false;

	public function __construct()
	{
		parent::__construct( 'TmeitGroupList', 'tmeitadmin' );
	}

	protected function prepare( $par )
	{
		if( $this->wasPosted() )
		{
			if( $this->hasField( 'submit-update' ) )
			{
				$tempGroups = $this->db->groupGetList();
				foreach( $tempGroups as $groupId => $group )
				{
                    $newIsInactive = $this->getBoolField( 'is_inactive_'.$groupId );
                    if( $newIsInactive != $group['is_inactive'] )
                        $this->db->groupSetIsInactive( $groupId, $newIsInactive );

					$newSortIdx = $this->getIntField( 'sort_idx_'.$groupId );
					if( $newSortIdx != $group['sort_idx'] )
						$this->db->groupUpdateSortIdx( $groupId, $newSortIdx );
				}
			}
			elseif( $this->hasField( 'submit-create' ) )
				$this->db->groupSaveNew( $this->getTextField( 'group_title' ) );

			$this->saved = true;
		}

		$this->thisUrl = $this->getPageTitle()->getFullURL();
		$this->groups = $this->db->groupGetList();

		return true;
	}

	protected function render()
	{
		$indexTip = 'Anger sorteringsordningen på grupperna.';
        $inactiveTip = 'Döljer medlemmar i gruppen från vissa listor.';
?>
<p class="tmeit-important-note">
	Viktigt: Det är farligt att göra ändringar här om du inte vet precis vad du gör.
</p>
<?
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
				Namn
			</th>
			<th>
				Index
				[<a href="javascript:alert('Index: <?=$indexTip; ?>');" title="<?=$indexTip; ?>">?</a>]
			</th>
            <th>
                Inaktiva
                [<a href="javascript:alert('Inaktiva: <?=$inactiveTip; ?>');" title="<?=$inactiveTip; ?>">?</a>]
            </th>
		</tr>
<?
		foreach( $this->groups as $id => $group ):
?>
		<tr>
			<td class="action-column"></td>
			<td class="main-column">
				<?=htmlspecialchars( $group['title'] ); ?>
			</td>
			<td class="field_column">
				<input type="text" class="tiny-text" name="sort_idx_<?=$id; ?>" value="<?=htmlspecialchars( $group['sort_idx'] ); ?>" />
			</td>
            <td class="field_column">
                <input type="checkbox" name="is_inactive_<?=$id; ?>"<? if( $group['is_inactive'] ): ?> checked="checked"<? endif; ?> />
            </td>
		</tr>
<?
		endforeach;
?>
		<tr>
			<td></td>
			<td colspan="4" class="submit-column">
				<input type="submit" name="submit-update" value="Spara" />
			</td>
		</tr>
	</table>

	<h3>Lägg till en grupp</h3>

	<table class="wikitable tmeit-table tmeit-third-width">
		<tr>
			<td class="caption-column">
				Namn
			</td>
			<td class="field-column">
				<input type="text" class="medium-text" name="group_title" value="" />
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
	<li><a href="<?=$this->getSpecialPageUrl( 'TmeitGroupList' ); ?>">Hantera grupper</a></li>
	<li><a href="<?=$this->getSpecialPageUrl( 'TmeitTitleList' ); ?>">Hantera titlar</a></li>
</ul>

<script type="text/javascript">
	function promptDelete( id )
	{
		if( confirm( 'Är du säker på att du vill ta bort gruppen?' ) )
			location.href = '<?=$this->thisUrl; ?>?delete=' + id;
	}
</script>
<?
	}
}
?>