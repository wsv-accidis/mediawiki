<?php
/*
 * TMEIT SAML authentication extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

if( !defined( 'MEDIAWIKI' ) )
	exit( -1 );

class TmeitSamlAuth
{
	/** @var SimpleSAML_Auth_Simple */
	private static $auth;

	private static function initialize() {
		if( null !== self::$auth )
			return;

		global $wgSamlSimpleSAMLphpPath, $wgSamlEntity;

		require_once( $wgSamlSimpleSAMLphpPath.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'_autoload.php' );
		self::$auth = new SimpleSAML_Auth_Simple( $wgSamlEntity );
	}

	/**
	 * @param $template QuickTemplate
	 * @return bool
	 */
	public static function hookUserLoginForm( &$template )
	{
		self::initialize();

		$returnUrl = Title::newMainPage()->getFullURL();
		$loginUrl = self::$auth->getLoginURL( $returnUrl );

		global $wgStylePath;
		$template->set( 'header', '<a href="'.$loginUrl.'"><img id="kth-login-link" src="'.$wgStylePath.'/tmeit/images/loggain.png" /></a>' );

		return true;
	}
}
