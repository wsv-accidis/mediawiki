<?php
/*
 * TMEIT Members extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class TmeitBadges
{
	protected $id;
	protected $img;
	protected $name;
	protected $title;
	protected $auto;
	protected static $allBadges = array();

	// DO NOT CHANGE IDS OR YOU WILL DIE HORRIBLY
	const Lime = 1;
	const Gaffa = 2;
    const Skapbil = 3;
    const RockOn = 4;
    const Beer = 5;
    const Computer = 6;
    const MegaMan = 7;

    const Workhorse = 300;
    const Junk = 301;
    const Gourmet = 302;
    const Satan = 303;
    const Pajas = 304;

    const NoLeague = 599;
	const WoodLeague = 600;
	const WoodLeague2 = 601;
    const MetalLeague = 602;
    const BronzeLeague = 603;
    const SilverLeague = 604;
    const GoldLeague = 605;
    const AppleLeague = 606;
    const DiamondLeague = 607;
    const StarLeague = 608;

	public static function getAll()
	{
		return self::$allBadges;
	}

	public static function getAssignable()
	{
		$result = array();
		foreach( self::$allBadges as $badge )
			if( !$badge->auto )
				$result[] = $badge;
		return $result;
	}

	/**
	 * @param $id
	 * @return TmeitBadges
	 */
	public static function getById( $id )
	{
		if( !isset( self::$allBadges[$id] ) )
			return NULL;
		return self::$allBadges[$id];
	}


	public static function _init()
	{
		$badges = array(
			// Manually assigned
			new self( self::Lime, "Lime.png", "Limes", "Har gjort otroligt snygga pixelart-badges!" ),
			new self( self::Gaffa, "Gaffa.png", "Gaffatejp", "Har hittat och rapporterat minst en bugg i hemsidan" ),
            new self( self::Skapbil, "Skapbil.png", "Skåpbil", "Har kört skåpbilen till [DATA EXPUNGED]" ),
            new self( self::RockOn, "RockOn.png", "Rock on!", "" ),
            new self( self::Beer, "Beer.png", "Ölsejdel", "" ),
            new self( self::Satan, "Satan.png", "Pentagram", "Sektmedlem" ),

			// Auto assigned (can still be manually assigned)
            new self( self::Workhorse, "Horse.png", "Arbetshäst", "Har avrapporterat fler än 20 arbetstillfällen" ),
            new self( self::MegaMan, "MegaMan.png", "ExMästare", "Har varit Mästare i TMEIT" ),
            new self( self::Computer, "Computer.png", "Webb*", "Har varit webbprao/marskalk/vraq i TMEIT" ),
            new self( self::Junk, "Junk.png", "Junk*", "Har varit junkprao/marskalk/vraq i TMEIT" ),
            new self( self::Gourmet, "Gourmet,png", "Gourmet*", "Har varit gourmetprao/marskalk/vraq i TMEIT" ),
            new self( self::Pajas, "Trash.png", "Pajas", "") // For "special" people <3

			// Leagues
			new self( self::NoLeague, "NoLeague.png", "No league", "No league ({EXP} poäng)", true ),
			new self( self::WoodLeague, "WoodLeague.png", "Wood league", "Wood league ({EXP} poäng)", true ),
			new self( self::WoodLeague2, "WoodLeague2.png", "Better Wood league", "Better Wood league ({EXP} poäng)", true ),
			new self( self::MetalLeague, "MetalLeague.png", "Some Kind of Metal league", "Some Kind of Metal league ({EXP} poäng)", true ),
			new self( self::BronzeLeague, "BronzeLeague.png", "Bronze league", "Bronze league ({EXP} poäng)", true ),
			new self( self::SilverLeague, "SilverLeague.png", "Silver league", "Silver league ({EXP} poäng)", true ),
			new self( self::GoldLeague, "GoldLeague.png", "Gold league", "Gold league ({EXP} poäng)", true ),
            new self( self::AppleLeague, "AppleLeague.png", "Apple league", "Apple league ({EXP} poäng)", true ),
            new self( self::DiamondLeague, "DiamondLeague.png", "Diamond league", "Diamond league ({EXP} poäng)", true ),
            new self( self::StarLeague, "StarLeague.png", "Star league", "Star league ({EXP} poäng)", true )
		);

		/** @var TmeitBadges $badge */
		foreach( $badges as $badge )
			self::$allBadges[$badge->id] = $badge;
	}

	private function __construct( $id, $img, $name, $title, $auto = false )
	{
		$this->id = (int) $id;
		$this->img = $img;
		$this->name = $name;
		$this->title = $title;
		$this->auto = $auto;
	}

	public function getId()
	{
		return $this->id;
	}

	public function getImage()
	{
		return $this->img;
	}

	public function getUrl()
	{
		global $wgScriptPath;
		return $wgScriptPath."/extensions/TmeitMembers/badges/".$this->img;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getTitle()
	{
		return $this->title;
	}
}

TmeitBadges::_init();
