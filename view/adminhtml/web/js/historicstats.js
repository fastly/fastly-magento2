define([
    'jquery',
    'mage/translate',
    'https://www.gstatic.com/charts/loader.js'
], function($) {
    "use strict";

    return function (config) {

        var requests = [];
        var bandwith = [];
        var hitRatio = [];

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
            var sample_rate = $('#sample-rate').val();
            var region = $('#region').val();

            $.ajax({
                type: "POST",
                url: config.applyHistoricStatsURL,
                showLoader: true,
                data: {
                    'from': from,
                    'to': to,
                    'sample_rate': sample_rate,
                    'region': region
                },
                beforeSend: function (xhr) {

                }
            }).done(function (response) {
                if (response.status == true) {
                    var data = response.stats.data;
                    /* Empty charts arrays */
                    requests = [];
                    bandwith = [];
                    hitRatio = [];
                    /* Parse Fastly Historic stats */
                    $.each(data, function (key, value) {
                        var d = new Date();
                        d.setTime(value.start_time*1000);
                        requests.push([d, value.requests]);
                        bandwith.push([d, value.bandwidth]);
                        var ratio = (value.hits / (value.hits + value.miss)) * 100;
                        hitRatio.push([d, ratio]);
                    });

                    google.charts.load('current', {'packages':['corechart']});
                    google.charts.setOnLoadCallback(requestsChart);
                    google.charts.setOnLoadCallback(bandwithChart);
                    google.charts.setOnLoadCallback(hitRatioChart);

                    $('.charts').show();
                }
            }.bind(this)).fail(function () {

            });
        });

        function hitRatioChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('datetime', 'Date');
            data.addColumn('number', 'Ratio');
            data.addRows(hitRatio);

            var options = {
                hAxis:{
                    format: 'MMMM d',
                    gridlines: {color: 'transparent'}
                },
            vAxis:{
                gridlines: {color: 'transparent'}
            }};

            var formatter = new google.visualization.NumberFormat({suffix: '%'});
            formatter.format(data, 1);
            var chart = new google.visualization.AreaChart(document.getElementById('hitratio'));
            chart.draw(data,options);

        }

        function bandwithChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('datetime', 'Date');
            data.addColumn('number', 'Bandwith');
            data.addRows(bandwith);

            var options = {
                colors: ['#F17C09'],
                hAxis:{
                    format: 'MMMM d',
                    gridlines: {color: 'transparent'}
                },
                vAxis:{
                    gridlines: {color: 'transparent'}
                }};

            var chart = new google.visualization.AreaChart(document.getElementById('bandwith'));
            chart.draw(data,options);

        }

        function requestsChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('datetime', 'Date');
            data.addColumn('number', 'Requests');
            data.addRows(requests);

            var options = {
                hAxis:{
                    format: 'MMMM d',
                    gridlines: {color: 'transparent'}
                },
                vAxis:{
                    gridlines: {color: 'transparent'}
                }};
            var chart = new google.visualization.AreaChart(document.getElementById('requests'));
            chart.draw(data,options);

        }

        function formatBytes(bytes,decimals) {
            if(bytes == 0) return '0 Bytes';
            var k = 1000,
                dm = decimals + 1 || 3,
                sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'],
                i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }
    }
});
