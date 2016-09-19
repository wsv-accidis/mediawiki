$('#tmeit-uploaded-image').imgAreaSelect({
    aspectRatio: '11:12',
    handles: 'corners',
    minWidth: 110,
    minHeight: 120,
    persistent: true,
    show: true,
    x1: 0,
    y1: 0,
    x2: 110,
    y2: 120,

    onSelectEnd: function (img, selection) {
        $('#selection_x').val(selection.x1);
        $('#selection_y').val(selection.y1);
        $('#selection_w').val(selection.width);
        $('#selection_h').val(selection.height);
    }
});
