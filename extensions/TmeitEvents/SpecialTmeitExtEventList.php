<?php
/*
 * TMEIT Events extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class SpecialTmeitExtEventList extends TmeitSpecialPage
{
	private $events;
	private $thisUrl;

	public function __construct()
	{
		parent::__construct( 'TmeitExtEventList', 'tmeit' );
	}

	protected function prepare( $par )
	{
		$deleteId = $this->getIntField( 'delete' );
		if( $this->isAdmin && $deleteId > 0 )
			$this->db->extEventDelete( $deleteId );

		global $wgUser;
		$currentUserId = $this->db->userGetIdByMediaWikiUserId( $wgUser->getId() );
		$this->events = $this->db->extEventGetList( $currentUserId );
		$this->thisUrl = $this->getPageTitle()->getFullURL();
		return true;
	}

	protected function render()
	{
?>
<p style="margin-bottom: 10px">
	Här anmäler du dig till externa evenemang. Mästare kan också skapa och redigera evenemang.
</p>
<?
		$this->renderEvents();
		$this->renderFooter();
	}

	private function renderEvents()
	{
		global $wgScriptPath;
		$imagesPath = "$wgScriptPath/extensions/TmeitCommon/images/";
		$extEventUrl = $this->getSpecialPageUrl( 'TmeitExtEventEdit' ).'/';

?>
<table class="tmeit-new-table tmeit-full-width">
	<thead>
		<tr>
			<th></th>
			<th>Titel</th>
			<th>Datum</th>
			<th>Sista anmälan</th>
			<th>Anmälda</th>
		</tr>
	</thead>
	<tbody>
<?
		foreach( $this->events as $eventId => $event ):
?>
		<tr>
			<td class="action-column">
				<? $this->action( $extEventUrl.$eventId, $imagesPath.'edit.png', 'Öppna' ); ?>
				<? $this->actionIf( $this->isAdmin, 'javascript:promptDelete('.$eventId.');', $imagesPath.'delete.png', 'Ta bort' ); ?>
			</td>
			<td class="icon-column<? if( $event['is_past'] ): ?> tmeit-extevent-past<? endif; ?>">
				<a href="<?=$extEventUrl.$eventId; ?>"><?=htmlspecialchars( $event['title'] ); ?></a>
				<? $this->iconIf( $event['is_attending'], $imagesPath.'checkoff-ok.png', 'Du är anmäld!' ); ?>
			</td>
			<td class="icon-column">
				<?=htmlspecialchars( $event['start_date'] ); ?>
				<? $this->iconIf( $event['is_past'], $imagesPath.'past.png', 'Evenemanget har varit.' ); ?>
			</td>
			<td class="icon-column">
				<?=htmlspecialchars( $event['last_signup'] ); ?>
				<? $this->iconIf( $event['is_near_signup'] && !$event['is_past_signup'], $imagesPath.'warning.png', 'Sista datum är snart!' ); ?>
				<? $this->iconIf( $event['is_past_signup'] && !$event['is_past'], $imagesPath.'error.png', 'Sista datum är passerat!' ); ?>
			</td>
			<td>
				<?=$event['attendees']; ?>
			</td>
		</tr>
<?
		endforeach;
?>
	</tbody>
</table>
<?
	}

	private function renderFooter()
	{
?>
<h3 class="tmeit-links-header">Länkar</h3>
<ul>
<?
	if( $this->isAdmin ):
?>
	<li><a href="<?=$this->getSpecialPageUrl( 'TmeitExtEventEdit' ); ?>">Nytt externt evenemang</a></li>
<?
	endif;
?>
	<li><a href="<?=$this->getSpecialPageUrl( 'TmeitEventList' ); ?>">Lista TMEIT-evenemang</a></li>
	<li><a href="<?=$this->getPageUrl( 'Internsidor' ); ?>">Internsidor...</a></li>
</ul>

<script type="text/javascript">
	function promptDelete( id )
	{
		if( confirm( 'Är du säker på att du vill ta bort evenemanget?' ) )
			location.href = '<?=$this->thisUrl; ?>?delete=' + id;
	}
</script>
<?
	}
}