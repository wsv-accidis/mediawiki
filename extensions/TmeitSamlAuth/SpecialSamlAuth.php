<?php
/*
 * TMEIT SAML authentication extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

/**
 * This special page is just a redirect to let external services (e g the app) direct to the appropriate
 * login provider without having to know what the correct URL is.
 */
class SpecialSamlAuth extends SpecialPage
{
	public function __construct()
	{
		parent::__construct( 'SAMLAuth', '', false );
	}

	public function beforeExecute( $subPage )
	{
		global $wgOut, $wgRequest;
		$returnTo = $wgRequest->getVal( 'returnto' );
		$wgOut->redirect( TmeitSamlAuth::getLoginUrl( $returnTo ) );
		return false;
	}
}
