<?php
/*
 * TMEIT Events extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class SpecialTmeitEventList extends TmeitSpecialEventPage
{
	private $editEventUrl;
	private $events;
	private $isAdminOfTeam;
	private $lunch;
	private $lunches;
	private $reportUrl;
	private $reportSummaryUrl;
	private $saved = false;
	private $teams;
	private $thisUrl;
	private $workEventUrl;

	public function __construct()
	{
		parent::__construct( 'TmeitEventList', 'tmeit' );
	}

	protected function prepare( $par )
	{
		$deleteId = $this->getIntField( 'delete' );
		if( $this->isAdmin && $deleteId > 0 )
			$this->db->eventDelete( $deleteId );

		$this->lunch = $this->db->eventGetNewLunch();

		if( $this->isAdmin && $this->wasPosted() )
		{
			$this->lunch['title'] = $this->getTextField( 'event_title' );
			$this->lunch['team_id'] = $this->getIntField( 'team_id' );
			$this->lunch['workers_max'] = $this->getIntField( 'workers_max' );

			if( empty( $this->lunch['title'] ) )
				throw new FatalError( 'Evenemanget saknar titel.' );

			$date = $this->getTextField( 'start_date' );
			if( !$this->isValidDate( $date ) )
				throw new FatalError( 'Evenemanget saknar datum eller så är det felaktigt ifyllt. Formatet måste vara YYYY-MM-DD.' );
			$time = $this->getTextField( 'start_time' );
			if( !$this->isValidTime( $time ) )
				throw new FatalError( 'Evenemanget saknar tid eller så är det felaktigt ifyllt. Formatet måste vara hh:mm.' );

			$dates = $this->getBoolField( 'create_week' ) ? self::getDatesInWeek( $date ) : array( $date );
			foreach( $dates as $date )
			{
				$this->lunch['starts_at'] = $date.' '.$time;
				$this->db->eventSave( $this->lunch );
				$this->saved = true;
			}
		}

        $limit = $this->getIntField( 'limit' );
		$this->events = $this->db->eventGetList( TmeitDb::EventRegular, ( $limit <= 0 ? 20 : $limit ) );
		$this->lunches = $this->db->eventGetList( TmeitDb::EventLunch );
		$this->teams = $this->db->teamGetList();

		global $wgUser;
		$this->isAdminOfTeam = $this->db->userGetTeamAdminByMediaWikiUserId( $wgUser->getId() );
		return true;
	}

	protected function initSpecialUrls()
	{
		$this->manageTeamsUrl = SpecialPage::getTitleFor( 'TmeitTeamList' )->getFullURL();
		$this->editEventUrl = SpecialPage::getTitleFor( 'TmeitEventEdit' )->getFullURL().'/';
		$this->workEventUrl = SpecialPage::getTitleFor( 'TmeitEventWork' )->getFullURL().'/';
		$this->reportUrl = SpecialPage::getTitleFor( 'TmeitReportEdit' )->getFullURL().'/';
		$this->reportSummaryUrl = SpecialPage::getTitleFor( 'TmeitReportSummary' )->getFullURL().'/';
		$this->thisUrl = $this->getPageTitle()->getFullURL();
	}

	protected function render()
	{
		if( $this->saved ):
?>
<p class="tmeit-important-note">
	Sparat.
</p>
<?
		endif;
		$this->renderEvents();
		$this->renderLunches();
		$this->renderFooter();
	}

	private function renderEvents()
	{
		global $wgScriptPath;
		$imagesPath = "$wgScriptPath/extensions/TmeitCommon/images/";
?>
<table class="tmeit-new-table tmeit-full-width tmeit-table-spacing">
	<thead>
		<tr>
			<th class="tmeit-event-action-column"></th>
			<th>
				Titel
			</th>
			<th>
				Plats
			</th>
			<th>
				Datum
			</th>
			<th>
				Tid
			</th>
			<th>
				Arbetslag
			</th>
			<th>
				Arbetare
			</th>
		</tr>
	</thead>
	<tbody>
<?
		foreach( $this->events as $eventId => $event ):
			$mayWork = $this->mayWorkEvent( $event );
			$mayEdit = $this->db->eventMayEdit( $event, $this->isAdmin, $this->isAdminOfTeam );
			$mayReport = !$event['is_reported'] && $this->db->reportMayEdit( $event, $this->isAdmin, $this->isAdminOfTeam );
			$hasReport = $event['is_reported'];
			$mayDelete = $this->isAdmin;

			$workUrl = $this->workEventUrl.$eventId;
			$reportUrl = $this->reportUrl.$eventId;
			$editUrl = $this->editEventUrl.$eventId;
?>
		<tr>
			<td class="action-column">
				<? $this->actionIf( $mayWork, $workUrl, $imagesPath.'work.png?2', 'Jobba?' ); ?>
				<? $this->actionIf( $mayReport, $reportUrl, $imagesPath.'checkoff.png?1', 'Stäm av' ); ?>
				<? $this->actionIf( $hasReport, $reportUrl, $imagesPath.'checkoff-ok.png', 'Visa avstämning' ); ?>
				<? $this->actionIf( $mayEdit, $editUrl, $imagesPath.'edit.png', 'Redigera' ); ?>
				<? $this->actionIf( $mayDelete, 'javascript:promptDelete('.$eventId.');', $imagesPath.'delete.png', 'Ta bort' ); ?>
			</td>
			<td<? if( $event['is_past'] ): ?> class="tmeit-event-past"<? endif; ?>>
				<? $this->linkIf( $mayWork, $workUrl, $event['title'] ); ?>
			</td>
			<td>
				<?=htmlspecialchars( $event['location'] ); ?>
			</td>
			<td class="nowrap-column">
				<?=htmlspecialchars( $event['start_date'] ); ?>
			</td>
			<td>
				<?=htmlspecialchars( $event['start_time'] ); ?>
			</td>
			<td>
				<?=htmlspecialchars( $event['team_title'] ); ?>
			</td>
			<td>
<?
			if( !$event['is_past'] ):
?>
				<?=$event['workers_count']; ?>/<?=$event['workers_max']; ?>
<?
			endif;
?>
			</td>
		</tr>
<?
		endforeach;
?>
	</tbody>
</table>
<?
	}

	private function renderLunches()
	{
		global $wgScriptPath;
		$imagesPath = "$wgScriptPath/extensions/TmeitCommon/images/";
?>
<h2>Luncher</h2>

<table class="tmeit-new-table tmeit-table-spacing tmeit-fourfifth-width">
	<thead>
		<tr>
			<th class="tmeit-event-action-column"></th>
			<th class="tmeit-event-lunch-column">
				Titel
			</th>
			<th>
				Datum/tid
			</th>
			<th>
				Arbetslag
			</th>
			<th>
				Anmälda
			</th>
		</tr>
    </thead>
    <tbody>
<?
		foreach( $this->lunches as $eventId => $event ):
			$mayWork = $this->mayWorkEvent( $event );
			$mayReport = !$event['is_reported'] && $this->db->reportMayEdit( $event, $this->isAdmin, $this->isAdminOfTeam );
			$hasReport = $event['is_reported'];
			$mayDelete = $this->isAdmin;

			$workUrl = $this->workEventUrl.$eventId;
			$reportUrl = $this->reportUrl.$eventId;
?>
		<tr>
			<td class="action-column">
				<? $this->actionIf( $mayWork, $workUrl, $imagesPath.'work.png', 'Jobba?' ); ?>
				<? $this->actionIf( $mayReport, $reportUrl, $imagesPath.'checkoff.png', 'Stäm av' ); ?>
				<? $this->actionIf( $hasReport, $reportUrl, $imagesPath.'checkoff-ok.png', 'Visa avstämning' ); ?>
				<? $this->actionIf( $mayDelete, 'javascript:promptDelete('.$eventId.');', $imagesPath.'delete.png', 'Ta bort' ); ?>
			</td>
			<td<? if( $event['is_past'] ): ?> class="tmeit-event-past"<? endif; ?>>
				<?=htmlspecialchars( $event['title'] ); ?>
			</td>
			<td class="nowrap-column">
				<?=htmlspecialchars( $event['start_date'].' '.$event['start_time'] ); ?>
			</td>
			<td>
				<?=htmlspecialchars( $event['team_title'] ); ?>
			</td>
			<td>
<?
			if( !$event['is_past'] ):
?>
				<?=$event['workers_count']; ?>/<?=$event['workers_max']; ?>
<?
			endif;
?>
			</td>
		</tr>
<?
		endforeach;
?>
	</tbody>
</table>
<?
		if( $this->isAdmin ):
?>

<form action="" method="post">
    <h3>Lägg till luncher</h3>
	<table class="tmeit-new-table tmeit-table-spacing tmeit-half-width">
		<tr>
			<td>
				Namn
			</td>
			<td>
                <input type="text" class="short-text" name="event_title" value="<?=htmlspecialchars( $this->lunch['title'] ); ?>" />
			</td>
		</tr>
        <tr>
            <td rowspan="2">
                Datum
            </td>
            <td>
                <input type="text" class="short-text" name="start_date" value="<?=htmlspecialchars( substr( $this->lunch['starts_at'], 0, 10 ) ); ?>" />
                <span class="field-hint">YYYY-MM-DD</span>
            </td>
        </tr>
        <tr>
            <td>
				<input type="checkbox" name="create_week" value="1" checked="checked" /> Skapa för hela veckan
			</td>
		</tr>
        <tr>
            <td>
                Tid
            </td>
            <td>
                <input type="text" class="short-text" name="start_time" value="<?=htmlspecialchars( substr( $this->lunch['starts_at'], 11 ) ); ?>" />
                <span class="field-hint">HH:MM</span>
            </td>
        </tr>
		<tr>
			<td>
				Arbetslag
			</td>
			<td>
				<select name="team_id" class="short-text"<? if( !$this->isAdmin ): ?> disabled="disabled"<? endif; ?>>
					<option value="0">(Inget)</option>
<?
			foreach( $this->teams as $teamId => $team ):
?>
					<option value="<?=$teamId; ?>"<? if( $teamId == $this->lunch['team_id'] ): ?> selected="selected"<? endif; ?>>
						<?=htmlspecialchars( $team['title'] ); ?>
					</option>
<?
			endforeach;
?>
				</select>
			</td>
		</tr>
        <tr>
            <td>
                Antal arbetande
            </td>
            <td>
                <input type="text" class="shorter-text" name="workers_max" value="<?=htmlspecialchars( $this->lunch['workers_max'] ); ?>" />
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                <input type="submit" value="Lägg till" />
            </td>
        </tr>
	</table>
</form>
<?
		endif;
	}

	private function renderFooter()
	{
?>
<h3 class="tmeit-links-header">Länkar</h3>
<ul>
<?
		if( $this->isAdmin ):
?>
	<li><a href="<?=$this->editEventUrl; ?>">Nytt evenemang</a></li>
<?
		endif;
?>
    <li><a href="<?=$this->getSpecialPageUrl( 'TmeitExtEventList' ); ?>">Lista externa evenemang</a></li>
    <li><a href="<?=$this->reportSummaryUrl; ?>">Lista arbetstillfällen per medlem</a></li>
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

	private function mayWorkEvent( $event )
	{
		return !$event['is_past'] && ( $event['workers_max'] > 0 || $event['workers_count'] > 0 );
	}

	private static function getDatesInWeek( $date )
	{
		$monday = strtotime( $date );
		while( 1 != date( 'N', $monday ) )
			$monday -= 86400; // look backwards until we find a monday

		$result = array();
		for( $day = 0; $day < 5; $day++ )
			$result[] = date( 'Y-m-d', $monday + ( $day * 86400 ) );

		return $result;
	}
}