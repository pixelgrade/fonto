jQuery(document).ready(function ($) {

    $(".date_picker").datepicker({
        timeFormat: "HH:mm:ss",
        dateFormat: "yy-mm-dd"
    });

    jQuery(".datetime_picker").datetimepicker({
        timeFormat: "hh:mm tt",
        dateFormat: "yy-mm-dd"
    });

    // $("#mytabs .hidden").removeClass('hidden');
    $(".metabox_tabs").tabs();

});