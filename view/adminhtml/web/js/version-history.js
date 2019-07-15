define([
    "jquery",
    "setServiceLabel",
    "overlay",
    "resetAllMessages",
    "showErrorMessage",
    "showSuccessMessage",
    "Magento_Ui/js/modal/confirm",
    'mage/translate'
], function ($, setServiceLabel, overlay, resetAllMessages, showErrorMessage, showSuccessMessage, confirm) {
    return function (config, serviceStatus, isAlreadyConfigured) {

        //todo: uredit komentare
        //todo: upitat korisnika je li siguran u prebacivanje nov everzije
        //todo: prečešljat kod i pregledat šta se da uredit
        //todo: stavit da se učitava paginacija s zadnjih 10 elemenata
        let active_version = serviceStatus.active_version;

        let versionBtnErrorMsg = $("#fastly-error-versions-button-msg");

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

        function listOneVersion(version) {
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
         * Queries Fastly API to retrieve the list of Fastly versions
         *
         * @param active_version
         * @param loaderVisibility
         * @returns array
         */
        function listVersions(active_version, loaderVisibility) {
            $.ajax({
                type: "GET",
                url: config.versionHistory,
                showLoader: loaderVisibility,
                data: {'active_version': active_version},
                success: function (response) {
                    $('.loading-versions').hide();
                    if (response.status !== false) {
                        processVersions(response.versions);
                    }

                },
                fail: function () {
                    return versionBtnErrorMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                },
                beforeSend: function () {
                    $('.loading-versions').show();
                }
            });
        }

        function activateServiceVersion(active_version_param, version, loaderVisibility) {
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
                    button.setAttribute('class', 'action-delete fastly-save-action activate-action');
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
                    showSuccessMessage('Successfully activated version ' + response.version);
                }
            });
        }

        /**
         * Process and display the list of ACL containers
         *
         * @param versions
         */
        function processVersions(versions) {
            $.each(versions, function (index, version) {
                let tr = document.createElement('tr');
                let versionCell = document.createElement('td');
                let commentCell = document.createElement('td');
                let updatedCell = document.createElement('td');
                let actionCell = document.createElement('td');
                let span = document.createElement('span');

                let showVCLButton = document.createElement('button');
                showVCLButton.setAttribute('class', 'action-delete fastly-edit-active-modules-icon show-VCL-action');
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
                    button.setAttribute('class', 'action-delete fastly-save-action activate-action');
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

                $('.item-container').append(tr);
            });
        }

        /**
         * Version container list  on click event
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
                if (service.status === false) {
                    return versionBtnErrorMsg.text($.mage.__('Please check your Service ID and API token and try again.')).show();
                }

                active_version = service.active_version;
                let next_version = service.next_version;
                let service_name = service.service.name;

                overlay(versionContainerOptions);
                $('.upload-button').remove();
                setServiceLabel(active_version, next_version, service_name);
                listVersions(active_version, true);
            }).fail(function () {
                return versionBtnErrorMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });

        $('body').on('click', 'button.fastly-save-action', function () {
            resetAllMessages();
            let version_number = $(this).data('version-number');
            confirm({
                title: 'Activate Version',
                content: 'Are you sure you want to activate the version #<b>'+version_number +'</b> ?',
                actions: {
                    confirm: function () {
                        activateServiceVersion(active_version, version_number, true);
                    },
                    cancel: function () {}
                }
            });
        });

        $('body').on('click', 'button.fastly-edit-active-modules-icon', function () {
            resetAllMessages();
            let version_number = $(this).data('version-number');
            overlay(showVCLOptions);
            $('.upload-button').remove();
            listOneVersion(version_number);
        });
    }
});