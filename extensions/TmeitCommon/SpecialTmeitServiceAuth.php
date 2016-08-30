<?php
/*
 * TMEIT Common extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class SpecialTmeitServiceAuth extends TmeitSpecialPage
{
    const Direct = "direct";
    private $serviceAuth;

	public function __construct()
	{
		parent::__construct( 'TmeitServiceAuth', 'tmeit' );
	}

    protected function prepare( $par )
    {
        global $wgUser;
        $userId = $this->db->userGetIdByMediaWikiUserId( $wgUser->getId() );
        if( 0 == $userId )
            throw new FatalError( 'Användaren kunde inte hittas. Kontakta webbansvarig.' );

        if( $this->wasPosted() || $par == self::Direct )
        {
            $userName = $this->db->userGetUsernameById( $userId );
            $this->serviceAuth = $userName.'%'.$this->db->userCreateServiceAuth( $userId );
        }

        return true;
    }

    protected function render()
    {
        if( empty( $this->serviceAuth ) )
            $this->renderQuestion();
        else
            $this->renderNewServiceAuth();
    }

    private function renderNewServiceAuth()
    {
?>
<input type="text" readonly="readonly" value="<?=htmlspecialchars( $this->serviceAuth ); ?>" class="tmeit-field" id="tmeit-qrcode-field" />

<h3 class="tmeit-links-header">Länkar</h3>
<ul>
	<li><a href="<?=$this->getPageUrl( 'Internsidor' ); ?>">Internsidor...</a></li>
</ul>
<?
    }

    private function renderQuestion()
    {
?>
<p>
    Den här sidan används av applikationer som ansluter till TMEIT.se för att generera en åtkomstkod. Normalt behöver
    du aldrig gå in på den här sidan själv, det sköts internt i appen. Om du av någon anledning vill generera
    en ny kod så kan du göra det här.
</p>

<p class="tmeit-important-note">
    OBS: Den genererade koden kan användas för att komma åt ditt konto hos TMEIT. Hantera den som ett lösenord.
</p>

<script type="text/javascript">
    function runAway() {
        document.location.href = '<?=$this->getPageUrl( 'Internsidor' ); ?>';
    }
</script>

<form method="post" class="tmeit-member-qrcode-form">
    <input type="submit" value="Jag har förstått innebörden och vill generera en ny kod nu" class="tmeit-button" />
    <input type="button" value="Nej, ta mig långt bort härifrån" class="tmeit-button" onclick="runAway();" />
</form>
<?
    }
}