<?php
/**
 * TMEIT skin for MediaWiki.
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 * Based on "Pixeled" MediaWiki theme by Dan and Rebecca (http://www.memorydeleted.com/?p=210)
 * Originally the "Pixel" WordPress theme by Sam (http://www.webdesigncompany.net/pixel/)
 *
 * @file
 * @ingroup Skins
 */

if( !defined( 'MEDIAWIKI' ) )
	die( -1 );

$wgValidSkinNames['tmeit'] = 'tmeit';

$wgResourceModules['skins.tmeit.styles'] = array(
	'styles' => 'main.css',
	'remoteBasePath' => "$wgScriptPath/skins/tmeit",
	'localBasePath' => "$IP/skins/tmeit"
);
	
/**
 * @ingroup Skins
 */
class SkinTmeit extends SkinTemplate
{
	var $skinname = 'tmeit';
	var $stylename = 'tmeit';
	var $template = 'TmeitTemplate';
	var $useHeadElement = true;

	public function initPage( OutputPage $out )
	{
		parent::initPage( $out );
	}

	public function setupSkinUserCss( OutputPage $out )
	{
		parent::setupSkinUserCss( $out );
		$out->addModuleStyles( 'skins.tmeit.styles' );
	}
}

class TmeitTemplate extends BaseTemplate
{
	const ADMIN_HREF = 'mailto:wsv@kth.se';

	private $areWeOnMainPage;
	private $doesPageHaveCategories;
	private $events = array();
	private $notifications = array();
	private $isUserTmeit = false;

	/**
	 * @access private
	 */
	public function execute()
	{
		$this->areWeOnMainPage = $this->getAreWeOnMainPage();
		$this->doesPageHaveCategories = $this->getDoesPageHaveCategories();

		if( $this->areWeOnMainPage )
		{
			global $wgUser;
			$this->isUserTmeit = $wgUser->isAllowed( 'tmeit' );

			$db = new TmeitDb();
			$this->events = $db->eventGetPublicList();

            if( $this->isUserTmeit )
            {
                $userId = $db->userGetIdByMediaWikiUserId( $wgUser->getId() );
                if( 0 != $userId )
                    $this->notifications = $db->notifGetLatest( $userId );
            }
		}

		// Suppress warnings to prevent notices about missing indexes in $this->data
		wfSuppressWarnings();

		$this->html( 'headelement' );
		$this->render();
		$this->printTrail();
		echo Html::closeElement( 'body' );
		echo Html::closeElement( 'html' );

		wfRestoreWarnings();
	}

	private function getAreWeOnMainPage()
	{
		// HACK - Literal string comparison
		return 0 == strcasecmp( 'traditionsmesterit', $this->data['title'] );
	}

	private function getDoesPageHaveCategories()
	{
		// HACK - It seems that catlinks will always contain an empty div, so we need to check it some other way
		return FALSE !== strpos( $this->data['catlinks'], "<a" );
	}

	private function render()
	{
?>
	<div id="wrapper">
<?
		$this->renderHeader();
		$this->renderContentActions();
?>
		<div class="cleared"></div>
		<div id="main">
			<div id="fb-root"></div>
			<script>(function(d, s, id) {
			  var js, fjs = d.getElementsByTagName(s)[0];
			  if (d.getElementById(id)) return;
			  js = d.createElement(s); js.id = id;
			  js.src = "//connect.facebook.net/en_GB/sdk.js#xfbml=1&version=v2.7";
			  fjs.parentNode.insertBefore(js, fjs);
			}(document, 'script', 'facebook-jssdk'));</script>
<?
		$this->renderContent();
		if( $this->areWeOnMainPage || $this->doesPageHaveCategories ):
?>
			<div id="sidebars">
<?
			if( $this->areWeOnMainPage )
			{
			    $this->renderNotifications();
				$this->renderSearchBox();
				$this->renderNavigationBox();
				$this->renderToolBox();
				$this->renderEventBox();
			}
			else
				$this->renderCategoriesBox();
?>
				<div class="cleared"></div>
			</div><!-- sidebars -->
<?
		endif;
?>
			<div class="cleared"></div>
		</div><!-- main -->

		<div id="morefoot">
<?
		$this->renderLeftFooter();
		$this->renderCenterFooter();
		$this->renderRightFooter();
?>
			<div class="cleared"></div>
		</div><!-- morefoot -->

		<div class="cleared"></div>
	</div><!-- wrapper -->

	<div class="cleared"></div>
<?
	}

	private function renderHeader()
	{
		global $wgStylePath;
		$mainPageUrl = $this->data['nav_urls']['mainpage']['href'];
?>
		<div id="header">
			<div id="logo">
				<a href="<?=$mainPageUrl; ?>"><img src="<?=$wgStylePath; ?>/tmeit/images/logo.png" alt="TraditionsMEsterIT" /></a>
			</div>
			<div id="topright">
				<ul>
<?
		foreach( $this->getPersonalTools() as $key => $item )
			echo $this->makeListItem( $key, $item );
?>
				</ul>
			</div>
		</div>
<?
	}

	private function renderContentActions()
	{
?>
		<div id="catnav">
			<ul id="nav">
<?
				foreach( $this->data['content_actions'] as $key => $tab )
				{
					$linkAttribs = array( 'href' => $tab['href'] );

					if( isset( $tab["tooltiponly"] ) && $tab["tooltiponly"] )
					{
						$title = Linker::titleAttrib( "ca-$key" );
						if( $title !== false )
							$linkAttribs['title'] = $title;
					}
					else
						$linkAttribs += Linker::tooltipAndAccesskeyAttribs( "ca-$key" );

					$linkHtml = Html::element( 'a', $linkAttribs, $tab['text'] );

					/* Surround with a <li> */
					$liAttribs = array( 'id' => Sanitizer::escapeId( "ca-$key" ) );
					if( $tab['class'] )
						$liAttribs['class'] = $tab['class'];

					echo Html::rawElement( 'li', $liAttribs, $linkHtml );
				}
?>
			</ul>
		</div>
<?
	}

	private function renderContent()
	{
		$cwStyle = $this->areWeOnMainPage ? "contentwrapper" : "contentwrapper2";
?>
	<div id="<?=$cwStyle; ?>">
		<div class="topPost">
			<div class="topContent">
<?
		if( !$this->areWeOnMainPage ):
?>
				<h2><? $this->html( 'title' ); ?></h2>
<?
		endif;
?>
				<? $this->html( 'bodycontent' ); ?>
				<div class="visualClear"></div>
			</div>
		</div>
	</div>
<?
	}

	private function renderSearchBox()
	{
?>
				<div id="sidebar_full">
					<ul>
						<li>
							<div class="sidebarbox">
								<h2><? $this->msg( 'search' ); ?></h2>
								<div id="searchBody" class="pBody">
									<form action="<? $this->text( 'wgScript' ); ?>" id="searchform">
										<input type="hidden" name="title" value="<? $this->text( 'searchtitle' ); ?>"/>
										<div>
											<? echo $this->makeSearchInput( array( 'id' => 'searchInput' ) ); ?>
											<? echo $this->makeSearchButton( 'go', array( 'id' => 'searchGoButton', 'class' => 'searchButton' ) ); ?>
											<? echo $this->makeSearchButton( 'fulltext', array( 'id' => 'mw-searchButton', 'class' => 'searchButton' ) ); ?>
										</div>
									</form>
								</div>
							</div>
						</li>
					</ul>
				</div>
<?
	}

	private function renderNotifications()
    {
        if( !$this->isUserTmeit || empty( $this->notifications ) )
            return;
?>
				<div id="sidebar_notifications">
					<ul>
						<li>
							<div class="sidebarbox">
								<h2><?=htmlspecialchars( wfMessage( 'tmeitnotificationbox' ) ); ?></h2>
								<ul>
<?
									foreach( $this->notifications as $key => $not )
                                    {
                                        $class = ( $not['is_read'] ? 'tmeit-notification tmeit-notification-read' : 'tmeit-notification' );
                                        $link = Html::rawElement( 'a', array( 'href' => $not['url'], 'title' => $not['created'] ), $not['body'] );
   										$item = Html::rawElement( 'li', array( 'class' => $class ), $link );
   										echo $item;
                                    }
?>
								</ul>
							</div>
						</li>
					</ul>
				</div>
<?
    }

	private function renderCategoriesBox()
	{
?>
				<div id="sidebar_full">
					<ul>
						<li>
							<div class="sidebarbox">
								<? $this->html( 'catlinks' ); ?>
							</div>
						</li>
					</ul>
				</div>
<?
	}

	private function renderBox( $boxName, $boxContents, $id )
	{
?>
				<div id="<?=$id; ?>">
					<ul>
						<li>
							<div class="sidebarbox">
								<h2><?=htmlspecialchars( wfMessage( $boxName ) ); ?></h2>
								<ul>
<?
									foreach( $boxContents as $key => $val )
   										echo $this->makeListItem( $key, $val );
?>
								</ul>
							</div>
						</li>
					</ul>
				</div>
<?
	}

	private function renderNavigationBox()
	{
		$boxName = 'navigation';
		$boxContents = $this->data['sidebar'][$boxName];
		$this->renderBox( $boxName, $boxContents, 'sidebar_left' );
	}

	private function renderToolBox()
	{
		$boxContents = array();

		$pages = array( 'Reglementet', 'Mötesprotokoll', 'Mästarrådsprotokoll' );
		foreach( $pages as $page )
		{
			$title = Title::newFromText( $page );
			$boxContents[] = array( 'text' => $title->getText(), 'href' => $title->getFullURL() );
		}

		if( $this->isUserTmeit )
		{
			// Internal pages
			$boxContents[] = array( 'text' => wfMessage( 'tmeiteventlist-short' )->toString(),
									'href' => SpecialPage::getTitleFor( 'TmeitEventList' )->getFullURL() );
			$boxContents[] = array( 'text' => wfMessage( 'tmeitexteventlist-short' )->toString(),
									'href' => SpecialPage::getTitleFor( 'TmeitExtEventList' )->getFullURL() );
			$boxContents[] = array( 'text' => wfMessage( 'tmeitmemberlist-short' )->toString(),
									'href' => SpecialPage::getTitleFor( 'TmeitMemberList' )->getFullURL() );
			$boxContents[] = array( 'text' => wfMessage( 'tmeittoolbox-internal-pages' )->toString(),
									'href' => Title::newFromText( 'Internsidor' )->getFullURL() );
		}

		$this->renderBox( 'tmeittoolbox', $boxContents, 'sidebar_right' );
	}

	private function renderEventBox()
	{
		if( 0 == count( $this->events ) )
			return;

		$eventsPageUrl = SpecialPage::getTitleFor( 'TmeitEvents' )->getFullURL();
		$boxContents = array();
		foreach( $this->events as $id => $event )
			$boxContents[$id] = array(
				'text' => sprintf( '%s (%s kl %s i %s)', $event['title'], $event['start_date'], $event['start_time'], $event['location'] ),
				'href' => $eventsPageUrl.'?id='.$id
			);

		$this->renderBox( 'tmeiteventbox', $boxContents, 'sidebar_upcoming_events' );
	}

	private function renderFooter( $boxName, $boxContents, $class )
	{
?>
		<div class="<?=$class; ?>">
			<h3><?=htmlspecialchars( wfMessage( $boxName ) ); ?></h3>
			<ul>
<?
		foreach( $boxContents as $key => $val )
			echo $this->makeListItem( $key, $val );
?>
			</ul>
		</div>
<?
	}

	private function renderLeftFooter()
	{
		global $wgStylePath;
?>
				<div class="col1">
					<h3>Information</h3>
					<p>
						Den här sidan är en <a href="http://en.wikipedia.org/wiki/Wiki">wiki</a> där innehållet är skapat av TMEITs medlemmar. Har du frågor eller
						synpunkter, kontakta <a href="<?=self::ADMIN_HREF; ?>">webbansvarig</a>. Allt material omfattas av upphovsrätten.
					</p>
					<div id="in-logo">
						<a href="http://www.insektionen.se">
							<img src="<?=$wgStylePath; ?>/tmeit/images/in.png" title="Sektionen för Informations- och Nanoteknik" alt="Sektionen för Informations- och Nanoteknik" />
							<span>Sektionen för Informations-<br />och Nanoteknik</span>
						</a>
					</div>
					<p id="english-info">
						<img src="<?=$wgStylePath; ?>/tmeit/images/uk.png" title="English" alt="English" />
						The contents of this site are in Swedish. If you have questions or feedback, please <a href="<?=self::ADMIN_HREF; ?>">contact the administrator</a>.
					</p>
				</div>
		<?
	}

	private function renderCenterFooter()
	{
		$boxName = 'navigation';
		$boxContents = $this->data['sidebar'][$boxName];
		$this->renderFooter( $boxName, $boxContents, 'col2' );
	}

	private function renderRightFooter()
	{
		$boxContents = $this->getToolbox();
		$this->renderFooter( 'toolbox', $boxContents, 'col3' );
	}
}
?>