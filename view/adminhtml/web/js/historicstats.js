define([
    'jquery',
    'mage/translate',
    'https://www.gstatic.com/charts/loader.js'
], function($) {
    "use strict";

    return function (config) {

        var arr = [];

        $(document).ready(function () {
            // From picker init
            $('#from-picker').datetimepicker( { dateFormat: "mm/dd/yy" } );
            $(".ui-datepicker-trigger").removeAttr("style");
            $(".ui-datepicker-trigger").click(function(){
                $('#from-picker').focus();
            });

            // To picker init
            $('#to-picker').datetimepicker( { dateFormat: "mm/dd/yy" } );
            $(".ui-datepicker-trigger").removeAttr("style");
            $(".ui-datepicker-trigger").click(function(){
                $('#to-picker').focus();
            });
        });

        $('#apply-historic-stats').on('click', function () {
            var from = $('#from-picker').val();
            var to = $('#to-picker').val();

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
                if (response.status == true) {
                    var data = response.stats.data;
                    var index = 1;
                    $.each(data, function (key, value) {
                        var d = new Date();
                        d.setTime(value.start_time*1000);
                        arr.push([d, value.requests]);
                        index++;
                    });

                    google.charts.load('current', {'packages':['corechart']});
                    google.charts.setOnLoadCallback(drawChart);
                }
            }.bind(this)).fail(function () {

            });
        });

        function drawChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('datetime', 'Date');
            data.addColumn('number', 'Requests');
            data.addRows(arr);

            var options = {
                hAxis:{
                    format: 'MMMM d',
                    gridlines: {ticks: 6}
                }};
            var chart = new google.visualization.AreaChart(document.getElementById('chart_div'));
            chart.draw(data,options);

        }
    }
});
