window.tmeit = {};

window.tmeit.openEvent = function( id ) {
    var currentEvent = $('#tmeit-event-current');
    currentEvent.removeClass('loaded')
        .html('<img src="/w/skins/tmeit/wait_icon.gif" alt="Laddar ..." />');
    currentEvent.load('/w/tmeit-jobs/AjaxEvent.php?id=' + id, function() { currentEvent.addClass('loaded'); });
};

$(document).ready(
    function() {
        var firstEventId = mw.config.get( 'firstEventId' );
        tmeit.openEvent( firstEventId );
    }
);
