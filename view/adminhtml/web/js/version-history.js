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

        /**
         * Queries Fastly API to retrieve the list of Fastly versions
         *
         * @param active_version
         * @param loaderVisibility
         * @returns array
         */
        function listVersions(active_version, loaderVisibility)
        {
            return $.ajax({
                type: "GET",
                url: config.versionHistory,
                showLoader: loaderVisibility,
                data: {'active_version': active_version},
                success: function(response){

                    $('.loading-versions').hide();
                    if (response.status !== false) {
                        processVersions(response.versions);
                    }

                },
                fail: function(){
                    return versionBtnErrorMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
                },
                beforeSend: function () {
                    $('.loading-versions').show();
                }
            });
        }

        /**
         * Process and display the list of ACL containers
         *
         * @param versions
         */
        function processVersions(versions)
        {
            let html = '';
            $.each(versions, function (index, version) {
                html += "<tr id='fastly_version_" + index + "'>";
                html +=     "<td><input data-versionId='"+ version.number + "' id='version_" + index + "' value='"+ version.number +"' disabled='disabled' class='input-text' type='text'></td>";
                html +=     "<td><input data-versionId='"+ version.number + "' id='version_" + index + "' value='"+ version.comment +"' disabled='disabled' class='input-text' type='text'></td>";
                html +=     "<td><input data-versionId='"+ version.number + "' id='version_" + index + "' value='"+ version.updated_at +"' disabled='disabled' class='input-text' type='text'></td>";
                html += "</tr>";
            });
            if (html !== '') {
                $('.no-versions').hide();
            }
            $('.item-container').html(html);
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
                setServiceLabel(active_version, next_version, service_name);
                listVersions(active_version, true);
            }).fail(function () {
                return versionBtnErrorMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
            });
        });
    }
});