<?php
/*
 * TMEIT Members extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

abstract class TmeitAutoExperienceRule
{
	protected $db;

	public function __construct( TmeitDb $db = NULL )
	{
		$this->db = $db;
	}

	abstract function prepare( $user );

	public function explain()
	{
		return FALSE;
	}

	public function getBadges()
	{
		return NULL;
	}

	public function getPoints()
	{
		return 0;
	}
}

class TmeitEventsReportedRule extends TmeitAutoExperienceRule
{
    const Experience = 200;
    const Badge = TmeitBadges::Workhorse;
    const Treshold = 20;
    private $overTreshold;

    public function prepare( $user )
    {
        $eventsReported = $this->db->userGetNumberOfEventsReported( $user['id'] );
        $this->overTreshold = ( $eventsReported >= self::Treshold );
    }

    public function explain()
	{
		if( $this->overTreshold )
			return array( TmeitExperience::ExplainRootText => 'Har avrapporterat fler än 20 arbetstillfällen' );
		else
			return FALSE;
	}

    public function getBadges()
    {
        return ( $this->overTreshold ? self::Badge : NULL );
    }

    public function getPoints()
    {
        return ( $this->overTreshold ? self::Experience : 0 );
    }
}

class TmeitEventsWorkedRule extends TmeitAutoExperienceRule
{
	const ExperiencePerEventWorked = 100;
	private $eventsWorked;
	private $userId;

	public function prepare( $user )
	{
		$this->userId = $user['id'];
		$this->eventsWorked = $this->db->userGetNumberOfEventsWorked( $this->userId );
	}

	public function explain()
	{
		if( $this->eventsWorked > 0 )
		{
			$events = $this->db->eventGetTitlesByWorker( $this->userId );
			$rows = array();
			foreach( $events as $event )
				$rows[] = array( TmeitExperience::ExplainRowText => $event['event_title'].' ('.$event['event_date'].')',
								 TmeitExperience::ExplainRowPoints => self::ExperiencePerEventWorked );

			return array( TmeitExperience::ExplainRootText => 'Har jobbat vid '.$this->eventsWorked.' tillfälle'.( $this->eventsWorked > 1 ? 'n' : '' ),
						  TmeitExperience::ExplainRows => $rows );
		}
		else
			return FALSE;
	}

	public function getPoints()
	{
		return self::ExperiencePerEventWorked * $this->eventsWorked;
	}
}

class TmeitLeagueRule
{
    static $limits = array( 10000 => TmeitBadges::StarLeague,
                            5000 => TmeitBadges::DiamondLeague,
                            3500 => TmeitBadges::AppleLeague,
                            2500 => TmeitBadges::GoldLeague,
                            1900 => TmeitBadges::SilverLeague,
                            1400 => TmeitBadges::BronzeLeague,
                            900 => TmeitBadges::MetalLeague,
                            500 => TmeitBadges::WoodLeague2,
                            100 => TmeitBadges::WoodLeague );

	public static function noLeague()
	{
	// The "no league" badge is never actually awarded, it's a placeholder
		return array( 0, TmeitBadges::NoLeague );
	}

	public function getLeague( $points )
	{
		foreach( self::$limits as $limit => $badge )
			if( $points >= $limit )
				return array( $limit, $badge );

		return FALSE;
	}

	public function nextLeague( $points )
	{
		$previous = FALSE;
		foreach( self::$limits as $limit => $badge )
		{
			if( $points >= $limit )
				return $previous;
			$previous = array( $limit, $badge );
		}
		return $previous;
	}
}

class TmeitOldTitlesRule extends TmeitAutoExperienceRule
{
    private $wasMaster;
    private $wasWebb;
    private $wasJunk;
    private $wasGourmet;
    private $wasPajas;

    public function prepare( $user )
    {
        $titles = $this->db->userGetOldTitles( $user['id'] );
        foreach( $titles as $title )
        {
            if( FALSE !== stripos( $title, 'mästare' ) )
                $this->wasMaster = true;
            if( FALSE !== stripos( $title, 'web' ) )
                $this->wasWebb = true;
            if( FALSE !== stripos( $title, 'junk' ) )
                $this->wasJunk = true;
            if( FALSE !== stripos( $title, 'gourmet' ) )
                $this->wasGourmet = true;
            if( FALSE !== stripos( $title, 'pajas' ) )
                $this->wasPajas = true;
        }
    }

    public function getBadges()
    {
        $badges = array();
        if( $this->wasMaster )
            $badges[] = TmeitBadges::MegaMan;
        if( $this->wasWebb )
            $badges[] = TmeitBadges::Computer;
        if( $this->wasJunk )
            $badges[] = TmeitBadges::Junk;
        if( $this->wasGourmet )
            $badges[] = TmeitBadges::Gourmet;
        if( $this->wasPajas )
            $badges[] = TmeitBadges::Pajas;
        return $badges;
    }
}
