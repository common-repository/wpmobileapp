document.addEventListener('DOMContentLoaded', () => {
	jQuery(document).ready(() => {

		console.log('Admin Script: General', wpmaData)

		if (wpmaData.serviceWorker.enable != 1) {
			console.log('CLIENT: serviceWorker is disable.');
			unregisterServiceWorker();
		}

		// Materialize Tab
		jQuery('.tabs').tabs({
			swipeable: false,
			duration: 200
		});

		// Remember selected tab
		jQuery('.tabs').on('click', 'a', e => {
			Cookies.set('wpma_tab_setting', e.currentTarget.hash, {
				expires: 7,
				path: '/wp-admin'
			});
		});

		// Color picker
		jQuery('.wpma-color-picker').iris();

		// Materialize Select
		M.Select.getInstance(jQuery('select.wpma-select'));

		// Materialize Switch
		jQuery('.lever').on('click', function(e) {
			var curInput = jQuery(this).siblings('input')[0];
			// if input checked is switch to off 
			jQuery(curInput).val(curInput.checked ? 0 : 1);
		});

		// Materialize Chip
		jQuery('.chips').chips();

		for (id in wpmaData.settings) {
			if (wpmaData.settings[id].type === 'chips') {
				jQuery('#'+id).chips(wpmaData.settings[id]);
			}
		}

		// Add chip to input value (json)
		jQuery('.chips').on('chip.add', function(e, chip) {
			var inputTarget = jQuery('input[name="' + e.target.id + '"]');
			var oValue = [chip.tag];
			try {
				oValue = JSON.parse(inputTarget.val());
				oValue.push(chip.tag);
			} catch (e) {
				console.log(e, inputTarget.val())
			}
			inputTarget.val(JSON.stringify(oValue));
		});

		// Remove chip form input value (json)
		jQuery('.chips').on('chip.delete', function(e, chip) {
			var inputTarget = jQuery('input[name="' + e.target.id + '"]');
			var oValue = [chip.tag];
			try {
				var oValue = JSON.parse(inputTarget.val());
				oValue.map(function(aChip, i) {
					if (aChip === chip.tag) {
						oValue.splice(i, 1);
						return;
					}
				})
			} catch (e) {
				console.log(e, inputTarget.val())
			}
			inputTarget.val(JSON.stringify(oValue));
		});

		// Media Uploader
		var mediaUploader, curImage;
		jQuery('.wpma-media-upload img').click(function(e) {
			e.preventDefault();
			curImage = jQuery(this);
			//  If the uploader object has already been created, reopen the dialog
			if (mediaUploader) {
				mediaUploader.open();
				return;
			}
			//  Extend the wp.media object
			mediaUploader = wp.media({
				title: wpmaData.translate.chooseImage,
				button: {
					text: wpmaData.translate.chooseImage
				},
				multiple: false
			});
			//  When a file is selected, grab the URL and set it as the text field's value
			mediaUploader.on('select', function() {
				var attachment = mediaUploader.state().get('selection').first().toJSON();
				curImage.attr("src", attachment.url);
				jQuery('#' + curImage.attr("data")).attr("value", attachment.url);
			});
			//  Open the uploader dialog
			mediaUploader.open();
		});

		//  Can add button get token from server by js instead of callback in settings_field // 

		// Submit Ajax
		jQuery('#wpmaOptionsForm').submit(e => {
			// Get all options
			var aOptions = jQuery(e.currentTarget).serializeArray(),
				names = (function() {
					var n = [],
						l = aOptions.length - 1;
					for (; l >= 0; l--) {
						n.push(aOptions[l].name);
					}

					return n;
				})();

			// Include checkbox not checked
			jQuery('input[type="checkbox"]:not(:checked)').each(function() {
				if (jQuery.inArray(this.name, names) === -1) {
					aOptions.push({
						name: this.name,
						value: '0'
					});
				}
			});

			e.preventDefault();

			jQuery.post({
				url: wpmaData.url.ajax,
				type: 'POST',
				data: {
					action: 'ajaxOptions',
					options: aOptions
				},
				success: response => {
					var toastContent = jQuery('<span>' + wpmaData.translate.saveSuccess + '</span>');
					M.toast({
						html: toastContent
					})

					if (aOptions[0]['name'] === 'WPMA_TOKEN' && aOptions.length === 1) {
						location.reload();
					}
				}
			});
		});

		function unregisterServiceWorker() {
			if ('serviceWorker' in navigator && navigator.serviceWorker.controller !== null) {
				navigator.serviceWorker.getRegistration(wpmaData.serviceWorker.scope).then(register => {
					register.unregister();
				});
			}
		}
	});
}, false);