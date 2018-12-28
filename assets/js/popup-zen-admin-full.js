(function(window, document, $, undefined){

  var pzenFullAdmin = {};

  pzenFullAdmin.init = function() {
    $('#pzen_show_on_tags').suggest( window.ajaxurl + "?action=ajax-tag-search&tax=post_tag", {multiple:true, multipleSep: ","});
    $('#pzen_show_on_cats').suggest( window.ajaxurl + "?action=ajax-tag-search&tax=category", {multiple:true, multipleSep: ","});
    $('#pzen_show_on_types').suggest( window.ajaxurl + "?action=pzen_ajax_type_search", {multiple:true, multipleSep: ","});

    $('#pzen_show_exclude_pages').suggest( window.ajaxurl + "?action=pzen_ajax_page_search", {multiple:true, multipleSep: ","});

    $('body')
    	.on('change', 'select[name=mc_list_id]', pzenFullAdmin.resetGroups )
    	.on('change', 'select[name=mc_groups]', pzenFullAdmin.getMcInterests )
      .on('change', 'input[name=display_when]', pzenFullAdmin.displayToggle )

    if( $('select[name=mc_list_id]').val() ) {
    	pzenFullAdmin.getMcGroups(true)
    }

    // show link code on load if necessary
    pzenFullAdmin.displayToggle();
    
  }

  pzenFullAdmin.resetGroups = function() {
  	// reset
  	$('#mc_interest_checkboxes').html('');
  	$('#mailchimp-interests').hide();
  	$('select[name=mc_groups]').html('<option>None</option>').val('None');
  	$('#mailchimp-groups').hide();

  	pzenFullAdmin.getMcGroups();
  }

  pzenFullAdmin.getMcGroups = function( initialLoad ) {

  	pzenFullAdmin.showSpinner();

    var myNonce = $('#popupzen_meta_box_nonce').val();
    var listId = $('select[name=mc_list_id]').val();

    if( !listId || listId === ''  )
      return;

    $.ajax({
      method: "GET",
      url: window.ajaxurl,
      data: { nonce: myNonce, action: 'pzen_get_mc_groups', list_id: listId }
      })
      .done(function(msg) {

        pzenFullAdmin.populateGroups(msg, initialLoad )

        pzenFullAdmin.hideSpinner()

      })
      .fail(function(err) {
        console.log(err);
        pzenFullAdmin.hideSpinner()
      });

  }

  pzenFullAdmin.populateGroups = function( data, initialLoad ) {

    console.log(data);

  	var data = JSON.parse( data.data );
  	var cats = data.categories;

  	var menu = $('select[name="mc_groups"]');

  	var option = '';

  	for (var i=0;i<cats.length;i++){

  		option += '<option value="'+ cats[i].id + '">' + cats[i].title + '</option>';
  	}

  	menu.append(option);

  	$('#mailchimp-groups').fadeIn();

  	if( initialLoad )
  		pzenFullAdmin.selectCurrentGroups()

  	pzenFullAdmin.getMcInterests( initialLoad )

  }

  pzenFullAdmin.getMcInterests = function( initialLoad ) {

  	pzenFullAdmin.showSpinner()

    var myNonce = $('#popupzen_meta_box_nonce').val();
    var listId = $('select[name=mc_list_id]').val();
    var groupId = $('select[name=mc_groups]').val();

    if( !groupId || groupId === '' || groupId === 'None' )
      return;

    $.ajax({
      method: "GET",
      url: window.ajaxurl,
      data: { nonce: myNonce, action: 'pzen_get_mc_group_interests', list_id: listId, group_id: groupId }
      })
      .done(function(msg) {

        pzenFullAdmin.populateInterests( msg, initialLoad )

        pzenFullAdmin.hideSpinner()

      })
      .fail(function(err) {
        console.log(err);
        pzenFullAdmin.hideSpinner()
      });

  }

  pzenFullAdmin.populateInterests = function( data, initialLoad ) {

  	var data = JSON.parse( data.data );
  	var cats = data.interests;

    if( data.interests ) {
      $('#pzen-no-interests').hide();
    }

  	var menu = $('#mc_interest_checkboxes');

  	var checkboxes = '';
  	for (var i=0;i<cats.length;i++){
  	   checkboxes += '<p><input type="checkbox" name="mc_interests['+ cats[i].id + ']" value="true">' + cats[i].name + '</input></p>';
  	}

  	menu.append(checkboxes);

  	$('#mailchimp-interests').fadeIn();

  	if( initialLoad )
		pzenFullAdmin.selectCurrentInterests()

  }

  // only runs first page load to select current options
  pzenFullAdmin.selectCurrentGroups = function() {

  	var groupId = window.pzenAdmin.current_mc_group;

  	if( groupId ) {
  		$('select[name="mc_groups"] option[value=' + groupId + ']').prop('selected', true);
  	}

  }

  // only runs first page load to select current options
  pzenFullAdmin.selectCurrentInterests = function() {

  	var ids = window.pzenAdmin.current_mc_interests;

  	for (var key in ids) {
  		$('#mc_interest_checkboxes input[name="mc_interests[' + key + ']"]').prop('checked', true);
  	}

  }

  // Show link activation code when it's selected
  pzenFullAdmin.displayToggle = function() {

    var displayWhenVal = $('input[name=display_when]:checked').val();

    if( displayWhenVal === 'link' ) {

      $('#link-code').fadeIn().html(
        '<p><small>Copy/paste this code into any post or page:</small></p><pre>[pzen-upgrade id="' + window.pzenAdmin.post_id + '" link="Click here."]Exclusive bonus! Download my free guide.[/pzen-upgrade]</pre>'
        )
    } else {
      $('#link-code').hide();
    }

  }

  pzenFullAdmin.showSpinner = function() {

  	$('img.pzen-loading').show();

  }

  pzenFullAdmin.hideSpinner = function() {

  	$('img.pzen-loading').hide();
  	
  }

  pzenFullAdmin.init();

  window.pzenFullAdmin = pzenFullAdmin;

})(window, document, jQuery);