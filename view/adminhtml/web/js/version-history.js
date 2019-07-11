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
         * Trigger ACL container list
         */
        listVersions(active_version, false).done(function (response) {
            $('.loading-versions').hide();
            if (response.status !== false) {
                console.log(response);
                processVersions(response.versions);
            }
        }).fail(function () {
            return versionBtnErrorMsg.text($.mage.__('An error occurred while processing your request. Please try again.')).show();
        });

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
                beforeSend: function () {
                    $('.loading-versions').show();
                }
            });
        }

        /**
         * Process and display the list of ACL containers
         *
         * @param acls
         */
        function processVersions(versions)
        {
            let html = '';
            $.each(versions, function (index, version) {
                html += "<tr id='fastly_version_" + index + "'>";
                html += "<td><input data-versionId='"+ version.number + "' id='version_" + index + "' value='"+ version.number +"' disabled='disabled' class='input-text' type='text'></td>";
                html += "</tr>";
            });
            if (html !== '') {
                $('.no-versions').hide();
            }
            $('#fastly-versions-list').html(html);
        }
    }
});