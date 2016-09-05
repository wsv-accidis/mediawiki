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

	public static function initialize() {
		if( null !== self::$auth )
			return self::$auth;

		global $wgSamlSimpleSAMLphpPath, $wgSamlEntity;

		require_once( $wgSamlSimpleSAMLphpPath.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'_autoload.php' );
		self::$auth = new SimpleSAML_Auth_Simple( $wgSamlEntity );
		return self::$auth;
	}

	/**
	 * @param array $personal_urls
	 * @return bool
	 */
	public static function hookPersonalUrls( &$personal_urls )
	{
		if( isset( $personal_urls['login'] ) )
		{
			// Ensure regular login link is https
			$loginUrl = $personal_urls['login'];
			global $wgServer;
			$loginHref = $wgServer.$loginUrl['href'];
			$personal_urls['login']['href'] = $loginHref;

			// Add SAML login link
			$loginUrl = self::getLoginUrl();
			$personal_urls['login_saml'] = array(
				'text' => wfMessage( 'tmeitloginsaml' ),
				'href' => $loginUrl
			);
		}

		return true;
	}

	/**
	 * @param $template QuickTemplate
	 * @return bool
	 */
	public static function hookUserLoginForm( &$template )
	{
		$loginUrl = self::getLoginUrl();

		global $wgStylePath;
		$template->set( 'header', '<a href="'.$loginUrl.'"><img id="kth-login-link" src="'.$wgStylePath.'/tmeit/images/loggain.png" /></a>' );
		return true;
	}

	/**
	 * @param $returnTo string
	 * @return string
	 */
	public static function getLoginUrl( $returnTo = '' )
	{
		self::initialize();

		if( '' != $returnTo )
		{
			$target = Title::newFromText( $returnTo );
			$returnUrl = $target->getFullURL();
		}
		else
			$returnUrl = Title::newMainPage()->getFullURL();

		return self::$auth->getLoginURL( $returnUrl );
	}
}
