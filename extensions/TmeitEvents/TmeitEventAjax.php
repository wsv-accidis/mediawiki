<?php
/*
 * TMEIT Events extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class TmeitEventAjax
{
	/** @var TmeitDb $db */
	private $db;

	public function __construct()
	{
		$this->db = new TmeitDb();
	}

	public function run()
	{
		global $wgRequest;
		global $wgUser;

		$event = $this->db->eventGetById( $wgRequest->getInt( 'id' ) );
		$teamTitle = ( $event['team_id'] > 0 ? $this->db->teamGetTitleById( $event['team_id'] ) : NULL );
		$isMember = $wgUser->isAllowed( 'tmeit' );

		if( FALSE == $event || $event['is_hidden'] )
			die( 'Någonting gick snett, försök igen.' );

		if( !empty( $event['body'] ) )
			$wikiText = $this->sandboxParse( $event['body'] );
?>
<h2><?=htmlspecialchars( $event['title'] ); ?></h2>
<table cellspacing="0" class="tmeit-event">
	<tr>
		<th>Plats:</th>
		<td><?=htmlspecialchars( $event['location'] ); ?></td>
	</tr>
	<tr>
		<th>Datum/tid:</th>
		<td><?=htmlspecialchars( $event['starts_at'] ); ?></td>
	</tr>
<?
		if( $isMember && NULL != $teamTitle ):
?>
	<tr>
		<th>Arbetslag:</th>
		<td><?=htmlspecialchars( $teamTitle ); ?></td>
	</tr>
<?
		endif;
		if( !empty( $event['external_url'] ) ):
?>
	<tr>
		<th>Mer info:</th>
		<td><a href="<?=htmlspecialchars( $event['external_url'] ); ?>">Klicka här</a></td>
	</tr>
<?
		endif;
?>
</table>
<?
		if( isset( $wikiText ) ):
?>
<div><?=$wikiText; ?></div>
<?
		endif;
	}

	// from http://www.mediawiki.org/wiki/Manual:Special_pages#OutputPage-.3EaddWikiText.28.29
	private function sandboxParse( $wikiText )
	{
		global $wgUser;
		$myParser = new Parser();
		$myParserOptions = ParserOptions::newFromUser( $wgUser );
		$result = $myParser->parse( $wikiText, Title::newFromText( 'Main Page' ), $myParserOptions );
		return $result->getText();
	}
}
?>
