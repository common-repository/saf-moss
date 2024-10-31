jQuery(document).ready(function ($) {

	/**
	 * Download Configuration Metabox
	 */
	var VATMOSSSAF_Admin_Configuration = {
		init : function() {

			$('#check_moss_saf_license').on('click', function(e) {

				e.preventDefault();
				var me = $(this);

				var definition_key	= me.attr('definition_key_id');
				var definitionKeyEl = $('#' + definition_key);
				var definitionKey = definitionKeyEl.val();
				if (definitionKey.length == 0)
				{
					alert(vat_moss_saf_vars.ReasonNoLicenseKey);
					return false;
				}

				var loadingEl = $('#license-checking');
				loadingEl.css("display","inline-block");

				me.attr('disabled','disabled');

				var data = {
					vat_moss_saf_action:	'check_definition_license',
					definition_key:			definitionKey,
					url:					vat_moss_saf_vars.url
				};

				$.post(vat_moss_saf_vars.url, data, function (response) {
					loadingEl.hide();
					me.removeAttr('disabled');

					var json = {};
					try
					{
						json = jQuery.parseJSON( response );
						if (json.status && (json.status === "success" || json.status === "valid"))
						{
							alert(vat_moss_saf_vars.LicenseChecked.replace( '{credits}', json.credits ) );
							return;
						}
					}

					catch(ex)
					{
						console.log(ex);
						json.message = [vat_moss_saf_vars.UnexpectedErrorLicense];
					}

					if (json.message)
						alert(json.message.join('\n'));
				})
				.fail(function(){
					loadingEl.hide();
					me.removeAttr('disabled');
					alert(vat_moss_saf_vars.ErrorValidatingCredentials);
				})

			});

		}
	};

	VATMOSSSAF_Admin_Configuration.init();

});
