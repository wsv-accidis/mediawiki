<?php
/*
 * TMEIT Members extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class SpecialTmeitExperience extends TmeitSpecialPage
{
	private $assignableBadges;
	private $events;
	private $experienceTypes;
	private $thisUrl;
	private $users;

	public function __construct()
	{
		parent::__construct( 'TmeitExperience', 'tmeitadmin' );
	}

	protected function prepare( $par )
	{
		$deleteId = $this->getIntField( 'delete' );
		if( $this->isAdmin && $deleteId > 0 )
			$this->db->experienceDeleteManualEvent( $deleteId );

		if( $this->wasPosted() )
		{
			global $wgUser;
			$currentUserId = $this->db->userGetIdByMediaWikiUserId( $wgUser->getId() );
			if( 0 == $currentUserId )
				throw new FatalError( 'Användaren kunde inte hittas. Kontakta webbansvarig.' );

			$userId = $this->getIntField( 'user_id' );
			$badge = $this->getIntField( 'badge' );
			$exp = $this->getIntField( 'exp' );

			if( 0 == $userId || ( 0 == $badge && 0 == $exp ) )
				throw new FatalError( 'Du har inte fyllt i formuläret korrekt. Välj en medlem och antingen achievement, exp, eller bägge.' );

			$this->db->experienceSaveManualEvent( $userId, $exp, $badge, $currentUserId );
		}

		$this->assignableBadges = TmeitBadges::getAssignable();
		$this->events = $this->db->experienceGetEvents();
		$this->thisUrl = $this->getPageTitle()->getFullURL();
		$this->users = $this->db->userGetPicklistByActive();

		// TODO Refactor this to be common if ever necessary
		$this->experienceTypes = array( TmeitDb::ExperienceTypeManual => "Manuell" );
		return true;
	}

	private function getBadgeInfo( $badgeId, $exp )
	{
		$result = '';
		if( $badgeId != 0 && NULL != ( $badge = TmeitBadges::getById( $badgeId ) ) )
			$result .= '<img src="'.$badge->getUrl().'" alt="'.htmlspecialchars( $badge->getTitle() ).'" title="'.htmlspecialchars( $badge->getTitle() ).'" />';
		if( $exp > 0 )
			$result .= '+'.$exp;
		elseif( $exp < 0 )
			$result .= $exp;

		return $result;
	}

	protected function render()
	{
?>
<table class="wikitable tmeit-table tmeit-twothird-width">
    <tr>
        <td></td>
        <th>
            Datum/tid
        </th>
		<th>
			Badge/exp
		</th>
		<th>
			Namn
		</th>
		<th>
			Typ
		</th>
		<th>
			Tilldelad av
		</th>
	</tr>
<?
		foreach( $this->events as $eventId => $event ):
?>
	<tr>
		<td class="action-column">
<?
			if( $event['type'] == TmeitDb::ExperienceTypeManual ):
?>
            <a href="#" onclick="promptDelete( '<?=$eventId; ?>' );">Ta bort</a>
<?
			endif;
?>
		</td>
		<td>
			<?=htmlspecialchars( $event['timestamp'] ); ?>
		</td>
		<td class="icon-column">
			<?=$this->getBadgeInfo( $event['badge'], $event['exp'] ); ?>
		</td>
		<td>
			<?=htmlspecialchars( $event['user_name'] ); ?>
		</td>
		<td>
			<?=htmlspecialchars( isset( $this->experienceTypes[$event['type']] ) ? $this->experienceTypes[$event['type']] : "(Okänd)" ); ?>
		</td>
		<td>
			<?=htmlspecialchars( $event['admin_name'] ); ?>
		</td>
	</tr>
<?
		endforeach;
?>
</table>

<form id="tmeit-form" action="" method="post">
    <h3>Tilldela badge/exp</h3>
    <table class="wikitable tmeit-table tmeit-third-width">
		<tr>
			<td class="caption-column">
				Medlem
			</td>
			<td class="field-column">
				<select name="user_id" class="medium-text">
					<option value="0">(Ingen vald)</option>
<?
			foreach( $this->users as $userId => $user ):
?>
					<option value="<?=$userId; ?>">
						<?=htmlspecialchars( $user['realname'] ); ?>
					</option>
<?
			endforeach;
?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="caption-column">
				Achievement
			</td>
			<td class="field-column">
				<select name="badge" class="short-text">
					<option value="0">(Ingen)</option>
<?
			foreach( $this->assignableBadges as $badge ):
?>
					<option value="<?=$badge->getId(); ?>">
						<?=htmlspecialchars( $badge->getName() ); ?>
					</option>
<?
			endforeach;
?>
				</select>
			</td>
		</tr>
        <tr>
            <td class="caption-column">
                Exp
            </td>
            <td class="field-column">
                <input type="text" class="tiny-text" name="exp" value="0" />
			</td>
		</tr>
        <tr>
            <td></td>
            <td class="submit-column">
                <input type="submit" value="Spara" />
            </td>
        </tr>
	</table>
</form>

<script type="text/javascript">
	function promptDelete( id )
	{
		if( confirm( 'Är du säker på att du vill ta bort tilldelningen?' ) )
			location.href = '<?=$this->thisUrl; ?>?delete=' + id;
	}
</script>
<?
	}
}