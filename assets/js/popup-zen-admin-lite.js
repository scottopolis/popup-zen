(function(window, document, $, undefined){

  var pzen = {};

  pzen.init = function() {

    pzen.listeners();
    pzen.toggleShowOn();
    pzen.toggleTypes();
    pzen.toggleDatepicker();
    pzen.toggleEmailForm();

    $('.pzen-datepicker').datepicker({
      dateFormat : 'mm/dd/yy'
    });

    $('.pzen-colors').wpColorPicker();

  }

  pzen.listeners = function() {

    // Handle live preview update with visual editor
    $(document).on( 'tinymce-editor-init', function( event, editor ) {

      // Update preview on first page load
      pzen.updatePreviewContent();

      // add listener to visual editor for live updates
      window.tinymce.activeEditor.on( 'keyup', function(e) {
        pzen.updatePreviewContent();
      })

    } );

    // Updates preview if page loaded with HTML editor tab
    if( $('#wp-content-wrap').hasClass('html-active') ) {
      pzen.updatePreviewContent();
    }

    $('body')
    .on('change', '.pzen-switch input', pzen.toggleSwitch )
    .on('change', 'input[name=expiration]', pzen.toggleDatepicker )
    .on('change', 'input[name=show_on]', pzen.toggleShowOn )
    .on('change', 'input[name=pzen_type]', pzen.toggleTypes )
    .on('change', 'select[name=email_provider]', pzen.toggleEmailForm )
    .on('keyup', '#content', pzen.updatePreviewContent )
    .on('focus', 'input#scroll_delay', function() {
      $('input[name=display_when][value=delay]').prop('checked', 'checked');
    })
    .on('click', '#pzen-upload-btn', pzen.mediaUpload )

    $('#show_on_pages').suggest( window.ajaxurl + "?action=pzen_ajax_page_search", {multiple:true, multipleSep: ","});

  }

  pzen.toggleShowOn = function() {

    var showOnVal = $('input[name=show_on]:checked').val();
    var certainPages = $('#show-certain-pages');
    var cats = $('#pzen-cats');
    var tags = $('#pzen-tags');
    var types = $('#pzen-types');
    var exclude = $('#pzen-exclude');

    switch( showOnVal ) {
      case 'all':
        cats.hide();
        tags.hide();
        certainPages.hide();
        types.hide();
        exclude.hide();
        break;
      case 'limited':
        cats.show();
        tags.show();
        certainPages.show();
        types.show();
        exclude.show();
        break;
      default:
        cats.hide();
        tags.hide();
        certainPages.hide();
        types.hide();
        exclude.hide();
    }

  }

  // hide n/a settings when using banner
  pzen.toggleTypes = function() {

    var val = $('input[name=pzen_type]:checked').val();
    var pos = $('#position-settings');
    var hideBtn = $('#hide_btn, label[for=hide_btn]');
    var name = $('#pzen-name-fields');
    var permissionBtns = $('#permission-btns');
    var popupLinkOptions = $('#popup-link-options');

    switch( val ) {
      case 'pzen_header_bar':
        permissionBtns.fadeIn();
        popupLinkOptions.hide();
        hideBtn.hide();
        pos.hide();
        name.hide();
        break;
      case 'pzen_footer_bar':
        hideBtn.hide();
        permissionBtns.fadeIn();
        popupLinkOptions.hide();
        pos.hide();
        pOptions.show();
        name.hide();
        break;
      case 'pzen_popup_link':
        name.fadeIn();
        permissionBtns.hide();
        popupLinkOptions.fadeIn();
        hideBtn.hide();
        pos.hide();
        pOptions.fadeIn();
        break;
      case 'pzen_small_box':
        pos.fadeIn();
        permissionBtns.hide();
        popupLinkOptions.hide();
        name.show();
        hideBtn.fadeIn();
        break;
      default:
        permissionBtns.hide();
        popupLinkOptions.hide();
        hideBtn.fadeIn();
        pos.fadeIn();
        name.hide();
    }
  }

  // Handle display of different email options
  pzen.toggleEmailForm = function() {

    var noneSelected = $('#none-selected');
    var defaultDiv = $('#default-email-options');
    var custom = $('#custom-email-options');
    var checkedVal = $('select[name=email_provider]').val();
    var itemTypeVal = $('input[name=item_type]:checked').val();
    var mcFields = $('#mailchimp-fields');
    var acFields = $('#ac-fields');
    var ckFields = $('#convertkit-fields');
    var mailpoet = $('#mailpoet-fields');
    var drip = $('#drip-fields');

    // Show optin in preview
    if( itemTypeVal === 'optin' ) {

      $('#show-email-options').fadeIn();

    }

    if( checkedVal === 'default' ) {
      noneSelected.fadeIn();
      defaultDiv.hide();
      custom.hide();
      ckFields.hide();
      mcFields.hide();
      mailpoet.hide();
      acFields.hide();
      drip.hide();
    } else if( checkedVal === 'custom' ) {
      custom.fadeIn();
      defaultDiv.hide();
      noneSelected.hide();
      ckFields.hide();
      mcFields.hide();
      mailpoet.hide();
      acFields.hide();
      drip.hide();
    } else if( checkedVal === 'mc' ) {
      mcFields.fadeIn();
      defaultDiv.fadeIn();
      ckFields.hide();
      custom.hide();
      noneSelected.hide();
      mailpoet.hide();
      acFields.hide();
      drip.hide();
    } else if( checkedVal === 'ck' ) {
      ckFields.fadeIn();
      defaultDiv.fadeIn();
      mcFields.hide();
      custom.hide();
      noneSelected.hide();
      mailpoet.hide();
      acFields.hide();
      drip.hide();
    } else if( checkedVal === 'mailpoet' ) {
      mailpoet.fadeIn();
      defaultDiv.fadeIn();
      mcFields.hide();
      custom.hide();
      noneSelected.hide();
      ckFields.hide();
      acFields.hide();
      drip.hide();
    } else if( checkedVal === 'ac' ) {
      ckFields.hide();
      defaultDiv.fadeIn();
      mcFields.hide();
      custom.hide();
      noneSelected.hide();
      mailpoet.hide();
      acFields.fadeIn();
      drip.hide();
    } else if( checkedVal === 'drip' ) {
      drip.fadeIn();
      ckFields.hide();
      defaultDiv.fadeIn();
      mcFields.hide();
      custom.hide();
      noneSelected.hide();
      mailpoet.hide();
      acFields.hide();
    }

  }

  pzen.toggleChat = function() {

    if( $('input[name=show_chat]').is(':checked') ) {
      $('#pzen-chat').removeClass('pzen-hide');
    } else {
      $('#pzen-chat').addClass('pzen-hide');
    }

  }

  pzen.toggleSwitch = function() {

    pzen.toggleActiveAjax( $(this).data('id') );

  }

  // Toggle meta value via ajax
  pzen.toggleActiveAjax = function( id ) {

    var params = { action: 'pzen_toggle_active', id: id };

    // store interaction data
    $.ajax({
      method: "GET",
      url: window.ajaxurl,
      data: params
      })
      .done(function(msg) {
        // console.log(msg);
      })
      .fail(function(err) {
        console.log(err);
      });

  }

  pzen.toggleDatepicker = function() {

    if( $('input[name=expiration]').is(':checked') ) {
      $('#pzen-until-datepicker').show();
    } else {
      $('#pzen-until-datepicker').hide();
    }

  }

  pzen.updatePreviewContent = function() {

    var content;

    if( $('#wp-content-wrap').hasClass('tmce-active') ) {
      // rich editor selected
      content = window.tinymce.get('content').getContent();
    } else {
      // HTML editor selected
      content = $('#content').val();
    }

    var firstRow = document.getElementById('pzen-first-row');
    if( firstRow )
     firstRow.innerHTML = content;
  }

  pzen.mediaUpload = function(e) {

    e.preventDefault();

    var mediaUploader;

    // If the uploader object has already been created, reopen the dialog
    if (mediaUploader) {
      mediaUploader.open();
      return;
    }

    // Extend the wp.media object
    mediaUploader = wp.media.frames.file_frame = wp.media({
      title: 'Choose Image',
      button: {
      text: 'Choose Image'
    }, multiple: false });

    // When a file is selected, grab the URL and set it as the text field's value
    mediaUploader.on('select', function() {
      var attachment = mediaUploader.state().get('selection').first().toJSON();
      $('#pzen-image-url').val(attachment.url);
      $('.pzen-popup-image').attr("src", attachment.url);
    });
    // Open the uploader dialog
    mediaUploader.open();

  }

  pzen.init();

  window.pzenAdmin = pzen;

})(window, document, jQuery);