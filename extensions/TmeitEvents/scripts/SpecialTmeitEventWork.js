var workOpts = mw.config.get( 'workOpts' );

function tmeitHour(hr) {
    if(hr >= 24) {
        hr -= 24;
    }
    return ( hr < 10 ? '0' + hr : hr);
}

function tmeitSelectWorkOption(selectOpt) {
    workOpts.forEach(function(opt) {
        if(opt == selectOpt) {
            $('#tmeit-event-work-radio' + opt).addClass('tmeit-event-work-selected');
            $('#tmeit-event-work-description' + opt).addClass('tmeit-event-work-selected');
        } else {
            $('#tmeit-event-work-radio' + opt).removeClass('tmeit-event-work-selected');
            $('#tmeit-event-work-description' + opt).removeClass('tmeit-event-work-selected');
        }
    });

    if(selectOpt != 0) {
        $('#tmeit-event-work-between').fadeIn();
    } else {
        $('#tmeit-event-work-between').hide();
    }
}

mw.loader.using( ['jquery.ui.slider'], function() {
    var offset = mw.config.get( 'workMinHour' );
    var range = mw.config.get( 'workMaxHour' ) - offset;
    var initMin = mw.config.get( 'workInitMin' );
    var initMax = mw.config.get( 'workInitMax' );

    $('#work-from').val(initMin);
    $('#work-until').val(initMax);
    $('#label-work-from').text(tmeitHour(offset + initMin) + ':00');
    $('#label-work-until').text(tmeitHour(offset + initMax) + ':00');

    $("#tmeit-event-slider").slider({
        range: true,
        min: 0,
        max: range,
        values: [ initMin, initMax ],
        slide: function( e, ui ) {
            var low = ui.values[0], hi = ui.values[1];
            $('#work-from').val(low);
            $('#work-until').val(hi);
            $('#label-work-from').text(tmeitHour(offset + low) + ':00');
            $('#label-work-until').text(tmeitHour(offset + hi) + ':00');
            $('#work-has-range1').prop('checked', true);
        }
    });

    workOpts.forEach(function(opt) {
        $('#working' + opt).change(function() {
            tmeitSelectWorkOption(opt);
        });
    });
});
