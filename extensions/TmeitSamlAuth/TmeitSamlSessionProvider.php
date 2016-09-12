<?php
/*
 * TMEIT SAML authentication extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

if( !defined( 'MEDIAWIKI' ) )
	exit( -1 );

class TmeitSamlSessionProvider extends \MediaWiki\Session\SessionProvider
{
	public function __construct( $params = [] )
	{
		parent::__construct();
	}

	public function provideSessionInfo( WebRequest $request )
	{
		$auth = TmeitSamlAuth::initialize();

		if( !$auth->isAuthenticated() )
			return null;

		$attr = $auth->getAttributes();
		$username = $this->getUsername( $attr );
		$userId = User::idFromName( $username );

		if( $userId )
		{
			$userInfo = \MediaWiki\Session\UserInfo::newFromId( $userId );

			// Running this on every session init might upset some extensions - right now it's fine
			\Hooks::run( 'UserLoggedIn', [ $userInfo->getUser() ] );

			return new \MediaWiki\Session\SessionInfo( $this->priority, [
				'forceHTTPS' => true,
				'id' => $this->manager->generateSessionId(),
				'idIsSafe' => true,
				'persisted' => true,
				'remembered' => true,
				'provider' => $this,
				'userInfo' => $userInfo->verified()
			] );
		}
		else
		{
			// TODO Automatically create user in MediaWiki if $userId is null but authenticated in SAML
		}

		return null;
	}

	public function persistsSessionId()
	{
		return false;
	}

	public function canChangeUser()
	{
		return true;
	}

	public function persistSession( \MediaWiki\Session\SessionBackend $session, WebRequest $request )
	{
	}

	public function unpersistSession( WebRequest $request )
	{
		$auth = TmeitSamlAuth::initialize();

		if( $auth->isAuthenticated() ) {
			$auth->logout();
		}
	}

	private function getUsername( array $attr )
	{
		global $wgSamlUsernameAttr;
		$username = $attr[$wgSamlUsernameAttr][0];

		// Extract username before the '@' sign (wsv@kth.se => wsv)
		$atIdx = strpos( $username, '@' );
		if( FALSE !== $atIdx )
			$username = substr( $username, 0, $atIdx );

		// Ensure username is a valid MediaWiki title
		$title = Title::makeTitleSafe( NS_USER, $username );
		return $title->getText();
	}
}
