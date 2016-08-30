<?php
/*
 * TMEIT Events extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class SpecialTmeitEventEdit extends TmeitSpecialEventPage
{
	private $editEventUrl;
	private $event;
	private $listEventUrl;
	private $saved;
	private $teams;
	private $workEventUrl;

	public function __construct()
	{
		parent::__construct( 'TmeitEventEdit', 'tmeit' );
	}

	private function eventIsNew()
	{
		return !isset( $this->event['id'] );
	}

	protected function prepare( $par )
	{
		if( empty( $par ) )
			$this->event = $this->db->eventGetNew();
		elseif( FALSE == ( $this->event = $this->db->eventGetById( (int) $par ) ) )
			throw new FatalError( 'Evenemanget kunde inte hittas.' );

		$this->mayEditEventOrThrow( $this->event );

		if( $this->wasPosted() )
		{
			$this->event['title'] = $this->getTextField( 'event_title' );
			$this->event['location'] = $this->getTextField( 'location' );
			$this->event['starts_at'] = $this->getTextField( 'starts_at' );
			$this->event['body'] = $this->getTextField( 'body' );
			$this->event['external_url'] = $this->getTextField( 'external_url' );

			// Only admins can change the team
			if( $this->isAdmin )
				$this->event['team_id'] = $this->getIntField( 'team_id' );

			$this->event['is_hidden'] = $this->getBoolField( 'is_hidden' );
			$this->event['workers_max'] = $this->getIntField( 'workers_max' );

			if( empty( $this->event['title'] ) )
				throw new FatalError( 'Evenemanget saknar titel.' );
			if( !$this->isValidDateTime( $this->event['starts_at'] ) )
				throw new FatalError( 'Evenemanget saknar datum/tid eller så är det felaktigt ifyllt. Formatet måste vara YYYY-MM-DD hh:mm.' );

			// Save
			$savedId = $this->db->eventSave( $this->event );
			$this->saved = true;

			if( $this->eventIsNew() )
			{
				// Redirect to edit page, otherwise the URL will be wrong
				$this->redirectToSpecial( 'TmeitEventEdit', '/'.$savedId.'?saved=1' );
				return false;
			}
		}

		if( $this->eventIsNew() )
			$this->getOutput()->setPageTitle( "TMEIT - Nytt evenemang" );
		else
			$this->getOutput()->setPageTitle( "TMEIT - Redigera ".htmlspecialchars( $this->event['title'] ) );

		$this->teams = $this->db->teamGetList();
		$this->saved |= $this->getBoolField( 'saved' );
		$this->editEventUrl = $this->getPageTitle()->getFullURL();
		$this->listEventUrl = SpecialPage::getTitleFor( 'TmeitEventList' )->getFullURL();

		if( !$this->eventIsNew() )
			$this->workEventUrl = SpecialPage::getTitleFor( 'TmeitEventWork' )->getFullURL().'/'.$this->event['id'];

		return true;
	}

	protected function validateAccessEvent()
	{
		// Admins can always edit events
		if( $this->isAdmin )
			return true;

		// Team admins can edit their own team's events
		global $wgUser;
		return ( $this->event['team_id'] > 0 &&
			$this->event['team_id'] == $this->db->userGetTeamAdminByMediaWikiUserId( $wgUser->getId() ) );
	}

	protected function render()
	{
		if( $this->saved ):
?>
<p class="tmeit-form-saved">
	Sparad!
</p>
<?
		endif;
?>
<form method="post">
	<h3>Grundläggande attribut</h3>
	<table class="tmeit-new-table tmeit-twothird-width tmeit-table-spacing">
		<tr>
			<td>
				Namn
			</td>
			<td>
				<input type="text" class="long-text" name="event_title" value="<?=htmlspecialchars( $this->event['title'] ); ?>" />
			</td>
		</tr>
		<tr>
			<td>
				Plats
			</td>
			<td>
				<input type="text" class="medium-text" name="location" value="<?=htmlspecialchars( $this->event['location'] ); ?>" />
			</td>
		</tr>
		<tr>
			<td>
				Datum/tid
			</td>
			<td>
				<input type="text" class="medium-text" name="starts_at" value="<?=htmlspecialchars( $this->event['starts_at'] ); ?>" />
				<span class="field-hint">YYYY-MM-DD HH:MM</span>
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
					<option value="<?=$teamId; ?>"<? if( $teamId == $this->event['team_id'] ): ?> selected="selected"<? endif; ?>>
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
				Internt
			</td>
			<td>
				<input type="checkbox" name="is_hidden" id="is_hidden" value="1"<? if( $this->event['is_hidden'] ): ?> checked="checked"<? endif; ?> />
				<label for="is_hidden">Dölj det här evenemanget från offentliga listor</label>
			</td>
		</tr>
		<tr>
			<td>
				Antal arbetande
			</td>
			<td>
				<input type="text" class="shorter-text" name="workers_max" value="<?=htmlspecialchars( $this->event['workers_max'] ); ?>" />
			</td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="submit" value="Spara" />
			</td>
		</tr>
	</table>

	<h3>Information</h3>
	<table class="tmeit-new-table tmeit-fourfifth-width tmeit-table-spacing">
		<tr>
			<td class="topalign-column">
				Beskrivning
			</td>
			<td>
				<textarea id="tmeit-event-body-box" name="body"><?=htmlspecialchars( $this->event['body'] ); ?></textarea>
				<div class="field-hint-below">Kan innehålla <a href="http://www.mediawiki.org/wiki/Help:Editing">wikitext</a>.</div>
			</td>
		</tr>
		<tr>
			<td class="topalign-column">
				Extern URL
			</td>
			<td>
				<input type="text" class="longer-text" name="external_url" value="<?=htmlspecialchars( $this->event['external_url'] ); ?>" />
				<div class="field-hint-below">Länka till evenemanget på Facebook, Fester.nu eller dylikt.</div>
			</td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="submit" value="Spara" />
			</td>
		</tr>
	</table>
</form>

<h3 class="tmeit-links-header">Länkar</h3>
<ul>
<?
	if( !$this->eventIsNew() ):
?>
	<li><a href="<?=$this->workEventUrl; ?>">Visa/anmäl arbetande</a></li>
<?
	endif;
	if( $this->isAdmin ):
?>
	<li><a href="<?=$this->editEventUrl; ?>">Nytt evenemang</a></li>
<?
	endif;
?>
	<li><a href="<?=$this->listEventUrl; ?>">Lista evenemang</a></li>
	<li><a href="<?=$this->getPageUrl( 'Internsidor' ); ?>">Internsidor...</a></li>
</ul>
<?
	}
}