<?php
/*
 * TMEIT SAML authentication extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

if( !defined( 'MEDIAWIKI' ) )
	exit( -1 );

class TmeitSamlSessionProvider extends \MediaWiki\Session\CookieSessionProvider
{
	private static $isAutoCreationInProgress;

	public function __construct( $params = [] )
	{
		parent::__construct( [ 'priority' => \MediaWiki\Session\SessionInfo::MAX_PRIORITY ] );
	}

	public function provideSessionInfo( WebRequest $request )
	{
		// If there is a cookie session, use it
		$cookieSession = parent::provideSessionInfo( $request );
		if( null !== $cookieSession ) {
			return $cookieSession;
		}

		$auth = TmeitSamlAuth::initialize();

		if( !$auth->isAuthenticated() || self::$isAutoCreationInProgress )
			return null;

		$attr = $auth->getAttributes();
		$username = $this->getUsername( $attr );
		$userId = User::idFromName( $username );

		// Attempt to auto-create this user if it does not yet exist
		if( !$userId )
		{
			// Prevent recursive calls to this method as AuthManager goes looking for a session
			self::$isAutoCreationInProgress = true;

			// Set up $wgUser as anon because for whatever reason AuthManager::autoCreateUser hates us otherwise
			global $wgUser;
			$wgUser = new User;

			// Create the user for reals
			$user = User::newFromName( $username );
			$status = \MediaWiki\Auth\AuthManager::singleton()->autoCreateUser(
				$user,
				\MediaWiki\Auth\AuthManager::AUTOCREATE_SOURCE_SESSION,
				false
			);

			self::$isAutoCreationInProgress = false;

			// Failed to auto-create the user for whatever reason
			if( !$status->isGood() && !$status->isOK() )
				return null;

			$userId = User::idFromName( $username );
		}

		$userInfo = \MediaWiki\Session\UserInfo::newFromId( $userId );

		// This will only run once per login, because on the next load the cookie session will take priority
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

	public function persistsSessionId()
	{
		return true;
	}

	public function canChangeUser()
	{
		return true;
	}

	public function persistSession( \MediaWiki\Session\SessionBackend $session, WebRequest $request )
	{
		return parent::persistSession( $session, $request );
	}

	public function unpersistSession( WebRequest $request )
	{
		$auth = TmeitSamlAuth::initialize();

		if( $auth->isAuthenticated() ) {
			$auth->logout();
		}

		parent::unpersistSession( $request );
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
