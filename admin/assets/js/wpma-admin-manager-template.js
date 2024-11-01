document.addEventListener('DOMContentLoaded', () => {
  jQuery(document).ready(() => {
    console.log('Admin Script: Manager Template')

    eventButton();

    jQuery('.theme').click(function(e) {
      e.preventDefault();

      if (jQuery(e.target).attr('class') !== undefined && jQuery(e.target).attr('class').indexOf('button') === 0) {
        return;
      }

      detailTheme(e.currentTarget);
    });

    jQuery('#wp-filter-search-input').keyup(function(e) {
      var searchString = e.currentTarget.value;

      jQuery('.theme').find('.theme-name').each(function(index, element) {
        var themeName = element.computedName.toLowerCase();
        var themeElement = jQuery(element).parent().parent();
        console.log(themeElement)
        if (themeName.indexOf(searchString) < 0) {
          themeElement.hide();
        } else {
          themeElement.show();
        }
      });
    });
  });
}, false);

function eventButton() {
  jQuery('.theme-actions > .activate').unbind('click').click(activeTheme);
  jQuery('.theme-actions > .download').unbind('click').click(downloadTheme);
  jQuery('.theme-actions > .update').unbind('click').click(updateTheme);
  jQuery('.theme-actions > .purchase').unbind('click').click(purchaseTheme);
  jQuery('.theme-actions > .customize').unbind('click').click(customizeTheme);
}

function activeTheme(e) {
  e.preventDefault();

  var aOptions = [];
  aOptions.push({
    name: 'WPMA_TEMPLATE',
    value: e.currentTarget.dataset.id
  });

  jQuery.post({
    url: wpmaData.url.ajax,
    type: 'POST',
    dataType: 'json',
    data: {
      action: 'ajaxOptions',
      options: aOptions
    },
    success: function(response) {
      console.log(response);
      location.reload();
    }
  });
}

// Temporarily do not shorten the function

function downloadTheme(e) {
  e.preventDefault();
  jQuery.post({
    url: wpmaData.url.ajax,
    type: 'POST',
    dataType: 'json',
    data: {
      action: 'ajaxManagerTheme',
      go: 'downloadTheme',
      id: e.currentTarget.dataset.id,
    },
    success: function(response) {
      console.log(response);
      location.reload();
    }
  });
}

function updateTheme(e) {
  e.preventDefault();
  jQuery.post({
    url: wpmaData.url.ajax,
    type: 'POST',
    dataType: 'json',
    data: {
      action: 'ajaxManagerTheme',
      go: 'updateTheme',
      id: e.currentTarget.dataset.id,
    },
    success: function(response) {
      console.log(response);
      location.reload();
    }
  });
}

function purchaseTheme(e) {
  e.preventDefault();
  jQuery.post({
    url: wpmaData.url.ajax,
    type: 'POST',
    dataType: 'json',
    data: {
      action: 'ajaxManagerTheme',
      go: 'purchaseTheme',
      id: e.currentTarget.dataset.id,
    },
    success: function(response) {
      console.log(response);
      location.reload();
    }
  });
}

function customizeTheme(e) {
  e.preventDefault();
  var WPMACookie = Cookies.noConflict();
  WPMACookie.set('wpma_mobile_mode', true, {
    expires: 7,
    path: '/'
  });
  location.href = wpmaData.url.customizer;
}

function detailTheme(e) {
  if (e === undefined) {
    return;
  }

  var sCurrent = '',
    sAcitve = '',
    themeOverlay = jQuery('.theme-overlay');

  if (e.dataset.is_active === "1") {
    sCurrent = `<span class="current-label">` + themeOverlay.data('text-current') + `</span>`;
  }

  sButton = `<div class="theme-actions">` + e.dataset.button + `</div>`;

  var sDisableLeft = '';
  if (!e.dataset.previous) {
    sDisableLeft = ' disabled';
  }

  var sDisableRight = '';
  if (!e.dataset.next) {
    sDisableRight = ' disabled';
  }

  var sHTML = `<div class="theme-backdrop"></div>
    <div class="theme-wrap wp-clearfix">
      <div class="theme-header">
        <button class="left dashicons dashicons-no` + sDisableLeft + `"  id="` + e.dataset.previous + `">
          <span class="screen-reader-text">` + themeOverlay.data('text-previous') + `</span>
        </button>
        <button class="right dashicons dashicons-no` + sDisableRight + `" id="` + e.dataset.next + `">
          <span class="screen-reader-text">` + themeOverlay.data('text-next') + `</span>
        </button>
        <button class="close dashicons dashicons-no">
          <span class="screen-reader-text">` + themeOverlay.data('text-close') + `</span>
        </button>
      </div>
      <div class="theme-about wp-clearfix">
        <div class="theme-screenshots">
          <div class="screenshot">
            <img src="` + e.dataset.screenshot + `" alt=""/>
          </div>
        </div>
        <div class="theme-info">
          ` + sCurrent + `
          <h2 class="theme-name">
            ` + e.dataset.name + `
            <span class="theme-version">` + themeOverlay.data('text-version') + `: ` + e.dataset.version + `</span>
          </h2>
          <p class="theme-author">
            ` + themeOverlay.data('text-by') + `
            <a href="` + e.dataset.author_uri + `">` + e.dataset.author + `</a>
          </p>
          <p class="theme-description">` + e.dataset.description + `</p>
          <p class="theme-tags">
            <span>` + themeOverlay.data('text-tag') + `:</span>
            ` + e.dataset.tag + `
          </p>
        </div>
      </div>
      ` + sButton + `
    </div>`;

  themeOverlay.html(sHTML);

  jQuery('.theme-header > .left').click(function(e) {
    e.preventDefault();
    detailTheme(jQuery('#' + e.currentTarget.id)['0']);
  });

  jQuery('.theme-header > .right').click(function(e) {
    e.preventDefault();
    detailTheme(jQuery('#' + e.currentTarget.id)['0']);
  });

  jQuery('.theme-header > .close').click(function(e) {
    e.preventDefault();
    themeOverlay.html('');
  });

  eventButton();
}