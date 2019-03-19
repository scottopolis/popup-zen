(function(window, document, $, undefined){

  var pzen = {};

  pzen.init = function() {

    pzen.listeners();
    pzen.disableInputs();
    pzen.toggleShowOn();
    pzen.toggleTypes();
    pzen.toggleDatepicker();
    pzen.toggleEmailForm();
    pzen.colors();

    $('.pzen-datepicker').datepicker({
      dateFormat : 'mm/dd/yy'
    });

    $('.pzen-colors').wpColorPicker();

  }

  pzen.colors = function() {
     
    $('.pzen-accent-color').wpColorPicker( {
        change: function( event, ui ) {
          $( '#pzen-customize-wrap .pzen-btn' ).css('background-color', event.target.value );
        },
    } );

    $('.pzen-bg-color').wpColorPicker( {
        change: function( event, ui ) {
          $( '#pzen-customize-wrap .popup-zen-box' ).css('background-color', event.target.value );
        },
    } );

    $('.pzen-text-color').wpColorPicker( {
        change: function( event, ui ) {
          $( '.pzen-content, .pzen-content .pzen-title, .pzen-content p, .popup-zen-box label' ).css( 'cssText', 'color:' + event.target.value + ' !important' );
          $( '.popup-zen-box input[type=text], .popup-zen-box input[type=email]' ).css( 'cssText', 'border-bottom-color: ' + event.target.value + ' !important;color:' + event.target.value + ' !important' );
        },
    } );

    $('.pzen-btn-text-color').wpColorPicker( {
        change: function( event, ui ) {
          $( '.popup-zen-box .pzen-btn' ).css( 'color', event.target.value );
        },
    } );

  }

  pzen.listeners = function() {

    $('.pzen-preview-btn, .pzen-close').on( 'click', function(e) {
      e.preventDefault();
      $('#pzen-customize-wrap .popup-zen-box').toggleClass('pzen-show');

      $(e.target).closest('.popup-zen-box').removeClass('pzen-expanded')
    });

    $('.pzen-expand-btn').on('click', pzen.expand )

    $('.pzen-collapse').on('click', pzen.collapse )

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
    .on('keyup', '#show_on_pages', pzen.suggestPages )

  }

  pzen.suggestPages = function( e ) {

    if( e.key == 'Backspace' )
      return;

    var text = e.currentTarget.value;
    
    if( text && text.length <= 1 )
      return;
 
    $( 'input#show_on_pages' ).autocomplete({
          source: pzen.fetchPosts,
          minLength: 3,
          delay: 500,
          select: function(event, ui) {
              var terms = this.value.split(/,\s*/);
              terms.pop();
              terms.push(ui.item.value);
              terms.push('');
              this.value = terms.join(', ');
              return false;
          }
      });

  }

  pzen.fetchPosts = function( input, suggest ) {

    var term = input.term;
    
    // substring of new string (only when a comma is in string)
    if (term.indexOf(', ') > 0) {
        var index = term.lastIndexOf(', ');
        term = term.substring(index + 2);
    }

    $.ajax({
      url: window.ajaxurl,
      data: { action: 'pzen_ajax_page_search', term: term },
      success: function( e ) {  
        suggest( e.data ); 
      }
    });

  }

  // disable inputs to prevent form submissions
  pzen.disableInputs = function() {

    $('.popup-zen-box input, .popup-zen-box .pzen-email-btn').prop('disabled', true);

  }

  pzen.expand = function(e) {

    e.preventDefault();

    var $getBox = $(e.target).closest('.popup-zen-box');

    var id = $getBox.attr('id').split('-')[1];

    $getBox.addClass('pzen-expanded');

  }

  pzen.collapse = function(e) {

    e.preventDefault();

    var $getBox = $(e.target).closest('.popup-zen-box');

    $getBox.removeClass('pzen-expanded');

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
    var name = $('#pzen-name-fields');
    var popupLinkOptions = $('#popup-link-options');

    switch( val ) {
      case 'pzen_header_bar':
        popupLinkOptions.hide();
        pos.hide();
        name.hide();
        break;
      case 'pzen_footer_bar':
        permissionBtns.fadeIn();
        popupLinkOptions.hide();
        pos.hide();
        name.hide();
        break;
      case 'pzen_popup_link':
        name.fadeIn();
        popupLinkOptions.fadeIn();
        pos.hide();
        break;
      case 'pzen_box':
        pos.fadeIn();
        popupLinkOptions.hide();
        name.show();
        break;
      default:
        popupLinkOptions.hide();
        pos.fadeIn();
        name.hide();
    }
  }

  // Handle display of different email options
  pzen.toggleEmailForm = function() {

    var noneSelected = $('#none-selected');
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

      $('#pzen-email-options').fadeIn();

    }

    if( checkedVal === 'default' ) {
      noneSelected.fadeIn();
      custom.hide();
      ckFields.hide();
      mcFields.hide();
      mailpoet.hide();
      acFields.hide();
      drip.hide();
    } else if( checkedVal === 'custom' ) {
      custom.fadeIn();
      noneSelected.hide();
      ckFields.hide();
      mcFields.hide();
      mailpoet.hide();
      acFields.hide();
      drip.hide();
    } else if( checkedVal === 'mc' ) {
      mcFields.fadeIn();
      ckFields.hide();
      custom.hide();
      noneSelected.hide();
      mailpoet.hide();
      acFields.hide();
      drip.hide();
    } else if( checkedVal === 'ck' ) {
      ckFields.fadeIn();
      mcFields.hide();
      custom.hide();
      noneSelected.hide();
      mailpoet.hide();
      acFields.hide();
      drip.hide();
    } else if( checkedVal === 'mailpoet' ) {
      mailpoet.fadeIn();
      mcFields.hide();
      custom.hide();
      noneSelected.hide();
      ckFields.hide();
      acFields.hide();
      drip.hide();
    } else if( checkedVal === 'ac' ) {
      ckFields.hide();
      mcFields.hide();
      custom.hide();
      noneSelected.hide();
      mailpoet.hide();
      acFields.fadeIn();
      drip.hide();
    } else if( checkedVal === 'drip' ) {
      drip.fadeIn();
      ckFields.hide();
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
      $('.pzen-image').attr("src", attachment.url);
    });
    // Open the uploader dialog
    mediaUploader.open();

  }

  pzen.openTab = function( evt, id ) {

    console.log( 'open tab ' + id, evt);

    evt.preventDefault();

    // Declare all variables
    var i, tabcontent, tablinks;

    // Get all elements with class="tabcontent" and hide them
    tabcontent = document.getElementsByClassName("pzen-tab-content");
    for (i = 0; i < tabcontent.length; i++) {
      tabcontent[i].style.display = "none";
    }

    // Get all elements with class="tablinks" and remove the class "active"
    tablinks = document.getElementsByClassName("pzen-tab-link");
    for (i = 0; i < tablinks.length; i++) {
      tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    // Show the current tab, and add an "active" class to the link that opened the tab
    document.getElementById(id).style.display = "block";
    evt.currentTarget.className += " active";
  }

  pzen.init();

  window.pzenAdmin = pzen;

})(window, document, jQuery);