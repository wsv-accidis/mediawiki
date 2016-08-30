<?php
/*
 * TMEIT Members extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class TmeitExperience
{
	const ExplainTotalPoints = 'exp';
	const ExplainRoot = 'explain';
	const ExplainRootText = 'text';
	const ExplainRows = 'rows';
	const ExplainRowText = 'text';
	const ExplainRowPoints = 'exp';

	const ExperienceLowerLimit = 100;

	/** @var TmeitDb */
	private $db;

	public function __construct()
	{
		$this->db = new TmeitDb();
	}

	public static function formatPoints( $points )
	{
		return number_format( $points, 0, '.', ' ' );
	}

	public function getExplanationByUser( $user )
	{
		list( $points, $badges, $leagueRule, $rules ) = $this->calculateExperienceByUser( $user, true );

		$pointsRows = array();
		$pointsSum = 0;
		/** @var TmeitAutoExperienceRule $rule */
		foreach( $rules as $rule )
		{
			$rulePts = $rule->getPoints();
			if( $rulePts > 0 )
			{
				$pointsSum += $rulePts;
				$pointsRows[] = array( TmeitExperience::ExplainTotalPoints => $rulePts,
									   TmeitExperience::ExplainRoot => $rule->explain() );
			}
		}

		/** @var TmeitLeagueRule $leagueRule */
		$league = $leagueRule->getLeague( $points );
		if( FALSE === $league )
			$league = TmeitLeagueRule::noLeague();
		$nextLeague = $leagueRule->nextLeague( $points );

		return array(
			'points_total' => $points,
			'points_rules' => $pointsSum,
			'points_rows' => $pointsRows,
			'badges' => $badges,
			'current_league' => $league,
			'next_league' => $nextLeague
		);
	}

	public function getHtmlByUser( $userId )
	{
		$cache = $this->db->experienceGetCacheByUser( $userId );
		if( FALSE === $cache )
			return '';

		$result = '';
		if( $cache['points'] >= self::ExperienceLowerLimit )
		{
			$exp = self::formatPoints( $cache['points'] );
			$result .= '<span class="tmeit-member-exp-score">'.$exp.'</span>';
		}

		$i = 0;
		foreach( $cache['badges'] as $badgeId )
		{
			$badge = TmeitBadges::getById( $badgeId );
			if( NULL !== $badge )
			{
				if( 0 != $i++ )
					$result .= '&nbsp;';

				$title = str_replace( '{EXP}', $cache['points'], $badge->getTitle() );
				$result .= '<img src="'.$badge->getUrl().'" alt="'.htmlspecialchars( $title ).'" title="'.htmlspecialchars( $title ).'" />';
			}
		}

		return $result;
	}

	public function refreshCacheByUser( $user, $output = false )
	{
		list( $points, $badges ) = $this->calculateExperienceByUser( $user );

		if( $output )
		{
			echo 'Experience for '.htmlspecialchars( $user['username'] ).': '.$points.' point(s), '.count( $badges ).' badge(s).<br />';
			ob_flush();
			flush();
		}

		$this->db->experienceUpdateCache( $user, $points, $badges );
	}

	private function calculateExperienceByUser( $user, $noLeagueBadge = false )
	{
		require_once( 'TmeitExperience_Rules.php' );

		$leagueRule = new TmeitLeagueRule();
		$rules = $this->getDefaultRuleset();

		/** @var TmeitAutoExperienceRule $rule */
		foreach( $rules as $rule )
			$rule->prepare( $user );

		// Get base experience points
		$points = $this->getExperiencePoints( $rules );

		// Add points from experience events
		$badges = array();
		$this->getEvents( $user, $points, $badges );

		if( !$noLeagueBadge )
		{
			// Points are final now, set appropriate league badge
			$leagueBadge = $leagueRule->getLeague( $points );
			if( FALSE !== $leagueBadge )
				array_unshift( $badges, $leagueBadge[1] );
		}

		// Add additional rule-based badges
		$this->getBadges( $rules, $badges );

		return array( $points, $badges, $leagueRule, $rules );
	}

	private function getBadges( array $rules, array &$badges )
	{
		/** @var TmeitAutoExperienceRule $rule */
		foreach( $rules as $rule )
		{
			$temp = $rule->getBadges();
			if( NULL != $temp )
			{
				if( is_array( $temp ) )
					$badges = array_merge( $badges, $temp );
				else
					$badges[] = $temp;
			}
		}

		return $badges;
	}

	private function getDefaultRuleset()
	{
		return array(
			new TmeitEventsWorkedRule( $this->db ),
            new TmeitEventsReportedRule( $this->db ),
            new TmeitOldTitlesRule( $this->db )
		);
	}

	private function getExperiencePoints( array $rules )
	{
		$exp = 0;
		/** @var TmeitAutoExperienceRule $rule */
		foreach( $rules as $rule )
			$exp += $rule->getPoints();

		return $exp;
	}

	private function getEvents( $user, &$points, &$badges )
	{
		list( $exp, $moreBadges ) = $this->db->experienceGetEventSummaryByUserId( $user['id'] );
		$points += $exp;
		$badges = array_merge( $badges, $moreBadges );
	}
}