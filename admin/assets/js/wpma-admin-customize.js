document.addEventListener('DOMContentLoaded', () => {
	jQuery(document).ready(() => {
		console.log('TEMPLATE: WPMA Customizer initialize..')
		var readyStateCheckInterval = setInterval(function() {
			iframeurl = jQuery('#customize-preview iframe').attr('src');
			if (iframeurl !== undefined) {
				clearInterval(readyStateCheckInterval);
				wpmaSwitchButtonTheme(iframeurl);
			}
		}, 10);
	});
}, false);

function wpmaSwitchButtonTheme(url) {
	var WPMACookie = Cookies.noConflict();

	// Add switch theme button
	jQuery('#customize-info .accordion-section-title').append('<div><button style="margin-top: 10px;" id="switch_to" class="button">' + wpma.switchmobile + '</button></div>');
	var isMobile = WPMACookie.get('wpma_mobile_mode');

	var switchMode = isMobile === 'true' ? false : true;

	if (isMobile === 'false') {
		jQuery('#switch_to').text(wpma.switchmobile);
	} else {
		jQuery('#switch_to').text(wpma.switchdesktop);
		iframeurl = jQuery('#customize-preview iframe').attr('src');
		jQuery('#customize-preview iframe').attr('src', iframeurl + '&wpma_template');
	}

	// Button event
	jQuery('#switch_to').click(function(e) {
		e.preventDefault();
		e.stopImmediatePropagation();
		WPMACookie.set('wpma_mobile_mode', switchMode, {
			expires: 7,
			path: '/'
		});
		window.location.reload();
	});
}