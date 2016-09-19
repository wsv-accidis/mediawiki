<?php
/*
 * The "I Need More Ponies" extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class NeedMorePoniesHooks
{
	public static function beforePageDisplay( OutputPage &$out, Skin &$skin )
	{
		$out->addScriptFile( 'https://panzi.github.com/Browser-Ponies/basecfg.js' );
		$out->addScriptFile( 'https://panzi.github.com/Browser-Ponies/browserponies.js' );
		$out->addModules( 'ext.NeedMorePonies' );
		return true;
	}

	public static function setupParser( Parser &$parser )
	{
		$parser->setHook( 'hoofclap', 'NeedMorePoniesHoofclap::render' );
		return true;
	}
}
