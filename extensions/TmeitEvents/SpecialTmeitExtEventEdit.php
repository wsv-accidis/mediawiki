<?php

/*
 * TMEIT Events extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class SpecialTmeitExtEventEdit extends TmeitSpecialPage
{
    const TruncateUrlLength = 40;

    private $currentUserId;
    private $event;
    private $isCreatingEvent;
    private $isAttending;
    private $thisUrl;

    public function __construct()
    {
        parent::__construct( 'TmeitExtEventEdit', 'tmeit' );
    }

    protected function prepare( $par )
    {
        global $wgUser;
        $this->currentUserId = $this->db->userGetIdByMediaWikiUserId( $wgUser->getId() );
        if( 0 == $this->currentUserId )
			throw new FatalError( 'Användaren kunde inte hittas. Kontakta webbansvarig.' );

        $this->isCreatingEvent = empty( $par );
        $this->thisUrl = $this->getPageTitle()->getFullURL();

        if( $this->isCreatingEvent )
        {
            if( !$this->isAdmin )
            {
                $this->redirectToSpecial( 'TmeitExtEventList' );
                return false;
            }

            $this->event = $this->db->extEventGetNew();
            $this->isAttending = false;
            $this->getOutput()->setPageTitle( "TMEIT - Nytt externt evenemang" );
        }
        else
        {
            $this->event = $this->db->extEventGetById( (int) $par );
            if( FALSE === $this->event )
                throw new FatalError( 'Evenemanget kunde inte hittas.' );

            $unAttendId = $this->getIntField( 'unattend' );
		    if( $unAttendId > 0 && ( $this->isAdmin || $this->currentUserId == $unAttendId ) )
            {
			    $this->db->extEventUnsetAttendee( $this->event['id'], $unAttendId, ( $this->currentUserId != $unAttendId ) );
			    $this->redirectToSpecial( $this->getName(), '/'.$this->event['id'] );
            }

            $this->isAttending = isset( $this->event['attendees'][$this->currentUserId] );
            $this->getOutput()->setPageTitle( 'TMEIT - Externt evenemang' );
        }

        if( $this->wasPosted() )
        {
            if( !$this->isCreatingEvent && $this->hasField( 'signup-self' ) )
            {
                $dob = $this->getTextField( 'attendee_dob' );
                $foodPrefs = $this->getTextField( 'attendee_food' );
                $drinkPrefs = $this->getTextField( 'attendee_drink' );
                $notes = $this->getTextField( 'attendee_notes' );
                if( FALSE === ( $dob = TmeitUtil::validateDate( $dob ) ) )
                    throw new FatalError( 'Födelsedatumet tycks inte vara korrekt angivet. Kontrollera att det är på formatet YYYY-MM-DD.' );

                $this->db->extEventAddOrUpdateAttendee( $this->event['id'], $this->currentUserId, $dob, $foodPrefs, $drinkPrefs, $notes );
                $this->isAttending = true;
            }
            elseif( !$this->isCreatingEvent && $this->hasField( 'signup-other' ) )
            {
                $userId = $this->getIntField( 'attendee_id' );
                if( 0 != $userId && !isset( $this->event['attendee'][$userId] ) )
                    $this->db->extEventAddOrUpdateAttendee( $this->event['id'], $userId, '', '', '', '', true );
            }
            elseif( $this->hasField( 'edit-event' ) )
            {
                $title = $this->getTextField( 'event_title' );
                $date = $this->getTextField( 'event_date' );
                $lastSignup = $this->getTextField( 'event_last_signup' );
                $url = $this->getTextField( 'event_url' );
                $body = $this->getTextField( 'event_body' );

                if( FALSE === ( $date = TmeitUtil::validateDate( $date ) ) )
                    throw new FatalError( 'Datumet tycks inte vara korrekt angivet. Kontrollera att det är på formatet YYYY-MM-DD.' );

                if( empty( $lastSignup ) )
                    $lastSignup = $date;
                if( FALSE === ( $lastSignup = TmeitUtil::validateDate( $lastSignup ) ) )
                    throw new FatalError( 'Sista anmälningsdatum tycks inte vara korrekt angivet. Kontrollera att det är på formatet YYYY-MM-DD.' );

                if( $this->isCreatingEvent )
                {
                    $this->event['id'] = $this->db->extEventCreate( $title, $date, $lastSignup, $body, $url );
                    $this->redirectToSpecial( $this->getName(), '/'.$this->event['id'] );
                }
                else
                    $this->db->extEventUpdate( $this->event['id'], $title, $date, $lastSignup, $body, $url );
            }

            // Reload the event
            $this->event = $this->db->extEventGetById( $this->event['id'] );
        }

        return true;
    }

    protected function render()
    {
        $hasAttendees = count( $this->event['attendees'] ) > 0;
        if( !$this->isCreatingEvent )
        {
            $this->renderBasicInfo();

            if( !$this->event['is_past_signup'] )
                $this->renderSignupForm();

            if( $hasAttendees )
                $this->renderAttendeeInfo();
        }

        if( $this->isAdmin )
        {
            if( !$this->isCreatingEvent )
                $this->renderManualSignup();

            $this->renderEditForm();

            if( !$this->isCreatingEvent && $hasAttendees )
                $this->renderKmrExport();
        }

        if( count( $this->event['logs'] ) > 0 )
            $this->renderLogs();

        $this->renderFooter();
    }

    private static function getTextForLogEntry( $log )
    {
        switch( $log['action'] )
        {
            case TmeitDb::ExtEventLogAttend:
                return TmeitUtil::strF( '<span class="tmeit-color-dashing">{0}</span> anmälde sig.', htmlspecialchars( $log['user_name'] ) );
            case TmeitDb::ExtEventLogUnattend:
                return TmeitUtil::strF( '<span class="tmeit-color-pinka">{0}</span> avanmälde sig.', htmlspecialchars( $log['user_name'] ) );
            case TmeitDb::ExtEventLogAdminAttend:
                return TmeitUtil::strF( '<span class="tmeit-color-dashing">{0}</span> blev anmäld av en mästare.', htmlspecialchars( $log['user_name'] ) );
            case TmeitDb::ExtEventLogAdminUnattend:
                return TmeitUtil::strF( '<span class="tmeit-color-pinka">{0}</span> blev avanmäld av en mästare.', htmlspecialchars( $log['user_name'] ) );
            default:
                return 'Okänd händelse.';
        }
    }

    private function renderAttendeeInfo()
    {
		global $wgScriptPath;
		$imagesPath = "$wgScriptPath/extensions/TmeitCommon/images/";
?>
<h3>Deltagare</h3>
<table class="tmeit-new-table tmeit-full-width tmeit-table-spacing">
	<thead>
		<tr>
			<th></th>
			<th>Namn</th>
			<th>Född</th>
			<th>Matpreferens</th>
			<th>Dryckpreferens</th>
		</tr>
	</thead>
	<tbody>
<?
        foreach( $this->event['attendees'] as $attId => $att ):
?>
        <tr>
            <td class="action-column">
                <? $this->actionIf( $this->isAdmin || $attId == $this->currentUserId, 'javascript:promptDelete('.$attId.');', $imagesPath.'delete.png', 'Avanmäl' ); ?>
			</td>
			<td>
                <?=htmlspecialchars( $att['user_name'] ); ?>
            </td>
			<td class="nowrap-column">
                <?=htmlspecialchars( $att['dob'] ); ?>
            </td>
			<td>
                <?=htmlspecialchars( $att['food_prefs'] ); ?>
            </td>
			<td>
                <?=htmlspecialchars( $att['drink_prefs'] ); ?>
            </td>
        </tr>
<?
            if( !empty( $att['notes'] ) && $this->isAdmin ):
?>
        <tr>
            <td></td>
            <td colspan="4" class="tmeit-extevent-attendee-notes">
                <?=htmlspecialchars( $att['notes'] ); ?>
            </td>
        </tr>
<?
            endif;
        endforeach;
?>
	</tbody>
</table>

<script type="text/javascript">
	function promptDelete( id )
	{
		if( confirm( 'Är du säker på att du vill avanmäla?' ) )
			location.href = '<?=TmeitUtil::strF( "{0}/{1}?unattend=", $this->thisUrl, $this->event['id'] ); ?>' + id;
	}
</script>
<?
    }

    private function renderBasicInfo()
    {
?>
<table class="tmeit-extevent-info-table tmeit-naked-table">
    <tr>
        <td class="tmeit-extevent-info-left">
            <div class="tmeit-extevent-info-title tmeit-color-dashing tmeit-style-shadowed">
                <?=htmlspecialchars( $this->event['title'] ); ?>
            </div>
            <div class="tmeit-extevent-info-start-date tmeit-color-pinka tmeit-style-shadowed">
                <?=htmlspecialchars( $this->event['start_date'] ); ?>
            </div>
            <div class="tmeit-extevent-info-text">
                Sista anmälan
                <span><?=htmlspecialchars( $this->event['last_signup'] ); ?></span>
            </div>
<?
        if( empty( $this->event['attendees'] ) ):
?>
            <div class="tmeit-extevent-info-text tmeit-color-unicorn">
                Inga anmälda<? if( !$this->event['is_past'] ): ?> ännu<? endif; ?>.
            </div>
<?
        endif;
        $preText = $this->event['is_past_signup'] ? 'Anmälan är stängd och du är' : 'Du är just nu';
        if( $this->isAttending ):
            $postText = $this->event['is_past_signup'] ? 'Kontakta en mästare om du behöver göra ändringar.' : 'Du kan uppdatera dina uppgifter nedan.';
?>
            <div>
                <?=$preText; ?> <span class="tmeit-extevent-signed-up tmeit-color-unicorn">anmäld</span>. <?=$postText; ?>
            </div>
<?
        else:
            $postText = $this->event['is_past_signup'] ? (
                $this->event['is_past'] ? '' : 'Kontakta en mästare eller ansvarigt mästeri om sen anmälan.'
            ) : 'Du kan anmäla dig nedan.';
?>
            <div>
                <?=$preText; ?> <span class="tmeit-extevent-signed-up tmeit-color-dashing">inte anmäld</span>. <?=$postText; ?>
            </div>
<?
        endif;
?>
        </td>
        <td class="tmeit-extevent-info-right">
            <?=nl2br( htmlspecialchars( $this->event['body'] ) ); ?>
<?
        if( !empty( $this->event['external_url'] ) ):
?>
            <div class="tmeit-extevent-info-url">
                <a href="<?=htmlspecialchars( $this->event['external_url'] ); ?>">Klicka här för mer info</a>
                <span>på <a href="<?=htmlspecialchars( $this->event['external_url'] ); ?>"><?=self::truncateUrl( $this->event['external_url'] ); ?></a></span>
            </div>
<?
        endif;
?>
        </td>
    </tr>
</table>
<?
    }

    private function renderEditForm()
    {
        $title = $this->isCreatingEvent ? 'Nytt' : 'Redigera';
?>
<h3><?=$title; ?> evenemang</h3>
<form method="post">
    <table class="tmeit-new-table tmeit-fourfifth-width tmeit-table-spacing">
        <tbody>
            <tr>
                <td class="tmeit-extevent-caption-column">Namn</td>
                <td>
                    <input type="text" name="event_title" class="long-text" value="<?=htmlspecialchars( $this->event['title'] ); ?>" />
                </td>
            </tr>
            <tr>
                <td class="tmeit-extevent-caption-column">Datum</td>
                <td>
                    <input type="text" name="event_date" class="short-text" value="<?=htmlspecialchars( $this->event['start_date'] ); ?>" />
                    <span class="field-hint">YYYY-MM-DD</span>
                </td>
            </tr>
            <tr>
                <td class="tmeit-extevent-caption-column nowrap-column">Sista anmälan</td>
                <td>
                    <input type="text" name="event_last_signup" class="short-text" value="<?=htmlspecialchars( $this->event['last_signup'] ); ?>" />
                    <span class="field-hint">YYYY-MM-DD - kan lämnas tomt</span>
                </td>
            </tr>
            <tr>
                <td class="tmeit-extevent-caption-column">URL</td>
                <td>
                    <input type="text" name="event_url" class="longer-text" value="<?=htmlspecialchars( $this->event['external_url'] ); ?>" />
                </td>
            </tr>
            <tr>
                <td class="tmeit-extevent-caption-column topalign-column">Information</td>
                <td>
                    <textarea class="tmeit-extevent-body" name="event_body"><?=htmlspecialchars( $this->event['body'] ); ?></textarea>
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td>
                    <input type="submit" name="edit-event" value="Spara evenemang" />
                </td>
            </tr>
        </tfoot>
    </table>
</form>
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
	<li><a href="<?=$this->getSpecialPageUrl( 'TmeitExtEventList' ); ?>">Lista externa evenemang</a></li>
	<li><a href="<?=$this->getPageUrl( 'Internsidor' ); ?>">Internsidor...</a></li>
</ul>
<?
    }

    private function renderKmrExport()
    {
?>
<h3>Deltagarinfo till KMR</h3>
<p class="tmeit-extevent-kmr-export">
Antal deltagare: <?=count( $this->event['attendees'] ); ?><br /><br />
<?
        foreach( $this->event['attendees'] as $att ):
?>
    <?=htmlspecialchars( TmeitUtil::strF( '{0}, {1}, {2}, {3}', $att['user_name'], str_replace( '-', '', $att['dob'] ), $att['food_prefs'], $att['drink_prefs'] ) ); ?><br />
<?
        endforeach;
?>
</p>
<?
    }

    private function renderLogs()
    {
?>
<h3>Händelselogg</h3>
<table class="tmeit-new-table tmeit-twothird-width tmeit-table-spacing">
    <tbody>
<?
        foreach( $this->event['logs'] as $log ):
?>
        <tr>
            <td class="nowrap-column"><?=htmlspecialchars( $log['log_time'] ); ?></td>
            <td><?=self::getTextForLogEntry( $log ); ?></td>
        </tr>
<?
        endforeach;
?>
    </tbody>
</table>
<?
    }

    private function renderManualSignup()
    {
        $users = $this->db->userGetListOfNames();
?>
<h3>Lägg till deltagare</h3>
<p>
    Här kan du som är mästare lägga till deltagare. Detta för att kunna komplettera listan i efterhand.
</p>
<form method="post">
    <table class="tmeit-new-table tmeit-twothird-width tmeit-table-spacing">
        <tbody>
            <tr>
                <td class="tmeit-extevent-caption-column">
                    Välj medlem
                </td>
                <td>
                    <select name="attendee_id">
                        <option value="0">(Ingen)</option>
<?
        foreach( $users as $user ):
            if( isset( $this->event['attendees'][$user['id']] ) )
                continue;
?>
                        <option value="<?=$user['id']; ?>"><?=htmlspecialchars( $user['realname'] ); ?></option>
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
                    <input type="submit" name="signup-other" value="Lägg till" />
                </td>
            </tr>
        </tfoot>
    </table>
</form>
<?
    }

    private function renderSignupForm()
    {
        if( $this->isAttending )
            $myInfo = $this->event['attendees'][$this->currentUserId];
        else
            $myInfo = $this->db->extEventGetBlankAttendee( $this->currentUserId );
?>
<h3>Anmälan</h3>
<form method="post">
    <table class="tmeit-new-table tmeit-full-width tmeit-table-spacing">
        <tbody>
            <tr>
                <td class="tmeit-extevent-caption-column">
                    Namn
                </td>
                <td>
                    <input type="text" class="medium-text" name="attendee_name" title="Ej redigerbart." value="<?=htmlspecialchars( $myInfo['user_name'] ); ?>" readonly="readonly" />
                </td>
                <td>
                    Född
                </td>
                <td>
                    <input type="text" class="short-text" title="Skriv på formatet YYYY-MM-DD." name="attendee_dob" value="<?=htmlspecialchars( $myInfo['dob'] ); ?>" />
                    <span class="field-hint">YYYY-MM-DD</span>
                </td>
            </tr>
            <tr>
                <td class="tmeit-extevent-caption-column">
                    Allergier/matpref.
                </td>
                <td>
                    <input type="text" class="long-text" name="attendee_food" title="Skriv t ex &quot;nötallergi&quot;, &quot;vegan&quot; eller lämna tomt." value="<?=htmlspecialchars( $myInfo['food_prefs'] ); ?>" />
                </td>
                <td>
                    Dryckespref.
                </td>
                <td>
                    <input type="text" class="long-text" name="attendee_drink" title="Skriv t ex &quot;öh&quot;, &quot;cider&quot; eller &quot;alkfri&quot;." value="<?=htmlspecialchars( $myInfo['drink_prefs'] ); ?>" />
                </td>
            </tr>
            <tr>
                <td class="tmeit-extevent-caption-column">
                    Övrigt
                </td>
                <td colspan="3">
                    <textarea class="tmeit-extevent-attend-notes" name="attendee_notes"><?=htmlspecialchars( $myInfo['notes'] ); ?></textarea>
                    <div class="field-hint" style="float: right">
                        För övriga upplysningar.<br />
                        Kan ses av Mästarna och dig.
                    </div>
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td></td>
                <td colspan="3">
                    <input type="submit" name="signup-self" value="Spara anmälan" />
<?
        if( $this->isAttending ):
?>
                    <input type="button" onclick="promptDelete(<?=$this->currentUserId; ?>);" value="Avanmäl mig" />
<?
        endif;
?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>
<?
    }

    private static function truncateUrl( $url )
    {
        if( 0 == substr_compare( $url, 'http://', 0, 7, TRUE ) )
            $url = substr( $url, 7 );
        elseif( 0 == substr_compare( $url, 'https://', 0, 8, TRUE ) )
            $url = substr( $url, 8 );

        if( mb_strlen( $url ) > self::TruncateUrlLength )
            $url = substr( $url, 0, self::TruncateUrlLength ).'...';

        return rtrim( $url );
    }
}
