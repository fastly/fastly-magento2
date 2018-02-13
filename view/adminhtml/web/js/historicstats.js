define([
    'jquery',
    'mage/translate',
    'https://www.gstatic.com/charts/loader.js',
    'moment'
], function ($, t, g, moment) {
    "use strict";

    return function (config) {

        var requests = [];
        var bandwith = [];
        var hitRatio = [];
        var errors = [];
        var fromPicker = $('#from-picker')
        var toPicker = $('#to-picker');

        $(document).ready(function () {

            $('#Fastly').removeClass('ui-tabs-panel');
            // From picker init
            fromPicker.datetimepicker({dateFormat: "mm/dd/yy"});
            $(".ui-datepicker-trigger").removeAttr("style");
            $(".ui-datepicker-trigger").click(function () {
                fromPicker.focus();
            });

            // To picker init
            toPicker.datetimepicker({dateFormat: "mm/dd/yy"});
            $(".ui-datepicker-trigger").removeAttr("style");
            $(".ui-datepicker-trigger").click(function () {
                toPicker.focus();
            });

            /* Default picker values */
            var from = moment().utc().subtract(7, 'days').format('M/D/YYYY h:mm');
            var to = moment().utc().format('M/D/YYYY h:mm');
            fromPicker.val(from);
            toPicker.val(to);

            /* Init charts */
            $.ajax({
                type: "GET",
                url: config.serviceInfoUrl,
                showLoader: true,
            }).done(function (checkService) {
                if (checkService.status != false) {
                    applyCharts();
                } else {
                    $('#apply-historic-stats').prop("disabled", "disabled");
                    $('.charts').html('<h3 class="chart-title">Please check your Service ID and API token and try again.</h3>').show();
                }
            }).fail(function () {

            });

            $('.graph-options').on('change', function () {
                var option = $(this).find(":selected").val();
                var optionName = $(this).attr("name");
                $(this).parent().find('span').hide();
                $('#' +optionName+ '-number-' + option).show();
            });
            /* Last hour */
            $('#last-hour').on('click', function (e) {
                e.preventDefault();
                var from = moment().utc().subtract(1, 'hours').format('M/D/YYYY h:mm');
                var to = moment().utc().format('M/D/YYYY h:mm');
                fromPicker.val(from);
                toPicker.val(to);
            });
            /* Last 2 hours */
            $('#last-2hr').on('click', function (e) {
                e.preventDefault();
                var from = moment().utc().subtract(2, 'hours').format('M/D/YYYY h:mm');
                var to = moment().utc().format('M/D/YYYY h:mm');
                fromPicker.val(from);
                toPicker.val(to);
            });

            $('#last-4hr').on('click', function (e) {
                e.preventDefault();
                var from = moment().utc().subtract(4, 'hours').format('M/D/YYYY h:mm');
                var to = moment().utc().format('M/D/YYYY h:mm');
                fromPicker.val(from);
                toPicker.val(to);
            });

            $('#last-8hr').on('click', function (e) {
                e.preventDefault();
                var from = moment().utc().subtract(8, 'hours').format('M/D/YYYY h:mm');
                var to = moment().utc().format('M/D/YYYY h:mm');
                fromPicker.val(from);
                toPicker.val(to);
            });

            $('#last-day').on('click', function (e) {
                e.preventDefault();
                var from = moment().utc().subtract(1, 'days').format('M/D/YYYY h:mm');
                var to = moment().utc().format('M/D/YYYY h:mm');
                fromPicker.val(from);
                toPicker.val(to);
            });

            $('#last-week').on('click', function (e) {
                e.preventDefault();
                var from = moment().utc().subtract(7, 'days').format('M/D/YYYY h:mm');
                var to = moment().utc().format('M/D/YYYY h:mm');
                fromPicker.val(from);
                toPicker.val(to);
            });

            $('#last-month').on('click', function (e) {
                e.preventDefault();
                var from = moment().utc().subtract(1, 'months').format('M/D/YYYY h:mm');
                var to = moment().utc().format('M/D/YYYY h:mm');
                fromPicker.val(from);
                toPicker.val(to);
            });

            $('#last-year').on('click', function (e) {
                e.preventDefault();
                var from = moment().utc().subtract(1, 'years').format('M/D/YYYY h:mm');
                var to = moment().utc().format('M/D/YYYY h:mm');
                fromPicker.val(from);
                toPicker.val(to);
            });
        });

        $('#apply-historic-stats').on('click', function () {
            applyCharts();
        });

        function applyCharts()
        {
            var from = fromPicker.val();
            var to = toPicker.val();
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
                    if (response.stats.data.length > 0) {
                        var data = response.stats.data;
                        /* Empty charts arrays */
                        requests = [];
                        bandwith = [];
                        hitRatio = [];
                        errors = [];
                        var averageBandwidth = 0;
                        var minimumBandwidth = 0;

                        var averageRequests = 0;
                        var minimumRequests = 0;

                        var averageHitRatio = 0;
                        var minimumHitRatio = 0;
                        var maximumHitRatio = 0;

                        var averageError503 = 0;
                        var minimumError503 = 0;

                        /* Parse Fastly Historic stats */
                        $.each(data, function (key, value) {
                            var d = new Date();
                            d.setTime(value.start_time*1000);
                            /* Requests */
                            requests.push([d, value.requests]);
                            averageRequests += value.requests;
                            averageBandwidth += value.bandwidth;
                            averageError503 += value.status_503;

                            if (value.miss != 0 && value.hits != 0) {
                                averageHitRatio += (value.hits / (value.hits + value.miss)) * 100;
                            }

                            if (key == 0) {
                                minimumRequests = value.requests;
                                minimumBandwidth = value.bandwidth;
                                var initHitRatio = (value.hits / (value.hits + value.miss)) * 100;
                                minimumHitRatio = initHitRatio;
                                maximumHitRatio = initHitRatio;
                                minimumError503 = 0;
                            }

                            if (minimumRequests > value.requests) {
                                minimumRequests = value.requests;
                            }

                            if (minimumBandwidth > value.bandwidth) {
                                minimumBandwidth = value.bandwidth;
                            }

                            if (minimumHitRatio > (value.hits / (value.hits + value.miss)) * 100) {
                                minimumHitRatio = (value.hits / (value.hits + value.miss)) * 100;
                            }

                            if (maximumHitRatio < (value.hits / (value.hits + value.miss)) * 100) {
                                maximumHitRatio = (value.hits / (value.hits + value.miss)) * 100;
                            }

                            if (minimumError503 > value.status_503) {
                                minimumError503 = value.status_503;
                            }

                            /* Bandwidth */
                            bandwith.push([d, value.bandwidth]);
                            /* Hit / Miss ratio */
                            var ratio = (value.hits / (value.hits + value.miss)) * 100;
                            hitRatio.push([d, ratio]);
                            /* 500s errors */
                            errors.push([d, value.status_5xx, value.status_503]);
                        });

                        /* Bandwith stats */
                        $('#bandwidth-number-total').html(formatBytes(averageBandwidth, 1));
                        averageBandwidth = averageBandwidth / data.length;
                        averageBandwidth = round(averageBandwidth, 2);
                        $('#bandwidth-number-average').html(formatBytes(averageBandwidth, 1));
                        $('#bandwidth-number-minimum').html(formatBytes(minimumBandwidth, 1));

                        /* Requests stats */
                        var totalRequests = nFormatter(averageRequests, 1);
                        $('#requests-number-total').html(totalRequests);
                        averageRequests = averageRequests / data.length;
                        averageRequests = round(averageRequests, 2);
                        averageRequests = nFormatter(averageRequests, 1);
                        $('#requests-number-average').html(averageRequests);
                        $('#requests-number-minimum').html(nFormatter(minimumRequests, 1));

                        /* HitRatio stats*/
                        averageHitRatio = averageHitRatio / data.length;
                        averageHitRatio = round(averageHitRatio, 2);
                        $('#hitratio-number-average').html(averageHitRatio + '%');
                        $('#hitratio-number-minimum').html(minimumHitRatio + '%');
                        $('#hitratio-number-maximum').html(round(maximumHitRatio, 2) + '%');

                        /* Requests stats */
                        $('#errors-number-total').html(averageError503);
                        averageError503 = averageError503 / data.length;
                        averageError503 = round(averageError503, 2);
                        $('#errors-number-average').html(averageError503);
                        $('#errors-number-minimum').html(minimumError503);

                        google.charts.load('current', {'packages':['corechart']});
                        google.charts.setOnLoadCallback(requestsChart);
                        google.charts.setOnLoadCallback(bandwithChart);
                        google.charts.setOnLoadCallback(hitRatioChart);
                        google.charts.setOnLoadCallback(errorsChart);

                        $('.charts').show();
                    } else {
                        $('.charts').hide();
                        $('.no-data').show();
                    }
                }
            }.bind(this)).fail(function () {

            });
        }

        function errorsChart()
        {
            var data = new google.visualization.DataTable();
            data.addColumn('datetime', 'Date');
            data.addColumn('number', '5xx');
            data.addColumn('number', '503');
            data.addRows(errors);

            var options = {
                hAxis:{
                    format: 'MMMM d',
                    gridlines: {color: 'transparent'}
                },
                vAxis:{
                    gridlines: {color: 'transparent'},
                    format: 'short'
                }};
            var chart = new google.visualization.AreaChart(document.getElementById('errorschart'));
            chart.draw(data,options);
        }

        function hitRatioChart()
        {
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
                    gridlines: {color: 'transparent'},
                    format: '#\'%\''
                }};

            var formatter = new google.visualization.NumberFormat({suffix: '%'});
            formatter.format(data, 1);
            var chart = new google.visualization.AreaChart(document.getElementById('hitratio'));
            chart.draw(data,options);

        }

        function bandwithChart()
        {
            var data = new google.visualization.DataTable();
            data.addColumn('datetime', 'Date');
            data.addColumn('number', 'Bandwith');
            data.addRows(bandwith);

            // custom format data values
            for (var i = 0; i < data.getNumberOfRows(); i++) {
                var val = data.getValue(i, 1);
                var formattedVal = formatBytes(val, 0);
                data.setFormattedValue(i, 1, formattedVal);
            }
            var options = {
                colors: ['#F17C09'],
                hAxis:{
                    format: 'MMMM d',
                    gridlines: {color: 'transparent'}
                }
            };
            var chart = new google.visualization.AreaChart(document.getElementById('bandwith'));
            // get the axis values and reformat them
            var runOnce = google.visualization.events.addListener(chart, 'ready', function () {
                google.visualization.events.removeListener(runOnce);
                var bb, val, formattedVal, suffix, ticks = [], cli = chart.getChartLayoutInterface();
                for (var i = 0; bb = cli.getBoundingBox('vAxis#0#gridline#' + i); i++) {
                    val = cli.getVAxisValue(bb.top);
                    // sometimes, the axis value falls 1/2 way though the pixel height of the gridline,
                    // so we need to add in 1/2 the height
                    // this assumes that all axis values will be integers
                    if (val != parseInt(val)) {
                        val = cli.getVAxisValue(bb.top + bb.height / 2);
                    }
                    console.log(val);
                    formattedVal = formatBytes(val, 0, true);

                    ticks.push({v: val, f: formattedVal});
                }
                options.vAxis = options.vAxis || {gridlines: {color: 'transparent'}};
                options.vAxis.ticks = ticks;
                chart.draw(data, options);
            });

            chart.draw(data,options);

        }

        function requestsChart()
        {
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
                    gridlines: {color: 'transparent'},
                    format: 'short'
                },
                animation:{
                    duration: 1000,
                    easing: 'out'
                }
            };
            var chart = new google.visualization.AreaChart(document.getElementById('requests'));
            chart.draw(data,options);
        }

        function round(value, exp)
        {
            if (typeof exp === 'undefined' || +exp === 0) {
                return Math.round(value);
            }

            value = +value;
            exp = +exp;

            if (isNaN(value) || !(typeof exp === 'number' && exp % 1 === 0)) {
                return NaN;
            }

            // Shift
            value = value.toString().split('e');
            value = Math.round(+(value[0] + 'e' + (value[1] ? (+value[1] + exp) : exp)));

            // Shift back
            value = value.toString().split('e');
            return +(value[0] + 'e' + (value[1] ? (+value[1] - exp) : -exp));
        }

        function formatBytes(bytes, decimals, roundValue)
        {
            if (bytes == 0) {
                return '0 Bytes';
            }
            var k = 1000,
                dm = decimals + 1 || 3,
                sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'],
                i = Math.floor(Math.log(bytes) / Math.log(k));
            if (typeof roundValue == "undefined") {
                return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
            } else {
                var result = parseFloat((bytes / Math.pow(k, i)).toFixed(dm));
                result = round(result, 0);
                return result + ' ' + sizes[i];
            }
        }

        function nFormatter(num, digits)
        {
            var si = [
                { value: 1E18, symbol: "E" },
                { value: 1E15, symbol: "P" },
                { value: 1E12, symbol: "T" },
                { value: 1E9,  symbol: "G" },
                { value: 1E6,  symbol: "M" },
                { value: 1E3,  symbol: "k" }
            ], rx = /\.0+$|(\.[0-9]*[1-9])0+$/, i;
            for (i = 0; i < si.length; i++) {
                if (num >= si[i].value) {
                    return (num / si[i].value).toFixed(digits).replace(rx, "$1") + si[i].symbol;
                }
            }
            return num.toFixed(digits).replace(rx, "$1");
        }
    }
});
