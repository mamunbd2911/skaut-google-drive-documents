'use strict';

jQuery(document).ready(function ($) {
  function showDir(path) {

    /* If path is not initialized */
    if (!(path instanceof Array)) {
      path = [];
    }

    $('#rootPath tbody tr').not('.loadingCircle').fadeOut();
    $('.loadingCircle').fadeIn('slow');

    $.ajax({
      url: sgddRootPathLocalize.ajaxUrl,
      type: 'GET',
      data: {
        action: 'listDrive',
        path: path,
        _ajax_nonce: sgddRootPathLocalize.nonce // eslint-disable-line camelcase
      },
      success: function (response) {
        var html = '';
        var i;

        $('.loadingCircle').fadeOut();
        $('#rootPath tbody tr').not('.loadingCircle').fadeIn('slow');

        /* Print path */
        if (0 < path.length) {
          html += '<a data-id="">' + sgddRootPathLocalize.driveList + '</a> > ';
          for (i = 0; i < response.pathNames.length; i++) {
            if (0 < i) {
              html += ' > ';
            }
            html += '<a data-id="' + path[i] + '">' + response.pathNames[i] + '</a>';
          }
        } else {
          html += '<a data-id="">' + sgddRootPathLocalize.driveList + '</a>';
          $('#submit').attr('disabled', 'disabled');
        }
        $('.tablePath').html(html);

        /* Up directory dots */
        html = '<tr class="loadingCircle"></tr>';
        if (0 < path.length) {
          html += '<tr><td class="row-title"><label>..</label></tr>';
          $('.tableBody').html(html);
        }

        /* List dir content */
        for (i = 0; i < response.content.length; i++) {
          html += '<tr class="';

          if (0 == i % 2) {
            html += 'alternate';
          }

          html += '"><td class="row-title"><label data-id="' + response.content[i].pathId + '">' + response.content[i].pathName + '</label>';
        }
        $('.tableBody').html(html);

        $('.tableBody label').click(function () {
          dirClick(path, this);
          $('#submit').removeAttr('disabled');
        });

        $('.tablePath a').click(function () {
          pathClick(path, this);
          $('#submit').removeAttr('disabled');
        });

        $('#sgdd_root_path').val(JSON.stringify(path));
      },
      error: function (response) {
        var html = '<div class="notice notice-error"><p>' + response.error + '</p></div>';
        $('#rootPath').replaceWith(html);
      }
    });
  }

  /**
   * Reloads table content on path click
   *
   * @param {string[]} path - Array of folder ids from root
   * @param {*} element
   */
  function pathClick(path, element) {
    var elementIndex = path.indexOf($(element).data('id'));
    var newPath = path.slice(0, elementIndex + 1);

    showDir(newPath);
  }

  /**
   * Reloads table content on directory click
   *
   * @param {string[]} path - Array of folder ids from root
   * @param {*} element
   */
  function dirClick(path, element) {
    var newID = $(element).data('id');

    if (newID) {
      path.push(newID);
    } else {
      path.pop();
    }

    showDir(path);
  }

  showDir(sgddRootPathLocalize.path);
});
