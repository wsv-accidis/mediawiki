<?php
/*
 * TMEIT Members extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class SpecialTmeitMemberEdit extends TmeitSpecialMemberPage
{
    const MinPasswordLength = 8;
    const RandomPasswordLength = 16;
    const RandomPasswordChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz012345689_-!@&%$';

	const WikiViewContribsUrl = '/index.php?title=Special:Contributions&contribs=user&target=';
	const WikiEditRightsUrl = '/index.php?title=Special:UserRights&user=';

	private $groups;
	private $memberImageUrl;
	private $saved;
	private $teams;
	private $titles;
	private $user;
	private $viewMemberUrl;

	public function __construct()
	{
		parent::__construct( 'TmeitMemberEdit', 'tmeitadmin' );
	}

	private function userIsNew()
	{
		return !isset( $this->user['id'] );
	}

	protected function prepare( $par )
	{
		if( empty( $par ) )
			$this->user = $this->db->userGetNew();
		elseif( FALSE == ( $this->user = $this->db->userGetByName( $par ) ) )
			throw new FatalError( 'Användaren kunde inte hittas.' );

		if( $this->wasPosted() )
		{
            if( $this->userIsNew() )
                $this->user['username'] = strtolower( $this->getTextField( 'username' ) );

            $password = $this->getTextField( 'password' );
            if( '' == $password && $this->userIsNew() )
                $password = $this->createRandomPassword(); // new user gets a random password if none supplied
            if( '' != $password && !$this->userIsNew() && strlen( $password ) < self::MinPasswordLength )
                throw new FatalError( 'Lösenordet måste vara minst '.self::MinPasswordLength.' tecken långt.' );

            // Basic attributes
			$this->user['realname'] = $this->getTextField( 'realname' );
			$this->user['phone'] = $this->getTextField( 'phone' );
			$this->user['email'] = $this->getTextField( 'email' );
			$this->user['is_admin'] = $this->getBoolField( 'is_admin' );
			$this->user['is_team_admin'] = $this->getBoolField( 'is_team_admin' );
			$this->user['is_hidden'] = $this->getBoolField( 'is_hidden' );
			$this->user['group_id'] = $this->getIntField( 'group_id' );
			$this->user['team_id'] = $this->getIntField( 'team_id' );
			$this->user['title_id'] = $this->getIntField( 'title_id' );

			// Properties
			$props = array();

			$passcard = $this->getTextField( 'passcard' );
			if( !empty( $passcard ) )
				TmeitDb::userAddProp( $props, TmeitDb::PropPasscard, 0, $passcard );

			if( $this->getBoolField( 'flag_stad' ) )
				TmeitDb::userAddProp( $props, TmeitDb::PropFlagStad, true );
			if( $this->getBoolField( 'flag_fest' ) )
				TmeitDb::userAddProp( $props, TmeitDb::PropFlagFest, true );
			if( $this->getBoolField( 'flag_permit' ) )
				TmeitDb::userAddProp( $props, TmeitDb::PropFlagPermit, true );
            if( $this->getBoolField( 'flag_drivers_license' ) )
                TmeitDb::userAddProp( $props, TmeitDb::PropFlagDriversLicense, true );

			$dateOfBirth = $this->getTextField( 'date_of_birth' );
			$datePrao = $this->getTextField( 'date_prao' );
			$dateMars = $this->getTextField( 'date_mars' );
			$dateVraq = $this->getTextField( 'date_vraq' );

			if( !empty( $dateOfBirth ) )
			{
				if( FALSE === ( $dateOfBirth = TmeitUtil::validateDate( $dateOfBirth ) ) )
					throw new FatalError( 'Födelsedatumet tycks inte vara korrekt angivet. Kontrollera att det är på formatet YYYY-MM-DD.' );
				TmeitDb::userAddProp( $props, TmeitDb::PropBirthdate, 0, $dateOfBirth );
			}

			if( !empty( $datePrao ) )
			{
				if( FALSE === ( $datePrao = TmeitUtil::validateDate( $datePrao ) ) )
					throw new FatalError( 'Prao-datumet tycks inte vara korrekt angivet. Kontrollera att det är på formatet YYYY-MM-DD.' );
				TmeitDb::userAddProp( $props, TmeitDb::PropDatePrao, 0, $datePrao );
			}

			if( !empty( $dateMars ) )
			{
				if( FALSE === ( $dateMars = TmeitUtil::validateDate( $dateMars ) ) )
					throw new FatalError( 'Marskalks-datumet tycks inte vara korrekt angivet. Kontrollera att det är på formatet YYYY-MM-DD.' );
				TmeitDb::userAddProp( $props, TmeitDb::PropDateMars, 0, $dateMars );
			}

			if( !empty( $dateVraq ) )
			{
				if( FALSE === ( $dateVraq = TmeitUtil::validateDate( $dateVraq ) ) )
					throw new FatalError( 'Vraq-datumet tycks inte vara korrekt angivet. Kontrollera att det är på formatet YYYY-MM-DD.' );
				TmeitDb::userAddProp( $props, TmeitDb::PropDateVraq, 0, $dateVraq );
			}

			$oldTitleOrder = 0;
			$oldTitles = $this->getTextField( 'old_titles' );
			foreach( explode( "\n", $oldTitles ) as $title )
			{
				$title = trim( $title );
				if( !empty( $title ) )
					TmeitDb::userAddProp( $props, TmeitDb::PropOldTitle, $oldTitleOrder++, $title );
			}

			$this->user['props'] = $props;

			// Save
			$this->user['id'] = $this->db->userSave( $this->user );
			$this->saved = true;

            // Change password in MW
            if( '' != $password )
                $this->createMediaWikiUserOrSetPassword( $password );

			// Sync user
			$sync = new TmeitMemberSync();
			$sync->initTmeitUserInMediaWiki( $this->user );

            if( $this->userIsNew() )
			{
				// Redirect to edit page, otherwise the URL will be wrong
				$this->redirectToSpecial( 'TmeitMemberEdit', '/'.htmlspecialchars( $this->user['username'] ).'?saved=1' );
				return false;
			}
		}

		if( $this->userIsNew() )
			$this->getOutput()->setPageTitle( "TMEIT - Ny medlem" );
		else
			$this->getOutput()->setPageTitle( "TMEIT - Redigera ".htmlspecialchars( $this->user['username'] ) );

		$this->groups = $this->db->groupGetList();
		$this->saved |= $this->getBoolField( 'saved' );
		$this->teams = $this->db->teamGetList();
		$this->titles = $this->db->titleGetList();
		$this->viewMemberUrl = SpecialPage::getTitleFor( 'TmeitMember' )->getFullURL().'/';
		$this->memberImageUrl = SpecialPage::getTitleFor( 'TmeitMemberImage' )->getFullURL().'/';
		return true;
	}

	protected function render()
	{
        global $wgScriptPath;
		if( $this->userIsNew() ):
?>
<table class="tmeit-naked-table tmeit-new-member-info-table">
    <tr>
        <td><img src="<?=$wgScriptPath; ?>/skins/tmeit/images/school-kth.png" /></td>
        <td class="tmeit-new-member-info">
            <span>När den nya medlemmen har ett KTH-ID</span><br />
            Användarnamnet måste <b>exakt</b> matcha personens inloggningsnamn på KTH, annars kommer kopplingar till forum, wiki och annat inte att fungera. Det går inte
            att ändra användarnamn i efterhand. Du behöver inte ange ett lösenord.
        </td>
    </tr>
    <tr>
		<td style="height: 10px"></td>
	</tr>
    <tr>
        <td><img src="<?=$wgScriptPath; ?>/skins/tmeit/images/school-other.png" /></td>
        <td class="tmeit-new-member-info">
            <span>När den nya medlemmen inte har ett KTH-ID</span><br />
            Användarnamnet kan vara vad som helst men får inte krocka med något existerande KTH-ID. Välj gärna ett prefix eller suffix (t ex en siffra) som inte ingår i personens namn för att
            minska risken för kollisioner. Endast små bokstäver kan användas. Kom ihåg att även ange ett lösenord.
        </td>
    </tr>
</table>
<?
		endif;
		if( $this->saved ):
?>
<p class="tmeit-form-saved">
	Sparad!
</p>
<?
		endif;
?>
<form id="tmeit-form" action="" method="post">
	<h3>Grundläggande attribut</h3>
	<table class="tmeit-new-table tmeit-twothird-width tmeit-table-spacing">
<?
		if( $this->userIsNew() ):
?>
		<tr>
			<td class="tmeit-member-caption-column">
				Användarnamn
			</td>
			<td class="field-column">
				<input type="text" class="long-text" name="username" value="<?=htmlspecialchars( $this->user['username'] ); ?>" />
			</td>
		</tr>
<?
		endif;
?>
		<tr>
			<td class="tmeit-member-caption-column">
				Riktigt namn
			</td>
			<td class="field-column">
				<input type="text" class="long-text" name="realname" value="<?=htmlspecialchars( $this->user['realname'] ); ?>" />
			</td>
		</tr>
		<tr>
			<td class="tmeit-member-caption-column">
				Telefonnummer
			</td>
			<td class="field-column">
				<input type="text" class="medium-text" name="phone" value="<?=htmlspecialchars( $this->user['phone'] ); ?>" />
			</td>
		</tr>
		<tr>
			<td class="tmeit-member-caption-column">
				E-post
			</td>
			<td class="field-column">
				<input type="text" class="long-text" name="email" value="<?=htmlspecialchars( $this->user['email'] ); ?>" />
			</td>
		</tr>
        <tr>
            <td class="tmeit-member-caption-column">
                Passerkort
            </td>
            <td class="field-column">
                <input type="text" class="short-text" name="passcard" value="<?=htmlspecialchars( $this->getUserPropString( TmeitDb::PropPasscard ) ); ?>" />
            </td>
        </tr>
		<tr>
			<td class="tmeit-member-caption-column">
				Övrigt
			</td>
			<td class="field-column">
				<input type="checkbox" name="is_admin" id="is_admin" value="1"<? if( $this->user['is_admin'] ): ?> checked="checked"<? endif; ?> />
				<label for="is_admin">Har adminrättigheter</label> &nbsp; &nbsp;
				<input type="checkbox" name="is_hidden" id="is_hidden" value="1"<? if( $this->user['is_hidden'] ): ?> checked="checked"<? endif; ?> />
				<label for="is_hidden">Dölj från listor</label>
			</td>
		</tr>
		<tr>
			<td></td>
			<td class="submit-column">
				<input type="submit" value="Spara" />
			</td>
		</tr>
	</table>

	<h3>Grupper, utbildningar etc</h3>
	<table class="tmeit-new-table tmeit-twothird-width tmeit-table-spacing">
		<tr>
			<td class="tmeit-member-caption-column">
				Arbetslag
			</td>
			<td class="field-column">
				<select name="team_id" class="short-text">
					<option value="0">(Inget)</option>
<?
			foreach( $this->teams as $teamId => $team ):
?>
					<option value="<?=$teamId; ?>"<? if( $teamId == $this->user['team_id'] ): ?> selected="selected"<? endif; ?>>
						<?=htmlspecialchars( $team['title'] ); ?>
					</option>
<?
			endforeach;
?>
				</select>
				<span class="tmeit-member-is-team-admin">
					<input type="checkbox" name="is_team_admin" id="is_team_admin" value="1"<? if( $this->user['is_team_admin'] ): ?> checked="checked"<? endif; ?> />
					<label for="is_team_admin">Är arbetslagsledare</label>
                </span>
			</td>
		</tr>
		<tr>
			<td class="tmeit-member-caption-column">
				Grupp
			</td>
			<td class="field-column">
				<select name="group_id" class="short-text">
					<option value="0">(Ingen)</option>
<?
			foreach( $this->groups as $groupId => $group ):
?>
					<option value="<?=$groupId; ?>"<? if( $groupId == $this->user['group_id'] ): ?> selected="selected"<? endif; ?>>
						<?=htmlspecialchars( $group['title'] ); ?>
					</option>
<?
			endforeach;
?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="tmeit-member-caption-column">
				Titel
			</td>
			<td class="field-column">
				<select name="title_id" class="medium-text">
					<option value="0">(Ingen)</option>
<?
			foreach( $this->titles as $titleId => $title ):
?>
					<option value="<?=$titleId; ?>"<? if( $titleId == $this->user['title_id'] ): ?> selected="selected"<? endif; ?>>
						<?=htmlspecialchars( $title['title'] ); ?>
					</option>
<?
			endforeach;
?>
				</select>
			</td>
		</tr>
		<tr>
			<td class="tmeit-member-caption-column">
				Utbildningar
			</td>
			<td class="field-column">
				<input type="checkbox" name="flag_stad" id="flag_stad" value="1"<? if( $this->getUserPropBool( TmeitDb::PropFlagStad ) ): ?> checked="checked"<? endif; ?> />
				<label for="flag_stad">Har STAD</label> &nbsp; &nbsp;
				<input type="checkbox" name="flag_fest" id="flag_fest" value="1"<? if( $this->getUserPropBool( TmeitDb::PropFlagFest ) ): ?> checked="checked"<? endif; ?> />
				<label for="flag_fest">Har FEST</label><br />
			</td>
		</tr>
		<tr>
			<td class="tmeit-member-caption-column">
				Tillstånd
			</td>
			<td class="field-column">
				<input type="checkbox" name="flag_permit" id="flag_permit" value="1"<? if( $this->getUserPropBool( TmeitDb::PropFlagPermit ) ): ?> checked="checked"<? endif; ?> />
				<label for="flag_permit">Står på serveringstillståndet</label>
			</td>
		</tr>
        <tr>
            <td class="tmeit-member-caption-column">
                Körkort
            </td>
            <td class="field-column">
                <input type="checkbox" name="flag_drivers_license" id="flag_drivers_license" value="1"<? if( $this->getUserPropBool( TmeitDb::PropFlagDriversLicense ) ): ?> checked="checked"<? endif; ?> />
                <label for="flag_drivers_license">Har körkort (minst B-behörighet)</label>
            </td>
        </tr>
		<tr>
			<td></td>
			<td class="submit-column">
				<input type="submit" value="Spara" />
			</td>
		</tr>
	</table>

	<h3>Historik</h3>
	<table class="tmeit-new-table tmeit-twothird-width tmeit-table-spacing">
		<tr>
			<td class="tmeit-member-caption-column">
				Födelsedatum
			</td>
			<td class="field-column">
				<input type="text" class="short-text" name="date_of_birth" value="<?=htmlspecialchars( $this->getUserPropString( TmeitDb::PropBirthdate ) ); ?>" />
				<span class="field-hint">YYYY-MM-DD</span>
			</td>
		</tr>
		<tr>
			<td class="tmeit-member-caption-column">
				Prao från
			</td>
			<td class="field-column">
				<input type="text" class="short-text" name="date_prao" value="<?=htmlspecialchars( $this->getUserPropString( TmeitDb::PropDatePrao ) ); ?>" />
				<span class="field-hint">YYYY-MM-DD</span>
			</td>
		</tr>
		<tr>
			<td class="tmeit-member-caption-column">
				Marskalk från
			</td>
			<td class="field-column">
				<input type="text" class="short-text" name="date_mars" value="<?=htmlspecialchars( $this->getUserPropString( TmeitDb::PropDateMars ) ); ?>" />
				<span class="field-hint">YYYY-MM-DD</span>
			</td>
		</tr>
		<tr>
			<td class="tmeit-member-caption-column">
				Vraq från
			</td>
			<td class="field-column">
				<input type="text" class="short-text" name="date_vraq" value="<?=htmlspecialchars( $this->getUserPropString( TmeitDb::PropDateVraq ) ); ?>" />
				<span class="field-hint">YYYY-MM-DD</span>
			</td>
		</tr>
		<tr>
			<td class="tmeit-member-caption-column topalign-column">
				Gamla titlar och utmärkelser
			</td>
			<td class="field-column">
				<textarea id="tmeit-old-titles-box" name="old_titles"><?=implode( "\n", array_map( 'htmlspecialchars', TmeitDb::userGetPropStringArray( $this->user['props'], TmeitDb::PropOldTitle ) ) ); ?></textarea>
				<div class="field-hint" style="float: right; text-align: left">
					Skriv en titel<br />
					per rad i<br />
					datumordning.
				</div>
			</td>
		</tr>
		<tr>
			<td></td>
			<td class="submit-column">
				<input type="submit" value="Spara" />
			</td>
		</tr>
	</table>

    <h3>Lösenord</h3>
    <table class="tmeit-new-table tmeit-twothird-width tmeit-table-spacing">
        <tr>
            <td class="tmeit-member-caption-column topalign-column">
                Ange nytt lösenord
            </td>
            <td class="field-column">
                <input type="password" class="short-text" name="password" autocomplete="new-password" value="" />
                <div class="field-hint" style="margin-top: 10px; margin-left: 0">
					Lösenord kan inte visas, bara ändras. Minst <?=self::MinPasswordLength; ?> tecken.
					Lösenordet används bara vid inloggning utan KTH-ID. För medlem som har KTH-ID behöver inget lösenord anges.
                </div>
            </td>
        </tr>
        <tr>
            <td></td>
            <td class="submit-column">
                <input type="submit" value="Spara" />
            </td>
        </tr>
    </table>

<?
        if( !$this->userIsNew() ):
?>
    <h3>Avancerat</h3>
	<table class="tmeit-new-table tmeit-twothird-width tmeit-table-spacing">
		<tr>
			<td class="tmeit-member-caption-column">
				Wiki
			</td>
			<td>
<?
		    if( $this->user['mediawiki_user_id'] > 0 ):
			global $wgScriptPath;
?>
			<a href="<?=$wgScriptPath.self::WikiEditRightsUrl.$this->user['username']; ?>">Rättigheter</a> |
			<a href="<?=$wgScriptPath.self::WikiViewContribsUrl.$this->user['username']; ?>">Bidrag</a>
<?
		    else:
?>
				(Inget konto)
<?
		    endif;
?>
			</td>
		</tr>
	</table>
<?
        endif;
?>
</form>

<h3 class="tmeit-links-header">Länkar</h3>
<ul>
<?
	if( !$this->userIsNew() ):
?>
	<li><a href="<?=$this->memberImageUrl.htmlspecialchars( $this->user['username'] ); ?>">Ladda upp/radera foton</a></li>
<?
	endif;
?>

	<li><a href="<?=$this->viewMemberUrl.htmlspecialchars( $this->user['username'] ); ?>">Visa medlem</a></li>
	<li><a href="<?=$this->listMembersUrl; ?>">Lista medlemmar</a></li>
	<li><a href="<?=$this->getPageUrl( 'Internsidor' ); ?>">Internsidor...</a></li>
</ul>
<?
	}

	private function getUserPropBool( $propId )
	{
		return TmeitDb::userGetPropBool( $this->user['props'], $propId );
	}

	private function getUserPropString( $propId )
	{
		return TmeitDb::userGetPropString( $this->user['props'], $propId );
	}

    private function createRandomPassword()
    {
        $str = "";
        $chars = self::RandomPasswordChars;
        $maxChar = strlen( self::RandomPasswordChars ) - 1;
        for( $i = 0; $i < self::RandomPasswordLength; $i++ )
            $str .= $chars[mt_rand( 0, $maxChar )];
        return $str;
    }

    private function createMediaWikiUserOrSetPassword( $password )
    {
		$user = User::newFromName( $this->user['username'] );
		if( !$user )
			return; // invalid username

		$status = \MediaWiki\Auth\AuthManager::singleton()->autoCreateUser(
			$user,
			\MediaWiki\Auth\AuthManager::AUTOCREATE_SOURCE_SESSION,
			false
		);

		if( !$status->isGood() && !$status->isOK() )
			return; // failed to create for some reason

		$user = User::newFromName( $user->getName() );
		$user->setEmail( $this->user['email'] );
		$user->setRealName( $this->user['realname'] );
		$user->saveSettings();

		$user->changeAuthenticationData( [
			'username' => $user->getName(),
			'password' => $password,
			'retype' => $password,
		] );
    }
}
