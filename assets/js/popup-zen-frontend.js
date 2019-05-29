(function(window, document, $, undefined){

  var pzen = {};

  pzen.init = function() {

    // set defaults
    pzen.newVisitor = false;

    pzen.checkForPreview();

  }

  // Polyfill for Date.now() on IE
  if ( ! Date.now ) {

    Date.now = function now() {

      return new Date().getTime();

    };

  }

  function pzenGetUrlParameter( name ) {

    name = name.replace( /[\[]/, '\\[').replace(/[\]]/, '\\]' );

    var regex = new RegExp( '[\\?&]' + name + '=([^&#]*)' );

    var results = regex.exec( location.search );

    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));

  }

  // if using the ?pzen_preview=ID query string, show it no matter what
  pzen.checkForPreview = function() {

    if( pzenGetUrlParameter( 'pzen_preview' ) ) {

      var id = pzenGetUrlParameter( 'pzen_preview' );

      pzen.setCookie( 'pzen-' + id + '_hide', '', -1 );

      pzen.showItem( id );

      pzen.noteListeners( id );

      return;

    }

    // No preview, continue...
    // determine if new or returning visitor
    pzen.checkCookie();

    // if we have active items, loop through them
    if( window.popupZenVars.active )
      pzen.doActive( window.popupZenVars.active );

  }

  // determine if new or returning visitor
  pzen.checkCookie = function() {

    if( pzen.getCookie('pzen_visit') === "" ) {

      // New visitor, set visitor cookie. This tracks original visit
      pzen.setCookie('pzen_visit', Date.now(), parseInt( window.popupZenVars.expires ));
      pzen.setCookie('pzen_new', 'true', 1 );
      pzen.newVisitor = true;

    } else if( pzen.getCookie('pzen_new') != "" ) {
      pzen.newVisitor = true;
    }

  }

  // Notification box exists
  pzen.doActive = function( ids ) {

    for (var i = 0; i < ids.length; i++) {
      pzen.doChecks( ids[i] );
    }

  }

  // Check if we should display item
  pzen.doChecks = function( id, forceShow ) {

    // If markup doesn't exist, bail
    var item = document.getElementById( 'pzen-' + id );
    if( !item )
      return;

    var vars = window.popupZenVars[id];

    if( !vars.visitor )
      return;

    if( vars.visitor === 'new' && pzen.newVisitor != true )
      return;

    if( vars.visitor === 'returning' && pzen.newVisitor != false )
      return;

    // hide after interacted with?
    if( vars.showSettings === 'interacts' && pzen.getCookie( 'pzen_' + id + '_int' ) != '' )
      return;

    // maybe hide on certain devices
    if( window.popupZenVars.isMobile === "" && vars.devices === "mobile_only" ) {
      return;
    } else if( window.popupZenVars.isMobile === "1" && vars.devices === "desktop_only" ) {
      return;
    }

    var shown = pzen.getCookie( 'pzen_' + id + '_shown' );

    // only show once?
    if( vars.showSettings === 'hide_for' && shown === 'true' && !forceShow )
      return;

    // passes checks, show it

    if( vars.display_when === 'exit' ) {

      // bail if it's been closed already
      if( pzen.getCookie( 'pzen-' + id + '_hide' ) === 'true' )
        return;

      // add exit listener
      $('body').on( 'mouseleave', pzen.mouseExit );

    }

    // Delay showing item?
    if( vars.display_when != 'scroll' ) {

      // don't show yet if using exit detect or link activation. Shows when we use the forceShow argument
      if( vars.display_when === 'exit' && !forceShow || vars.display_when === 'link' && !forceShow )
        return;

      var delay = ( vars.display_when === 'delay' ? parseInt( vars.delay ) : 0 );

      // should we show popup?
      if( vars.type === 'pzen-popup' && vars.showSettings === 'always' ) {

        // remove cookie so popup shows properly
        pzen.setCookie( 'pzen-' + id + '_hide', '', -1 );

      } else if( vars.type === 'pzen-popup' && vars.showSettings === 'interacts' && pzen.getCookie( 'pzen-' + id + '_hide' ) === 'true' ) {

        // don't show popup if user has hidden
        return;

      }

      setTimeout( function() {
        pzen.showItem( id );

        // Track that note was shown. Here because this loads once per page, showItem() loads on hide/show, too many times.
        pzen.trackingAndListeners(id);

      }, delay * 1000 );

    } else {

      // Use scroll detect setting
      pzen.detectScroll( id );

    }

  }

  // Event listeners
  pzen.noteListeners = function( id ) {

    $('body')
    .on('click', '.pzen-close', pzen.closeClick )
    .on('click', '#pzen-' + id + ' .pzen-email-btn', pzen.emailSubmitClick )
    .on('focus', '.pzen-input input', function(e) {
      $( e.currentTarget ).addClass('input-filled');
    })
    .on('click', '.pzen-backdrop', pzen.bdClick );

    $('#pzen-' + id + ' .pzen-email-input').on('keypress', pzen.submitEmailOnEnter );

    $('#pzen-' + id + ' a').on('click', pzen.interactionLink )

    $('.pzen-expand-btn').on('click', pzen.expand )

    $('.pzen-collapse').on('click', pzen.collapse )

  }

  pzen.expand = function(e) {

    e.preventDefault();

    var $getBox = $(e.target).closest('.popup-zen-box');

    var id = $getBox.attr('id').split('-')[1];

    $getBox.addClass('pzen-expanded');

    if( $('input.pzen-name') ) {
      $('input.pzen-name').focus().addClass('input-filled');
    } else {
      $('input.pzen-email-input').focus().addClass('input-filled');
    }

    // maybe show backdrop
    if( $getBox.hasClass('pzen-popup') ) {
      pzen.transitionIn( $('#pzen-bd-' + id) );
    }

  }

  pzen.collapse = function(e) {

    e.preventDefault();

    var $getBox = $(e.target).closest('.popup-zen-box');

    $getBox.removeClass('pzen-expanded');

    var id = $getBox.attr('id').split('-')[1];

    $( '#pzen-' + id + ' input' ).val('').removeClass('input-filled');

    // maybe show backdrop
    if( $getBox.hasClass('pzen-popup') ) {
      pzen.transitionOut( $('#pzen-bd-' + id) );
    }

  }

  // detect when user scrolls partway down the page
  // https://www.sitepoint.com/jquery-capture-vertical-scroll-percentage/
  pzen.detectScroll = function( id ) {

    $(window).scroll(
      // debounce so we don't adversely affect scroll performance
      pzen.debounce( function() {

        var wintop = $(window).scrollTop(), docheight = $(document).height(), winheight = $(window).height();
        var  scrolltrigger = ( window.popupZenVars[id].scrollPercent ? parseInt( window.popupZenVars[id].scrollPercent ) / 100 : 0.5 );

        // when user scrolls below fold, show it
        if( (wintop/(docheight-winheight)) > scrolltrigger && !pzen.show['pzen-' + id] ) {
          pzen.showItem( id );
          pzen.show['pzen-' + id] = true

          // track
          pzen.trackingAndListeners(id);
        }
      }, 100) )

  }

  // Show/hide elements based on options object
  pzen.showItem = function( id ) {

    var options = window.popupZenVars[id];

    var item = document.getElementById( 'pzen-' + id );

    // Show the box and what's in it
    pzen.transitionIn( item );

  }

  // Set a cookie. https://www.w3schools.com/js/js_cookies.asp
  pzen.setCookie = function(cname, cvalue, exdays, path) {
      var d = new Date();
      d.setTime(d.getTime() + (exdays*24*60*60*1000));
      var expires = "expires="+ d.toUTCString();
      if( !path )
        path = '/';
      document.cookie = cname + "=" + cvalue + ";" + expires + ";path=" + path;
      return cvalue;
  }

  // Get a cookie by name. https://www.w3schools.com/js/js_cookies.asp
  pzen.getCookie = function(cname) {
      var name = cname + "=";
      var decodedCookie = decodeURIComponent(document.cookie);
      var ca = decodedCookie.split(';');
      for(var i = 0; i <ca.length; i++) {
          var c = ca[i];
          while (c.charAt(0) == ' ') {
              c = c.substring(1);
          }
          if (c.indexOf(name) == 0) {
              return c.substring(name.length, c.length);
          }
      }
      return "";
  }

  // Reusable function to throttle or debounce function calls
  pzen.debounce = function(fn, delay) {
    var timer = null;
    return function () {
      var context = this, args = arguments;
      clearTimeout(timer);
      timer = setTimeout(function () {
        fn.apply(context, args);
      }, delay);
    };
  }

  // Add top-margin to body when banner is present
  pzen.toggleBnrMargin = function( id, hide ) {

    // reset
    $('body').css('padding-top', '');

    if( !hide ) {
      var height = $('#pzen-' + id).outerHeight();
      $('body').css('padding-top', height);
    }
    
  }

  // clicked close btn
  pzen.closeClick = function(e) {

    e.stopImmediatePropagation();

    var closest = $(e.target).closest('.popup-zen-box');

    var id = closest.attr('id').split('-')[1];

    pzen.hideItem( id );

  }

  // User clicked hide
  pzen.hideItem = function( id ) {

    pzen.transitionOut( $('#pzen-' + id ) );

    if( $('#pzen-' + id ).hasClass('pzen-popup') ) {
      pzen.transitionOut( $('#pzen-bd-' + id) );
    }

    pzen.setCookie( 'pzen-' + id + '_hide', 'true', 1 );
    pzen.setCookie('pzen_' + id + '_int', 'true', 1 );

    // prevent duplicate firing
    return false;

  }

  pzen.transitionIn = function(item) {

    $(item).css('display','block');

    setTimeout( function() {
      $(item).addClass('pzen-transition-in').removeClass('pzen-hide');
    }, 1);

    setTimeout( function() {
      $(item).removeClass('pzen-transition-in').addClass('pzen-show');
    }, 300);

  }

  pzen.transitionOut = function(item) {
    
    $(item).addClass('pzen-transition-out').removeClass('pzen-show');

    setTimeout( function() {
      $(item).removeClass('pzen-transition-out').addClass('pzen-hide');
      $(item).css('display','');
    }, 200);

  }

  pzen.show = function(item) {
    
    item.style.display = 'block';
    $(item).removeClass('pzen-hide').addClass('pzen-show');

  }

  pzen.hide = function(item) {
    
    $(item).removeClass('pzen-show').addClass('pzen-hide');
    $(item).css('display','');

  }

  // Detect enter key
  pzen.submitEmailOnEnter = function(e) {

    if ( e.target.value && e.target.value != '' && e.keyCode == 13 ) {

      var id = $(e.target).closest('.popup-zen-box').attr('id').split('-')[1];

      e.preventDefault();

      pzen.emailSubmitted( id );

    }

  }

  // handle click of email submit btn
  pzen.emailSubmitClick = function(e) {

    e.stopImmediatePropagation();
    e.preventDefault();

    var id = $(e.target).closest('.popup-zen-box').attr('id').split('-')[1];

    pzen.emailSubmitted( id );
  }

  // User submitted email, send to server
  pzen.emailSubmitted = function( id ) {

    var email = $('#pzen-' + id + ' .pzen-email-input input[type=email]').val();

    if( !email ) {
      alert( window.popupZenVars.emailErr + ' err1' );
      return;
    }

    var name = $('#pzen-' + id + ' .pzen-name').val();

    var title = $('#pzen-' + id + ' .pzen-title').text();

    // validate email
    if( email.indexOf('@') === -1 || email.indexOf('.') === -1 ) {
      alert( window.popupZenVars.emailErr + ' err2')
      return;
    }

    // honeypot
    if( $( '#pzen-' + id + ' input[name=pzen_hp]').val() != "" )
      return;

    $( '#pzen-' + id ).addClass('pzen-loading');

    // do different things for email providers
    if( window.popupZenVars[id].emailProvider === 'ck' ) {
      pzen.ckSubscribe( email, id );
      return;
    } else if( window.popupZenVars[id].emailProvider === 'mc' ) {
      pzen.mcSubscribe( email, id );
      return;
    } else if( window.popupZenVars[id].emailProvider === 'ac' ) {
      pzen.acSubscribe( email, id );
      return;
    } else if( window.popupZenVars[id].emailProvider === 'mailpoet' ) {
      pzen.mpSubscribe( email, id );
      return;
    } else if( window.popupZenVars[id].emailProvider === 'drip' ) {
      pzen.dripSubscribe( email, id );
      return;
    } else if( window.popupZenVars[id].emailProvider === 'default' ) {
      pzen.sendEmail( email, id );
      return;
    }

  }

  // Send email
  pzen.sendEmail = function( email, id ) {

    $.ajax({
      method: "GET",
      url: window.popupZenVars.ajaxurl,
      data: { email: email, id: id, action: 'pzen_send_email', nonce: window.popupZenVars.pzenNonce }
      })
      .done(function(msg) {

        pzen.showConfirmation( id );

        pzen.conversion( id );

      })
      .fail(function(err) {
        console.log(err);
        pzen.resetForm( id );
      });
  }


  // ConvertKit subscribe through API
  pzen.ckSubscribe = function( email, id ) {

    var options = window.popupZenVars[id];

    var formId = $('#pzen-' + id + ' .ck-form-id').val();
    var apiUrl = 'https://api.convertkit.com/v3/forms/' + formId + '/subscribe';

    var name = $('#pzen-' + id + ' .pzen-name').val();

    $.ajax({
      method: "POST",
      url: apiUrl,
      data: { email: email, api_key: options.ckApi, first_name: name }
      })
      .done(function(msg) {

        // reset to defaults
        pzen.showConfirmation( id );
        pzen.conversion( id );

      })
      .fail(function(err) {

        pzen.resetForm( id );

        var msg = 'There seems to be a problem, can you try again?';

        if( err.responseJSON && err.responseJSON.error ) {
          msg = err.responseJSON.error;

          if( err.responseJSON.message ) {
            msg += ' ' + err.responseJSON.message;
          }
        }

        pzen.doError( msg, id );

        console.log(err);
      });

  }

  // Submit to MailChimp
  pzen.mcSubscribe = function( email, id ) {

    var listId = $('#pzen-' + id + ' .mc-list-id').val();
    var name = $('#pzen-' + id + ' .pzen-name').val();

    var interestIds = $('#pzen-' + id + ' .mc-interests').val();
    if( interestIds )
      interestIds = JSON.parse( interestIds );

    if( !listId ) {
      alert("MailChimp list ID is missing.");
      pzen.resetForm( id );
      return;
    }

    $.ajax({
      method: "GET",
      url: window.popupZenVars.ajaxurl,
      data: { email: email, list_id: listId, action: 'pzen_mc_subscribe', interests: interestIds, nonce: window.popupZenVars.pzenNonce, name: name }
      })
      .done(function(msg) {

        // reset to defaults
        pzen.showConfirmation( id );
        pzen.conversion( id );

      })
      .fail(function(err) {
        console.log(err);
        pzen.resetForm( id );
        pzen.doError( 'There was a problem.', id );
      });

  }

  // Submit to Active Campaign
  pzen.acSubscribe = function( email, id ) {

    var listId = $('#pzen-' + id + ' .ac-list-id').val();
    var name = $('#pzen-' + id + ' .pzen-name').val();

    if( !listId ) {
      alert("List ID is missing.");
      pzen.resetForm( id );
      return;
    }

    $.ajax({
      method: "GET",
      url: window.popupZenVars.ajaxurl,
      data: { email: email, list_id: listId, action: 'pzen_ac_subscribe', nonce: window.popupZenVars.pzenNonce, name: name }
      })
      .done(function(msg) {

        // console.log(msg)

        if( msg.success == true ) {

          // reset to defaults
          pzen.showConfirmation( id );
          pzen.conversion( id );

        } else {
          console.warn(msg)
          $('#pzen-' + id + ' .pzen-content').html('<p>' + msg.data + '</p>');
          pzen.resetForm( id );
        }

      })
      .fail(function(err) {
        console.warn(err);
        pzen.resetForm( id );
        pzen.doError( 'There was a problem.', id );
      });

  }

  // Submit to Drip
  pzen.dripSubscribe = function( email, id ) {

    if( !window._dcq ) {
      alert("Drip code not installed properly.");
      pzen.resetForm( id );
      return;
    }

    var tags = $('#pzen-' + id + ' .drip-tags').val();
    var name = $('#pzen-' + id + ' .pzen-name').val();

    var tagArr = tags.split(",");

    pzen.dripid = id;

    var response = _dcq.push(["identify", {
      email: email,
      first_name: name,
      tags: tagArr,
      success: pzen.dripResponse,
      failure: pzen.dripResponse
    }]);

  }

  pzen.dripResponse = function( response ) {

    pzen.resetForm( id );

    if( response.success == true ) {

      // reset to defaults
      pzen.showConfirmation( pzen.dripid );
      pzen.conversion( pzen.dripid );

    } else {
      console.warn(response);
      pzen.doError( response.error, id );
    }

  }

  // Submit to MailPoet
  pzen.mpSubscribe = function( email, id ) {

    var listId = $('#pzen-' + id + ' .mailpoet-list-id').val();

    if( !listId ) {
      alert("List ID is missing.");
      return;
    }

    var name = $('#pzen-' + id + ' .pzen-name').val();

    $.ajax({
      method: "GET",
      url: window.popupZenVars.ajaxurl,
      data: { email: email, list_id: listId, action: 'pzen_mailpoet_subscribe', nonce: window.popupZenVars.pzenNonce, name: name }
      })
      .done(function(msg) {

        // console.log(msg)

        // reset to defaults
        pzen.showConfirmation( id );
        pzen.conversion( id );

      })
      .fail(function(err) {
        console.log(err);
        pzen.resetForm( id );
        pzen.doError( response.error, id );
      });

  }

  // callback when interaction link clicked
  pzen.interactionLink = function(e) {

    // don't count attribution clicks as conversions
    if( e.target.href === 'https://getpopupzen.com/' ) 
      return;

    var id = $(e.target).closest('.popup-zen-box').attr('id').split('-')[1];

    pzen.conversion( id );
  }

  // Callback for user interaction
  pzen.conversion = function( id ) {

    var params = { action: 'pzen_track_event', nonce: window.popupZenVars.pzenNonce, id: id };

    // store interaction data
    $.ajax({
      method: "GET",
      url: window.popupZenVars.ajaxurl,
      data: params
      })
      .done(function(msg) {

        var redirect = window.popupZenVars[id].redirect;

        if( redirect ) {

          $('#pzen-' + id + ' .pzen-content').append( ' Redirecting... <img src="' + window.popupZenVars.pluginUrl + 'assets/img/loading.gif" class="pzen-loading" />');

          setTimeout( function() {
            window.location.href = redirect;
          }, 1000);

        }

      })
      .fail(function(err) {
        console.log(err);
      });

    pzen.setCookie('pzen_' + id + '_int', 'true', 1 );

  }

  // show confirmation message after email submitted
  pzen.showConfirmation = function( id ) {

    $('.pzen-email-btn span').css('opacity', 0 );

    pzen.resetForm( id );

    var options = window.popupZenVars[id];

    var msg = ( options.confirmMsg != '' ? options.confirmMsg : "Thanks!" );

    $('#pzen-' + id + ' .pzen-form').after('<div class="pzen-confirmation-message"><svg class="pzen-checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52"><circle class="pzen-checkmark__circle" cx="26" cy="26" r="25" fill="none"/><path class="pzen-checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/></svg>' + msg + '</div>');

    setTimeout( function() {

      $('#pzen-' + id ).removeClass('pzen-loading').addClass('pzen-success');

    }, 100);

    setTimeout( function() {
      $('#pzen-' + id + ' .pzen-close').click();
    }, 4000 );

  }

  // Callback for tracking
  pzen.trackingAndListeners = function( id ) {

    // add click listeners and such. Doing it here because it's most reliable way of knowing when note is actually shown on page.
    pzen.noteListeners( id );

    var options = window.popupZenVars[id];

    var hideFor = ( options.hideForDays ? parseInt( options.hideForDays ) : 1 );
    pzen.setCookie( 'pzen_' + id + '_shown', 'true', hideFor );

    // should we track impressions via GA?
    if( window.popupZenVars.ga_tracking != '1' || typeof 'ga' != "function" ) {
      return;
    }

    // store interaction data
    ga(
      'send',
      'event',
      'popup-zen-impression',
      'impression',
      'popup-zen',
      id
    );

  }

  pzen.resetForm = function( id ) {
    $( '#pzen-' + id ).removeClass('pzen-loading');
  }

  pzen.doError = function( msg, id ) {

    $('#pzen-' + id + ' #pzen-err').text( msg ).addClass('show-error');

    setTimeout( function() {
      $('#pzen-' + id + ' #pzen-err').removeClass('show-error').text('');
    }, 5000);

  }

  pzen.bdClick = function(e) {

    e.stopImmediatePropagation();

    var id = $(e.currentTarget).data('id');

    pzen.hideItem( id );

    pzen.transitionOut( $('#pzen-bd-' + id ) );

  }

  // detect mouse leave and show a box
  pzen.mouseExit = function(e) {

    var el = $('.popup-zen-box.pzen-show-on-exit')[0];

    if( !el )
      return;

    if( $(el).hasClass('pzen-show') )
      return;
    
    var id = el.id.split('-')[1];

    pzen.doChecks( id, true );

  }

  $(window).on( 'load', function() {
    pzen.init();
  });

  window.popupZen = pzen;

})(window, document, jQuery);