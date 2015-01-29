jQuery(document).ready(function ($) {

	/**
	 * Download Configuration Metabox
	 */
	var ECSL_Admin_Configuration = {
		init : function() {

			$( '.tips' ).tipTip({
				'attribute': 'data-tip',
				'fadeIn': 50,
				'fadeOut': 50,
				'delay': 200
			});

			$('#validate_credentials').on('click', function(e) {

				e.preventDefault();
				var me = $(this);

				var sender_id	= me.attr('sender_id');
				var password	= me.attr('password_id');
				var test_mode	= me.attr('test_mode_id');

				var test_modeEl = $('#' + test_mode);
				var test_mode = test_modeEl.is(':checked');

				var senderIdEl = $('#' + sender_id);
				var senderId = senderIdEl.val();
				if (!test_mode && senderId.length == 0)
				{
					alert(ecsl_vars.ReasonNoSenderId);
					return false;
				}

				var passwordEl = $('#' + password);
				var password = passwordEl.val();
				if (!test_mode && password.length == 0)
				{
					alert(ecsl_vars.ReasonNoPassword);
					return false;
				}

				var loadingEl = $('#ecsl-loading');
				loadingEl.css("display","inline-block");

				me.attr('disabled','disabled');

				var data = {
					ecsl_action: 'verify_ecsl_credentials',
					senderid:	 senderId,
					password: 	 password
				};

				$.post(ecsl_vars.url, data, function (response) {
					loadingEl.hide();
					me.removeAttr('disabled');
					var json = {};
					try
					{
						json = jQuery.parseJSON( response );
						if (json.status && (json.status === "success" || json.status === "valid"))
						{
							alert(ecsl_vars.CredentialsValidated);
							return;
						}
					}
					catch(ex)
					{
						console.log(ex);
						
						json.message = ["An unexpected error occurred validating the credentials.  If this error persists, contact the administrator."];
					}
					alert(json.message.join('\n'));
				})
				.fail(function(){
					loadingEl.hide();
					me.removeAttr('disabled');
					alert(ecsl_vars.ErrorValidatingCredentials);
				})

			});
			
			$('#check_license').on('click', function(e) {

				e.preventDefault();
				var me = $(this);

				var submission_key	= me.attr('submission_key_id');
				var submissionKeyEl = $('#' + submission_key);
				var submissionKey = submissionKeyEl.val();
				if (submissionKey.length == 0)
				{
					alert(ecsl_vars.ReasonNoLicenseKey);
					return false;
				}

				var loadingEl = $('#license-checking');
				loadingEl.css("display","inline-block");

				me.attr('disabled','disabled');

				var data = {
					ecsl_action:	'check_submission_license',
					submission_key:	submissionKey,
					url:			ecsl_vars.url
				};

				$.post(ecsl_vars.url, data, function (response) {
					loadingEl.hide();
					me.removeAttr('disabled');

					var json = jQuery.parseJSON( response );
					if (json.status && (json.status === "success" || json.status === "valid"))
					{
						alert(ecsl_vars.LicenseChecked.replace( '{credits}', json.credits ) );
						return;
					}

					if (json.message)
						alert(json.message.join('\n'));
				})
				.fail(function(){
					loadingEl.hide();
					me.removeAttr('disabled');
					alert(ecsl_vars.ErrorValidatingCredentials);
				})

			});

			$('.ip_address_link').on('click', function(e) {

				var ip_dialog = $('<div></div>')
							   .html('<iframe style="border: 0px; " src="' + $(this).attr('href') + '" width="100%" height="100%"></iframe>')
							   .dialog({
								   autoOpen: false,
								   modal: true,
								   height: 485,
								   width: 550,
								   title: ecsl_vars.IPAddressInformation,
								   dialogClass: 'vat_ip_address'
							   });
				ip_dialog.dialog('open');
				return false;

			});
		}
	};

	ECSL_Admin_Configuration.init();

});
