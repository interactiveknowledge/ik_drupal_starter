(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.ikBehaviors = {
    attach: function (context, settings) {
      if (settings['ik_d8_theme_api'] && settings['ik_d8_theme_api'].frontendUrl) {
        var url = settings['ik_d8_theme_api'].frontendUrl,
            isAdmin = settings['ik_d8_theme_api'].isAdmin,
            currentPath = settings['ik_d8_theme_api'].currentPath,
            forwarding = settings['ik_d8_theme_api'].forwarding,
            frontendUrl = url + currentPath,
            vid = settings['ik_d8_theme_api'].vid ? '?v=' + settings['ik_d8_theme_api'].vid : '',
            iframeUrl = settings['ik_d8_theme_api'].frontendIframeUrl + currentPath + vid,
            showIframe = settings['showIframe']

            height = $(window).height() - $('header').height() - $('.region-pre-content').height();

        /** 
         * If we have forwarding active and current path is not an admin page:
         * If user is loggedin, then we'll show an iframe with the current page.
         * If not, we'll just redirect the whole page to the right URL.
         */
        if (currentPath && !isAdmin) {
          if ($('body').hasClass('user-logged-in')) {
            if (showIframe) {
              $('#block-ik-d8-theme-api-content').height(height);
              $('#block-ik-d8-theme-api-content').empty().append('<iframe src="' + iframeUrl + '" style="width: 100%; height: 100%;"/>');
              $('#block-ik-d8-theme-api-local-tasks').find('.tabs.primary').append('<li class="tabs__tab"><a href="' + frontendUrl + '" target="_blank" rel="nofollow noreferrer">Open Page in New Window</a></li>');
            }
          } else if (forwarding && !isAdmin) {
            $('body').hide();
            window.location.replace(frontendUrl);
          } else {
            if (currentPath.indexOf('user/login') < 0) {
              $('body').hide();
              window.location.replace(window.location.origin + '/user/login?destination=' + currentPath);
            }
          }
        }
      }
    }
  };
})(jQuery, Drupal);