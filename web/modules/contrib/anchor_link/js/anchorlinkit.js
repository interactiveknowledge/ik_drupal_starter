
(function ($, Drupal, drupalSettings, CKEDITOR) {
  'use strict';

  CKEDITOR.plugins.add('anchorlinkit', {icons: 'anchorlinkit'});

  CKEDITOR.on('dialogDefinition', function (ev) {
    var plugin = CKEDITOR.plugins.link,
      autocompletePath = ev.editor.config.drupalLink_dialogLinkitPath,
      initialLinkText;

    // Check if the definition is from the dialog window you are interested in
    // (the "Link" dialog window).
    if (ev.data.name == 'link') {
      var dialogDefinition = ev.data.definition;
      // Get a reference to the "Link Info" tab.
      var infoTab = dialogDefinition.getContents('info');

      var urlField = infoTab.get('url');
      urlField.onLoad = function () {

        this.allowOnChange = true;
        $('input', '#' + this.domId).addClass('form-linkit-autocomplete')
          .attr('data-autocomplete-path', autocompletePath)
          .attr('placeholder', "Start typing to find content")
          .closest('.cke_dialog_page_contents').wrap('<form></form>');

        Drupal.behaviors.linkit_autocomplete.attach($('.cke_dialog_body'));

      };
      dialogDefinition.onOk = function () {
        var data = {};

        // Collect data from fields.
        this.commitContent(data);

        if (!this._.selectedElements.length) {
          insertLinksIntoSelection(ev.editor, data);
        }
        else {
          editLinksInSelection(ev.editor, this._.selectedElements, data);

          delete this._.selectedElements;
        }
      };

      function createRangeForLink(editor, link) {
        var range = editor.createRange();

        range.setStartBefore(link);
        range.setEndAfter(link);

        return range;
      }

      function addLinkitAttributes(data, attributes){
        if (data.url.entitySub !== undefined) {
          attributes.set['data-entity-substitution'] = data.url.entitySub;
        }
        else {
          attributes.removed.push('data-entity-substitution');
        }
        if (data.url.entityUuid !== undefined) {
          attributes.set['data-entity-uuid'] = data.url.entityUuid;
        }
        else {
          attributes.removed.push('data-entity-uuid');
        }
        if (data.url.entityType !== undefined) {
          attributes.set['data-entity-type'] = data.url.entityType;
        }
        else {
          attributes.removed.push('data-entity-type');
        }
      }

      function insertLinksIntoSelection(editor, data) {
        var attributes = plugin.getLinkAttributes(editor, data),
          ranges = editor.getSelection().getRanges(),
          style = new CKEDITOR.style({
            element: 'a',
            attributes: attributes.set
          }),
          rangesToSelect = [],
          range,
          text,
          nestedLinks,
          i,
          j;

        addLinkitAttributes(data, attributes);

        style.type = CKEDITOR.STYLE_INLINE; // need to override... dunno why.

        for (i = 0; i < ranges.length; i++) {
          range = ranges[i];

          // Use link URL as text with a collapsed cursor.
          if (range.collapsed) {
            // Short mailto link text view
            // (https://dev.ckeditor.com/ticket/5736).
            text = new CKEDITOR.dom.text(data.linkText || (data.type == 'email' ?
              data.email.address : attributes.set['data-cke-saved-href']), editor.document);
            range.insertNode(text);
            range.selectNodeContents(text);
          }
          else if (initialLinkText !== data.linkText) {
            text = new CKEDITOR.dom.text(data.linkText, editor.document);

            // Shrink range to preserve block element.
            range.shrink(CKEDITOR.SHRINK_TEXT);

            // Use extractHtmlFromRange to remove markup within the selection.
            // Also this method is a little smarter than range#deleteContents
            // as it plays better e.g. with table cells.
            editor.editable().extractHtmlFromRange(range);

            range.insertNode(text);
          }

          // Editable links nested within current range should be removed, so
          // that the link is applied to whole selection.
          nestedLinks = range._find('a');

          for (j = 0; j < nestedLinks.length; j++) {
            nestedLinks[j].remove(true);
          }


          // Apply style.
          style.applyToRange(range, editor);

          rangesToSelect.push(range);
        }

        editor.getSelection().selectRanges(rangesToSelect);
      }

      function editLinksInSelection(editor, selectedElements, data) {
        var attributes = plugin.getLinkAttributes(editor, data),
          ranges = [],
          element,
          href,
          textView,
          newText,
          i;

        for (i = 0; i < selectedElements.length; i++) {
          // We're only editing an existing link, so just overwrite the
          // attributes.
          element = selectedElements[i];
          href = element.data('cke-saved-href');
          textView = element.getHtml();

          addLinkitAttributes(data, attributes);

          element.setAttributes(attributes.set);
          element.removeAttributes(attributes.removed);


          if (data.linkText && initialLinkText != data.linkText) {
            // Display text has been changed.
            newText = data.linkText;
          }
          else if (href == textView || data.type == 'email' && textView.indexOf('@') != -1) {
            // Update text view when user changes protocol
            // (https://dev.ckeditor.com/ticket/4612). Short mailto link text
            // view (https://dev.ckeditor.com/ticket/5736).
            newText = data.type == 'email' ? data.email.address : attributes.set['data-cke-saved-href'];
          }

          if (newText) {
            element.setText(newText);
          }

          ranges.push(createRangeForLink(editor, element));
        }

        // We changed the content, so need to select it again.
        editor.getSelection().selectRanges(ranges);
      }



      infoTab.elements.push({
        type: 'text',
        id: 'linkitDirtyCheck',
        label: 'Dirty Check',
        onLoad: function () {
          this.getElement().find('input').getItem(0).setAttribute('name', 'href_dirty_check');
          this.getElement().hide();
        },
        setup: function (data) {
          this.allowOnChange = false;
          if (data.url) {
            this.setValue(data.url.url);
          }
          this.allowOnChange = true;
        }
      });

      infoTab.elements.push({
        type: 'text',
        id: 'linkitEntityType',
        label: 'Entity Type',
        onLoad: function () {
          this.getElement().find('input').getItem(0).setAttribute('name', 'attributes[data-entity-type]');
          this.getElement().hide();
        },
        setup: function (data) {
          var elements = plugin.getSelectedLink(ev.editor, true),
            firstLink = elements[0] || null;

          this.allowOnChange = false;
          if (firstLink) {
            this.setValue(firstLink.getAttribute('data-entity-type'));
          }
          this.allowOnChange = true;
        },
        commit: function (data) {
          if (!data.url) {
            data.url = {};
          }
          var dialog = this.getDialog();

          if (
            this.getValue() &&
            dialog.getValueOf('info', 'url') == dialog.getValueOf('info', 'linkitDirtyCheck') &&
            dialog.getValueOf('info', 'protocol') === '' &&
            dialog.getValueOf( 'info', 'linkType' ) === 'url'
          ) {
            data.url.entityType = this.getValue();
          }
        }
      });

      infoTab.elements.push({
        type: 'text',
        id: 'linkitEntityUuid',
        label: 'Entity UUID',
        onLoad: function () {
          this.getElement().find('input').getItem(0).setAttribute('name', 'attributes[data-entity-uuid]');
          this.getElement().hide();
        },
        setup: function (data) {
          var elements = plugin.getSelectedLink(ev.editor, true),
            firstLink = elements[0] || null;

          this.allowOnChange = false;
          if (firstLink) {
            this.setValue(firstLink.getAttribute('data-entity-uuid'));
          }
          this.allowOnChange = true;
        },
        commit: function (data) {
          if (!data.url) {
            data.url = {};
          }
          var dialog = this.getDialog();

          if (
            this.getValue() &&
            dialog.getValueOf('info', 'url') == dialog.getValueOf('info', 'linkitDirtyCheck') &&
            dialog.getValueOf('info', 'protocol') === '' &&
            dialog.getValueOf( 'info', 'linkType' ) === 'url'
          ) {
            data.url.entityUuid = this.getValue();
          }
        }
      });
      infoTab.elements.push({
        type: 'text',
        id: 'linkitEntitySubstitution',
        label: 'Entity Substitution',
        onLoad: function () {
          this.getElement().find('input').getItem(0).setAttribute('name', 'attributes[data-entity-substitution]');
          this.getElement().hide();
        },
        setup: function (data) {
          var elements = plugin.getSelectedLink(ev.editor, true),
            firstLink = elements[0] || null;

          this.allowOnChange = false;
          if (firstLink) {
            this.setValue(firstLink.getAttribute('data-entity-substitution'));
          }
          this.allowOnChange = true;
        },
        commit: function (data) {
          if (!data.url) {
            data.url = {};
          }
          var dialog = this.getDialog();

          if (
            this.getValue() &&
            dialog.getValueOf('info', 'url') == dialog.getValueOf('info', 'linkitDirtyCheck') &&
            dialog.getValueOf('info', 'protocol') === '' &&
            dialog.getValueOf( 'info', 'linkType' ) === 'url'
          ) {
            data.url.entitySub = this.getValue();
          }
        }
      });

    }
  });
})(jQuery, Drupal, drupalSettings, CKEDITOR);
