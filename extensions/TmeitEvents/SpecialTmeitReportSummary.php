<?php
	/*
	 * TMEIT Events extension for MediaWiki
	 *
	 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
	 */

class SpecialTmeitReportSummary extends TmeitSpecialEventPage
{
	const FirstYear = 2013;

	private $listEventUrl;
	private $groups;
	private $users;
	private $selectedYear;
	private $thisYear;
	private $listMembersUrl;
	static private $months = array( 1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Maj', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Dec' );

	public function __construct()
	{
		parent::__construct( 'TmeitReportSummary', 'tmeit' );
		$this->thisYear = (int) date( 'Y' );
		$this->thisMonth = (int) date( 'n' );
	}

	protected function prepare( $par )
	{
		$this->selectedYear = (int) $par;
		if( $this->selectedYear < self::FirstYear )
			$this->selectedYear = $this->thisYear;

		$this->groups = $this->db->groupGetList();
		foreach( $this->groups as $groupId => $group )
			$this->users[$group['title']] = $this->db->userGetWorkSummaryByGroupId( $groupId, $this->selectedYear );
		$this->users['Ingen grupp'] = $this->db->userGetWorkSummaryByGroupId( NULL, $this->selectedYear );

		$this->getOutput()->setPageTitle( 'TMEIT - Arbetstillfällen per medlem '.$this->selectedYear );
		return true;
	}

	protected function initSpecialUrls()
	{
		$this->listEventUrl = SpecialPage::getTitleFor( 'TmeitEventList' )->getFullURL();
		$this->listMembersUrl = SpecialPage::getTitleFor( 'TmeitMemberList' )->getFullURL();
	}

	protected function render()
	{
		$this->renderMenu();

		foreach( $this->users as $header => $list )
			if( !empty( $list ) )
				$this->renderSingleTable( $header, $list );

		$this->renderFooter();
	}

	private function renderFooter()
	{
?>
<h3>Länkar</h3>
<ul>
	<li><a href="<?=$this->listEventUrl; ?>">Lista evenemang</a></li>
	<li><a href="<?=$this->listMembersUrl; ?>">Lista medlemmar</a></li>
</ul>
<?
	}

	private function renderMenu()
	{
		$thisUrl = $this->getPageTitle()->getFullURL();
		$links = array();
		for( $year = self::FirstYear; $year <= $this->thisYear; $year++ )
			$links[] = TmeitUtil::strF( '<a href="{0}/{1}">{1}</a>', $thisUrl, $year );
?>
<p>
	Välj år att visa: [ <?=implode( ' | ', $links ); ?> ]
</p>
<?
	}

	private function renderSingleTable( $header, array $users )
	{
		$maxMonth = ( $this->selectedYear == $this->thisYear ? $this->thisMonth : 12 );
?>
<h3><?=htmlspecialchars( $header ); ?></h3>
<table class="tmeit-new-table tmeit-table-spacing <?=( $maxMonth <= 6 ? 'tmeit-twothird-width' : 'tmeit-full-width' ); ?>">
	<thead>
		<tr>
			<th>
				Namn
			</th>
<?
		for( $month = 1; $month <= $maxMonth; $month++ ):
?>
			<th>
				<?=self::$months[$month]; ?>
			</th>
<?
		endfor;
?>
			<th>
				TOTAL
			</th>
		</tr>
    </thead>
    <tbody>
<?
		foreach( $users as $user ):
?>
		<tr>
			<td style="width: 220px">
				<?=htmlspecialchars( $user['realname'] ); ?>
			</td>
<?
		$userMonths = $user['months'];
		for( $month = 1; $month <= $maxMonth; $month++ ):
			if( isset( $userMonths[$month] ) )
			{
				$count = $userMonths[$month]['count'];
				$events = array();
				foreach( $userMonths[$month]['events'] as $event )
					$events[] = $event['title'].' ('.$event['date'].')';

				$events = implode( ', ', $events );
			}
			else
				$count = $events = '';
?>
			<td title="<?=htmlspecialchars( $events ); ?>">
				<?=$count; ?>
			</td>
<?
		endfor;
?>
			<td>
				<?=$user['total_count']; ?>
			</td>
		</tr>
<?
		endforeach;
?>
	</tbody>
</table>
<?
	}
}