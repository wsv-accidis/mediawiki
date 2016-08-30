<?php
/*
 * TMEIT Common extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class TmeitGcm
{
    const CollapseKey = 'tmeit_broadcast';
    const GcmSendUrl = 'https://android.googleapis.com/gcm/send';
    const Timeout = 10; // seconds

    private $db;
    private $verbose;

    public function __construct( $verbose = false )
    {
        $this->db = new TmeitDb();
        $this->verbose = $verbose;
    }

    public function sendPendingNotifications()
    {
        $notificationIds = $this->db->notifGetNotSentByGcm();
        if( !empty( $notificationIds ) )
        {
            $registrationIds = $this->db->gcmGetAllRegistered();
            $success = TRUE;

            if( !empty( $registrationIds ) )
                $success = $this->notifyGcm( $registrationIds );

            if( $success )
                $this->db->notifSetSentByGcm( $notificationIds );
        }
    }

    private function notifyGcm( array $registrationIds )
    {
        $data = json_encode( array(
            'collapse_key' => self::CollapseKey,
            'registration_ids' => $registrationIds
        ) );

        $ch = curl_init( self::GcmSendUrl );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, self::Timeout );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_TIMEOUT, self::Timeout );

		global $wgGoogleApiKey;
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
                'Authorization: key='.$wgGoogleApiKey,
                'Content-Type: application/json' )
        );

        $result = curl_exec( $ch );

        if( $this->verbose ):
            if( FALSE !== $result ):
?>
<p>
    Request succeeded - response from GCM:
</p>
<pre><?=$result; ?></pre>
<?
            else:
?>
<p>
    Request failed - error message from cURL:
</p>
<pre><?=curl_error( $ch ); ?></pre>
<?
            endif;
        endif;

        return ( FALSE !== $result );
    }
}