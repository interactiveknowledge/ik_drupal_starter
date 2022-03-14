(function ($) {
  'use strict';

  Drupal.behaviors.ik_modals = {
    attach: function (context, settings) {
      var modalShown = false;
      var storageLastVisit = 'Drupal.ik_modals.last_visit';
      var sessionModalConverted = 'Drupal.ik_modals.converted.';
      var sessionModalDismissed = 'Drupal.ik_modals.dismissed.';
      var sessionModalSeen = 'Drupal.ik_modals.last_seen.';

      /**
       * Creates a RegExp from the given string, converting asterisks to .* expressions,
       * and escaping all other characters.
       *
       * @param {string} s string with wildcard.
       *
       * @return {object} returns regular expression object.
       */
      function wildcardToRegExp(s) {
        return new RegExp('^' + s.split(/\*+/).map(regExpEscape).join('.*') + '$');
      }

      /**
       * RegExp-escapes all characters in the given string.
       *
       * @param {string} s string with wildcard.
       *
       * @return {string} returns string in regular expression
       */
      function regExpEscape(s) {
        return s.replace(/[|\\{}()[\]^$+*?.]/g, '\\$&');
      }

      /**
       *  Method that checks if the modal should be shown at all.
       *
       * @param {string} modalId the id attribute of a modal DOM element.
       * @param {object} modalSettings the settings for the modal.
       * @param {bool} modalShown if the modal has already been shown.
       *
       * @return {bool} if the modal is active and should be shown.
       */
      function isModalAvailable(modalId, modalSettings, modalShown) {
        var isActive = false;
        var path = document.location.pathname;
        var origin = document.location.origin;
        var date = moment().format('YYYY-MM-DD');
        // Helps determine if the criteria has already failed a check.
        var failedPrevCheck = false;
        // What we have stored in cookies. Saves the last-seen date of the modal.
        var lastSeen = localStorage.getItem(sessionModalSeen + modalId);
        // Last converted
        var lastConverted = localStorage.getItem(sessionModalConverted + modalId);
        // Last Dismissed
        var lastDismissed = localStorage.getItem(sessionModalDismissed + modalId);
        // Users last visit to site.
        var lastVisit = localStorage.getItem(storageLastVisit);
        // Users location info.
        var userSettings = settings.modals.user;
        var visitExpired;
        var showAgain;
        var matched;
        var regex;
        var modalDebug = [];
        var {
          debugName,
          showAgainConvert,
          showAgainDismiss,
          showDateStart,
          showDateEnd,
          showIfReferred,
          showLocationsCountries,
          showLocationsState,
          showOnPages,
          userVisitedLast
        } = modalSettings;

        // If we have <= 0 set in backend, make it equal null
        if (parseInt(showAgainConvert) <= 0) {
          showAgainConvert = null;
        }
        if (parseInt(showAgainDismiss) <= 0) {
          showAgainDismiss = null;
        }
        if (parseInt(userVisitedLast) <= 0) {
          userVisitedLast = null;
        }

        // Check last visit setting.
        // Only show users the modal if it's been x days since last visit.
        // Additional check below if it's set and not been past yet.
        if (lastVisit !== null && userVisitedLast !== null) {
          visitExpired = moment(lastVisit).add(userVisitedLast, 'days');

          if (lastVisit && moment().isAfter(visitExpired, 'minute')) {
            isActive = true;
            modalDebug.push(isActive + '- user visited less than ' + userVisitedLast + ' days ago');
          }
        }

        // Check Page Settings.
        // If no page settings, active on all pages.
        if (showOnPages.length === 0) {
          isActive = true;
          modalDebug.push(isActive + '- because 0 showOnPages value');
        }
        else if (showOnPages.length > 0) {

          // If there are pages, activate them on the specific paths.
          matched = false;
          for (var i = 0; i < showOnPages.length; i++) {
            if (showOnPages[i].includes('*')) {
              regex = wildcardToRegExp(showOnPages[i]);

              if (path.match(regex)) {
                isActive = true;
                matched = true;
                modalDebug.push(isActive + ' because ' + path + ' matched regex.');
              }
            }
            else if (showOnPages[i] === path || showOnPages[i].replace(origin, '') === path) {
              isActive = true;
              matched = true;
              modalDebug.push(isActive + ' because ' + path + ' matched');
            }
            else if (path === '/' && (showOnPages[i] === '/home' || showOnPages[i] === '<front>')) {
              isActive = true;
              matched = true;
              modalDebug.push(isActive + ' because ' + path + ' matched');
            }
          }

          if (matched === false) {
            isActive = false;
            failedPrevCheck = true;
            modalDebug.push(isActive + ' because there were no showOnPages matches.');
          }
        }

        // Check Location Settings: Country.
        if (showLocationsCountries.length > 0 && failedPrevCheck === false) {
          if (userSettings && userSettings.country_code && showLocationsCountries.indexOf(userSettings.country_code) > -1) {
            isActive = true;
            modalDebug.push(isActive + ' because country matched: ' + userSettings.country_code);
          }
          else {
            isActive = false;
            failedPrevCheck = true;
            modalDebug.push(isActive + ' because country did not match: ' + userSettings.country_code);
          }
        }

        // Check Location Settings: State.
        if (showLocationsState.length > 0 && failedPrevCheck === false) {
          if (userSettings && userSettings.region_code && showLocationsState.indexOf(userSettings.region_code) > -1) {
            isActive = true;
            modalDebug.push(isActive + ' because state matched: ' + userSettings.region_code);
          }
          else {
            isActive = false;
            failedPrevCheck = true;
            modalDebug.push(isActive + ' because state did not match: ' + userSettings.region_code);
          }
        }

        // Check Referral Settings. (if previous check hasn't failed)
        if (showIfReferred.length > 0 && failedPrevCheck === false) {
          var parsed = document.referrer ? document.referrer.match(/^(https?\:)\/\/(([^:\/?#]*)(?:\:([0-9]+))?)([\/]{0,1}[^?#]*)(\?[^#]*|)(#.*|)$/) : null;
          var previousURL = parsed && {
            href: document.referrer,
            protocol: parsed[1],
            origin: parsed[2],
            hostname: parsed[3],
            port: parsed[4],
            pathname: parsed[5],
            search: parsed[6],
            hash: parsed[7]
          };
          matched = false;

          if (previousURL) {
            for (var i = 0; i < showIfReferred.length; i++) {
              if (showIfReferred[i].includes('*')) {
                regex = wildcardToRegExp(showOnPages[i]);

                if (previousURL.pathname.match(regex)) {
                  isActive = true;
                  matched = true;
                  modalDebug.push(isActive + ' because matched referrer url: ' + previousURL.pathname);
                }
              }
              else if (previousURL.pathname === '/' && (showIfReferred[i] === '/home' || showIfReferred[i] === '<front>')) {
                isActive = true;
                matched = true;
                modalDebug.push(isActive + ' because matched referrer url: ' + previousURL.pathname);
              }
              else if (showIfReferred[i].replace(origin, '') === previousURL.pathname && previousURL.origin === origin) {
                isActive = true;
                matched = true;
                modalDebug.push(isActive + ' because matched referrer url: ' + previousURL.pathname);
              }
              else if (showIfReferred[i] === document.referrer) {
                isActive = true;
                matched = true;
                modalDebug.push(isActive + ' because matched referrer url: ' + document.referrer);
              }
              else if (showIfReferred[i].indexOf('http') > -1) {
                var referrerParsed = showIfReferred[i].match(/^(https?\:)\/\/(([^:\/?#]*)(?:\:([0-9]+))?)([\/]{0,1}[^?#]*)(\?[^#]*|)(#.*|)$/);
                var referrer = referrerParsed && {
                  href: showIfReferred[i],
                  protocol: referrerParsed[1],
                  origin: referrerParsed[2],
                  hostname: referrerParsed[3],
                  port: referrerParsed[4],
                  pathname: referrerParsed[5],
                  search: referrerParsed[6],
                  hash: referrerParsed[7]
                };
                var referHost = referrer.hostname.replace('www.', '');
                var previousHost = previousURL.hostname.replace('www.', '');

                // If it's twitter's shortening service.
                if (referHost === 'twitter.com' && previousHost === 't.co') {
                  isActive = true;
                  matched = true;
                  modalDebug.push(isActive + ' because matched referrer url: ' + previousHost);
                }

                if (referHost === previousHost && (previousURL.pathname === referrer.pathname) || referrer.pathname === '/') {
                  isActive = true;
                  matched = true;
                  modalDebug.push(isActive + ' because matched referrer url: ' + previousURL.pathname);
                }
              }
            }
          }

          // If no matches, we need to make inactive.
          if (matched === false) {
            isActive = false;
            failedPrevCheck = true;
            modalDebug.push(isActive + ' because matched there were no matching referrer urls');
          }
        }

        // Check Date Settings.
        // Only effect if isActive is true.
        // We'll disable if dates are set and are outside today.
        if (isActive === true && showDateStart) {
          if (!showDateEnd) {
            showDateEnd = showDateStart;
          }

          if (showDateStart > date) {
            isActive = false;
          }

          if (showDateEnd < date) {
            isActive = false;
            failedPrevCheck = true;
            modalDebug.push(isActive + ' because show end date has past');
          }
        }

        // Check what cookies exist.
        // Check generic lastSeen
        if (isActive === true && lastSeen) {
          if (showAgainDismiss) {
            showAgain = moment(lastSeen).add(showAgainDismiss, 'days').format('YYYY-MM-DD hh:mm:ss');

            if (moment().isBefore(showAgain, 'minute')) {
              isActive = false;
              failedPrevCheck = true;
              modalDebug.push(isActive + ' because show again setting requirement');
            }
          }
          else {
            isActive = false;
            failedPrevCheck = true;
            modalDebug.push(isActive + ' because show again setting requirement');
          }
        }

        // Check lastDismissed
        if (isActive === true && lastDismissed) {
          if (showAgainDismiss) {
            showAgain = moment(lastDismissed).add(showAgainDismiss, 'days').format('YYYY-MM-DD hh:mm:ss');

            if (moment().isBefore(showAgain, 'minute')) {
              isActive = false;
              failedPrevCheck = true;
              modalDebug.push(isActive + ' because show again dismiss setting requirement');
            }
          }
          else {
            isActive = false;
            failedPrevCheck = true;
            modalDebug.push(isActive + ' because show again dismiss setting requirement');
          }
        }

        // Check lastDismissed
        if (isActive === true && lastConverted) {
          if (showAgainConvert) {
            showAgain = moment(lastConverted).add(showAgainConvert, 'days').format('YYYY-MM-DD hh:mm:ss');

            if (moment().isBefore(showAgain, 'minute')) {
              isActive = false;
              failedPrevCheck = true;
              modalDebug.push(isActive + ' because show again conversion setting requirement');
            }
          }
          else {
            isActive = false;
            failedPrevCheck = true;
            modalDebug.push(isActive + ' because show again conversion setting requirement');
          }
        }

        // Double check if the last visit should make active false.
        if (isActive === true && userVisitedLast !== null && lastVisit !== null) {
          visitExpired = moment().subtract(userVisitedLast, 'days').format('YYYY-MM-DD hh:mm:ss');

          if (lastVisit && moment(lastVisit).isBefore(visitExpired)) {
            isActive = false;
            failedPrevCheck = true;
            modalDebug.push(isActive + ' because show again setting requirement');
          }
        }

        // Check if we've already shown a modal.
        if (isActive === true && modalShown) {
          isActive = false;
          failedPrevCheck = true;
          modalDebug.push(isActive + ' because a modal is already active on the page. Will show next time.');
        }

        if (settings.modals && settings.modals.debug === true) {
          console.log({
            modalId,
            debugName,
            modalSettings,
            modalDebug
          });
        }

        return isActive;
      }

      /**
       * Sets the modal cookie.
       *
       * @param {string} key Storage key of the cookie.
       */
      function setModalCookie(key) {
        var time = moment().format('YYYY-MM-DD hh:mm:ss');
        localStorage.setItem(key, time);
      }

      /**
       * Sets a timeout when to show the modal.
       *
       * @param {Element} modal the modal DOM element
       * @param {string} modalId the modal ID attribute
       * @param {integer} timeout time to show the modal (in milliseconds)
       *
       */
      function showModalTimeout(modal, modalId, timeout) {
        setModalCookie(sessionModalSeen + modalId);

        setTimeout(function () {
          modal.modal('show');
        }, timeout);
      }

      /**
       * Handles internal elements in modal
       *
       * @param {Element} modal the modal DOM element
       * @param {string} modalId the modal ID attribute
       * @param {integer} convertExpiresIn number of days the cookie expires after conversion
       * @param {integer} dismissExpiresIn number of days the cookie expires after dismissal
       */
      function handleModalInternals(modal, modalId, convertExpiresIn, dismissExpiresIn) {
        // If user clicks on a link, assume it's a conversion.
        // Set a different expiration for a conversion.
        modal.find('a').each(function () {
          $(this).on('click', function (e) {
            e.preventDefault();
            setModalCookie(sessionModalConverted + modalId);
            window.location.replace($(this).attr('href'));
          });
        });

        // If user submits a form, count it as a conversion.
        // Stop the event, add our conversion cookie and then submit.
        modal.find('form').each(function () {
          $(this).on('submit', function (e) {
            e.preventDefault();
            setModalCookie(sessionModalConverted + modalId);
            $(this).submit();
          });
        });

        // Reset the expiration on modal hide.
        modal.on('hide.bs.modal', function () {
          setModalCookie(sessionModalDismissed + modalId);
        });
      }

      $(document).ready(function () {
        $('.ik-modal').each(function () {
          var modal = $(this);
          var modalId = modal.attr('id');
          var modalSettings = settings.modals[modalId];
          // Check if this modal is active.
          var isActive = isModalAvailable(modalId, modalSettings, modalShown);
          // Delay for when modal should show on page load.
          var timeout = modalSettings.delay ? (modalSettings.delay * 1000) : 3000;

          handleModalInternals(modal, modalId, modalSettings.showAgainConvert, modalSettings.showAgainDismiss);

          // only target our modal content type
          if (modalId.indexOf('modal--') > -1) {
            if (isActive && modalShown === false) {
              modalShown = true;
              showModalTimeout(modal, modalId, timeout);
            }
          }
        });

        // Set a general cookie each pageload so we can determine our last visit.
        var time = moment().format('YYYY-MM-DD hh:mm:ss');
        localStorage.setItem(storageLastVisit, time);
      });
    }
  };
})(jQuery, Drupal);
