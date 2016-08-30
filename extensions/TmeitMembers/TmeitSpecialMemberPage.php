<?php
/*
 * TMEIT Members extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

abstract class TmeitSpecialMemberPage extends TmeitSpecialPage
{
	protected $listMembersUrl;
	protected $manageGroupsUrl;
	protected $manageTeamsUrl;
	protected $manageTitlesUrl;

	protected function initSpecialUrls()
	{
		$this->listMembersUrl = SpecialPage::getTitleFor( 'TmeitMemberList' )->getFullURL();
		$this->manageGroupsUrl = SpecialPage::getTitleFor( 'TmeitGroupList' )->getFullURL();
		$this->manageTeamsUrl = SpecialPage::getTitleFor( 'TmeitTeamList' )->getFullURL();
		$this->manageTitlesUrl = SpecialPage::getTitleFor( 'TmeitTitleList' )->getFullURL();
	}
}