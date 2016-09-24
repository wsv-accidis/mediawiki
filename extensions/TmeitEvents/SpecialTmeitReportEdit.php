<?php
/*
 * TMEIT Events extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class SpecialTmeitReportEdit extends TmeitSpecialEventPage
{
	const MaxEditableAge = 180; // 6 months

	private $event;
	private $isAdminOfTeam;
	private $isSaved;
	private $hasReport;
	private $mayEdit;
	private $multOptions;
	private $moreWorkers;
	private $report;
	private $userId;
	private $workers;

	function __construct()
	{
		parent::__construct( 'TmeitReportEdit', 'tmeit' );

		$this->multOptions = array(
			0 => '-',
			25 => '<2h',
			50 => '4h',
			75 => '6h',
			100 => '8h',
			125 => '>10h'
		);
	}

	protected function prepare( $par )
	{
		global $wgUser;
		$this->userId = $this->db->userGetIdByMediaWikiUserId( $wgUser->getId() );
		$this->isAdminOfTeam = $this->db->userGetTeamAdminByMediaWikiUserId( $wgUser->getId() );

		$eventId = (int) $par;
		if( FALSE == ( $this->event = $this->db->eventGetById( $eventId ) ) )
			throw new FatalError( 'Evenemanget kunde inte hittas.' );
		if( !$this->event['is_past'] )
			throw new FatalError( 'Evenemanget har inte varit ännu.' );

		$this->mayEdit = $this->db->reportMayEdit( $this->event, $this->isAdmin, $this->isAdminOfTeam );
		$this->report = $this->db->reportGetByEventId( $eventId );
		$this->hasReport = ( FALSE != $this->report );
		$this->workers = $this->hasReport ? $this->report['workers'] : $this->db->reportGetWorkersByEvent( $eventId );

		// If you're not an admin or a team lead, you may only view existing reports
		if( !$this->hasReport && !$this->mayEdit )
			throw new PermissionsError( 'tmeitadmin' );

		// Don't allow events older than a certain age to be reported except by admins
		if( $this->mayEdit && !$this->isAdmin && $this->db->eventGetAgeById( $eventId ) > self::MaxEditableAge )
			$this->mayEdit = false;

		if( $this->wasPosted() )
		{
			$workers = array();

			$workersListStr = $this->getTextField( 'worker_list' );
			if( !empty( $workersListStr ) )
			{
				$workerIds = array_map( 'intval', explode( ',', $workersListStr ) );
				foreach( $workerIds as $workerId )
				{
					$multi = $this->getIntField( 'worker_mult_'.$workerId );
					$comment = $this->getTextField( 'worker_comm_'.$workerId );
					$workers[$workerId] = array( 'multi' => $multi, 'comment' => $comment );
				}
			}

			if( $this->hasField( 'add_worker' ) && 0 != ( $addWorkerId = $this->getIntField( 'add_worker_id' ) )  && !isset( $workers[$addWorkerId] ) )
			{
				$worker = $this->db->reportGetBlankWorker( $addWorkerId );
				if( FALSE != $worker )
					$workers[$addWorkerId] = $worker;
			}

			$this->db->reportCreateOrUpdate( $eventId, $this->userId, $workers );

			// Reload
			$this->report = $this->db->reportGetByEventId( $eventId );
			$this->workers = $this->report['workers'];
			$this->hasReport = TRUE;
			$this->isSaved = TRUE;
		}

		$this->moreWorkers = $this->db->userGetPicklistByActive( array_keys( $this->workers ) );
		return true;
	}

	protected function render()
	{
		$this->renderIntro();

		if( $this->mayEdit )
		{
			if( !empty( $this->workers ) )
				$this->renderEditableTable();
			else
				$this->renderNoWorkers();

			$this->renderAddWorker();
		}
		else
			$this->renderReadonlyTable();

		$this->renderFooter();
	}

	private function renderAddWorker()
	{
		global $wgScriptPath;
?>
	<h3>Lägg till arbetare</h3>
	<table class="tmeit-new-table tmeit-twothird-width tmeit-table-spacing">
		<tbody>
			<tr>
                <td>
                    Välj medlem
                </td>
				<td>
					<select name="add_worker_id">
						<option value="0">(Välj)</option>
<?
		foreach( $this->moreWorkers as $userId => $user ):
?>
						<option value="<?=$userId; ?>"><?=htmlspecialchars( $user['realname'] ); ?></option>
<?
		endforeach;
?>
					</select>
				</td>
			</tr>
		</tbody>
        <tfoot>
            <tr>
                <td></td>
                <td>
					<input type="submit" name="add_worker" value="Lägg till"/>
					<img src="<?=$wgScriptPath; ?>/extensions/TmeitEvents/images/wait.gif" style="display: none" id="add_worker_wait" alt="Laddar..." />
                </td>
            </tr>
        </tfoot>
    </table>
</form>
<?
		if( $this->hasReport && !$this->wasPosted() ):
?>
<p class="tmeit-important-note">
	Det här är en gammal avstämning, men du kan fortfarande redigera den.<br />
	Tänk på att de grupper och arbetslag som visas är de som gäller nu, de kan ha ändrats sedan avstämningen gjordes.
</p>
<?
		endif;
	}

	private function renderFooter()
	{
?>
<h3 class="tmeit-links-header">Länkar</h3>
<ul>
    <li><a href="<?=$this->getSpecialPageUrl( 'TmeitEventList' ); ?>">Lista evenemang</a></li>
    <li><a href="<?=$this->getPageUrl( 'Internsidor' ); ?>">Internsidor...</a></li>
</ul>
<?
	}

	private function renderIntro()
	{
		if( $this->isSaved ):
?>
<p class="tmeit-form-saved">
	Sparad!
</p>
<?
		endif;
?>
<p class="tmeit-event-info">
    Det här är en avstämning av <span class="tmeit-color-unicorn tmeit-style-shadowed"><?=htmlspecialchars( $this->event['title'] ); ?></span>
    den <span class="tmeit-color-pinka tmeit-style-shadowed"><?=htmlspecialchars( $this->event['starts_at'] ); ?></span>.
</p>
<?
		if( $this->hasReport ):
?>
<p class="tmeit-event-report-info">
	Avstämningen gjordes av <span class="tmeit-color-dashing"><?=htmlspecialchars( $this->report['reporter_name'] ); ?></span>.
<?
			if( $this->report['reporter_id'] != $this->report['last_editor_id'] ):
?>
	Den uppdaterades senast av <span class="tmeit-color-dashing"><?=htmlspecialchars( $this->report['last_editor_name'] ); ?></span>.
<?
			endif;
?>
</p>
<?
		endif;
	}

	private function renderEditableTable()
	{
		global $wgScriptPath;
		$imagesPath = "$wgScriptPath/extensions/TmeitCommon/images/";
?>
<form method="post">
	<input type="hidden" name="worker_list" value="<?=implode( ',', array_keys( $this->workers ) ); ?>" />
    <table class="tmeit-new-table tmeit-full-width" style="margin-top: 10px" id="workers_table">
    	<thead>
			<tr>
				<th class="tmeit-event-worker-work-column">
					Jobbade
				</th>
				<th>
					Namn
				</th>
				<th>
					Grupp
				</th>
				<th>
					Arbetslag
				</th>
				<th class="tmeit-event-worker-comment-column">
					Kommentar
				</th>
			</tr>
        </thead>
        <tbody class="tmeit-pairwise-zebra">
<?
		foreach( $this->workers as $id => $worker ):
?>
			<tr>
				<td class="nowrap-column">
					<input type="checkbox" name="worker_<?=$id; ?>" id="worker_<?=$id; ?>" value="1"<? if( $worker['multi'] > 0 ): ?> checked="checked"<? endif; ?> onchange="refreshStateFromCheckbox(<?=$id; ?>);" />
					<select name="worker_mult_<?=$id; ?>" id="worker_mult_<?=$id; ?>" onchange="refreshStateFromSelect(<?=$id; ?>);">
<?
			foreach( $this->multOptions as $value => $key ):
?>
						<option value="<?=$value; ?>"<? if( $value == $worker['multi'] ): ?> selected="selected"<? endif; ?>><?=htmlspecialchars( $key ); ?></option>
<?
			endforeach;
?>
					</select>
				</td>
				<td class="nowrap-column">
					<label for="worker_<?=$id; ?>"><?=htmlspecialchars( $worker['user_name'] ); ?></label>
				</td>
				<td>
					<?=htmlspecialchars( empty( $worker['group_title'] ) ? '(Ingen)' : $worker['group_title'] ); ?>
				</td>
				<td>
					<?=htmlspecialchars( empty( $worker['team_title'] ) ? '(Inget)' : $worker['team_title'] ); ?>
				</td>
				<td class="action-column tmeit-event-worker-comment-column">
					<img id="worker_comm_on_<?=$id; ?>" src="<?=$imagesPath; ?>comment.png" onclick="toggleCommentOff(<?=$id; ?>);"<? if( empty( $worker['comment'] ) ): ?> style="display: none"<? endif; ?> />
					<img id="worker_comm_off_<?=$id; ?>" src="<?=$imagesPath; ?>comment-off.png" onclick="toggleCommentOn(<?=$id; ?>);"<? if( !empty( $worker['comment'] ) ): ?> style="display: none"<? endif; ?> />
				</td>
			</tr>
			<tr id="worker_comm_<?=$id; ?>" <? if( empty( $worker['comment'] ) ): ?> style="display: none"<? endif; ?>>
				<td></td>
				<td colspan="4">
					<input type="text" class="tmeit-event-report-comments" name="worker_comm_<?=$id; ?>" value="<?=htmlspecialchars( $worker['comment'] ); ?>" />
				</td>
			</tr>
<?
		endforeach;
?>
		</tbody>
	</table>

	<p class="tmeit-event-report-save">
		<input type="submit" class="tmeit-button" value="Spara" />
	</p>

	<script type="text/javascript">
		function refreshStateFromCheckbox(id) {
			if($('#worker_' + id).is(':checked')) {
				$('#worker_mult_' + id + " option[value='100']").prop('selected', true);
			} else {
				$('#worker_mult_' + id + " option[value='0']").prop('selected', true);
			}
		}

		function refreshStateFromSelect(id) {
			if($('#worker_mult_' + id).val() != '0') {
				$('#worker_' + id).prop('checked', true);
			} else {
				$('#worker_' + id).prop('checked', false);
			}
		}

		function toggleCommentOn(id) {
			$('#worker_comm_on_' + id).show();
			$('#worker_comm_off_' + id).hide();
			$('#worker_comm_' + id).show();
		}

		function toggleCommentOff(id) {
			$('#worker_comm_on_' + id).hide();
			$('#worker_comm_off_' + id).show();
			$('#worker_comm_' + id).hide();
		}
	</script>
<?
	}

	private function renderNoWorkers()
	{
?>
<form method="post">
    <p class="tmeit-explain-note">
        Listan är tom.
    </p>
<?
	}

	private function renderReadonlyTable()
	{
?>
<table class="tmeit-new-table tmeit-full-width tmeit-table-spacing">
	<thead>
		<tr>
			<th class="tmeit-event-worker-work-column">
				Jobbade
			</th>
			<th>
				Namn
			</th>
			<th>
				Grupp
			</th>
			<th>
				Arbetslag
			</th>
		</tr>
	</thead>
	<tbody>
<?
		foreach( $this->workers as $id => $worker ):
?>
		<tr>
			<td class="nowrap-column">
				<input type="checkbox" value="1" checked="checked" disabled="disabled" />
				<?=htmlspecialchars( $this->multOptions[$worker['multi']] ); ?>
			</td>
			<td class="nowrap-column">
				<?=htmlspecialchars( $worker['user_name'] ); ?>
			</td>
			<td>
				<?=htmlspecialchars( empty( $worker['group_title'] ) ? '(Ingen)' : $worker['group_title'] ); ?>
			</td>
			<td>
				<?=htmlspecialchars( empty( $worker['team_title'] ) ? '(Inget)' : $worker['team_title'] ); ?>
			</td>
		</tr>
<?
			if( !empty( $worker['comment'] ) ):
?>
		<tr>
			<td></td>
			<td colspan="4" class="tmeit-event-worker-comments">
				<?=htmlspecialchars( $worker['comment'] ); ?>
			</td>
		</tr>
<?
			endif;
		endforeach;
?>
	</tbody>
</table>

<p class="tmeit-important-note">
	Det här är en gammal avstämning. Kontakta mästare eller arbetslagsledare om du har synpunkter.<br />
	Tänk på att de grupper och arbetslag som visas är de som gäller nu, de kan ha ändrats sedan avstämningen gjordes.
</p>
<?
	}
}