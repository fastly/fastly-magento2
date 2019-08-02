define([
    "jquery",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    "showSuccessMessage",
    "Magento_Ui/js/modal/confirm",
    'uploadVcl',
    'mage/translate'
], function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage, showSuccessMessage, confirm, uploadVcl) {
    return function (config, serviceStatus, isAlreadyConfigured) {

        let active_version = serviceStatus.active_version;
        let versions_response = [];    //response from config.versionHistory
        let number_of_pages;    //number that says how many pages are filled with data
        let versionBtnErrorMsg = $("#fastly-error-versions-button-msg");
        let versions_per_page = 10;
        let pagination_switch = false;

        /**
         * ACL container modal overlay options
         *
         * @type {{title: *, content: (function(): string), actionOk: actionOk}}
         */
        let versionContainerOptions = {

            title: jQuery.mage.__('List of versions'),
            content: function () {
                return document.getElementById('fastly-version-history-template').textContent;
            }
        };

        let showVCLOptions = {
            title: jQuery.mage.__('Generated VCL'),
            content: function () {
                return document.getElementById('show-VCL-container').textContent;
            }
        };

        /**
         * displays Active version: above list and renders VCL container on click
         */
        function displayActiveVersion()
        {
            $("#pagination-active-version").empty();
            let a = document.createElement('a');
            let span = document.createElement('span');
            let text = document.createTextNode('Active Version: ');
            let version = document.createTextNode(active_version);
            a.setAttribute('data-version-number', active_version);
            a.append(version);
            span.append(text);
            span.append(a);
            a.on('click', function () {
                resetAllMessages();
                let version_number = $(this).data('version-number');
                overlay(showVCLOptions);
                $('.upload-button').remove();
                insertVCLContent(version_number);
            });
            $("#pagination-active-version").append(span);
        }

        /**
         * method that returns start and end of the array that holds all the service versions
         * @param perPage
         * @returns {{start: number, end: *}}
         */
        function arraySlice(perPage)
        {
            let input = parseInt($("#pagination-input").val());
            let start = versions_response.number_of_versions - (input * perPage);
            let end = start + perPage;
            return {
                'start': start,
                'end': end
            };
        }

        function insertVCLContent(version)
        {
            $.ajax({
                type: 'GET',
                url: config.versionReference,
                showLoader: true,
                data: {'version': version},
                success: function (response) {
                    if (response.status !== true) {
                        modal.modal('closeModal');
                        showErrorMessage(response.msg);
                        return;
                    }

                    let text = document.createTextNode(response.content);
                    $("#version-vcl-container").append(text);
                }
            });
        }

        /**
         * logic that handles paging in pagination nav bar
         */
        function paginationLogic()
        {

            $('body').on('click', 'button.action-next', function () {
                resetAllMessages();
                let pagination_input = $("#pagination-input").val();
                $("#pagination-input").val(++pagination_input);
                handleInputNumber();
                let properties = arraySlice(versions_per_page);
                processVersions(versions_response.versions.slice(properties.start, properties.end));
            });

            //handle next button
            $('body').on('click', 'button.action-previous', function () {
                resetAllMessages();
                let pagination_input = $("#pagination-input").val();
                $("#pagination-input").val(--pagination_input);
                handleInputNumber();
                let properties = arraySlice(versions_per_page);
                processVersions(versions_response.versions.slice(properties.start, properties.end));

            });

            //handle pressing enter
            $('body').on('keypress', function (e) {
                if (e.which != 13) {
                    return;
                }
                resetAllMessages();
                handleInputNumber();
                let properties = arraySlice(versions_per_page);
                processVersions(versions_response.versions.slice(properties.start, properties.end));
            });

            function handleInputNumber()
            {
                //handle higher/lower then min and max input
                if ($("#pagination-input").val() > number_of_pages) {
                    $("#pagination-input").val(number_of_pages)
                } else if ($("#pagination-input").val() < 1) {
                    $("#pagination-input").val(1)
                }

                //handle equal as min and max
                if (parseInt($("#pagination-input").val()) === 1) {
                    $(".action-previous").attr('disabled', 'disabled');
                } else if (parseInt($("#pagination-input").val()) === number_of_pages) {
                    $(".action-next").attr('disabled', 'disabled');
                }

                //handle disabled attributes on prev/next button
                if ($("#pagination-input").val() > 1 && $(".action-previous").attr('disabled') === 'disabled') {
                    $(".action-previous").removeAttr('disabled');
                } else if ($("#pagination-input").val() < number_of_pages && $(".action-next").attr('disabled') === 'disabled') {
                    $(".action-next").removeAttr('disabled');
                }
            }
        }

        /**
         * Queries Fastly API to retrieve the list of Fastly versions
         * - set api response into versions_response variable
         *
         * @param active_version
         * @param loaderVisibility
         */
        function catchVersions(active_version, loaderVisibility)
        {

            $.ajax({
                type: "GET",
                url: config.versionHistory,
                showLoader: loaderVisibility,
                data: {'active_version': active_version},
                success: function (response) {
                    $('.loading-versions').hide();
                    if (response.status !== false) {
                        versions_response = response;
                        number_of_pages = response.number_of_pages;
                        let properties = arraySlice(versions_per_page);
                        $(".admin__control-support-text").append(document.createTextNode(number_of_pages));
                        processVersions(response.versions.slice(properties.start, properties.end));
                        if (!pagination_switch) {
                            paginationLogic();
                            pagination_switch = true;
                        }

                        return;
                    }

                    showErrorMessage(response.msg);
                },
                beforeSend: function () {
                    $('.loading-versions').show();
                }
            });
        }

        /**
         * Activate specific version
         *
         * @param active_version_param
         * @param version
         * @param loaderVisibility
         */
        function activateServiceVersion(active_version_param, version, loaderVisibility)
        {
            resetAllMessages();
            $.ajax({
                type: 'GET',
                url: config.activateVersion,
                showLoader: loaderVisibility,
                data: {'version': version, 'active_version': active_version_param},
                success: function (response) {
                    if (!response.status) {
                        showErrorMessage(response.msg);
                        return;
                    }
                    let text = document.createTextNode('Activated');
                    let span = document.createElement('span');
                    let button = document.createElement('button');
                    button.setAttribute('class', 'fastly-activate-vcl-action activate-action');
                    button.setAttribute('id', 'action_version_' + response.old_version);
                    button.setAttribute('title', 'Activate');
                    button.setAttribute('data-version-number', response.old_version);
                    button.setAttribute('style', 'margin-right: 2rem;');
                    span.setAttribute('id', 'action_version_' + response.version);
                    span.setAttribute('data-version-number', response.version);
                    span.setAttribute('style', 'margin-right: 2rem;');
                    span.appendChild(text);
                    $("#action_activate_version_" + response.old_version).empty();
                    $("#action_activate_version_" + response.old_version).append(button);
                    $("#action_activate_version_" + response.version).empty();
                    $("#action_activate_version_" + response.version).append(span);
                    active_version = version;
                    serviceStatus.active_version = active_version;
                    uploadVcl(config, serviceStatus, isAlreadyConfigured);
                    displayActiveVersion();
                    showSuccessMessage('Successfully activated version ' + response.version);
                }
            });
        }

        /**
         * Process and display the list of versions
         *
         * @param versions, list of versions that will be displayed
         */
        function processVersions(versions)
        {
            $(".item-container").empty();

            $.each(versions, function (index, version) {
                let tr = document.createElement('tr');
                let versionCell = document.createElement('td');
                let commentCell = document.createElement('td');
                let updatedCell = document.createElement('td');
                let actionCell = document.createElement('td');
                let span = document.createElement('span');

                let showVCLButton = document.createElement('button');
                showVCLButton.setAttribute('class', 'fastly-view-vcl-action show-vcl-action');
                showVCLButton.setAttribute('id', 'action_show_VCL_' + version.number);
                showVCLButton.setAttribute('title', 'Show VCL');
                showVCLButton.setAttribute('data-version-number', version.number);
                span.setAttribute('id', 'action_activate_version_' + version.number);

                let button = '';
                let versionText = document.createTextNode(version.number);
                let commentText = document.createTextNode(version.comment);
                let updatedText = document.createTextNode(version.updated_at);
                actionCell.setAttribute('id', 'action_cell_' + version.number);
                if (active_version !== version.number) {
                    button = document.createElement('button');
                    button.setAttribute('class', 'fastly-activate-vcl-action activate-action');
                    button.setAttribute('id', 'action_version_' + version.number);
                    button.setAttribute('title', 'Activate');
                    button.setAttribute('data-version-number', version.number);
                    button.setAttribute('style', 'margin-right: 2rem;');
                } else {
                    let text = document.createTextNode('Activated');
                    button = document.createElement('span');
                    button.setAttribute('id', 'action_version_' + version.number);
                    button.setAttribute('data-version-number', version.number);
                    button.setAttribute('style', 'margin-right: 2rem;');
                    button.appendChild(text);
                }
                span.appendChild(button);
                versionCell.appendChild(versionText);
                commentCell.appendChild(commentText);
                updatedCell.appendChild(updatedText);
                actionCell.appendChild(span);
                actionCell.appendChild(showVCLButton);
                tr.setAttribute('id', 'fastly_version_' + version.number);
                tr.append(versionCell);
                tr.append(commentCell);
                tr.append(updatedCell);
                tr.append(actionCell);

                $('.item-container').prepend(tr);
            });
        }

        /**
         * Version container list on click event
         */
        $('#list-versions-container-button').on('click', function () {

            if (isAlreadyConfigured !== true) {
                $(this).attr('disabled', true);
                return alert($.mage.__('Please save config prior to continuing.'));
            }

            resetAllMessages();

            $.when(
                $.ajax({
                    type: "GET",
                    url: config.serviceInfoUrl,
                    showLoader: true
                })
            ).done(function (service) {
                if (service.status !== true) {
                    return versionBtnErrorMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                let next_version = service.next_version;
                let service_name = service.service.name;

                overlay(versionContainerOptions);

                $('.upload-button').remove();
                $("#pagination-input").val(1);
                $(".action-previous").attr('disabled', 'disabled');
                displayActiveVersion();

                setServiceLabel(active_version, next_version, service_name);
                catchVersions(active_version, true);

            }).fail(function () {
                return versionBtnErrorMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        /**
         * Activate version
         */
        $('body').on('click', 'button.activate-action', function () {
            resetAllMessages();
            let version_number = $(this).data('version-number');
            confirm({
                title: 'Activate Version',
                content: 'Are you sure you want to activate the version #<b>' + version_number + '</b> ?',
                actions: {
                    confirm: function () {
                        activateServiceVersion(active_version, version_number, true);
                    },
                    cancel: function () {
                    }
                }
            });
        });

        /**
         * Show versions's VCL
         */
        $('body').on('click', 'button.show-vcl-action', function () {
            resetAllMessages();
            let version_number = $(this).data('version-number');
            overlay(showVCLOptions);
            $('.upload-button').remove();
            insertVCLContent(version_number);
        });
    }
});
