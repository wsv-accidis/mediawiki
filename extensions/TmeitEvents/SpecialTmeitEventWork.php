<?php
/*
 * TMEIT Events extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class SpecialTmeitEventWork extends TmeitSpecialPage
{
	// Note: Keep in sync with TmeitWsWorkEvent
    const MinHour = 8;
    const MaxHour = 29;

	private $canSayYes;
	private $currentComment;
	private $currentWorking;
	private $currentRange;
	private $editEventUrl;
	private $event;
	private $maxCount;
	private $optionsLong;
	private $optionsShort;
	private $yesCount;
	private $workers;
	private $eventWorkUrl;

	public function __construct()
	{
		parent::__construct( 'TmeitEventWork', 'tmeit' );

		$this->optionsLong = array(
			TmeitDb::WorkYes => 'Ja, jag kommer att jobba',
			TmeitDb::WorkMaybe => 'Kanske',
			TmeitDb::WorkNo => 'Nej, jag kan inte jobba' );

		$this->optionsShort = array(
			TmeitDb::WorkYes => 'Svarat Ja',
			TmeitDb::WorkMaybe => 'Svarat Kanske',
			TmeitDb::WorkNo => 'Svarat Nej' );
	}

	protected function prepare( $par )
	{
        if( empty( $par ) ||  FALSE == ( $this->event = $this->db->eventGetById( (int) $par ) ) )
            throw new FatalError( 'Evenemanget kunde inte hittas.' );
        if( 0 == $this->event['workers_max'] || $this->event['is_past'] )
            throw new FatalError( 'Det här evenemanget är gammalt eller redan fullt, det går inte att anmäla sig till det.' );

		$this->editEventUrl = $this->getSpecialPageUrl( 'TmeitEventEdit' ).'/'.$this->event['id'];
		$this->eventWorkUrl = $this->getPageTitle()->getFullURL();
		$this->workers = $this->db->eventGetWorkersById( $this->event['id'] );
		$this->maxCount = $this->event['workers_max'];

		global $wgUser;
		$currentUserId = $this->db->userGetIdByMediaWikiUserId( $wgUser->getId() );
		if( 0 == $currentUserId )
			throw new FatalError( 'Användaren kunde inte hittas. Kontakta webbansvarig.' );

		$this->currentComment = '';
		$this->currentWorking = TmeitDb::WorkNo;
		$this->currentRange = array( self::MinHour + 2, self::MaxHour - 2 );

		$this->yesCount = 0;
		foreach( $this->workers as $id => $worker )
		{
			if( $worker['working'] == TmeitDb::WorkYes )
				++$this->yesCount;

			if( $id == $currentUserId )
			{
				$this->currentWorking = $worker['working'];
				$this->currentComment = $worker['comment'];
				$this->currentRange = $worker['range'];

				if( FALSE != $this->currentRange )
                {
                    // Sanity checks in case Min/Max change over time
                    if( $this->currentRange[0] < self::MinHour )
                        $this->currentRange[0] = self::MinHour;
                    if( $this->currentRange[1] > self::MaxHour )
                        $this->currentRange[1] = self::MaxHour;
                    if( $this->currentRange[0] >= $this->currentRange[1] )
                        $this->currentRange = FALSE;
                }
			}
		}

		$this->canSayYes = ( $this->currentWorking == TmeitDb::WorkYes || $this->yesCount < $this->maxCount );

		if( $this->isAdmin && 0 != ( $removeUserId = $this->getIntField( 'remove' ) ) )
		{
			$this->db->eventRemoveWorker( $this->event['id'], $removeUserId );
			$this->redirectToSpecial( 'TmeitEventWork', '/'.$this->event['id'] );
		}
		elseif( $this->wasPosted() )
		{
			$working = $this->getIntField( 'working' );
			if( !$this->canSayYes && $working == TmeitDb::WorkYes )
				throw new FatalError( 'Evenemanget är fullt, du kan inte anmäla dig till att jobba.' );

			$comment = $this->getTextField( 'comment' );
			if( $this->getBoolField( 'work_has_range' ) )
            {
                $workFrom = self::MinHour + $this->getIntField( 'work_from' );
                $workUntil = self::MinHour + $this->getIntField( 'work_until' );
                if( $workFrom < self::MinHour )
                    $workFrom = self::MinHour;
                if( $workUntil > self::MaxHour )
                    $workUntil = self::MaxHour;

                if( $workFrom >= $workUntil )
                    throw new FatalError( 'Tidintervallet måste omfatta minst en timme (hur hade du tänkt jobba 0 minuter?)' );

                $range = array( $workFrom, $workUntil );
            }
            else
                $range = FALSE;

			$this->db->eventSaveWorker( $this->event['id'], $currentUserId, $working, $range, $comment );
			$this->redirectToSpecial( 'TmeitEventWork', '/'.$this->event['id'].'?saved=1' );
		}

		$this->workers = self::splitWorkers( $this->workers );
		$this->setupJs();
		return true;
	}

    private function setupJs()
    {
    	$out = $this->getOutput();
		$out->addJsConfigVars( 'workOpts', array_keys( $this->optionsLong ) );
		$out->addJsConfigVars( 'workMinHour', self::MinHour );
		$out->addJsConfigVars( 'workMaxHour', self::MaxHour );
		$out->addJsConfigVars( 'workInitMin', ( FALSE == $this->currentRange ? 2 : $this->currentRange[0] - self::MinHour ) );
		$out->addJsConfigVars( 'workInitMax', ( FALSE == $this->currentRange ? self::MaxHour - self::MinHour - 2 : $this->currentRange[1] - self::MinHour ) );
		$out->addModules( ['jquery.ui.slider', 'ext.tmeit.events.specialtmeiteventwork'] );
    }

	private static function getTimelineImage( $imagesPath, $color, $i = 0 )
    {
        if( 'green' == $color || 'red' == $color )
            return $imagesPath.'timeline/'.$color.( $i < 10 ? '0'.$i : $i ).'.png';
        else
            return $imagesPath.'timeline/gray.png';
    }

	private static function splitWorkers( $workers )
	{
		$splitWorkers = array(
			TmeitDb::WorkYes => array(),
			TmeitDb::WorkMaybe => array(),
			TmeitDb::WorkNo => array() );

		foreach( $workers as $id => $worker )
			$splitWorkers[$worker['working']][$id] = $worker;

		return $splitWorkers;
	}

	protected function render()
	{
		$this->renderIntro();
		$this->renderForm();
		$this->renderWorkersList( TmeitDb::WorkYes );
		$this->renderWorkersList( TmeitDb::WorkMaybe );
		$this->renderWorkersList( TmeitDb::WorkNo );
		$this->renderFooter();
	}

	private function renderFooter()
	{
?>
<h3 class="tmeit-links-header">Länkar</h3>
<ul>
<?
		if( $this->isAdmin ):
?>
	<li><a href="<?=$this->editEventUrl; ?>">Redigera evenemanget</a></li>
<?
		endif;
?>
	<li><a href="<?=$this->getSpecialPageUrl( 'TmeitEventList' ); ?>">Lista evenemang</a></li>
	<li><a href="<?=$this->getSpecialPageUrl( 'TmeitMemberList' ); ?>">Lista medlemmar</a></li>
	<li><a href="<?=$this->getPageUrl( 'Internsidor' ); ?>">Internsidor...</a></li>
</ul>
<script type="text/javascript">
	function promptDelete( id )
	{
		if( confirm( 'Är du säker på att du vill ta bort jobbaren?' ) )
			location.href = '<?=TmeitUtil::strF( "{0}/{1}?remove=", $this->eventWorkUrl, $this->event['id'] ); ?>' + id;
	}
</script>
<?
	}

	private function renderForm()
	{
		if( $this->getBoolField( 'saved' ) ):
?>
<p class="tmeit-form-saved">Sparat!</p>
<?
		endif;
?>
<form id="tmeit-form" action="" method="post">
    <h3>Din anmälan</h3>
	<table class="tmeit-new-table tmeit-half-width tmeit-table-spacing">
	    <tbody>
<?
		foreach( $this->optionsLong as $key => $description ):
?>
            <tr>
                <td id="tmeit-event-work-radio<?=$key; ?>" class="tmeit-event-work-select-column<? if( $this->currentWorking == $key ): ?> tmeit-event-work-selected<? endif; ?>" >
                    <input type="radio" name="working" id="working<?=$key; ?>" value="<?=$key; ?>"<? if( $this->currentWorking == $key ): ?> checked="checked"<? endif; if( !$this->canSayYes && $key == TmeitDb::WorkYes ): ?> disabled="disabled"<? endif; ?> />
                </td>
                <td id="tmeit-event-work-description<?=$key; ?>" class="<? if( $this->currentWorking == $key ): ?>tmeit-event-work-selected<? endif; ?>">
                    <label for="working<?=$key; ?>"><?=htmlspecialchars( $description ); ?></label>
                </td>
            </tr>
<?
		endforeach;
?>
        </tbody>
        <tfoot>
       		<tr>
                <td class="tmeit-center" style="padding-top: 10px">
                    <input type="submit" value="Spara" />
                </td>
		    </tr>
        </tfoot>
	</table>

    <div id="tmeit-event-work-between"<? if( TmeitDb::WorkNo == $this->currentWorking ): ?> style="display: none"<? endif; ?>>
        <h3>Vilka tider kan du jobba?</h3>
        <table class="tmeit-new-table tmeit-fourfifth-width tmeit-table-spacing">
            <tbody>
                <tr>
                    <td class="tmeit-event-work-select-column">
                        <input type="radio" name="work_has_range" id="work-has-range0" value="0"<? if( FALSE == $this->currentRange ): ?> checked="checked"<? endif; ?> />
                    </td>
                    <td>
                        <label for="work-has-range0">Jag vet inte vilka tider jag kan jobba</label>
                    </td>
                </tr>
                <tr>
                    <td class="tmeit-event-work-select-column">
                        <input type="radio" name="work_has_range" id="work-has-range1" value="1"<? if( FALSE != $this->currentRange ): ?> checked="checked"<? endif; ?> />
                    </td>
                    <td class="tmeit-event-slider-description">
                        <label for="work-has-range1">Jag kan jobba mellan <span id="label-work-from" class="tmeit-color-dashing"></span> <span>-</span> <span id="label-work-until" class="tmeit-color-dashing"></span>.</label>
                        <span class="field-hint">Dra i handtagen för att välja tidsintervall.</span>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td id="tmeit-event-slider-container">
                        <div id="tmeit-event-slider"></div>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td class="tmeit-center">
                        <input type="submit" value="Spara" />
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <input type="hidden" id="work-from" name="work_from" value="-1" />
    <input type="hidden" id="work-until" name="work_until" value="-1" />

    <h3>Andra kommentarer</h3>
    <table class="tmeit-new-table tmeit-twothird-width tmeit-table-spacing">
        <tbody>
            <tr>
                <td>
                    <textarea id="tmeit-event-work-comments" name="comment"><?=htmlspecialchars( $this->currentComment ); ?></textarea>
                </td>
            </tr>
		</tbody>
		<tfoot>
            <tr>
                <td>
                    <input type="submit" value="Spara" />
                </td>
            </tr>
		</tfoot>
	</table>
</form>
<?
	}

	private function renderIntro()
	{
?>
<p class="tmeit-event-info">
	Just nu är <span><?=$this->yesCount; ?></span> av maximalt <span><?=$this->maxCount; ?></span> anmäld<?=( $this->maxCount > 1 ? 'a' : '' ); ?>
	till <span class="tmeit-color-unicorn tmeit-style-shadowed"><?=htmlspecialchars( $this->event['title'] ); ?></span> som äger rum
	<span class="tmeit-color-pinka tmeit-style-shadowed"><?=htmlspecialchars( $this->event['starts_at'] ); ?></span>
	i <span><?=htmlspecialchars( $this->event['location'] ); ?></span>.
</p>
<?
		if( !$this->canSayYes ):
?>
<p class="tmeit-important-note">Du kan inte anmäla dig att jobba eftersom evenemanget är fullt.</p>
<?
		endif;
	}

	private function renderTimeline( $worker, $imagesPath )
    {
        $imageFormat = '<img src="{0}" alt="{1}" title="{1}" />';
        $range = $worker['range'];
        $rangeMin = ( FALSE == $range ? FALSE : $range[0] );
        $rangeMax = ( FALSE == $range ? FALSE : $range[1] );

        for( $hour = self::MinHour; $hour <= self::MaxHour; $hour++ )
        {
            $hr = $hour >= 24 ? $hour - 24 : $hour;
            $hourFormat = ( $hr < 10 ? '0'.$hr : $hr ).':00';
            if( FALSE != $range )
            {
                $color = ( $hour >= $rangeMin && $hour <= $rangeMax ? 'green' : 'red' );
                echo TmeitUtil::strF( $imageFormat, self::getTimelineImage( $imagesPath, $color, $hr ), $hourFormat );
            }
            else
                echo TmeitUtil::strF( $imageFormat, self::getTimelineImage( $imagesPath, 'gray' ), $hourFormat );
        }
    }

	private function renderWorkersList( $working )
	{
		if( empty( $this->workers[$working] ) )
			return;

		global $wgScriptPath;
		$imagesPath = "$wgScriptPath/extensions/TmeitCommon/images/";
?>
<h3><?=htmlspecialchars( $this->optionsShort[$working] ); ?></h3>

<table class="tmeit-new-table tmeit-full-width tmeit-table-spacing">
	<thead>
		<tr>
			<th></th>
			<th class="tmeit-event-worker-name-column">
				Namn
			</th>
			<th class="tmeit-event-worker-phone-column" colspan="2">
				Telefonnummer
			</th>
			<th class="tmeit-event-worker-email-column">
				E-post
			</th>
			<th class="tmeit-event-worker-group-column">
				Grupp
			</th>
			<th class="tmeit-event-worker-team-column">
				Arbetslag
			</th>
		</tr>
	</thead>
	<tbody>
<?
		foreach( $this->workers[$working] as $id => $worker ):
?>
	<tr>
		<td class="action-column">
			<? $this->actionIf( $this->isAdmin, 'javascript:promptDelete('.$id.');', $imagesPath.'delete.png', 'Ta bort' ); ?>
		</td>
		<td>
			<?=htmlspecialchars( $worker['realname'] ); ?>
		</td>
		<td class="nowrap-column" colspan="2">
			<?=htmlspecialchars( $worker['phone'] ); ?>
		</td>
		<td>
			<a href="mailto:<?=htmlspecialchars( $worker['email'] ); ?>"><?=htmlspecialchars( $worker['email'] ); ?></a>
		</td>
		<td>
			<?=htmlspecialchars( $worker['group_title'] ); ?>
		</td>
		<td>
			<?=( NULL != $worker['team_title'] ? htmlspecialchars( $worker['team_title'] ) : '(Inget)' ); ?>
		</td>
	</tr>
<?
            if( $working != TmeitDb::WorkNo ):
?>
	<tr>
        <td></td>
        <td colspan="2" class="tmeit-event-worker-comments">
            <?=htmlspecialchars( $worker['comment'] ); ?>
        </td>
        <td colspan="5" class="tmeit-event-worker-timeline">
<?
                $this->renderTimeline( $worker, $imagesPath );
?>
        </td>
    </tr>
<?
			elseif( !empty( $worker['comment'] ) ):
?>
	<tr>
        <td></td>
        <td colspan="7" class="tmeit-event-worker-comments">
            <?=htmlspecialchars( $worker['comment'] ); ?>
        </td>
    </tr>
<?
			endif;
		endforeach;
?>
	</tbody>
</table>
<?
	}
}