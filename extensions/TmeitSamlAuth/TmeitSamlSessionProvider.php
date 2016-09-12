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
	private static $isAutoCreationInProgress;

	public function __construct( $params = [] )
	{
		parent::__construct();
	}

	public function provideSessionInfo( WebRequest $request )
	{
		$auth = TmeitSamlAuth::initialize();

		if( !$auth->isAuthenticated() || self::$isAutoCreationInProgress )
			return null;

		$attr = $auth->getAttributes();
		$username = $this->getUsername( $attr );
		$userId = User::idFromName( $username );

		// Attempt to auto-create this user if it does not yet exist
		if( !$userId )
		{
			// Prevent recursive calls to this method as AutoManager goes looking for a session
			self::$isAutoCreationInProgress = true;

			// Set up $wgUser as anon because for whatever reason AutoManager::autoCreateUser hates us otherwise
			global $wgUser;
			$wgUser = new User;

			// Actually create the user now
			$user = User::newFromName( $username );
			$status = \MediaWiki\Auth\AuthManager::singleton()->autoCreateUser(
				$user,
				\MediaWiki\Auth\AuthManager::AUTOCREATE_SOURCE_SESSION,
				false
			);

			// Failed to auto-create the user for whatever reason
			if( !$status->isGood() && !$status->isOK() )
				return null;

			self::$isAutoCreationInProgress = false;
			$userId = User::idFromName( $username );
		}

		$userInfo = \MediaWiki\Session\UserInfo::newFromId( $userId );

		// TODO Running this on every session init is a bit bad for performance and might upset someone
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
