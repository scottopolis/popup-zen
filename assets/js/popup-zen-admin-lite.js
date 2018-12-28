(function(window, document, $, undefined){

  var pzen = {};

  pzen.init = function() {

    pzen.listeners();
    pzen.toggleShowOn();
    pzen.toggleTypes();
    pzen.toggleDatepicker();
    pzen.toggleEmailForm();
    pzen.toggleTimeAgo();

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
    .on('change', 'select[name=fomo_integration]', pzen.toggleTimeAgo )

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
    var popChat = $('#popout_meta_box, #show_chat');
    var hideBtn = $('#hide_btn, label[for=hide_btn]');
    var popMeta = $('#popout_meta_box');
    var showOptin = $('#show-optin');
    var avatar = $('.avatar-email');
    var templates = $('#popup-templates');
    var pOptions = $('#popup-options');
    var name = $('#pzen-name-fields');
    var sendBtn = $('#send-btn-color');
    var fomoSettings = $('#fomo-settings');
    var editor = $('#postdivrich');
    var disappear = $('#pzen-disappear');

    switch( val ) {
      case 'pzen-banner':
        popChat.hide();
        hideBtn.hide();
        popMeta.hide();
        pos.hide();
        showOptin.fadeIn();
        avatar.hide();
        templates.hide();
        pOptions.hide();
        name.hide();
        sendBtn.show();
        fomoSettings.hide();
        editor.fadeIn();
        disappear.fadeIn();
        break;
      case 'footer-bar':
        popChat.hide();
        hideBtn.hide();
        popMeta.hide();
        pos.hide();
        showOptin.fadeIn();
        avatar.hide();
        templates.hide();
        pOptions.show();
        name.hide();
        sendBtn.show();
        fomoSettings.hide();
        editor.fadeIn();
        disappear.fadeIn();
        break;
      case 'pzen-popup':
        templates.fadeIn();
        name.fadeIn();
        hideBtn.hide();
        popChat.fadeIn();
        popMeta.hide();
        pos.hide();
        showOptin.fadeIn();
        avatar.hide();
        pOptions.fadeIn();
        sendBtn.show();
        fomoSettings.hide();
        editor.fadeIn();
        disappear.fadeIn();
        break;
      case 'popout':
        popMeta.fadeIn();
        pos.fadeIn();
        showOptin.fadeIn();
        avatar.fadeIn();
        templates.hide();
        pOptions.hide();
        name.show();
        sendBtn.show();
        fomoSettings.hide();
        editor.fadeIn();
        disappear.fadeIn();
        break;
      case 'fomo':
        fomoSettings.fadeIn();
        popMeta.hide();
        popChat.hide();
        showOptin.hide();
        avatar.hide();
        templates.hide();
        pOptions.hide();
        name.hide();
        sendBtn.hide();
        hideBtn.hide();
        editor.hide();
        disappear.hide();
        break;
      default:
        popChat.fadeIn();
        hideBtn.fadeIn();
        pos.fadeIn();
        popMeta.hide();
        showOptin.fadeIn();
        avatar.fadeIn();
        templates.hide();
        pOptions.hide();
        name.hide();
        sendBtn.show();
        fomoSettings.hide();
        editor.fadeIn();
        disappear.fadeIn();
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

  pzen.toggleTimeAgo = function() {

    var fomoIntegration = $('select[name=fomo_integration]').val();

    var timeAgoOptions = $('#fomo-time_ago-settings');

    switch( fomoIntegration ) {
      case 'edd':
        timeAgoOptions.fadeIn();
        break;
      case 'woo':
        timeAgoOptions.fadeIn();
        break;
      default:
        timeAgoOptions.hide();
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