<?php
/*
 * TMEIT Events extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class SpecialTmeitEvents extends TmeitSpecialPage
{
	private $events;
	private $firstEventId;

	public function __construct()
	{
		parent::__construct( 'TmeitEvents' );
	}

	protected function prepare( $par )
	{
		$this->events = $this->db->eventGetPublicList();

		$this->firstEventId = $this->getIntField( 'id' );
		if( 0 == $this->firstEventId && !empty( $this->events ) )
			$this->firstEventId = key( $this->events );

		return true;
	}

	protected function render()
	{
		global $wgScriptPath;
		if( empty( $this->events ) ):
?>
<div id="tmeit-event-list">
	Just nu finns inga kommande evenemang. Titta gärna in igen senare! Du kan också hitta fester och pubbar på <a href="http://fester.nu">Fester.nu</a>.
</div>
<?
			return;
		endif;
?>
<div id="tmeit-event-current"></div>
<div id="tmeit-event-list">
<?
		$lastDate = NULL;
		$inList = false;
		foreach( $this->events as $id => $event ):
			if( $event['start_date'] != $lastDate ):
				$lastDate = $event['start_date'];
				if( $inList ) echo "	</ul>\n";
?>
	<h3><?=htmlspecialchars( $this->formatEventDate( $event['start_month'], $event['start_day'], $event['start_weekday'] ) ); ?></h3>
	<ul>
<?
				$inList = true;
			endif;
?>
		<li><a href="#" onclick="openEvent( <?=$id; ?> );"><?=htmlspecialchars( $event['title'] ); ?></a><? if( $this->isMember && !empty( $event['team_title'] ) ): ?> (<?=htmlspecialchars( $event['team_title'] ); ?>)<? endif; ?></li>
<?
		endforeach;
		if( $inList ) echo "	</ul>\n";
?>
</div>

<p class="tmeit-post-script">
	Du kan också hitta fester och pubbar i Stockholm på <a href="http://fester.nu">Fester.nu</a>.
</p>

<script type="text/javascript">
	function openEvent( id ) {
		var currentEvent = $('#tmeit-event-current');
        currentEvent.removeClass('loaded')
				.html('<img src="<?=$wgScriptPath; ?>/skins/tmeit/wait_icon.gif" alt="Laddar ..." />');
        currentEvent.load('<?=$wgScriptPath; ?>/tmeit-jobs/AjaxEvent.php?id=' + id, function() { currentEvent.addClass('loaded'); });
	}

	$(document).ready(function() { openEvent( <?=$this->firstEventId; ?> ); });
</script>
<?
	}

	private static function formatEventDate( $month, $day, $weekDay )
	{
		$str = '';

		switch( $weekDay )
		{
			case 1: $str .= 'Söndag'; break;
			case 2: $str .= 'Måndag'; break;
			case 3: $str .= 'Tisdag'; break;
			case 4: $str .= 'Onsdag'; break;
			case 5: $str .= 'Torsdag'; break;
			case 6: $str .= 'Fredag'; break;
			case 7: $str .= 'Lördag'; break;
			default: $str .= 'ERROR'; break;
		}

		$str .= ' '.$day.' ';

		switch( $month )
		{
			case 1: $str .= 'januari'; break;
			case 2: $str .= 'februari'; break;
			case 3: $str .= 'mars'; break;
			case 4: $str .= 'april'; break;
			case 5: $str .= 'maj'; break;
			case 6: $str .= 'juni'; break;
			case 7: $str .= 'juli'; break;
			case 8: $str .= 'augusti'; break;
			case 9: $str .= 'september'; break;
			case 10: $str .= 'oktober'; break;
			case 11: $str .= 'november'; break;
			case 12: $str .= 'december'; break;
			default: $str .= 'ERROR'; break;
		}

		return $str;
	}
}