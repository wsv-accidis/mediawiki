<?php
/*
 * The "I Need More Ponies" extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class NeedMorePoniesHoofclap
{
	public static function render( $input, array $args, Parser $parser, PPFrame $frame )
	{
		if( NULL == trim( $input ) )
			return "Hoofclap error: Which pony? Syntax: &lt;hoofclap&gt;twilight sparkle&lt;/hoofclap&gt;";

		$image = self::translateInput( $input );
		if( NULL == $image )
			return "Hoofclap error: I don't know that pony! Try &lt;hoofclap&gt;twilight sparkle&lt;/hoofclap&gt; instead?";

		global $wgScriptPath;
		$path = $wgScriptPath.'/extensions/NeedMorePonies/images/hoofclap/';
		return sprintf( '<img src="%s" alt="Hoofclap" />', $path.$image );
	}

	private static function translateInput( $input )
	{
		switch( strtolower( $input ) )
		{
			case 'ab': case 'applebloom':
				return 'applebloom.gif';

			case 'aj': case 'applejack':
				return 'applejack.gif';

			case 'babs': case 'babs seed':
				return 'babs_seed.gif';

			case 'bonbon': case 'sweetie drops':
				return 'bonbon.gif';

			case 'braeburn':
				return 'braeburn.gif';

			case 'cadence': case 'cadance': case 'princess cadence': case 'princess cadance':
				return 'cadence.gif';

			case 'celestia': case 'princess celestia':
				return 'celestia.gif';

			case 'chrysalis': case 'queen chrysalis':
				return 'chrysalis.gif';

			case 'colgate': case 'minuette':
				return 'colgate.gif';

			case 'd': case 'dh': case 'derpy': case 'derpy hooves':
				return 'derpy.gif';

			case 'fs': case 'fluttershy':
				return 'fluttershy.gif';

			case 'luna': case 'princess luna':
				return 'luna.gif';

			case 'lyra': case 'lyra heartstrings':
				return 'lyra.gif';

			case 'pp': case 'pinkie pie':
				return 'pinkie_pie.gif';

			case 'rd': case 'rainbow dash':
				return 'rainbow_dash.gif';

			case 'r': case 'rarity':
				return 'rarity.gif';

			case 'scootaloo':
				return 'scootaloo.gif';

			case 'shining armor':
				return 'shining_armor.gif';

			case 'spitfire':
				return 'spitfire.gif';

			case 'sb': case 'sweetie belle':
				return 'sweetie_belle.gif';

			case 'trixie': case 'trixie lulamoon': case 'the great and powerful trixie':
				return 'trixie.gif';

			case 'ts': case 'twi': case 'best pony': case 'twilight sparkle':
				return 'twilight_sparkle.gif';

			case 'vinyl': case 'vinyl scratch': case 'dj pon3':
				return 'vinyl_scratch.gif';
		}

		return NULL;
	}
}