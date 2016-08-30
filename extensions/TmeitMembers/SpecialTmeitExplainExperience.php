<?php
/*
 * TMEIT Members extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class SpecialTmeitExplainExperience extends TmeitSpecialPage
{
	private $user;
	private $expl;

	public function __construct()
	{
		parent::__construct( 'TmeitExplainExperience', 'tmeit' );
	}

	protected function prepare( $par )
	{
		if( !empty( $par ) && $this->isAdmin )
		{
			if( FALSE == ( $this->user = $this->db->userGetByName( $par ) ) )
				throw new FatalError( 'Användaren kunde inte hittas.' );

			$this->getOutput()->setPageTitle( "TMEIT - Achivements och exp för ".htmlspecialchars( $this->user['username'] ) );
		}
		else
		{
			global $wgUser;
			$userId = $this->db->userGetIdByMediaWikiUserId( $wgUser->getId() );
			if( 0 == $userId )
				throw new FatalError( 'Användaren kunde inte hittas. Kontakta webbansvarig.' );
			$this->user = $this->db->userGetSimpleById( $userId );
		}

		$experience = new TmeitExperience();
		$this->expl = $experience->getExplanationByUser( $this->user );
		return true;
	}

	protected function render()
	{
		$pointsFromRules = $this->expl['points_rules'];
		$pointsTotal = $this->expl['points_total'];
		$pointsFromUnicorns = $pointsTotal - $pointsFromRules;
		$league = TmeitBadges::getById( $this->expl['current_league'][1] );
		$leagueTitle = str_replace( '{EXP}', $this->expl['current_league'][0], $league->getTitle() );
		$nextLeague = TmeitBadges::getById( $this->expl['next_league'][1] );
		$nextLeagueTitle = ( NULL != $nextLeague ? str_replace( '{EXP}', $this->expl['next_league'][0], $nextLeague->getTitle() ) : NULL );
?>
<p>
	Du har <span class="tmeit-explain-exp-span tmeit-style-shadowed tmeit-color-pinka"><?=TmeitExperience::formatPoints( $pointsTotal ); ?></span> exp-poäng,
	varav <span class="tmeit-explain-exp-span tmeit-style-shadowed tmeit-color-dashing"><?=TmeitExperience::formatPoints( $pointsFromRules ); ?></span> från din erfarenhet
	och <span class="tmeit-explain-exp-span tmeit-style-shadowed tmeit-color-unicorn"><?=TmeitExperience::formatPoints( $pointsFromUnicorns ); ?></span> från oförklarliga magiska enhörningar.
</p>
<p>
	Du ligger i <span class="tmeit-explain-league-span tmeit-style-shadowed"><img src="<?=$league->getUrl(); ?>" alt="" /> <?=htmlspecialchars( $leagueTitle ); ?></span>.
<?
	if( NULL !== $nextLeague ):
?>
	Nästa steg är <span class="tmeit-explain-next-league-span tmeit-style-shadowed"><img src="<?=$nextLeague->getUrl(); ?>" alt="" /> <?=htmlspecialchars( $nextLeagueTitle ); ?></span>.
<?
	endif;
?>
</p>
<?
	if( !empty( $this->expl['badges'] ) ):
?>
<h4>Dina achievements</h4>
<table class="tmeit-new-table tmeit-twothird-width tmeit-zebra-table tmeit-explain-badges-table">
<?
		foreach( $this->expl['badges'] as $badgeId ):
		$badge = TmeitBadges::getById( $badgeId );
?>
	<tr>
		<td class="tmeit-explain-badge-column"><img src="<?=$badge->getUrl(); ?>" alt="" /></td>
		<td><?=htmlspecialchars( $badge->getTitle() ); ?></td>
	</tr>
<?
		endforeach;
?>
</table>
<?
	endif;
	if( !empty( $this->expl['points_rows'] ) ):
?>
<h4>Dina poäng</h4>
<table class="tmeit-new-table tmeit-twothird-width tmeit-explain-points-table">
<?
		foreach( $this->expl['points_rows'] as $outerRow ):
			$expl = $outerRow[TmeitExperience::ExplainRoot];
?>
	<tr>
		<td class="tmeit-explain-points-outer-text"><?=htmlspecialchars( $expl[TmeitExperience::ExplainRootText] ); ?></td>
		<td></td>
		<td class="tmeit-explain-points-outer-points"><?=TmeitExperience::formatPoints( $outerRow[TmeitExperience::ExplainTotalPoints] ); ?></td>
	</tr>
<?
			if( isset( $expl[TmeitExperience::ExplainRows] ) ):
				foreach( $expl[TmeitExperience::ExplainRows] as $innerRow ):
?>
	<tr>
		<td class="tmeit-explain-points-inner-text"><?=htmlspecialchars( $innerRow[TmeitExperience::ExplainRowText] ); ?></td>
		<td class="tmeit-explain-points-inner-points"><?=TmeitExperience::formatPoints( $innerRow[TmeitExperience::ExplainRowPoints] ); ?></td>
		<td></td>
	</tr>
<?
				endforeach;
			endif;
		endforeach;
		if( $pointsFromUnicorns > 0 ):
?>
	<tr>
		<td class="tmeit-explain-points-outer-text tmeit-color-unicorn">Magiska enhörningar</td>
		<td></td>
		<td class="tmeit-explain-points-outer-points tmeit-color-unicorn"><?=TmeitExperience::formatPoints( $pointsFromUnicorns ); ?></td>
	</tr>
<?
		endif;
?>
	<tr>
		<td class="tmeit-explain-points-outer-text tmeit-color-pinka">SUMMA</td>
		<td></td>
		<td class="tmeit-explain-points-outer-points tmeit-color-pinka"><?=TmeitExperience::formatPoints( $pointsTotal ); ?></td>
	</tr>
</table>
<?
	endif;
?>

<p class="tmeit-explain-note">
	Tycker du att du saknar exp för någonting? Det är förmodligen Webbwraqs fel, men prata med mästare eller arbetslagsledare! ALLTING ÄR VÄLDIGT BETA. Räkna med att beräkningarna kommer att förändras löpande. Det är bara på skoj!
</p>
<?
	}
}