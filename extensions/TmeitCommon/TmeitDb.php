<?php
/*
 * TMEIT Common extension for MediaWiki
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

class TmeitDb
{
	const DuplicateKey = 1062;

	const ExperienceTypeManual = 0;

	const EventRegular = 0;
	const EventLunch = 1;

	const ExtEventLogAttend = 10;
	const ExtEventLogUnattend = 11;
	const ExtEventLogAdminAttend = 20;
	const ExtEventLogAdminUnattend = 21;

	const GroupMaster = 'Mästare';
	const GroupMarskalk = 'Marskalk';
	const GroupPrao = 'Prao';
	const GroupVraq = 'Vraq';
    const GroupInaktiva = 'Inaktiva';
    const GroupEx = 'Ex';

    const MediaWikiTmeitGroup = 'tmeit';
	const MediaWikiTmeitAdminGroup = 'tmeitadmin';

	const PropDatePrao = 1;
	const PropDateMars = 2;
	const PropDateVraq = 3;
	const PropBirthdate = 9;
	const PropOldTitle = 10;
	const PropPasscard = 11;
	const PropFlagStad = 20;
	const PropFlagFest = 21;
	const PropFlagPermit = 22;
    const PropFlagDriversLicense = 23;
    const PropLastDrinkPrefs = 40;
    const PropLastFoodPrefs = 41;

	const RefNone = 0;
	const RefEvent = 1;
	const RefExtEvent = 2;

    const ServiceAuthKeyStrength = 56;
    const ServiceAuthLimit = 5;

    const TableEvents = 'tmeit_events';
	const TableEventsWorkers = 'tmeit_events_workers';
	const TableExperienceCache = 'tmeit_experience_cache';
	const TableExperienceEvents = 'tmeit_experience_events';
	const TableExternalEvents = 'tmeit_extevents';
	const TableExternalEventAttend = 'tmeit_extevent_attend';
	const TableExternalEventLogs = 'tmeit_extevent_logs';
	const TableGcm = 'tmeit_gcm';
	const TableGroups = 'tmeit_groups';
	const TableMediaWikiUsers = 'wiki_user';
	const TableMediaWikiUserGroups = 'wiki_user_groups';
	const TableNotifications = 'tmeit_notifications';
	const TableNotificationsRead = 'tmeit_notifications_read';
	const TableProps = 'tmeit_users_props';
	const TableServiceAuth = 'tmeit_service_auth';
	const TableTeams = 'tmeit_teams';
	const TableTemp = 'tmeit_temp';
	const TableTitles = 'tmeit_titles';
	const TableUsers = 'tmeit_users';
	const TableWorkReports = 'tmeit_work_reports';
	const TableWorkReportsWorkers = 'tmeit_work_reports_workers';

    const WorkYes = 2;
	const WorkMaybe = 1;
	const WorkNo = 0;

	/** @var \DatabaseBase $db */
	private $db;

	public function __construct()
	{
		$this->db = wfGetDB( DB_MASTER );
	}

    public function getDatabase()
    {
        return $this->db;
    }

	private function dbAddQuotes( $str )
	{
		return "'".$this->db->strencode( $str )."'";
	}

	private function dbAddQuotesOrNull( $str )
	{
		return ( empty( $str ) ? 'NULL' : $this->dbAddQuotes( $str ) );
	}

    private function dbGetColumn( ResultWrapper $qr, $column = 0, $toInt = false )
    {
        $list = array();
        while( NULL != ( $row = $qr->fetchRow() ) )
            $list[] = ( $toInt ? (int) $row[$column] : $row[$column] );
        $qr->free();
        return $list;
    }

    private function dbGetValue( ResultWrapper $qr, $column = 0, $default = NULL )
    {
        $q = $qr->fetchRow();
        $qr->free();
        return ( NULL == $q ? $default : $q[$column] );
    }

    private function dbMakeQuotedArray( array $array )
    {
        if( count( $array ) == 0 )
            return NULL;
        return implode( ', ', array_map( array( $this, 'dbAddQuotes' ), $array ) );
    }

	private function dbStrF( $str )
	{
		$args = func_get_args();
		array_shift( $args );

		$escArgs = array();
		for( $i = 0; $i < count( $args ); $i++ )
			$escArgs[] = $this->dbAddQuotes( $args[$i] );
		$str = TmeitUtil::strFA( $str, $escArgs );
		$str = TmeitUtil::strFXA( $str, $args );
		return $str;
	}

	private static function generateRandomString( $length )
    {
        // See http://php.net/manual/en/function.crypt.php
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz123456890./';
        $alphabetLength = strlen( $alphabet );

        $bytes = openssl_random_pseudo_bytes( $length );

        $result = '';
        for( $i = 0; $i < $length; $i++ )
        {
            $alphaIdx = ord( $bytes[$i] ) % $alphabetLength;
            $result .= $alphabet[$alphaIdx];
        }

        return $result;
    }

	/*
	 * =================================================================================================================
	 *  EVENTS
	 * =================================================================================================================
	 */

	public function eventGetAgeById( $id )
	{
		$qr = $this->db->query( $this->dbStrF( 'SELECT DATEDIFF(NOW(), starts_at) AS age FROM {X0} WHERE id = {1}', self::TableEvents, $id ) );
		$q = $qr->fetchRow();
		$qr->free();

		if( NULL == $q )
			return FALSE;

		// Note - this value is in age, and negative for future events
		return (int) $q['age'];
	}

	public function eventDelete( $id )
	{
		$this->db->query( 'DELETE FROM '.self::TableEvents.' WHERE id = '.$this->dbAddQuotes( $id ).' LIMIT 1' );
		$this->notifDeleteByRef( self::RefExtEvent, $id );
	}

	public function eventGetById( $id )
	{
		$qr = $this->db->query( "SELECT id, title, location, DATE_FORMAT( starts_at, '%Y-%m-%d %H:%i' ) AS starts_at, body, external_url, team_id, is_hidden, type, workers_max, CURRENT_DATE() > DATE( starts_at ) AS is_past "
				.'FROM '.self::TableEvents.' '
				.'WHERE id = '.$this->dbAddQuotes( $id ).' LIMIT 1' );
		$q = $qr->fetchRow();
		$qr->free();

		if( NULL == $q )
			return FALSE;

		return array(
			'id' 			=> (int) $q['id'],
			'title'			=> $q['title'],
			'location'		=> $q['location'],
			'starts_at'		=> $q['starts_at'],
			'body'			=> $q['body'],
			'external_url'	=> $q['external_url'],
			'team_id'		=> (int) $q['team_id'],
			'is_hidden'		=> (bool) $q['is_hidden'],
			'type'			=> (int) $q['type'],
			'workers_max'	=> (int) $q['workers_max'],
			'is_past'		=> (bool) $q['is_past']
		);
	}

	public function eventGetList( $type = self::EventRegular, $limit = 20 )
	{
		$qr = $this->db->query( "SELECT e.id, e.title, e.location, DATE( e.starts_at ) AS start_date, DATE_FORMAT( e.starts_at, '%H:%i' ) AS start_time, e.workers_count, e.workers_max, t.id AS team_id, t.title AS team_title, CURRENT_DATE() > DATE( starts_at ) AS is_past, "
				.'EXISTS ( SELECT * FROM '.self::TableWorkReports.' AS w WHERE w.event_id = e.id ) AS is_reported '
				.'FROM '.self::TableEvents.' AS e LEFT JOIN '.self::TableTeams.' AS t ON e.team_id = t.id '
				.'WHERE e.type = '.$this->dbAddQuotes( $type ).' '
				.'ORDER BY e.starts_at DESC LIMIT '.( (int) $limit ) );

		$rows = array();
		while( NULL != ( $q = $qr->fetchRow() ) )
			$rows[$q['id']] = array(
				'title' 		=> $q['title'],
				'location' 		=> $q['location'],
				'start_date' 	=> $q['start_date'],
				'start_time' 	=> $q['start_time'],
				'workers_count' => (int) $q['workers_count'],
				'workers_max'	=> (int) $q['workers_max'],
				'team_id'		=> (int) $q['team_id'],
				'team_title'	=> empty( $q['team_title'] ) ? "(Inget)" : $q['team_title'],
				'is_past'		=> (bool) $q['is_past'],
				'is_reported'	=> (bool) $q['is_reported']
			);
		$qr->free();

		return $rows;
	}

	public function eventGetNew()
	{
		return array(
			'title' 		=> '',
			'location' 		=> 'Kistan',
			'starts_at' 	=> date( 'Y-m-d H:i' ),
			'body' 			=> '',
			'external_url' 	=> '',
			'team_id' 		=> 0,
			'is_hidden'		=> false,
			'type'			=> self::EventRegular,
			'workers_max'	=> 20
		);
	}

	public function eventGetNewLunch()
	{
		$event = $this->eventGetNew();
		$event['title'] = 'Lunch';
		$event['location'] = '';
		$event['starts_at'] = date( 'Y-m-d' ).' 12:00';
		$event['type'] = self::EventLunch;
		return $event;
	}

	public function eventGetPublicList()
	{
		$qr = $this->db->query( "SELECT e.id, e.title, e.location, DATE( e.starts_at ) AS start_date, MONTH( e.starts_at ) AS start_month, DAY( e.starts_at ) AS start_day, DAYOFWEEK( e.starts_at ) AS start_dow, DATE_FORMAT( e.starts_at, '%H:%i' ) AS start_time, t.title AS team_title "
			.'FROM '.self::TableEvents.' AS e LEFT JOIN '.self::TableTeams.' AS t ON e.team_id = t.id '
			.'WHERE CURRENT_DATE() <= DATE( e.starts_at ) AND e.is_hidden = 0 AND e.type = '.$this->dbAddQuotes( self::EventRegular ).' ORDER BY e.starts_at' );

		$rows = array();
		while( NULL != ( $q = $qr->fetchRow() ) )
			$rows[$q['id']] = array(
				'title'			=> $q['title'],
				'location'		=> $q['location'],
				'start_date'	=> $q['start_date'],
				'start_month'	=> (int) $q['start_month'],
				'start_day'		=> (int) $q['start_day'],
				'start_weekday'	=> (int) $q['start_dow'],
				'start_time'	=> $q['start_time'],
				'team_title'	=> $q['team_title'] );
		$qr->free();

		return $rows;
	}

	public function eventGetTitlesByWorker( $workerId )
	{
		$qr = $this->db->query( 'SELECT e.id, e.title, DATE(e.starts_at) AS start_date FROM '.self::TableEvents.' e WHERE e.id IN '
			.'( SELECT r.event_id FROM '.self::TableWorkReports.' r WHERE r.id IN '
			.'( SELECT rw.report_id FROM '.self::TableWorkReportsWorkers.' rw WHERE rw.user_id = '.$this->dbAddQuotes( $workerId ).' ) ) ORDER BY e.starts_at' );

		$rows = array();
		while( NULL != ( $q = $qr->fetchRow() ) )
			$rows[] = array(
				'event_id'		=> (int) $q['id'],
				'event_title'	=> $q['title'],
				'event_date'	=> $q['start_date'] );

		$qr->free();
		return $rows;
	}

	public function eventGetWorkersById( $id )
	{
		$qr = $this->db->query( 'SELECT w.working, w.work_from, w.work_until, w.comment, u.id, u.realname, u.email, u.phone, g.title AS group_title, t.title AS team_title '
			.'FROM '.self::TableEventsWorkers.' AS w LEFT JOIN '.self::TableUsers.' AS u ON w.user_id = u.id '
			.'LEFT JOIN '.self::TableGroups.' AS g ON u.group_id = g.id '
			.'LEFT JOIN '.self::TableTeams.' AS t ON u.team_id = t.id '
			.'WHERE w.event_id = '.$this->dbAddQuotes( $id ).' ORDER BY w.working DESC, u.realname ASC' );

		$rows = array();
		while( NULL != ( $q = $qr->fetchRow() ) )
			$rows[$q['id']] = array(
				'working'		=> (int) $q['working'],
				'range'         => $this->eventGetWorkRange( (int) $q['work_from'], (int) $q['work_until'] ),
				'comment'		=> $q['comment'],
				'realname'		=> $q['realname'],
				'email'			=> $q['email'],
				'phone'			=> $q['phone'],
				'group_title'	=> $q['group_title'],
				'team_title'	=> $q['team_title'] );
		$qr->free();

		return $rows;
	}

	private function eventGetWorkRange( $workFrom, $workUntil )
    {
        if( $workFrom < 0 || $workUntil < $workFrom )
            return FALSE;
        return array( $workFrom, $workUntil );
    }

	public function eventRemoveWorker( $eventId, $userId )
	{
		$this->db->query( 'DELETE FROM '.self::TableEventsWorkers.' WHERE '
			.'event_id = '.$this->dbAddQuotes( $eventId ).' AND '
			.'user_id = '.$this->dbAddQuotes( $userId ).' LIMIT 1' );

		$this->eventRefreshWorkerCount( $eventId );
	}

	public function eventSave( array $event )
	{
		if( isset( $event['id'] ) )
		{
			// Event already exists
			$this->db->query( 'UPDATE '.self::TableEvents.' SET '
				.'title = '.$this->dbAddQuotes( $event['title'] ).', '
				.'location = '.$this->dbAddQuotes( $event['location'] ).', '
				.'starts_at = '.$this->dbAddQuotes( $event['starts_at'] ).', '
				.'body = '.$this->dbAddQuotes( $event['body'] ).', '
				.'external_url = '.$this->dbAddQuotes( $event['external_url'] ).', '
				.'team_id = '.$this->dbAddQuotesOrNull( $event['team_id'] ).', '
				.'is_hidden = '.( $event['is_hidden'] ? '1' : '0' ).', '
				.'workers_max = '.$this->dbAddQuotes( $event['workers_max'] )
				.' WHERE id = '.$this->dbAddQuotes( $event['id'] ).' LIMIT 1' );
		}
		else
		{
			// New event
			$this->db->query( 'INSERT '.self::TableEvents.' ( title, location, starts_at, body, external_url, team_id, is_hidden, type, workers_max ) VALUES ( '
					.$this->dbAddQuotes( $event['title'] ).', '
					.$this->dbAddQuotes( $event['location'] ).', '
					.$this->dbAddQuotes( $event['starts_at'] ).', '
					.$this->dbAddQuotes( $event['body'] ).', '
					.$this->dbAddQuotes( $event['external_url'] ).', '
					.$this->dbAddQuotesOrNull( $event['team_id'] ).', '
					.( $event['is_hidden'] ? '1' : '0' ).', '
					.$this->dbAddQuotes( $event['type'] ).', '
					.$this->dbAddQuotes( $event['workers_max'] )
					.' )'
			);

			$event['id'] = $this->db->insertId();
		}

        if( self::EventLunch != $event['type'] )
            $this->notifCreateNew( self::RefEvent, $event['id'], TmeitUtil::strF( 'Nytt evenemang: <span>{0}</span>', $event['title'] ) );

		return $event['id'];
	}

	public function eventSaveWorker( $eventId, $userId, $working, $range, $comment )
	{
	    $this->db->query( $this->dbStrF( 'INSERT INTO {X0} ( event_id, user_id, working, work_from, work_until, comment ) VALUES '
	        .'( {1}, {2}, {3}, {5}, {6}, {4} ) ON DUPLICATE KEY UPDATE working = VALUES(working), work_from = VALUES(work_from), work_until = VALUES(work_until), comment = VALUES(comment)',
            self::TableEventsWorkers, $eventId, $userId, $working, $comment,
            ( FALSE == $range ? -1 : $range[0] ), ( FALSE == $range ? -1 : $range[1] ) ) );

    	$this->eventRefreshWorkerCount( $eventId );
	}

	public function eventRefreshWorkerCount( $eventId )
	{
		$this->db->query( 'UPDATE '.self::TableEvents.' SET workers_count = ( '
			.'SELECT COUNT(*) FROM '.self::TableEventsWorkers.' WHERE event_id = '.$this->dbAddQuotes( $eventId ).' AND working = '.$this->dbAddQuotes( self::WorkYes ).' ) '
			.'WHERE id = '.$this->dbAddQuotes( $eventId ) );
	}

	/*
	 * =================================================================================================================
	 *  EXPERIENCE
	 * =================================================================================================================
	 */

	public function experienceDeleteManualEvent( $id )
	{
		$this->db->query( 'DELETE FROM '.self::TableExperienceEvents.' WHERE '
			.'id = '.$this->dbAddQuotes( $id ).' AND '
			.'type = '.$this->dbAddQuotes( self::ExperienceTypeManual ).' LIMIT 1' );
	}

	public function experienceGetCacheByUser( $userId )
	{
		$qr = $this->db->query( 'SELECT points, badges FROM '.self::TableExperienceCache.' WHERE user_id = '.$this->dbAddQuotes( $userId ).' LIMIT 1' );

		$q = $qr->fetchRow();
		$qr->free();

		if( NULL == $q )
			return FALSE;

		$badges = ( $q['badges'] == '' ? array() : explode( ',', $q['badges'] ) );
		return array(
			'points' => (int) $q['points'],
			'badges' => $badges );
	}

	public function experienceGetEvents()
	{
		$qr = $this->db->query( "SELECT e.id, e.user_id, DATE_FORMAT( e.timestamp, '%Y-%m-%d %H:%i' ) AS timestamp, e.type, e.exp, e.badge, u.realname AS user_name, a.realname AS admin_name FROM ".self::TableExperienceEvents.' AS e'
			.' LEFT JOIN '.self::TableUsers.' AS u ON e.user_id = u.id'
			.' LEFT JOIN '.self::TableUsers.' AS a ON e.admin_id = a.id'
			.' ORDER BY e.timestamp DESC' );

		$rows = array();
		while( NULL != ( $q = $qr->fetchRow() ) )
			$rows[$q['id']] = array(
				'user_id'		=> (int) $q['user_id'],
				'timestamp'		=> $q['timestamp'],
				'type'			=> (int) $q['type'],
				'exp'			=> (int) $q['exp'],
				'badge'			=> (int) $q['badge'],
				'user_name'		=> $q['user_name'],
				'admin_name'	=> $q['admin_name']
			);
		$qr->free();

		return $rows;
	}

	public function experienceGetEventSummaryByUserId( $userId )
	{
		$qr = $this->db->query( 'SELECT exp, badge FROM '.self::TableExperienceEvents.' WHERE user_id = '.$this->dbAddQuotes( $userId ).' ORDER BY timestamp' );
		$points = 0;
		$badges = array();

		while( NULL != ( $q = $qr->fetchRow() ) )
		{
			$points += (int) $q[0];
			$thisBadge = (int) $q[1];
			if( $thisBadge > 0 )
				$badges[] = $thisBadge;
		}

		$qr->free();
		return array( $points, $badges );
	}

	public function experienceSaveManualEvent( $userId, $exp, $badge, $adminId )
	{
		$this->db->query( 'INSERT '.self::TableExperienceEvents.' ( user_id, timestamp, type, exp, badge, admin_id ) VALUES ( '
			.$this->dbAddQuotes( $userId ).', '
			.'NOW(), '
			.$this->dbAddQuotes( self::ExperienceTypeManual ).', '
			.$this->dbAddQuotes( $exp ).', '
			.$this->dbAddQuotes( $badge ).', '
			.$this->dbAddQuotes( $adminId )
			.' )'
		);

		return $this->db->insertId();
	}

	public function experienceUpdateCache( $user, $points, array $badges )
	{
		$badges = implode( ',', $badges );
		$this->db->query( 'REPLACE '.self::TableExperienceCache.' SET user_id = '.$this->dbAddQuotes( $user['id'] ).', username = '.$this->dbAddQuotes( $user['username'] )
			.', points = '.$this->dbAddQuotes( $points ).', badges = '.$this->dbAddQuotes( $badges ) );
	}

	/*
	 * =================================================================================================================
	 *  EXTERNAL EVENTS
	 * =================================================================================================================
	 */

	public function extEventAddOrUpdateAttendee( $eventId, $userId, $dob, $foodPrefs, $drinkPrefs, $notes, $byAdmin = false )
	{
		$this->db->query( $this->dbStrF( 'INSERT INTO {X0} ( user_id, extevent_id, food_prefs, drink_prefs, notes, is_attending, created ) '
			.'VALUES ( {1}, {2}, {3}, {4}, {5}, 1, NOW() ) ON DUPLICATE KEY UPDATE food_prefs = VALUES(food_prefs), '
			.'drink_prefs = VALUES(drink_prefs), notes = VALUES(notes)',
			self::TableExternalEventAttend, $userId, $eventId, $foodPrefs, $drinkPrefs, $notes ) );

		// With ON DUPLICATE KEY UPDATE, the affected-rows value per row is 1 if the
		// row is inserted as a new row and 2 if an existing row is updated.
		$wasCreated = ( 1 == $this->db->affectedRows() );
		$writeAttendLog = $wasCreated;
		if( !$wasCreated )
		{
			$this->db->query( $this->dbStrF( 'UPDATE {X0} SET is_attending = 1 WHERE user_id = {1} AND extevent_id = {2} LIMIT 1',
				self::TableExternalEventAttend, $userId, $eventId ) );
			$writeAttendLog = ( 1 == $this->db->affectedRows() );
		}

		if( $wasCreated || $writeAttendLog )
		{
			$this->db->query( $this->dbStrF( 'INSERT INTO {X0} ( extevent_id, user_id, action, log_time ) VALUES ( {1}, {2}, {3}, NOW() )',
					self::TableExternalEventLogs, $eventId, $userId, ( $byAdmin ? self::ExtEventLogAdminAttend : self::ExtEventLogAttend ) ) );
		}

		$props = $this->userGetPropsById( $userId );
		$this->userSetProp( $props, self::PropBirthdate, 0, $dob );
		$this->userSetProp( $props, self::PropLastFoodPrefs, 0, $foodPrefs );
		$this->userSetProp( $props, self::PropLastDrinkPrefs, 0, $drinkPrefs );
		$this->userSaveProps( $userId, $props );
	}

	public function extEventCreate( $title, $date, $lastSignup, $body, $externalUrl )
	{
		$this->db->query( $this->dbStrF( 'INSERT {X0} ( title, starts_at, last_signup, body, external_url ) VALUES ( {1}, {2}, {3}, {4}, {5} )',
			self::TableExternalEvents, $title, $date, $lastSignup, $body, $externalUrl ) );

        $id = $this->db->insertId();
        $this->notifCreateNew( self::RefExtEvent, $id, TmeitUtil::strF( 'Nytt externt evenemang: <span>{0}</span>', $title ) );
		return $id;
	}

	public function extEventDelete( $id )
	{
		$this->db->query( $this->dbStrF( 'DELETE FROM {X0} WHERE id = {1} LIMIT 1', self::TableExternalEvents, $id ) );
		$this->notifDeleteByRef( self::RefExtEvent, $id );
	}

	public function extEventGetById( $id )
	{
		$qr = $this->db->query( $this->dbStrF( 'SELECT title, DATE( starts_at ) AS start_date, DATE( last_signup ) AS last_signup_date,  body, external_url, '
			.'CURRENT_DATE() > DATE( starts_at ) AS is_past, CURRENT_DATE() > DATE( last_signup ) AS is_past_signup FROM {X0} '
			.'WHERE id = {1} LIMIT 1', self::TableExternalEvents, $id ) );

		$q = $qr->fetchRow();
		$qr->free();

		if( NULL == $q )
			return FALSE;

		return array(
			'id'			=> (int) $id,
			'title'			=> $q['title'],
			'start_date'	=> $q['start_date'],
			'last_signup'	=> $q['last_signup_date'],
			'is_past' 		=> (bool) $q['is_past'],
			'is_past_signup' => (bool) $q['is_past_signup'],
			'body'			=> $q['body'],
			'external_url'	=> $q['external_url'],
			'logs'			=> $this->extEventGetLogs( $id ),
			'attendees'		=> $this->extEventGetAttendees( $id )
		);
	}

	private function extEventGetAttendees( $id )
	{
		$qr = $this->db->query( $this->dbStrF( 'SELECT e.user_id, u.realname, p.prop_val_str AS birthdate, e.food_prefs, e.drink_prefs, e.notes FROM {X0} AS e '
			.'LEFT JOIN {X1} AS u ON e.user_id = u.id LEFT JOIN {X2} AS p ON p.user_id = e.user_id AND p.prop_id = {3} WHERE e.extevent_id = {4} AND e.is_attending = 1 ORDER BY created',
			 self::TableExternalEventAttend, self::TableUsers, self::TableProps, self::PropBirthdate, $id ) );

		$rows = array();
		while( NULL != ( $q = $qr->fetchRow() ) )
			$rows[(int) $q['user_id']] = array(
							 'user_name'	=> $q['realname'],
							 'dob'			=> $q['birthdate'],
							 'food_prefs'	=> $q['food_prefs'],
							 'drink_prefs'	=> $q['drink_prefs'],
							 'notes'		=> $q['notes'] );
		$qr->free();
		return $rows;
	}

	public function extEventGetBlankAttendee( $userId )
	{
		$user = $this->userGetSimpleById( $userId );
		$props = $this->userGetPropsById( $userId );

		$dateOfBirth = $this->userGetPropString( $props, self::PropBirthdate );
		$lastFood = $this->userGetPropString( $props, self::PropLastFoodPrefs );
		$lastDrink = $this->userGetPropString( $props, self::PropLastDrinkPrefs );

		return array(
			'user_name'		=> $user['realname'],
			'dob'			=> $dateOfBirth,
			'food_prefs'	=> $lastFood,
			'drink_prefs'	=> $lastDrink,
			'notes'			=> ''
		);
	}

	private function extEventGetLogs( $id )
	{
		$qr = $this->db->query( $this->dbStrF( 'SELECT l.user_id, u.realname, l.action, DATE_FORMAT( l.log_time, \'%Y-%m-%d %H:%i\' ) AS log_time_fmt FROM {X0} AS l '
			.'LEFT JOIN {X1} AS u ON l.user_id = u.id WHERE l.extevent_id = {2} ORDER BY l.log_time DESC',
			self::TableExternalEventLogs, self::TableUsers, $id ) );

		$rows = array();
		while( NULL != ( $q = $qr->fetchRow() ) )
			$rows[] = array( 'user_id'		=> (int) $q['user_id'],
							 'user_name'	=> $q['realname'],
							 'action'		=> (int) $q['action'],
							 'log_time'		=> $q['log_time_fmt'] );
		$qr->free();
		return $rows;
	}

	public function extEventGetList( $currentUserId = 0, $limit = 30 )
	{
    	$qr = $this->db->query( $this->dbStrF( 'SELECT e.id, e.title, DATE( e.starts_at ) AS start_date, DATE( e.last_signup ) AS last_signup, '
			.'CURRENT_DATE() > DATE( e.starts_at ) AS is_past, CURRENT_DATE() > DATE( e.last_signup ) AS is_past_signup, '
			.'CURRENT_DATE() > DATE_SUB( e.last_signup, INTERVAL 3 DAY ) AS is_near_signup, '
			.'( SELECT COUNT(*) FROM {X1} AS g WHERE g.extevent_id = e.id AND g.is_attending = 1 ) AS attendees, '
			.'EXISTS ( SELECT 1 FROM {X1} AS h WHERE h.extevent_id = e.id AND h.user_id = {3} AND h.is_attending = 1 ) AS is_attending '
			.'FROM {X0} AS e ORDER BY e.starts_at DESC, e.title ASC LIMIT {X2}',
			self::TableExternalEvents, self::TableExternalEventAttend, (int) $limit, $currentUserId ) );

		$rows = array();
		while( NULL != ( $q = $qr->fetchRow() ) )
			$rows[$q['id']] = array(
				'title'			=> $q['title'],
				'start_date'	=> $q['start_date'],
				'last_signup'	=> $q['last_signup'],
				'is_past'		=> (bool) $q['is_past'],
				'is_past_signup' => (bool) $q['is_past_signup'],
				'is_near_signup' => (bool) $q['is_near_signup'],
				'is_attending'  => (bool) $q['is_attending'],
				'attendees'		=> (int) $q['attendees'] );
		$qr->free();
		return $rows;
	}

	public function extEventGetNew()
	{
		return array(
			'id'			=> 0,
			'title'			=> '',
			'start_date'	=> '',
			'last_signup'	=> '',
			'body'			=> '',
			'external_url'	=> '',
			'logs'			=> array(),
			'attendees'		=> array()
		);
	}

	public function extEventUnsetAttendee( $id, $userId, $byAdmin )
	{
		$this->db->query( $this->dbStrF( 'UPDATE {X0} SET is_attending = 0 WHERE user_id = {1} AND extevent_id = {2} LIMIT 1',
				self::TableExternalEventAttend, $userId, $id ) );

		$this->db->query( $this->dbStrF( 'INSERT INTO {X0} ( extevent_id, user_id, action, log_time ) VALUES ( {1}, {2}, {3}, NOW() )',
				self::TableExternalEventLogs, $id, $userId, ( $byAdmin ? self::ExtEventLogAdminUnattend : self::ExtEventLogUnattend ) ) );
	}

	public function extEventUpdate( $id, $title, $date, $lastSignup, $body, $url )
	{
		$this->db->query( $this->dbStrF( 'UPDATE {X0} SET title = {1}, starts_at = {2}, last_signup = {3}, body = {4}, external_url = {5} '
			.'WHERE id = {6} LIMIT 1', self::TableExternalEvents, $title, $date, $lastSignup, $body, $url, $id ) );
	}

	/*
	 * =================================================================================================================
	 * GOOGLE CLOUD MESSAGING INTEGRATION
	 * =================================================================================================================
	 */

     public function gcmGetAllRegistered()
     {
         $qr = $this->db->query( $this->dbStrF( 'SELECT registration_id FROM {X0}', self::TableGcm ) );
         return $this->dbGetColumn( $qr, 'registration_id' );
     }

	 public function gcmRegister( $userId, $registrationId )
     {
        $this->db->query( $this->dbStrF( 'INSERT IGNORE INTO {X0} ( registration_id, user_id, created ) VALUES ( {1}, {2}, NOW() )',
            self::TableGcm, $registrationId, $userId ) );
     }

	 public function gcmUnregister( $userId, $registrationId )
     {
        $this->db->query( $this->dbStrF( 'DELETE FROM {X0} WHERE registration_id = {1} AND user_id = {2} LIMIT 1',
            self::TableGcm, $registrationId, $userId ) );
     }

	/*
	 * =================================================================================================================
	 *  GROUPS
	 * =================================================================================================================
	 */

    public function groupGetIdByTitle( $title )
    {
        $qr = $this->db->query( 'SELECT id FROM '.self::TableGroups.' WHERE title = '.$this->dbAddQuotes( $title ).' LIMIT 1' );
        return (int) $this->dbGetValue( $qr );
    }

    public function groupGetIsInactive( $id )
    {
        $qr = $this->db->query( 'SELECT is_inactive FROM '.self::TableGroups.' WHERE title = '.$this->dbAddQuotes( $id ).' LIMIT 1' );
        return (bool) $this->dbGetValue( $qr );
    }

	public function groupGetList()
	{
		$qr = $this->db->query( 'SELECT id, title, sort_idx, is_inactive FROM '.self::TableGroups.' ORDER BY sort_idx, title' );

		$rows = array();
		while( NULL != ( $q = $qr->fetchRow() ) )
			$rows[$q['id']] = array(
				'title' 			=> $q['title'],
				'sort_idx'			=> (int) $q['sort_idx'],
                'is_inactive'       => (bool) $q['is_inactive']
			);
		$qr->free();

		return $rows;
	}

	public function groupSaveNew( $title )
	{
		$title = trim( $title );
		if( empty( $title ) )
			throw new FatalError( 'Gruppen kan inte ha ett tomt namn.' );

		try
		{
			$this->db->query( 'INSERT '.self::TableGroups.' ( title ) VALUES ( '.$this->dbAddQuotes( $title ).' )' );
		}
		catch( DBQueryError $ex )
		{
			if( self::DuplicateKey == $ex->errno )
				throw new FatalError( 'Gruppen du försöker skapa finns redan.' );

			// Any other errors, just rethrow
			throw $ex;
		}
	}

    public function groupSetIsInactive( $id, $isInactive )
    {
        $this->db->query( 'UPDATE '.self::TableGroups.' SET '
            .'is_inactive = '.$this->dbAddQuotes( $isInactive ? 1 : 0 ).' '
            .'WHERE id = '.$this->dbAddQuotes( $id ).' LIMIT 1' );
    }

	public function groupUpdateSortIdx( $id, $sortIdx )
	{
		$this->db->query( 'UPDATE '.self::TableGroups.' SET '
				.'sort_idx = '.$this->dbAddQuotes( $sortIdx ).' '
				.'WHERE id = '.$this->dbAddQuotes( $id ).' LIMIT 1' );
	}

	/*
	 * =================================================================================================================
	 *  MEDIAWIKI INTEGRATION
	 * =================================================================================================================
	 */

	public function mwGetRealNameById( $mwUserId )
	{
		$qr = $this->db->query( 'SELECT user_real_name FROM '.self::TableMediaWikiUsers.' WHERE user_id = '.$this->dbAddQuotes( $mwUserId ).' LIMIT 1' );
        return $this->dbGetValue( $qr, 'user_real_name' );
	}

	public function mwGetUserIdByName( $username )
	{
		$qr = $this->db->query( 'SELECT user_id FROM '.self::TableMediaWikiUsers.' WHERE user_name = '.$this->dbAddQuotes( ucfirst( $username ) ).' LIMIT 1' );
        return (int) $this->dbGetValue( $qr, 'user_id' );
	}

	public function mwSetTmeitUserGroups( $mwUserId, $userIsActive, $userIsAdmin )
	{
		$this->db->query( 'DELETE FROM '.self::TableMediaWikiUserGroups.' WHERE ug_user = '.$this->dbAddQuotes( $mwUserId ).' '
			.'AND ug_group IN ( '.$this->dbAddQuotes( self::MediaWikiTmeitGroup ).', '.$this->dbAddQuotes( self::MediaWikiTmeitAdminGroup ).' )' );

        $groups = array();
        if( $userIsActive )
        {
		    $groups[] = self::MediaWikiTmeitGroup;
		    if( $userIsAdmin )
			    $groups[] = self::MediaWikiTmeitAdminGroup;
        }

		foreach( $groups as $group )
			$this->db->query( 'INSERT INTO '.self::TableMediaWikiUserGroups.' ( ug_user, ug_group ) VALUES '
				.'( '.$this->dbAddQuotes( $mwUserId ).', '.$this->dbAddQuotes( $group ).' )' );
	}

	public function mwSetUserRealName( $mwUserId, $realName )
	{
		$this->db->query( 'UPDATE '.self::TableMediaWikiUsers.' SET user_real_name = '.$this->dbAddQuotes( $realName ).' '
			.'WHERE user_id = '.$this->dbAddQuotes( $mwUserId ).' LIMIT 1' );
	}

	/*
	 * =================================================================================================================
	 * NOTIFICATIONS
	 * =================================================================================================================
	 */

    public function notifCreateNew( $refType, $refId, $body, $allowMultiple = false )
    {
        $qr = $this->db->query( $this->dbStrF( 'SELECT EXISTS ( SELECT 1 FROM {X0} WHERE ref_id = {1} AND ref_type = {2} LIMIT 1 )',
                self::TableNotifications, $refId, $refType ) );

        if( !$this->dbGetValue( $qr, 0, FALSE ) || $allowMultiple )
        {
            // No existing notification exists, OR we allow multiple notifs
            $this->db->query( $this->dbStrF( 'INSERT INTO {X0} ( ref_id, ref_type, body, gcm_sent, created ) VALUES ( {1}, {2}, {3}, 0, NOW() )',
                self::TableNotifications, $refId, $refType, $body ) );
        }
        else
        {
            // An existing notification exists, update it (update latest if multiple, although we should avoid doing that)
            $this->db->query( $this->dbStrF( 'UPDATE {X0} SET body = {1}, gcm_sent = 0 WHERE ref_id = {2} AND ref_type = {3} ORDER BY created DESC LIMIT 1',
                self::TableNotifications, $body, $refId, $refType ) );
        }
    }

    public function notifDeleteByRef( $refType, $refId )
    {
        $this->db->query( $this->dbStrF( 'DELETE FROM {X0} WHERE ref_id = {1} AND ref_type = {2}',
            self::TableNotifications, $refId, $refType ) );
    }

    /**
     * @param DateTime $dateTime
     * @return array
     */
    public function notifGetNewSince( $dateTime, $limit )
    {
        $qr = $this->db->query( $this->dbStrF( 'SELECT id, ref_id, ref_type, body, created FROM {X0} WHERE UNIX_TIMESTAMP(created) > {X1} ORDER BY created DESC LIMIT {X2}',
            self::TableNotifications, (int) $dateTime->getTimestamp(), (int) $limit ) );

        $result = array();
        while( NULL != ( $q = $qr->fetchRow() ) )
        {
            $id = (int) $q['id'];
            $result[$id] = array(
                    'url'       => $this->notifGetUrlByRef( (int) $q['ref_type'], (int) $q['ref_id'] ),
                    'body'      => $q['body'],
                    'created'   => $q['created']
                );
        }
        $qr->free();

        return $result;
    }

    public function notifGetNotSentByGcm()
    {
        $qr = $this->db->query( $this->dbStrF( 'SELECT id FROM {X0} WHERE gcm_sent = 0', self::TableNotifications ) );
        return $this->dbGetColumn( $qr, 'id', true );
    }

    public function notifGetLatest( $userId, $limit = 5, $markAsRead = true )
    {
        $qr = $this->db->query( $this->dbStrF( 'SELECT n.id, n.ref_id, n.ref_type, n.body, n.created, EXISTS '
            .'( SELECT 1 FROM {X1} AS nr WHERE nr.notif_id = n.id AND nr.user_id = {2} ) AS is_read '
            .'FROM {X0} AS n ORDER BY created DESC LIMIT {X3}',
                self::TableNotifications, self::TableNotificationsRead, $userId, (int) $limit ) );

        $result = array();
        $notRead = array();
        while( NULL != ( $q = $qr->fetchRow() ) )
        {
            $id = (int) $q['id'];
            $result[$id] = array(
                'url'       => $this->notifGetUrlByRef( (int) $q['ref_type'], (int) $q['ref_id'] ),
                'body'      => $q['body'],
                'created'   => $q['created'],
                'is_read'   => $q['is_read']
            );

            if( !$q['is_read'] )
                $notRead[] = $id;
        }
        $qr->free();

        // Mark all as-yet unread notifications as read
        if( $markAsRead )
            foreach( $notRead as $id )
                $this->notifMarkAsRead( $id, $userId );

        return $result;
    }

    public static function notifGetUrlByRef( $refType, $refId )
    {
        switch( $refType )
        {
            case self::RefEvent:
                $specialPage = 'TmeitEventWork';
                break;
            case self::RefExtEvent:
                $specialPage = 'TmeitExtEventEdit';
                break;
            default:
            case self::RefNone:
                return '';
        }

        return SpecialPage::getTitleFor( $specialPage )->getFullURL().'/'.$refId;
    }

    public function notifMarkAsRead( $id, $userId )
    {
        $this->db->query( $this->dbStrF( 'INSERT IGNORE INTO {X0} ( notif_id, user_id, read_at ) VALUES ( {1}, {2}, NOW() )',
            self::TableNotificationsRead, $id, $userId ) );
    }

    public function notifSetSentByGcm( array $list )
    {
        if( empty( $list ) )
            return;

        if( count( $list ) == 1 )
        {
            $id = $list[0];
            $this->db->query( $this->dbStrF( 'UPDATE {X0} SET gcm_sent = 1 WHERE id = {1} LIMIT 1', self::TableNotifications, $id ) );
        }
        else
        {
            $quotedArray = $this->dbMakeQuotedArray( $list );
            $this->db->query( $this->dbStrF( 'UPDATE {X0} SET gcm_sent = 1 WHERE id IN ( {X1} )', self::TableNotifications, $quotedArray ) );
        }
    }

	/*
	 * =================================================================================================================
	 * REPORT
	 * =================================================================================================================
	 */

	public function reportCreateOrUpdate( $eventId, $reporterId, array $workers )
	{
		$this->db->query( $this->dbStrF( 'INSERT INTO {X0} ( event_id, reporter_id, last_editor_id ) VALUES ( {1}, {2}, {2} ) '
			.'ON DUPLICATE KEY UPDATE last_editor_id = VALUES(last_editor_id)', self::TableWorkReports, $eventId, $reporterId ) );

		$reportId = $this->reportGetIdByEvent( $eventId );
		if( 0 == $reportId )
			return FALSE;

		$this->db->query( $this->dbStrF( 'DELETE FROM {X0} WHERE report_id = {1}', self::TableWorkReportsWorkers, $reportId ) );
		if( empty( $workers ) )
			return TRUE;

		$first = TRUE;
		$query = $this->dbStrF( 'INSERT INTO {X0} ( report_id, user_id, multi, comment ) VALUES ', self::TableWorkReportsWorkers );
		foreach( $workers as $userId => $worker )
		{
			if( !$first )
				$query .= ', ';
			else
				$first = FALSE;

			$query .= $this->dbStrF( '( {0}, {1}, {2}, {3} )', $reportId, $userId, $worker['multi'], $worker['comment'] );
		}

		$this->db->query( $query );
		return $reportId;
	}

	public function reportGetBlankWorker( $userId )
	{
		$user = $this->userGetSimpleById( $userId );
		if( FALSE == $user )
			return FALSE;

		return array(
			'user_name'		=> $user['realname'],
			'comment'		=> '',
			'multi'			=> 100,
			'team_title'	=> $user['team_title'],
			'group_title'	=> $user['group_title']
		);
	}

	public function reportDelete( $reportId )
	{
		$this->db->query( $this->dbStrF( 'DELETE FROM {X0} WHERE id = {1} LIMIT 1', self::TableWorkReports, $reportId ) );
	}

	public function reportGetByEventId( $id )
	{
		$qr = $this->db->query( $this->dbStrF( 'SELECT r.id, r.reporter_id, u1.realname AS reporter_name, r.last_editor_id, u2.realname AS last_editor_name '
			.'FROM {X0} AS r '
			.'LEFT JOIN {X1} AS u1 ON r.reporter_id = u1.id '
			.'LEFT JOIN {X1} AS u2 ON r.last_editor_id = u2.id '
			.'WHERE r.event_id = {2} LIMIT 1',
			self::TableWorkReports, self::TableUsers, $id ) );
		$q = $qr->fetchRow();
		$qr->free();

		if( NULL == $q )
			return FALSE;

		return array(
			'id'				=> $q['id'],
			'reporter_id'		=> (int) $q['reporter_id'],
			'reporter_name'		=> $q['reporter_name'],
			'last_editor_id'	=> (int) $q['last_editor_id'],
			'last_editor_name'	=> $q['last_editor_name'],
			'workers'			=> $this->reportGetWorkersById( $q['id'] )
		);
	}

	public function reportGetIdByEvent( $eventId )
	{
		$qr = $this->db->query( $this->dbStrF( 'SELECT id FROM {X0} WHERE event_id = {1} LIMIT 1', self::TableWorkReports, $eventId ) );
		return $this->dbGetValue( $qr, 'id', 0 );
	}

	public function reportGetWorkersByEvent( $eventId )
	{
		$qr = $this->db->query( $this->dbStrF( 'SELECT w.user_id, u.realname, t.title AS team_title, g.title AS group_title '
			.'FROM {X0} AS w '
			.'LEFT JOIN {X1} AS u ON w.user_id = u.id '
			.'LEFT JOIN {X2} AS t ON u.team_id = t.id '
			.'LEFT JOIN {X3} AS g ON u.group_id = g.id '
			.'WHERE w.event_id = {4} ORDER BY u.realname',
			self::TableEventsWorkers, self::TableUsers, self::TableTeams, self::TableGroups, $eventId ) );

		$workers = array();
		while( NULL != ( $q = $qr->fetchRow() ) )
			$workers[(int) $q['user_id']] = array(
				'user_name'		=> $q['realname'],
				'comment'		=> '',
				'multi'			=> 100,
				'team_title'	=> $q['team_title'],
				'group_title'	=> $q['group_title']
			);
		$qr->free();

		return $workers;
	}

	private function reportGetWorkersById( $id )
	{
		$qr = $this->db->query( $this->dbStrF( 'SELECT w.user_id, w.comment, w.multi, u.realname, t.title AS team_title, g.title AS group_title '
			.'FROM {X0} AS w '
			.'LEFT JOIN {X1} AS u ON w.user_id = u.id '
			.'LEFT JOIN {X2} AS t ON u.team_id = t.id '
			.'LEFT JOIN {X3} AS g ON u.group_id = g.id '
			.'WHERE w.report_id = {4} ORDER BY u.realname',
			self::TableWorkReportsWorkers, self::TableUsers, self::TableTeams, self::TableGroups, $id ) );

		$workers = array();
		while( NULL != ( $q = $qr->fetchRow() ) )
			$workers[(int) $q['user_id']] = array(
				'user_name'		=> $q['realname'],
				'comment'		=> $q['comment'],
				'multi'			=> (int) $q['multi'],
				'team_title'	=> $q['team_title'],
				'group_title'	=> $q['group_title']
			);
		$qr->free();

		return $workers;
	}

	/*
	 * =================================================================================================================
	 *  TEAMS
	 * =================================================================================================================
	 */

	public function teamDelete( $id )
	{
		$this->db->query( 'DELETE FROM '.self::TableTeams.' WHERE id = '.$this->dbAddQuotes( $id ).' LIMIT 1' );
	}

	public function teamGetList()
	{
		$qr = $this->db->query( 'SELECT id, title FROM '.self::TableTeams.' ORDER BY title' );

		$rows = array();
		while( NULL != ( $q = $qr->fetchRow() ) )
			$rows[$q['id']] = array( 'title' => $q['title'] );
		$qr->free();

		return $rows;
	}

	public function teamGetTitleById( $id )
	{
		$qr = $this->db->query( 'SELECT title FROM '.self::TableTeams.' WHERE id = '.$this->dbAddQuotes( $id ).' LIMIT 1' );
		if( NULL == ( $q = $qr->fetchRow() ) )
			return NULL;
		return $q[0];
	}

	public function teamSaveNew( $title )
	{
		$title = trim( $title );
		if( empty( $title ) )
			throw new FatalError( 'Arbetslaget kan inte ha ett tomt namn.' );

		try
		{
			$this->db->query( 'INSERT '.self::TableTeams.' ( title ) VALUES ( '.$this->dbAddQuotes( $title ).' )' );
		}
		catch( DBQueryError $ex )
		{
			if( self::DuplicateKey == $ex->errno )
				throw new FatalError( 'Arbetslaget du försöker skapa finns redan.' );

			// Any other errors, just rethrow
			throw $ex;
		}
	}

	/*
	 * =================================================================================================================
	 *  TEMPORARY
	 * =================================================================================================================
	 */

	public function tempInsert( $source, $value )
	{
		$value = serialize( $value );
		$this->db->query( 'INSERT INTO '.self::TableTemp.' ( source, value ) '
			.'VALUES ( '.$this->dbAddQuotes( $source ).', '.$this->dbAddQuotes( $value ).' )' );

		return $this->db->insertId();
	}

	public function tempDelete( $source, $id )
	{
		$this->db->query( 'DELETE FROM '.self::TableTemp.' WHERE id = '.$this->dbAddQuotes( $id ).' AND source = '.$this->dbAddQuotes( $source ).' LIMIT 1' );
	}

	public function tempDeleteAll( $source )
	{
		$this->db->query( 'DELETE FROM '.self::TableTemp.' WHERE source = '.$this->dbAddQuotes( $source ) );
	}

	public function tempGetObjects( $source, $limit = 0, $orderBy = "last_modified", $orderDesc = true )
	{
		$query = 'SELECT id, value FROM '.self::TableTemp.' WHERE source = '.$this->dbAddQuotes( $source );
		$query .= 'ORDER BY '.$orderBy.' '.( $orderDesc ? 'DESC' : 'ASC' );
		if( $limit > 0 )
			$query .= ' LIMIT '.$limit;

		$qr = $this->db->query( $query );

		$rows = array();
		while( NULL != ( $q = $qr->fetchRow() ) )
			$rows[$q['id']] = unserialize( $q['value'] );
		$qr->free();

		return $rows;
	}

	/*
	 * =================================================================================================================
	 *  TITLES
	 * =================================================================================================================
	 */

	public function titleDelete( $id )
	{
		$this->db->query( 'DELETE FROM '.self::TableTitles.' WHERE id = '.$this->dbAddQuotes( $id ).' LIMIT 1' );
	}

    public function titleGetById( $id )
    {
        $qr = $this->db->query( 'SELECT id, title FROM '.self::TableTitles.' WHERE id = '.$this->dbAddQuotes( $id ).' LIMIT 1' );
        $q = $qr->fetchRow();
        $qr->free();

        if( NULL == $q )
            return FALSE;

        return array( 'id'		=> (int) $q['id'],
                      'title'   => $q['title'] );
    }

	public function titleGetList()
	{
		$qr = $this->db->query( 'SELECT id, title, email, sort_idx FROM '.self::TableTitles.' ORDER BY title' );

		$rows = array();
		while( NULL != ( $q = $qr->fetchRow() ) )
			$rows[$q['id']] = array(
				'title' 	=> $q['title'],
				'email'		=> $q['email'],
				'sort_idx'	=> $q['sort_idx'] > 0 ? $q['sort_idx'] : NULL
			);
		$qr->free();

		return $rows;
	}

	public function titleSaveNew( $title )
	{
		$title = trim( $title );
		if( empty( $title ) )
			throw new FatalError( 'Titeln kan inte vara tom.' );

		try
		{
			$this->db->query( 'INSERT '.self::TableTitles.' ( title ) VALUES ( '.$this->dbAddQuotes( $title ).' )' );
		}
		catch( DBQueryError $ex )
		{
			if( self::DuplicateKey == $ex->errno )
				throw new FatalError( 'Titeln du försöker skapa finns redan.' );

			// Any other errors, just rethrow
			throw $ex;
		}
	}

	public function titleSetEmailAndSortIdx( $id, $email, $sortIdx )
	{
		$this->db->query( 'UPDATE '.self::TableTitles.' SET '
				.'email = '.$this->dbAddQuotes( $email ).', '
				.'sort_idx = '.$this->dbAddQuotes( $sortIdx ).' '
				.'WHERE id = '.$this->dbAddQuotes( $id ).' LIMIT 1' );
	}

	/*
	 * =================================================================================================================
	 *  USERS
	 * =================================================================================================================
	 */

	public function userGetNew()
	{
		return array(
			'username' 			=> '',
			'realname' 			=> '',
			'phone' 			=> '',
			'email' 			=> '',
			'is_admin' 			=> false,
			'is_team_admin'		=> false,
			'is_hidden'			=> false,
			'group_id'			=> 0,
			'team_id'			=> 0,
			'title_id'			=> 0,
			'props' 			=> array(),
			'mediawiki_user_id' => 0
		);
	}

	public function userGetNumberOfEventsReported( $userId )
    {
        $qr = $this->db->query( 'SELECT COUNT(*) FROM '.self::TableWorkReports.' WHERE reporter_id = '.$this->dbAddQuotes( $userId ) );
        return $this->dbGetValue( $qr, 0 );
    }

    public function userGetNumberOfEventsWorked( $userId )
    {
        $qr = $this->db->query( $this->dbStrF( 'SELECT COUNT(*) FROM {X0} WHERE user_id = {1} AND multi > 0', self::TableWorkReportsWorkers, $userId ) );
        return $this->dbGetValue( $qr, 0 );
    }

    public function userGetListByGroups( array $groups )
    {
        if( empty( $groups ) )
            return array();

        $where = TmeitUtil::strF( 'group_id IN ( {0} )', $this->dbMakeQuotedArray( $groups ) );
        return $this->userGetListByCustomWhereClause( $where );
    }

	public function userGetListByGroup( $groupId )
	{
		$where = 'group_id '.( $groupId > 0 ? ' = '.$this->dbAddQuotes( $groupId ) : 'IS NULL' );
		return $this->userGetListByCustomWhereClause( $where );
	}

	public function userGetListByTeam( $teamId )
	{
		$where = 'team_id '.( $teamId > 0 ? ' = '.$this->dbAddQuotes( $teamId ) : 'IS NULL' );
		return $this->userGetListByCustomWhereClause( $where );
	}

	// Special convenience function for SpecialTmeitMembers
    public function userGetListPublicByGroup( $groupTitle, $titleOrder = false, $getProps = false )
    {
        $groupId = $this->groupGetIdByTitle( $groupTitle );

        $query = 'SELECT u.id, u.username, u.realname, u.email, u.phone, t.title, t.email AS temail '
			.'FROM '.self::TableUsers.' AS u LEFT JOIN '.self::TableTitles.' AS t ON u.title_id = t.id '
			.'WHERE u.group_id = '.$this->dbAddQuotes( $groupId ).' '
			.'AND u.is_hidden = 0 '
			.'ORDER BY '.( $titleOrder ? 't.sort_idx' : 'u.realname' );

        $qr = $this->db->query( $query );

        $rows = array();
        while( NULL != ( $q = $qr->fetchRow() ) )
            $rows[$q['id']] = array(
                  'username' 		=> $q['username'],
                  'realname' 		=> $q['realname'],
                  'phone' 		    => $q['phone'],
                  'email' 		    => empty( $q['temail'] ) ? $q['email'] : $q['temail'],
                  'title'           => empty( $q['title'] ) ? $groupTitle : $q['title'] );
        $qr->free();

        if( $getProps )
            foreach( array_keys( $rows ) as $id )
                $rows[$id]['props'] = $this->userGetPropsById( $id );

        return $rows;
    }

    public function userGetListOfNames()
	{
		$qr = $this->db->query( 'SELECT id, username, realname FROM '.self::TableUsers.' ORDER BY username' );

		$rows = array();
        while( NULL != ( $q = $qr->fetchRow() ) )
            $rows[] = $q;

		return $rows;
	}

    public function userGetOldTitles( $userId )
    {
        $qr = $this->db->query( 'SELECT prop_val_str FROM '.self::TableProps.' WHERE user_id = '.$this->dbAddQuotes( $userId )
        	.' AND prop_id = '.$this->dbAddQuotes( self::PropOldTitle ).' ORDER BY prop_val_int' );

        return $this->dbGetColumn( $qr );
    }

	public function userGetSimpleById( $id )
	{
		$qr = $this->db->query( 'SELECT u.realname, g.title AS group_title, t.title AS team_title FROM '.self::TableUsers.' AS u '
			.'LEFT JOIN '.self::TableGroups.' AS g ON u.group_id = g.id '
			.'LEFT JOIN '.self::TableTeams.' AS t ON u.team_id = t.id '
			.'WHERE u.id = '.$this->dbAddQuotes( $id ).' LIMIT 1' );
		$q = $qr->fetchRow();
		$qr->free();

		if( NULL == $q )
			return FALSE;

		return array(
			'id'			=> (int) $id,
			'realname'		=> $q['realname'],
			'group_title'	=> $q['group_title'],
			'team_title'	=> $q['team_title'] );
	}

	public function userGetByName( $username )
	{
		$qr = $this->db->query( 'SELECT id, username, realname, phone, email, is_admin, is_team_admin, is_hidden, group_id, title_id, team_id,  mediawiki_user_id FROM '.self::TableUsers.' WHERE '
				.'username = '.$this->dbAddQuotes( $username ).' LIMIT 1' );
		$q = $qr->fetchRow();
		$qr->free();

		if( NULL == $q )
			return FALSE;

		return array(
			'id'			=> (int) $q['id'],
			'username' 		=> $q['username'],
			'realname' 		=> $q['realname'],
			'phone' 		=> $q['phone'],
			'email' 		=> $q['email'],
			'is_admin'		=> (bool) $q['is_admin'],
			'is_team_admin'	=> (bool) $q['is_team_admin'],
			'is_hidden'		=> (bool) $q['is_hidden'],
			'group_id'		=> (int) $q['group_id'],
			'title_id'		=> (int) $q['title_id'],
			'team_id'		=> (int) $q['team_id'],
			'mediawiki_user_id'	=> (int) $q['mediawiki_user_id'],
			'props'			=> $this->userGetPropsById( $q['id'] ),
		);
	}

	public function userGetIdByMediaWikiUserId( $mwUserId )
	{
		$qr = $this->db->query( 'SELECT id FROM '.self::TableUsers.' WHERE mediawiki_user_id = '.$this->dbAddQuotes( $mwUserId ).' LIMIT 1' );
		$q = $qr->fetchRow();
		$qr->free();
		return ( NULL == $q ? NULL : (int) $q['id'] );
	}

	public function userGetPicklistByActive( $excluded = NULL )
	{
		$query = 'SELECT u.id, u.realname, u.username FROM '.self::TableUsers.' AS u '.
			'LEFT JOIN '.self::TableGroups.' AS g ON u.group_id = g.id WHERE g.is_inactive = 0 ';
		if( is_array( $excluded ) && count( $excluded ) > 0 )
			$query .= 'AND u.id NOT IN ( '
				.implode( ', ', array_map( array( $this, 'dbAddQuotes' ), $excluded ) )
				.' ) ';
		$query .= 'ORDER BY u.realname';

		$qr = $this->db->query( $query );

		$rows = array();
		while( NULL != ( $q = $qr->fetchRow() ) )
			$rows[$q['id']] = array( 'realname' => $q['realname'], 'username' => $q['username'] );
		$qr->free();

		return $rows;
	}

	public function userGetPicklistByGroup( $groupId )
	{
		$qr = $this->db->query( 'SELECT id, realname FROM '.self::TableUsers.' WHERE group_id = '.$this->dbAddQuotes( $groupId ).' ORDER BY realname' );

		$rows = array();
		while( NULL != ( $q = $qr->fetchRow() ) )
			$rows[$q['id']] = array( 'realname' => $q['realname'] );
		$qr->free();

		return $rows;
	}

	public function userGetTeamAdminByMediaWikiUserId( $mwUserId )
	{
		$qr = $this->db->query( 'SELECT team_id FROM '.self::TableUsers.' WHERE mediawiki_user_id = '.$this->dbAddQuotes( $mwUserId ).' AND is_team_admin = 1 LIMIT 1' );
		return $this->dbGetValue( $qr, 'team_id' );
	}

	public function userGetUsernameById( $userId )
    {
         $qr = $this->db->query( $this->dbStrF( 'SELECT username FROM {X0} WHERE id = {1} LIMIT 1', self::TableUsers, $userId ) );
         return $this->dbGetValue( $qr, 'username' );
    }

	public function userGetWorkSummaryByGroupId( $groupId, $year )
	{
		$qr = $this->db->query( $this->dbStrF( 'SELECT u.id, u.realname, MONTH(e.starts_at) AS month, COUNT(wrw.report_id) AS count '
			.'FROM {X0} AS wrw '
			.'LEFT JOIN {X1} AS u ON wrw.user_id = u.id AND wrw.multi > 0 '
			.'LEFT JOIN {X2} AS wr ON wrw.report_id = wr.id '
			.'LEFT JOIN {X3} AS e ON wr.event_id = e.id '
			.'WHERE u.group_id = {4} AND YEAR(e.starts_at) = {5} AND e.type = {6} '
			.'GROUP BY u.id, MONTH(e.starts_at) '
			.'ORDER BY u.realname',
			self::TableWorkReportsWorkers, self::TableUsers, self::TableWorkReports, self::TableEvents, $groupId, $year, self::EventRegular ) );

		$result = array();
		$thisUser = NULL;
		$thisUserId = 0;
		$thisUserTotal = 0;
		while( NULL != ( $q = $qr->fetchRow() ) )
		{
			if( $thisUserId !== (int) $q['id'] )
			{
				if( $thisUserId > 0 && $thisUserTotal > 0 )
				{
					$thisUser['total_count'] = $thisUserTotal;
					$result[$thisUserId] = $thisUser;
				}

				$thisUserId = (int) $q['id'];
				$thisUserTotal = 0;
				$thisUser = array( 'realname' => $q['realname'], 'months' => array() );
			}

			$month = (int) $q['month'];
			$count = (int) $q['count'];
			$thisUserTotal += $count;
			$events = $this->userGetEventsWorkedByDate( $thisUserId, $year, $month );
			$thisUser['months'][$month] = array( 'count' => $count, 'events' => $events );
		}

		// Last one
		if( $thisUserId > 0 && $thisUserTotal > 0 )
		{
			$thisUser['total_count'] = $thisUserTotal;
			$result[$thisUserId] = $thisUser;
		}

		return $result;
	}

	public function userGetEventsWorkedByDate( $userId, $year, $month )
	{
		$qr = $this->db->query( $this->dbStrF( 'SELECT e.title, DATE(e.starts_at) AS start_date FROM {X0} AS wrw '
			.'LEFT JOIN {X1} AS wr ON wrw.report_id = wr.id '
			.'LEFT JOIN {X2} AS e ON wr.event_id = e.id '
			.'WHERE wrw.user_id = {3} AND wrw.multi > 0 '
			.'AND YEAR( e.starts_at ) = {4} '
			.'AND MONTH( e.starts_at ) = {5} '
			.'ORDER BY e.starts_at',
			self::TableWorkReportsWorkers, self::TableWorkReports, self::TableEvents, $userId, $year, $month ) );

		$result = array();
		while( NULL != ( $q = $qr->fetchRow() ) )
			$result[] = array( 'date' => $q['start_date'], 'title' => $q['title'] );

		$qr->free();
		return $result;
	}

    public function userCreateServiceAuth( $userId )
    {
        // See http://php.net/manual/en/function.crypt.php
        $salt = '$2y$10$'.self::generateRandomString( 22 );
        $key = self::generateRandomString( self::ServiceAuthKeyStrength );
        $hashedKey = crypt( $key, $salt );

        $this->db->query( $this->dbStrF( 'INSERT INTO {X0} ( service_auth, user_id, created ) VALUES ( {1}, {2}, NOW() )',
            self::TableServiceAuth, $hashedKey, $userId ) );

        return $key;
    }

	public function userSave( array $user )
	{
		if( isset( $user['id'] ) )
		{
			// User already exists
			$this->db->query( 'UPDATE '.self::TableUsers.' SET '
				.'realname = '.$this->dbAddQuotes( $user['realname'] ).', '
				.'phone = '.$this->dbAddQuotes( $user['phone'] ).', '
				.'email = '.$this->dbAddQuotes( $user['email'] ).', '
				.'is_admin = '.( $user['is_admin'] ? '1' : '0' ).', '
				.'is_team_admin = '.( $user['is_team_admin'] ? '1' : '0' ).', '
				.'is_hidden = '.( $user['is_hidden'] ? '1' : '0' ).', '
				.'group_id = '.$this->dbAddQuotesOrNull( $user['group_id'] ).', '
				.'title_id = '.$this->dbAddQuotesOrNull( $user['title_id'] ).', '
				.'team_id = '.$this->dbAddQuotesOrNull( $user['team_id'] )
				.' WHERE id = '.$this->dbAddQuotes( $user['id'] ).' LIMIT 1' );
		}
		else
		{
			// New user
			try
			{
				$this->db->query( 'INSERT '.self::TableUsers.' ( username, realname, phone, email, is_admin, is_team_admin, is_hidden, group_id, title_id, team_id ) VALUES ( '
					.$this->dbAddQuotes( $user['username'] ).', '
					.$this->dbAddQuotes( $user['realname'] ).', '
					.$this->dbAddQuotes( $user['phone'] ).', '
					.$this->dbAddQuotes( $user['email'] ).', '
					.( $user['is_admin'] ? '1' : '0' ).', '
					.( $user['is_team_admin'] ? '1' : '0' ).', '
					.( $user['is_hidden'] ? '1' : '0' ).', '
					.$this->dbAddQuotesOrNull( $user['group_id'] ).', '
					.$this->dbAddQuotesOrNull( $user['title_id'] ).', '
					.$this->dbAddQuotesOrNull( $user['team_id'] )
					.' )'
				);

				$user['id'] = $this->db->insertId();
			}
			catch( DBQueryError $ex )
			{
				if( self::DuplicateKey == $ex->errno )
					throw new FatalError( 'Medlemmen du försöker skapa finns redan.' );

				// Any other errors, just rethrow
				throw $ex;
			}
		}

		$this->userSaveProps( $user['id'], $user['props'] );
		return $user['id'];
	}

	public function userSetMediaWikiUserId( $userId, $mwUserId )
	{
		$this->db->query( 'UPDATE '.self::TableUsers.' SET mediawiki_user_id = '
			.$this->db->addQuotes( $mwUserId ).' WHERE id = '.$this->db->addQuotes( $userId ).' LIMIT 1' );
	}

    public function userCleanupServiceAuth( $userId )
    {
        $qr = $this->db->query( $this->dbStrF( 'SELECT COUNT(*) FROM {X0} WHERE user_id = {1}', self::TableServiceAuth, $userId ) );
        $overLimit = $this->dbGetValue( $qr ) - self::ServiceAuthLimit;
        if( $overLimit > 0 )
            $this->db->query( $this->dbStrF( 'DELETE FROM {X0} WHERE user_id = {1} ORDER BY created LIMIT {X2}', self::TableServiceAuth, $userId, $overLimit ) );
    }

    public function userValidateServiceAuth( $username, $serviceAuth )
    {
        $qr = $this->db->query( $this->dbStrF( 'SELECT id FROM {X0} WHERE username = {1} LIMIT 1', self::TableUsers, $username ) );
        if( 0 == ( $userId = (int) $this->dbGetValue( $qr, 'id', 0 ) ) )
            return 0;

        $qr = $this->db->query( $this->dbStrF( 'SELECT service_auth FROM {X0} WHERE user_id = {1} ORDER BY created DESC', self::TableServiceAuth, $userId ) );
        $keyList = $this->dbGetColumn( $qr );

        foreach( $keyList as $key )
            if( $key === crypt( $serviceAuth, $key ) )
                return $userId;

        return 0;
    }

	private function userDeleteProps( $id )
	{
		$this->db->query( 'DELETE FROM '.self::TableProps.' WHERE user_id = '.$this->dbAddQuotes( $id ) );
	}

	private function userGetListByCustomWhereClause( $where )
	{
		$festUsers = $this->userGetUserIdsByProperty( self::PropFlagFest );
		$stadUsers = $this->userGetUserIdsByProperty( self::PropFlagStad );
		$permitUsers = $this->userGetUserIdsByProperty( self::PropFlagPermit );
        $licenseUsers = $this->userGetUserIdsByProperty( self::PropFlagDriversLicense );

		$query = 'SELECT id, username, realname, phone, email, is_admin, is_team_admin, team_id, group_id, title_id FROM '.self::TableUsers.' ';
		if( NULL != $where )
			$query .= 'WHERE '.$where.' ';
		$query .= 'ORDER BY realname';

		$qr = $this->db->query( $query );

		$rows = array();
		while( NULL != ( $q = $qr->fetchRow() ) )
			$rows[$q['id']] = array(
				'username' 		=> $q['username'],
				'realname' 		=> $q['realname'],
				'phone' 		=> $q['phone'],
				'email' 		=> $q['email'],
				'is_admin'		=> (bool) $q['is_admin'],
				'is_team_admin'	=> (bool) $q['is_team_admin'],
				'group_id'		=> (int) $q['group_id'],
				'team_id'		=> (int) $q['team_id'],
				'title_id'      => (int) $q['title_id'],
				'has_fest'		=> in_array( $q['id'], $festUsers ),
				'has_stad'		=> in_array( $q['id'], $stadUsers ),
				'has_permit'	=> in_array( $q['id'], $permitUsers ),
                'has_license'   => in_array( $q['id'], $licenseUsers )
			);
		$qr->free();

		return $rows;
	}

	public function userGetPropsById( $id )
	{
		$qr = $this->db->query( 'SELECT prop_id, prop_val_int, prop_val_str FROM '.self::TableProps.' WHERE '
				.'user_id = '.$this->dbAddQuotes( $id ) );

		$props = array();
		while( NULL != ( $q = $qr->fetchRow() ) )
			self::userAddProp( $props, $q['prop_id'], $q['prop_val_int'], $q['prop_val_str'] );

		$qr->free();
		return $props;
	}

	private function userGetUserIdsByProperty( $propId )
	{
		$qr = $this->db->query( 'SELECT user_id FROM '.self::TableProps.' WHERE prop_id = '.$this->dbAddQuotes( $propId ) );

		$rows = array();
		while( NULL != ( $q = $qr->fetchRow() ) )
			$rows[] = $q['user_id'];
		$qr->free();

		return $rows;
	}

	private function userSaveProps( $id, $props )
	{
		$this->userDeleteProps( $id );
		if( !is_array( $props ) || count( $props ) == 0 )
			return;

		$query = 'INSERT INTO '.self::TableProps.' ( user_id, prop_id, prop_val_int, prop_val_str ) VALUES ';
		$queryParts = array();
		foreach( $props as $propId => $values )
			foreach( $values as $value )
				$queryParts[] = '( '.$this->dbMakeQuotedArray( array( $id, $propId, $value[0], $value[1] ) ).' )';

		$query .= implode( $queryParts, ', ' );
		$this->db->query( $query );
	}

	public static function userAddProp( array &$props, $propId, $valueInt, $valueStr = '' )
	{
		$toAdd = array( (int) $valueInt, $valueStr );

		if( isset( $props[$propId] ) )
		{
			$current = $props[$propId];
			$current[] = $toAdd;
		}
		else
			$current = array( $toAdd );

		$props[$propId] = $current;
	}

	public static function userSetProp( array &$props, $propId, $valueInt, $valueStr = '' )
	{
		$toSet = array( (int) $valueInt, $valueStr );
		$props[$propId] = array( $toSet );
	}

	public static function userGetPropBool( array &$props, $propId, $default = false )
	{
		return (bool) self::userGetPropInt( $props, $propId, $default ? 1 : 0 );
	}

	public static function userGetPropInt( array &$props, $propId, $default = 0 )
	{
		if( !isset( $props[$propId] ) )
			return $default;

		return $props[$propId][0][0];
	}

	public static function userGetPropString( array &$props, $propId, $default = '' )
	{
		if( !isset( $props[$propId] ) )
			return $default;

		return $props[$propId][0][1];
	}

	public static function userGetPropStringArray( array &$props, $propId )
	{
		if( !isset( $props[$propId] ) )
			return array();

		$array = $props[$propId];
        $sortFun = function( $x, $y ) { return $x[0] - $y[0]; };
		usort( $array, $sortFun );
        $mapFun = function( $x ) { return $x[1]; };
		return array_map( $mapFun, $array );
	}
}
