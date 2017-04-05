define([
    'jquery',
    'mage/translate'
], function($) {
    "use strict";

    return function (config) {
        $(document).ready(function () {

            // From picker init
            $('#from-picker').datetimepicker( { dateFormat: "dd/mm/yy" } );
            $(".ui-datepicker-trigger").removeAttr("style");
            $(".ui-datepicker-trigger").click(function(){
                $('#from-picker').focus();
            });

            // To picker init
            $('#to-picker').datetimepicker( { dateFormat: "dd/mm/yy" } );
            $(".ui-datepicker-trigger").removeAttr("style");
            $(".ui-datepicker-trigger").click(function(){
                $('#to-picker').focus();
            });
        });

        $('#apply-historic-stats').on('click', function () {
            var from = $('#from-picker').val();
            var to = $('#to-picker').val();

            console.log(from);
            console.log(to);

            $.ajax({
                type: "POST",
                url: config.applyHistoricStatsURL,
                data: {
                    'from': from,
                    'to': to
                },
                beforeSend: function (xhr) {

                }
            }).done(function (response) {
                console.log(response);
            }).fail(function () {

            });
        });
    }
});
