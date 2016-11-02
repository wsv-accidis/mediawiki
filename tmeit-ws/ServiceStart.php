<?php
/*
 * TMEIT Web Services
 *
 * By Wilhelm Svenselius (wilhelm.svenselius@gmail.com)
 */

chdir( '../' );
require( __DIR__ . '/../includes/WebStart.php' );

global $wgAutoloadClasses;
$dir = dirname( __FILE__ ).'/classes/';

$wgAutoloadClasses['TmeitWsAttendExternalEvent'] = $dir.'TmeitWsAttendExternalEvent.php';
$wgAutoloadClasses['TmeitWsGetEventDetails'] = $dir.'TmeitWsGetEventDetails.php';
$wgAutoloadClasses['TmeitWsGetEventReport'] = $dir.'TmeitWsGetEventReport.php';
$wgAutoloadClasses['TmeitWsGetEvents'] = $dir.'TmeitWsGetEvents.php';
$wgAutoloadClasses['TmeitWsGetExternalEventDetails'] = $dir.'TmeitWsGetExternalEventDetails.php';
$wgAutoloadClasses['TmeitWsGetExternalEvents'] = $dir.'TmeitWsGetExternalEvents.php';
$wgAutoloadClasses['TmeitWsGetNotifications'] = $dir.'TmeitWsGetNotifications.php';
$wgAutoloadClasses['TmeitWsGetMembers'] = $dir.'TmeitWsGetMembers.php';
$wgAutoloadClasses['TmeitWsGetService'] = $dir.'TmeitWsGetService.php';
$wgAutoloadClasses['TmeitWsPostService'] = $dir.'TmeitWsPostService.php';
$wgAutoloadClasses['TmeitWsRegisterGcm'] = $dir.'TmeitWsRegisterGcm.php';
$wgAutoloadClasses['TmeitWsReportEvent'] = $dir.'TmeitWsReportEvent.php';
$wgAutoloadClasses['TmeitWsServiceBase'] = $dir.'TmeitWsServiceBase.php';
$wgAutoloadClasses['TmeitWsUnregisterGcm'] = $dir.'TmeitWsUnregisterGcm.php';
$wgAutoloadClasses['TmeitWsUploadMemberPhoto'] = $dir.'TmeitWsUploadMemberPhoto.php';
$wgAutoloadClasses['TmeitWsValidateAuth'] = $dir.'TmeitWsValidateAuth.php';
$wgAutoloadClasses['TmeitWsWorkEvent'] = $dir.'TmeitWsWorkEvent.php';
