function Plugin_SetCategoryVisibility( hash, path, category, visibility ) {

		if( visibility ) {
			visibility = '1';
		} else {
			visibility = '0';
		}

		jQuery('#categoryvisibilitycb-'+category).hide();	
		jQuery('#savingcategoryvisibility-'+category).fadeIn(300);	
			
	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		cat: category,
		visibility: visibility
	};
		
	jQuery.post(ajaxurl, data, function(response) {	
		jQuery('#savingcategoryvisibility-'+category).fadeOut(300, function() { jQuery('#categoryvisibilitycb-'+category).fadeIn(100);	});	
	});		
		
}


function Plugin_SetGenericCalendarOption( hash, path, option, value ) {
	
	jQuery('#defaultRsvpAdminEmail').attr('disabled', 'disabled'); 
	jQuery('#caloption-'+option).fadeOut(300, function() { jQuery('#savingcaloption-'+option).fadeIn(300, function() { 																											
		var data = {
			action: 'core_t4s_callback',
			hash: hash,
			actiont4s: 'setoption',
			option: option,
			value: value
		};
			
		jQuery.post(ajaxurl, data, function(response) {
			jQuery('#savingcaloption-'+option).fadeOut(300, function() { jQuery('#caloption-'+option).fadeIn(500);	});	
			jQuery('#defaultRsvpAdminEmail').removeAttr('disabled'); 
		});																																											
		
	});	
	});
	
}


function Plugin_DeleteRsvpField(hash, path, fieldID) {

	var response = confirm('Are you sure you want to delete this RSVP field option?');
	
	if( !response )
		return false;

	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'delete_rsvp_field',
		field: fieldID
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		jQuery('#rsvp-field-'+fieldID).fadeOut(800);		
	});		
	
}


function Plugin_SaveRsvpField(hash, path, fieldID) {
	
	
	var format = 0;
	var name = jQuery('#txtRsvpField-'+fieldID).val();
	
	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'update_rsvp_field',
		field: fieldID,
		name: encodeURIComponent(name),
		format: format
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		jQuery('#displayRsvpField-'+fieldID).html(name);
		jQuery('#txtRsvpField-'+fieldID).val(name);
		rsvpFieldBeingEdited = false;
		Plugin_HideRsvpFieldEdit(hash, fieldID);		
	});			
}


function Plugin_DonationsRefreshTopDonations(hash, path, container, filterType, startDate, endDate, limit) {

	jQuery('#'+container).fadeTo(500, 0.4, function() {

		var data = {
			action: 'core_t4s_callback',
			hash: hash,
			actiont4s: 'get_top_donations_by_'+filterType,
			start: encodeURIComponent(startDate),
			end: encodeURIComponent(endDate),
			limit: limit
		};
			
		jQuery.post(ajaxurl, data, function(response) {		
				jQuery('#'+container).html(response);
				jQuery('#'+container).fadeTo(500, 1.0);				
		});																																													 
	} ); 																		 	
	
}


function Plugin_RefreshContactList(hash, path, fund) {

	var fundOption = '';
	if( fund === '' ) {
		fundOption = "&view=all";
	}
	
	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'refresh_contactlist',
		fund: fund,
		path: path + fundOption
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		document.getElementById('fundContactList').innerHTML = response;	
	});	
	
}


function Plugin_LinkContact(hash, path, fund, contact) {

	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'link_contact',
		fund: fund,
		contact: contact,
		path: path
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		document.getElementById('fundContactList').innerHTML = response;
		Plugin_RefreshNonContactOptions(hash, path, fund);		
	});	
	
}


function Plugin_AddContactDropdown(hash, obj, fund, path) {
	
	switch(obj.value) {
		case "CreateNew":
			jQuery('#newContact').fadeIn(500);
			break;
		default:
			Plugin_LinkContact(path, fund, jQuery('#addContactDropDown').val() );
			jQuery('#newContact').fadeOut(200);
			break;	
	}
	
}


function Plugin_RemoveContact(hash, path, fund, contact, view) {

	if( view == 'all' ) {
		var contactName = document.getElementById('contactname-'+contact).innerHTML;
		var result = confirm("Are you sure you want to remove the contact '"+contactName+"', entirely?");
		if( !result )
			return false;
	}

	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'remove_contact',
		fund: fund,
		contact: contact,
		path: path
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		document.getElementById('fundContactList').innerHTML = response;
		Plugin_RefreshNonContactOptions(hash, path, fund);			
	});	
	
}


var inEditMode = false;
var inEditModeContactName = '';

function Plugin_SaveContact( hash, path, contact) {
	
	var newName = jQuery('#txtContactName-'+contact).val();
	var newEmail = jQuery('#txtContactEmail-'+contact).val();
	jQuery('#savecontact-'+contact).hide();	
	jQuery('#savingcontact-'+contact).fadeIn(100);

	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'update_contact',
		contact: contact,
		name: newName,
		email: newEmail
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		document.getElementById('contactname-'+contact).innerHTML = newName;
		document.getElementById('contactemail-'+contact).innerHTML = newEmail;
		jQuery('#savingcontact-'+contact).fadeOut('800', function() { Plugin_CloseContactUpdate( hash, contact ); } );				
	});		
	
}



function Plugin_EditContact( hash, contact, contactName ) {
	
	if( inEditMode ) {
		alert("Please Save or Cancel your changes to '"+inEditModeContactName+"' before editing another contact.");
		return false;
	}
	inEditMode = true;
	inEditModeContactName = contactName;
	jQuery('#editcontact-'+contact).hide();	
	jQuery('#savecontact-'+contact).fadeIn(300);
	
	jQuery('#contactname-'+contact).hide();
	jQuery('#editcontactname-'+contact).fadeIn(300);
	
	jQuery('#contactemail-'+contact).hide();	
	jQuery('#editcontactemail-'+contact).fadeIn(300);
	
}


function Plugin_RefreshNonContactOptions( hash, path, fund ) {

	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'get_noncontact_options',
		fund: fund,
		path: path
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		if( jQuery('#addContactDropDown').length > 0 ) {
			document.getElementById('addContactDropDown').innerHTML = response;
		}
	});	
	
}


function Plugin_CloseContactUpdate( hash, contact ) {

	inEditMode = false;
	inEditModeContactName = '';
	
	jQuery('#txtContactName-'+contact).val(document.getElementById('contactname-'+contact).innerHTML);
	jQuery('#txtContactEmail-'+contact).val(document.getElementById('contactemail-'+contact).innerHTML);
	
	jQuery('#savecontact-'+contact).hide();	
	jQuery('#editcontact-'+contact).fadeIn(300);
	
	jQuery('#editcontactname-'+contact).hide();
	jQuery('#contactname-'+contact).fadeIn(300);
	
	jQuery('#editcontactemail-'+contact).hide();	
	jQuery('#contactemail-'+contact).fadeIn(300);
	
}


function Plugin_RefreshDonations(hash, path, container, fund, startDate, endDate, page, limit, order) {
														
	jQuery('#'+container).fadeTo(500, 0.4, function() {	
			
		var data = {
			action: 'core_t4s_callback',
			hash: hash,
			actiont4s: 'get_campaign_donations_list',
			path: path,
			fund: fund,
			start: startDate,
			end: endDate,
			page: page,
			limit: limit,
			order: order
		};
			
		jQuery.post(ajaxurl, data, function(response) {
			jQuery('#'+container).html(response);
			jQuery('#'+container).fadeTo(500, 1.0);
		});
		
	} ); 
	
}


function Plugin_RefreshDonationsByMonth(hash, path, container, fund, year) {
		
	jQuery('#'+container).fadeTo(500, 0.4, function() {														
																
		var data = {
			action: 'core_t4s_callback',
			hash: hash,
			actiont4s: 'get_donations_by_month',
			path: path,
			fund: fund,
			year: year
		};
			
		jQuery.post(ajaxurl, data, function(response) {
			jQuery('#'+container).html(response);
			jQuery('#'+container).fadeTo(500, 1.0);
		});																																												 
	} ); 
	
}


function Plugin_CreateNewContact(hash, path, fund) {

	var name = jQuery('#newContactName').val();
	var email = jQuery('#newContactEmail').val();
	
		var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'create_contact',
		fund: fund,
		name: name,
		email: email,
		path: path
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		document.getElementById('fundContactList').innerHTML = response;
		jQuery('#newContact').fadeOut(200);
		if( jQuery('#addContactDropDown').length > 0 ) {
			document.getElementById('addContactDropDown').selectedIndex = 0;
		}
		
		jQuery('#newContactName').val('');
		jQuery('#newContactEmail').val('');
		
		jQuery('#btnCancelNewContact').hide();
		jQuery('#btnNewContact').show();

	});	
	
}


function Plugin_RefreshDonationOptions(hash, path) {

	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'refresh_optionslist'
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		document.getElementById('donationOptionsList').innerHTML = response;
	});	
	
}


function Plugin_UpdateDonationOption(hash, path, option, checked) {

	var value = 0;
	if( checked ) {
		value = 1;	
	}
	
	jQuery('#checkoption-'+option).hide();	
	jQuery('#savingoption-'+option).fadeIn(100);
	
	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'update_option',
		option: option,
		value: value
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		jQuery('#savingoption-'+option).fadeOut('800', function() { 
			jQuery('#checkoption-'+option).fadeIn(300);	 
			if( option == 'enable_tributes' ) { Plugin_RefreshDonationOptions(hash, path); }
			} );		
		});	
		
}


var rsvpFieldBeingEditedID = '';
var rsvpFieldBeingEdited = false;


function Plugin_EditRsvpField(hash, fieldID) {
	
	if( rsvpFieldBeingEdited ) {
		var response = confirm('You have not saved your changes to the RSVP field currently being edited.\n\nDo you wish to discard those changes and continue anyway?');
		if( !response )
			return false;
			
		Plugin_CancelRsvpField(hash, rsvpFieldBeingEditedID);
	}
	
	rsvpFieldBeingEdited = true;
	rsvpFieldBeingEditedID = fieldID;
	
	Plugin_ShowRsvpFieldEdit(hash, fieldID);
	
	
	
}

function Plugin_HideRsvpFieldEdit(hash, fieldID) {
	jQuery('#saversvpfield-'+fieldID).fadeOut(300, function() { jQuery('#editrsvpfield-'+fieldID).fadeIn(800);  });
	jQuery('#cancelrsvpfield-'+fieldID).fadeOut(300, function() { jQuery('#cancelplaceholder-'+fieldID).fadeIn(800); });
	jQuery('#divtxtRsvpField-'+fieldID).fadeOut(300, function() { jQuery('#divDisplayRsvpField-'+fieldID).fadeIn(800);  });
	jQuery('#editrsvpfieldformat-'+fieldID).fadeOut(300, function() { jQuery('#displayrsvpfieldformat-'+fieldID).fadeIn(800);  });
	
}

function Plugin_ShowRsvpFieldEdit(hash, fieldID) {
	jQuery('#editrsvpfield-'+fieldID).fadeOut(300, function() { jQuery('#saversvpfield-'+fieldID).fadeIn(800);  });
	jQuery('#displayrsvpfieldformat-'+fieldID).fadeOut(300, function() { jQuery('#editrsvpfieldformat-'+fieldID).fadeIn(800);  });	
	jQuery('#cancelplaceholder-'+fieldID).fadeOut(300, function() { jQuery('#cancelrsvpfield-'+fieldID).fadeIn(800); });
	jQuery('#divDisplayRsvpField-'+fieldID).fadeOut(300, function() { jQuery('#divtxtRsvpField-'+fieldID).fadeIn(800); jQuery('#txtRsvpField-'+fieldID).select();
																	
	jQuery('#txtRsvpField-'+fieldID).focus(); });
}

function Plugin_CancelRsvpField(hash, fieldID) {
	jQuery('#txtRsvpField-'+fieldID).val(jQuery('#displayRsvpField-'+fieldID).html());
	Plugin_HideRsvpFieldEdit(hash, fieldID);
	rsvpFieldBeingEdited = false;
}


function Plugin_SaveOrgDetails(hash) {
	
	website = jQuery('#txtOrgWebsite').val();
	website = website.replace('http://', '');
	website = website.replace('www.', '');
	
	var orgName =  jQuery('#txtOrgName').val();
	var orgEmail =  jQuery('#txtOrgContactEmail').val();
	var orgAddress =  jQuery('#txtOrgAddress').val();
	var orgAddress2 =  jQuery('#txtOrgAddress2').val();
	var orgCity =  jQuery('#txtOrgCity').val();
	var orgState =  jQuery('#txtOrgState').val();
	var orgZip =  jQuery('#txtOrgZip').val();
	var orgSite =  website;
	var org501c3TaxId = jQuery('#txtOrg501c3TaxID').val();
	orgAddress = orgAddress.replace(/\n/g, "<br/>");
	orgSite = orgSite.replace("https://", "");
	
	jQuery('#saveorgdetails').hide();
	jQuery('#savingorgdetails').fadeIn(200);
	
	var actionUrl = ajaxurl;
	
	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'update_org_details',
		name: orgName,
		email: orgEmail,
		address: orgAddress,
		address2: orgAddress2,
		city: orgCity,
		state: orgState,
		zip: orgZip,
		'501c3': org501c3TaxId,
		site: orgSite
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		jQuery('#savingorgdetails').fadeOut(800, function() { 
			jQuery('#saveorgdetails').fadeIn(200); 
			jQuery('#txtOrgName').removeClass('changedField');
			jQuery('#txtOrgContactEmail').removeClass('changedField');
			jQuery('#txtOrgAddress').removeClass('changedField');
			jQuery('#txtOrgAddress2').removeClass('changedField');
			jQuery('#txtOrgCity').removeClass('changedField');
			jQuery('#txtOrgState').removeClass('changedField');
			jQuery('#txtOrgZip').removeClass('changedField');
			jQuery('#txtOrgWebsite').removeClass('changedField');
			jQuery('#txtOrg501c3TaxID').removeClass('changedField');
		} );	
		
		updateT4SSettings();
		
	});
			
}


function Plugin_SaveEmailPreferences(hash) {
	
	var outgoingbcc =  jQuery('#txtOrgOutgoingBCC').val();
	
	jQuery('#saveemailprefs').hide();
	jQuery('#savingemailprefs').fadeIn(200);
	
	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'save_email_preferences',
		outgoingbcc: outgoingbcc
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		jQuery('#savingemailprefs').fadeOut(800, function() {
			jQuery('#txtOrgOutgoingBCC').removeClass('changedField');	
			jQuery('#saveemailprefs').fadeIn(200);
		});			
	});		
			
}


function Plugin_SaveGatewayPreferences(hash) {
	
	var gateway = jQuery('#gateway').val();
	
	jQuery('#saveemailprefs').hide();
	jQuery('#savinggatewayprefs').fadeIn(200);

	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'save_gateway_preferences',
		gateway: gateway
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		jQuery('#savinggatewayprefs').fadeOut(800, function() {
			jQuery('#gateway').removeClass('changedField');	
			jQuery('#savegatewayprefs').fadeIn(200);
		});
		updateT4SSettings();		
	});		
		
}


function Plugin_SavingOrgPaypalConfig(hash) {
	
	if( !confirm("Are you sure you want to modify your PayPal Configuration? Your PayPal Business email address must match the e-mail address of your actual PayPal account for live transactions to function.\n\nIf your PayPal address saved here is not correct, PayPal transactions through Tools 4 Shuls will not work.") )
		return false;
	
	var pps =  jQuery('#selectPayPalServer').val();
	var ppLiveEmail =  jQuery('#txtPayPalBusinessEmail').val();
	var ppTestEmail =  jQuery('#txtPayPalSandboxBusinessEmail').val();
	
	jQuery('#saveorgpaypalconfig').hide();
	jQuery('#savingorgpaypalconfig').fadeIn(200);
	
	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'save_paypal_config',
		pps: pps,
		pple: ppLiveEmail,
		ppte: ppTestEmail
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		jQuery('#savingorgpaypalconfig').fadeOut(800, function() {
			jQuery('#fsPaypalConfig input').removeClass('changedField');		
			jQuery('#fsPaypalConfig select').removeClass('changedField');		
			jQuery('#saveorgpaypalconfig').fadeIn(200);
			updateT4SSettings();
		});
	});		
	
}

function Plugin_SavingAuthorizeNetConfig(hash) {
	
	if( !confirm("Are you sure you want to modify your Authorize.NET Configuration?") )
		return false;
		
	var ppLogin =  jQuery('#txtAuthorizeNetApiLoginID').val();
	var ppKey =  jQuery('#txtAuthorizeNetTransactionKey').val();
	var ppHash =  jQuery('#txtAuthorizeNetHash').val();
	
	jQuery('#saveorgauthconfig').hide();
	jQuery('#savingorgauthconfig').fadeIn(200);
	
	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'save_authorize_config',
		ppLogin: ppLogin,
		ppKey: ppKey,
		ppHash: ppHash
	};
		
	jQuery.post(ajaxurl, data, function(response) {
			jQuery('#savingorgauthconfig').fadeOut(800, function() {
			jQuery('#fsPayPalConfig input').removeClass('changedField');		
			jQuery('#fsPayPalConfig select').removeClass('changedField');		
			jQuery('#saveorgauthconfig').fadeIn(200);
			updateT4SSettings();
		});
	});		
	
}


function Plugin_CreateAdminButtonClick(hash) {
	
	jQuery('#createAdminAccount').hide();
	jQuery('#newAdminUserRow').show();
	
}


function Plugin_CancelNewAdmin(hash) {
	jQuery('#newAdminUserRow').hide();
	jQuery('#createAdminAccount').fadeIn(300);
	var name = jQuery('#newAdminName').val('');
	var email = jQuery('#newAdminEmail').val('');
	var pass = jQuery('#newAdminPass').val('');
	var pass2 = jQuery('#newAdminPassConfirm').val('');
}


function Plugin_CreateNewAdmin(hash,path) {

	var name = jQuery('#newAdminName').val();
	var email = jQuery('#newAdminEmail').val();
	var pass = jQuery('#newAdminPass').val();
	var pass2 = jQuery('#newAdminPassConfirm').val();	
	
	if( email === '' ) {
		alert('You must enter a valid e-mail address for the new admin user.');
		jQuery('#newAdminEmail').focus();
		return false;
	}
	
	if( name === '' ) {
		alert('You must enter a name for the new admin user.');
		jQuery('#newAdminName').focus();
		return false;
	}
	
	if( pass === '' || pass.length < 5) {
		alert('You must enter a password that is at least 5 characters (no spaces) for the new admin user.');
		jQuery('#newAdminPass').focus();
		jQuery('#newAdminPass').select();
		return false;
	}
	
	if( pass !== pass2 ) {
		alert('The passwords you entered for the new user do not match.');
		jQuery('#newAdminPassConfirm').focus();
		jQuery('#newAdminPassConfirm').select();
		return false;
	}
	
	var g = '';
	for( i = 1; i <= 7; i++ ) {
		if( document.getElementById('newAdminCbGp'+i) !== null ) {
			if( document.getElementById('newAdminCbGp'+i).checked ) { g += i+','; } 
		}
	}
	
	
	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'create_admin',
		name: name,
		email: email,
		pass: pass,
		path: path,
		g: g
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		jQuery('#newAdminUserRow').hide();
		jQuery('#createAdminAccount').fadeIn(300);
		Plugin_RefreshAdminList(hash, path);
	});	
	
}


function Plugin_RefreshAdminList(hash,path) {

	jQuery('#loadingAdminUserListGraphic').fadeIn(300);
	
	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'refresh_adminlist',
		path: path
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		document.getElementById('adminUserList').innerHTML = response;
		jQuery('#loadingAdminUserListGraphic').fadeOut(300);
	});	
	
}


function Plugin_RemoveAdmin(hash, path, admin) {

	var adminName = document.getElementById('adminname-'+admin).innerHTML;
	var result = confirm("Are you sure you want to remove the admin user '"+adminName+"', entirely?");
	if( !result )
		return false;

	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'remove_admin',
		admin: admin,
		path: path
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		Plugin_CloseAdminUpdate(hash, admin);
		Plugin_RefreshAdminList(hash, path);				
	});	
	
}



var inEditMode = false;
var inEditModeAdminName = '';
var inEditModeAdminId = '';

function Plugin_SaveAdmin( hash, path, admin) {
	
	var newName = jQuery('#txtAdminName-'+admin).val();
	var newEmail = jQuery('#txtAdminEmail-'+admin).val();
	
	jQuery('#saveadmin-'+admin).hide();	
	jQuery('#savingadmin-'+admin).fadeIn(200);
	
	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'update_admin',
		admin: admin,
		name: newName,
		email: newEmail
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		document.getElementById('adminname-'+admin).innerHTML = newName;
		document.getElementById('adminemail-'+admin).innerHTML = newEmail;
		jQuery('#savingadmin-'+admin).fadeOut(500, function() { Plugin_CloseAdminUpdate(hash, admin ); } );				
	});		
	
}



function Plugin_CloseAdminUpdate( hash, admin ) {

	inEditMode = false;
	inEditModeAdminName = '';
	inEditModeAdminId = '';
	
	jQuery('#txtAdminName-'+admin).val(document.getElementById('adminname-'+admin).innerHTML);
	jQuery('#txtAdminEmail-'+admin).val(document.getElementById('adminemail-'+admin).innerHTML);
	
	jQuery('#saveadmin-'+admin).hide();	
	jQuery('#editadmin-'+admin).fadeIn(300);
	
	jQuery('#editadminname-'+admin).hide();
	jQuery('#adminname-'+admin).fadeIn(300);
	
	jQuery('#editadminemail-'+admin).hide();	
	jQuery('#adminemail-'+admin).fadeIn(300);
	
	jQuery('#btnRowTd-'+admin).removeClass('adminHighlightedRow');
	jQuery('#nameRowTd-'+admin).removeClass('adminHighlightedRow');
	jQuery('#emailRowTd-'+admin).removeClass('adminHighlightedRow');
	jQuery('#suRowTd-'+admin).removeClass('adminHighlightedRow');
	jQuery('#calRowTd-'+admin).removeClass('adminHighlightedRow');
	jQuery('#donRowTd-'+admin).removeClass('adminHighlightedRow');
	jQuery('#annRowTd-'+admin).removeClass('adminHighlightedRow');
	jQuery('#arcRowTd-'+admin).removeClass('adminHighlightedRow');
	jQuery('#galRowTd-'+admin).removeClass('adminHighlightedRow');
	jQuery('#newsRowTd-'+admin).removeClass('adminHighlightedRow');
	
}

function Plugin_EditAdmin( hash, admin, adminName ) {
	
	if( inEditMode ) {
		Plugin_CloseAdminUpdate(hash, inEditModeAdminId);
	}
	
	inEditMode = true;
	inEditModeAdminName = adminName;
	inEditModeAdminId = admin;
	
	jQuery('#editadmin-'+admin).hide();	
	jQuery('#saveadmin-'+admin).fadeIn(300);
	
	jQuery('#adminname-'+admin).hide();
	jQuery('#editadminname-'+admin).fadeIn(300);
	
	jQuery('#adminemail-'+admin).hide();	
	jQuery('#editadminemail-'+admin).fadeIn(300);
	
	jQuery('#btnRowTd-'+admin).addClass('adminHighlightedRow');
	jQuery('#nameRowTd-'+admin).addClass('adminHighlightedRow');
	jQuery('#emailRowTd-'+admin).addClass('adminHighlightedRow');
	jQuery('#suRowTd-'+admin).addClass('adminHighlightedRow');
	jQuery('#calRowTd-'+admin).addClass('adminHighlightedRow');
	jQuery('#donRowTd-'+admin).addClass('adminHighlightedRow');
	jQuery('#annRowTd-'+admin).addClass('adminHighlightedRow');
	jQuery('#arcRowTd-'+admin).addClass('adminHighlightedRow');
	jQuery('#galRowTd-'+admin).addClass('adminHighlightedRow');
	jQuery('#newsRowTd-'+admin).addClass('adminHighlightedRow');
	
}

function Plugin_SetPermission( hash, path, admin, group, permission ) {

	if( permission ) {
		permission = '1';
	} else {
		permission = '0';
	}
	
	jQuery('#admingrpcb-'+admin+'-'+group).hide();	
	jQuery('#savingpermission-'+admin+'-'+group).fadeIn(300);				

	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'set_permission',
		admin: admin,
		group: group,
		permission: permission,
		path: path
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		Plugin_RefreshAdminList(hash, path);
	});		
		
}


function Plugin_ResetPassword(hash, admin) {
	
	val1 = prompt("Please enter the new password for this user:");
	if( !val1 ) {
		return false;
	}
	val2 = prompt("Please re-enter the new password for this user to confirm the change:");
	if( !val2 ) {
		return false;
	}
	if( val1 != val2 ) {
		alert("The two passwords entered did not match.  Please re-try.");
		return false;
	}

	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'reset_user_password',
		u: admin,
		p: val1
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		alert("The user's password has been reset.  ");
	});		
	
}


function Plugin_SaveOrgWebDetails(hash) {
	
	var domain =  encodeURIComponent(jQuery('#txtDomain').val().replace('https://', ''));
	var calLink =  encodeURIComponent(jQuery('#txtCalendarURL').val());
	var donLink =  encodeURIComponent(jQuery('#txtDonationsURL').val());
	var annLink =  encodeURIComponent(jQuery('#txtAnnouncementsURL').val());
	var arcLink =  encodeURIComponent(jQuery('#txtArchiveURL').val());
	var galLink =  encodeURIComponent(jQuery('#txtGalleryURL').val());
	
	jQuery('#saveorgwebdetails').hide();
	jQuery('#savingorgwebdetails').fadeIn(200);
	
	var website = jQuery('#txtDomain').val();
	website = website.replace('http://', '');
	website = website.replace('www.', '');

	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'update_org_web_details',
		domain: website,
		calLink: calLink,
		donLink: donLink,
		annLink: annLink,
		arcLink: arcLink,
		galLink: galLink
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		jQuery('#savingorgwebdetails').fadeOut(800, function() { 
			jQuery('#saveorgwebdetails').fadeIn(200); 
			jQuery('#websiteDetailsTable input').removeClass('changedField');
		} );
	});	
	
}



function Plugin_DeleteCategory(hash, id) {

	var res = confirm('Are you sure you want to delete this category?');

	if( !res ) 
		return false;

	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'delete_category',
		id: id
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		jQuery('#categoryrow-'+id).fadeOut(1000);			
	});	

}


function Plugin_DeleteEvent(hash, path, id, series, returnPath) {

	var deleteEvent = false;
	var deleteSeries = false;
	var url = '';
	var res = confirm('Are you sure you want to delete this event?');
	if( res ) {
		url += 'edit?event_id='+id;
		if( series ) {
			ser = confirm('This event is part of a series.  Would you like to delete the entire series?');	
			if( ser )
				url += '&delseries=1';
		}
		url += '&del=1&ret_link='+returnPath;
		
		url = path+encodeURIComponent(url);
	}
	
	if( url === '' )
		return false;

	window.location = decodeURIComponent(url);
	
}

function Plugin_SetContactPrimary(hash, path, contact, primary) {

	var isPrimary = '0';
	if( primary ) {
		isPrimary = '1';
	}
	
	$('#contactprimarystatuscb-'+contact).hide();
	$('#savingcontactprimarystatus-'+contact).fadeIn(100);
	
	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'set_primary',
		contact: contact,
		primary: isPrimary,
		path: path
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		jQuery('#savingcontactprimarystatus-'+contact).fadeOut(300, function() { $('#contactprimarystatuscb-'+contact).fadeIn(100); });				
	});	
	
}


function Plugin_AddNewRSVPField(hash, path) {
	
	var fieldName, fieldFormat;

	fieldName = document.getElementById('new_rsvp_field_name').value;
	fieldFormat = document.getElementById('new_rsvp_field_format').value;
	
	if( fieldName === "" ) {
		alert("You must enter a name for this new RSVP field.");
		return false;
	}
	
	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'newRSVPField',
		name: fieldName,
		format: fieldFormat
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		 document.getElementById('new_rsvp_field_name').value = "";
		 document.getElementById('newRSVPFields').innerHTML += response.responseText;
	 });	
	
}


function Plugin_GoConfirm() {

	if( totalDonationAmount >= 0.0 ) {
	
		UpdateDonations(); 
		
		var x = document.getElementId('donateForm').getAttribute('action');
								
		if (document.getElementId('donateForm').getAttribute('action').substring(0, 4) != "http") {
			document.getElementId('donateForm').setAttribute('action', 'https://'+x);
		}
		
		document.donateForm.submit();
	}
	
}



function Plugin_AddRSVPPayment(hash, rec, pageReload) {
	
	var txtPaymentAmount = document.getElementById('paymentAmount-'+rec);
	var txtPaymentDate = document.getElementById('paymentDate_'+rec);
	var selPaymentMethod = document.getElementById('paymentMethod-'+rec);
	var divPaymentBlock = document.getElementById('addPaymentBlock-'+rec);
	var divFullyPaid = document.getElementById('divFullyPaid-'+rec);
	var aPaymentLink = document.getElementById('addPaymentLink-'+rec);
	var aCancelPaymentLink = document.getElementById('cancelPaymentLink-'+rec);
	var imgPaymentWorking = document.getElementById('loadGif-'+rec);
	
	if( !ValueIsNumeric(txtPaymentAmount.value) ){
		alert("Payment value must be numeric (example: '5' or '5.00')");
		txtPaymentAmount.focus();
		return false;	
	}
	
	if( txtPaymentDate.value === "" ) {
		alert("A date for this payment must be entered.");
		txtPaymentDate.focus();
		return false;
	}
	
	txtPaymentAmount.disabled = true;
	selPaymentMethod.disabled = true;
	ShowElement(imgPaymentWorking.id);
	
	var amt = txtPaymentAmount.value;
	var type = selPaymentMethod.value;
	var date = txtPaymentDate.value;
	
	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'addrsvppayment',
		rec: rec,
		date: encodeURIComponent(date),
		amt: amt,
		paytype: type
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		 		 
		HideElement(divPaymentBlock.id);
		HideElement(imgPaymentWorking.id);
		
		txtPaymentAmount.disabled = false;
		txtPaymentAmount.value = '';
		selPaymentMethod.disabled = false;		
			
		 var arr = response.split('||');

		 document.getElementById('rsvpAmountPaid-'+rec).innerHTML = arr[1];
		 
		 if( arr[2] == 1 ) {
			HideElement(aCancelPaymentLink.id);
			HideElement(aPaymentLink.id);		
			ShowElement(divFullyPaid.id);
		 } else {
			ShowElement(aPaymentLink.id); 
			HideElement(aCancelPaymentLink.id);
		 }
		 
		 if( pageReload ) 
			location.reload(true);

	 });	
	
}



function Plugin_DeleteRSVP(hash, path, receipt) {

	var res = confirm('Are you sure you want to delete this RSVP?\n\nYou will lose all associated payment information.  This action cannot be undone.');
	
	if( !res )
		return false;
	
	var data = {
		action: 'core_t4s_callback',
		hash: hash,
		actiont4s: 'delete_rsvp',
		rsvp: receipt
	};
		
	jQuery.post(ajaxurl, data, function(response) {
		jQuery('#rsvprowa-'+receipt).fadeOut(800);
		jQuery('#rsvprowb-'+receipt).fadeOut(800);
	});		
	
}


function updateT4SSettings() {

	var data = {
		'action': 't4s_update_settings'
	};

	jQuery.post(ajaxurl, data, function(response) {
		location.reload();
	});

}