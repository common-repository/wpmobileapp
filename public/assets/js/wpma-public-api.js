var wpmaAPI = wpmaAPI || {};
readyDOM(() => {
	jQuery(document).ready(jQuery => {

		// DEBUG
		if (wpmaData.debug.enable != 1) {
			console.warn('CLIENT: disable debug.');
			console.log = function() {}
			console.warn = function() {}
			console.error = function() {}
			console.table = function() {}
		}

		// CORE
		if (wpmaData.core.enable != 1) {
			console.log('API: WPMA is stoped.');
			return false;
		}

		//////////// WPMA API: Action ////////////
		// Show log in class
		wpmaAPI.showLog = (context, method, message = '') => {
			return function() {
				let mess = [message].concat(Array.prototype.slice.apply(arguments));
				let string = mess.join(' ');

				if (typeof wpmaData.contentLog !== 'undefined') {
					wpmaData.contentLog.innerHTML += string + "<p>";
				}

				method.apply(context, mess.slice(1))
			}
		}

		// Check network online
		wpmaAPI.isOnline = () => {
			console.log('API: isOnline', navigator.onLine);
			return navigator.onLine;
		}

		// 
		wpmaAPI.refreshMode = (sClass, func) => {
			console.log('API: refreshMode initialize..');
			window.focus();

			var target = jQuery(sClass);

			function fKey(e) {
				if ((e.which || e.keyCode) == 116) {
					target.off('keydown')

					// Stop browser refresh
					e.preventDefault();
					e.stopPropagation();

					func();
				}
			}

			// Disable browser pull-refesh
			target.css({
				'touch-action': 'none',
				'overflow-y': 'hidden'
			});

			target.off('keydown');
			target.on('keydown', fKey);

			var startTouchY, pageY;

			// Add loading animation
			if (jQuery('.wpma-drag-loading').length === 0) {
				jQuery('body').before('<div class="wpma-question-loading">Reload this page?</div><div class="wpma-drag-loading"></div>');
			}

			var loadingQuestion = jQuery('.wpma-question-loading');
			loadingQuestion.hide();

			var loadingAnimation = jQuery('.wpma-drag-loading');
			loadingAnimation.hide();

			//jQuery
			target.draggable({
				axis: 'y',
				revertDuration: 250,
				refreshPositions: false,
				start: (e, ui) => {
					startTouchY = ui.offset.top;
				},
				drag: (e, ui) => {
					pageY = ui.position.top;

					// Check is DHR
					if (pageY > 0) {
						target.draggable('option', 'revert', event => {

							target.data("uiDraggable").originalPosition = {
								top: 0
							};

							return true;
						})
					} else {
						target.draggable('option', 'revert', false);
					}

					// Show loading
					if (startTouchY + 100 < ui.offset.top && pageY > 170) {
						if (!loadingAnimation.is(":visible")) {
							loadingAnimation.show();
						}
						loadingQuestion.hide();

						// Show question
					} else if (startTouchY + 30 < ui.offset.top) {
						loadingQuestion.show();
						loadingAnimation.hide();
					}
				},
				stop: (e, ui) => {

					// Run function
					if (loadingAnimation.is(":visible")) {
						func();
					}

					loadingAnimation.hide();
				}
			})
		}

		// Push a Notification
		wpmaAPI.pushNotification = jsonData => {
			if (!jsonData || jsonData.length === 0) {
				console.log('API: pushNotification fail.', jsonData);
				return false;
			}

			// Send notice for current client
			const title = jsonData.title;
			const options = {
				body: jsonData.excerpt,
				icon: jsonData.thumbnail != false ? jsonData.thumbnail : '',
				badge: jsonData.permalink
			};

			console.log('API: push a notification', options);
			registration.showNotification(title, options);
		}

		// Register a Sync tag in Service-Worker
		wpmaAPI.registerSync = tag => {
			console.log('API: registing sync in progress..', tag);

			if (typeof tag === 'undefined') {
				console.warn('API: sync tag not set.');
				return false;
			}

			if (navigator.serviceWorker.controller === null) {
				console.warn('API: service worker not running.');
				return false;
			}

			if (!('SyncManager' in window)) {
				console.error('API: SyncManager isn\'t supported.');
				return false;
			}

			navigator.serviceWorker.ready.then(registration => {
				registration.sync.register(tag).then(() => {
					console.log('API: registing sync success.');
				}).catch(error => {
					console.error('API: registing sync error,', error);
				})
			});
		}

		// Function create cache
		wpmaAPI.createCache = sURL => {
			if (!sURL) {
				console.warn('API: Not eligible!',
					'URL:', sURL,
				);
				return false;
			}

			console.log('API: Creating iframe!', sURL)

			// Use iframe for cache api in component
			jQuery('<iframe>', {
				src: sURL,
				id: 'cacher',
				frameborder: 0,
				width: 0,
				height: 0,
				scrolling: 'no'
			}).appendTo('body');

			var cacher = jQuery('#cacher');

			// Remove iframe when rended
			cacher.load(() => {
				console.log('API: AppShell and Component has cached, waiting for cache some image...', cacher)

				setTimeout(() => {
					console.log('API: Cached! remove iframe!', sURL)
					cacher.remove();
					return true;
				}, 3000);
			})
		}

		// Function remove cache
		wpmaAPI.removeCache = url => {

			if (!'postMessage' in navigator) {
				console.warn('API: postMessage isn\'t supported.');
				return false;
			}

			if (!url) {
				console.warn('API: Not eligible!',
					'URL:', url,
				);
				return false;
			}

			navigator.serviceWorker.controller.postMessage({
				'action': 'removeCache',
				'request': url
			});
		}

		//////////// WPMA API: Event ////////////

		// Class for show log api
		wpmaAPI.enableShowLog = () => {
			let className = '.wpma-show-log';
			if (!wpmaData.contentLog) {
				wpmaData.contentLog = document.querySelector(className);
			}

			if (wpmaData.contentLog === '') {
				console.warn('API: enableShowLog not found any class.', className)
				return false;
			}

			// Need a #wpmadebug hash
			if (wpmaData.debug.enable === 1 && window.location.hash.indexOf('#wpmadebug') >= 0) {
				console.log('API: enableShowLog initialize..');

				if ('serviceWorker' in navigator) {
					// Handler for messages coming from the service worker
					navigator.serviceWorker.addEventListener('message', e => {
						if (e.data.action === 'showLog') {
							wpmaData.contentLog.innerHTML += e.data.log;
						}
					}, {
						once: true
					});
				}

				jQuery(className).show();

				if (
					console.log.toString().indexOf('[native code]') > 0 &&
					console.warn.toString().indexOf('[native code]') > 0 &&
					console.error.toString().indexOf('[native code]') > 0
				) {
					console.log = wpmaAPI.showLog(console, console.log, '<p class="log">')
					console.warn = wpmaAPI.showLog(console, console.warn, '<p class="warn">')
					console.error = wpmaAPI.showLog(console, console.error, '<p class="error">')
				}
			} else {
				if (typeof wpmaData.contentLog !== 'undefined') {
					jQuery(className).hide();
				}
			}
		}

		// Class auto check network then add a sticky alert offline
		wpmaAPI.enableOfflineAlertSticky = () => {
			let className = '.wpma-offline-alert-sticky';
			let contentAlertSticky = jQuery(className);

			if (contentAlertSticky.length < 1) {
				console.warn('API: enableOfflineAlertSticky not found any class.', className);
				return false;
			}

			console.log('API: enableOfflineAlertSticky initialize..');

			if (wpmaAPI.isOnline() === false) {
				contentAlertSticky.show();
				contentAlertSticky.text(wpmaData.translate.offline);
			} else {
				if (typeof contentAlertSticky !== 'undefined') {
					contentAlertSticky.hide();
				}
			}
		}

		// Class click to create cache with dataset url
		wpmaAPI.enableCacheURL = () => {
			let className = '.wpma-create-cache';
			let clickToSave = jQuery(className);

			if (clickToSave.length < 1) {
				console.warn('API: enableCacheURL not found any class.', className)
				return false;
			}

			console.log('API: enableCacheURL initialize..');

			clickToSave.click(e => {
				e.stopImmediatePropagation();
				e.preventDefault();
				wpmaAPI.createCache(e.currentTarget.dataset.url)
			});
		}

		// Class click to remove cache with dataset url
		wpmaAPI.enableRemoveCache = () => {
			let className = '.wpma-remove-cache';
			let clickToRemoveCache = jQuery(className);

			if (clickToRemoveCache.length < 1) {
				console.warn('API: enableRemoveCache not found any class.', className)
				return false;
			}

			console.log('API: enableRemoveCache initialize..');

			clickToRemoveCache.click(e => {
				e.stopImmediatePropagation();
				e.preventDefault();

				wpmaAPI.removeCache(e.currentTarget.dataset.url);
			});
		}

		// Class click to send a notification with json data
		wpmaAPI.enablePushNotification = () => {
			let className = '.wpma-push-notification';
			let clickToPushNotification = jQuery(className);

			if (clickToPushNotification.length < 1) {
				console.warn('API: enablePushNotification not found any class.', className)
				return false;
			}

			console.log('API: enablePushNotification initialize..');

			clickToPushNotification.click(e => {
				e.stopImmediatePropagation();
				e.preventDefault();

				let jsonData = JSON.parse(e.currentTarget.dataset.json);

				console.log('API: push notification in progress.', jsonData);

				wpmaAPI.pushNotification(jsonData);
			});
		}

		// Class click to share a link with navigator
		wpmaAPI.enableShareLink = () => {
			let className = '.wpma-share-link';
			let clickToShareLink = jQuery(className);

			if (clickToShareLink.length < 1) {
				console.warn('API: enableShareLink not found any class.', className)
				return false;
			}

			console.log('API: enableShareLink initialize..');

			clickToShareLink.click(e => {
				e.stopImmediatePropagation();
				e.preventDefault();

				if (!navigator.share) {
					console.error('API: Native Sharing isn\'t supported.');
					return false;
				}

				if (!e.currentTarget.dataset.title || !e.currentTarget.dataset.text || !e.currentTarget.dataset.url) {
					console.warn('API: Not eligible!',
						'Title:', e.currentTarget.dataset.title,
						'Text:', e.currentTarget.dataset.text,
						'URL:', e.currentTarget.dataset.url
					);
					return false;
				}

				console.log('API: Open sharing')
				navigator.share({
						title: e.currentTarget.dataset.title,
						text: e.currentTarget.dataset.text,
						url: e.currentTarget.dataset.url,
					}).then(() => {
						console.log('API: Successful share')
					})
					.catch(error => {
						console.warn('API: Error sharing', error)
					});
				return true;
			});
		}

		// Function auto parse Vue template
		wpmaAPI.template = {
			// ID Module
			rootID: '#wpma-template',

			// Class element
			elementClass: '.wpma-el',

			addComponent: function(id, args) {
				Vue.component(id, args);
			},

			// Initialize data wpma-template
			init: function() {
				var api = this;

				//Check Vue is loaded
				if (Vue === undefined) {
					console.error('TEMPLATE: Vue is\'t running');
					return false;
				}

				// Create store list element
				api.ElementsShell = jQuery('body').find(api.elementClass).toArray();

				// Check template has wpma-template-element class
				if (api.ElementsShell.length < 1) {
					console.warn('TEMPLATE: WPMA Template class not found.', api.elementClass)
					return false;
				}

				// Get element in document-fragment
				var documentFragment = recursiveTemplateElement(document, []);
				jQuery.each(documentFragment, (i, element) => {
					let foundElement = jQuery(element).find(api.elementClass);
					if (foundElement.length > 0) {
						jQuery.each(foundElement, (k, wpmaElement) => {
							let id = api.ElementsShell.length + k;
							api.ElementsShell[id] = wpmaElement;
						})
					}
				})

				api.run();
			},

			// Parse document
			run: function() {
				var api = this;

				// Parse components by element
				jQuery.each(api.ElementsShell, (i, element) => {
					//Get ID component
					var elementID = (jQuery(element).prop('tagName')).toLowerCase();
					api.parse(elementID);
				})

				// Creates original Vue object
				var Template = new Vue({
					el: api.rootID,
				})

				console.log(Template)
			},

			// Parse element
			parse: function(elementID) {

				var api = this;

				if (!elementID || elementID === 'div') {
					console.warn('TEMPLATE: WPMA Component not enough requirements.', 'ID: ' + elementID)
					return false;
				}

				api.addComponent(elementID, {

					props: ['name', 'url', 'amount'],

					data: function() {
						var object = {}
						object['isLoading'] = true;

						if (this.name && this.amount) {
							object[this.name] = Array.apply(null, {
								length: this.amount
							}).map(Number.call, Number)
						}
						return object;
					},

					mounted: function() {
						if (this.url) {
							console.log('TEMPLATE: component ready get json data', this.url);
							this.fetchData(this.url);
						} else {
							this.isLoading = false;
							console.log('TEMPLATE: component loaded.', 'Url:', this.url);
						}
					},

					methods: {
						// Get api json data
						fetchData: function(jsonURL) {
							var self = this;
							jQuery.getJSON(jsonURL, function(jsonData) {

								self[self.name] = jsonData;
								self.isLoading = false;

								console.log('TEMPLATE: component json data', typeof jsonData, jsonData);
							}).fail(e => {

								self[self.name] = [];
								self.isLoading = false;

								console.log('TEMPLATE: component json fail', e);
							})
						}
					}
				});
			},
		}

		function recursiveTemplateElement(element, array) {
			var templateElement = element.querySelector("template");
			if (templateElement) {
				var documentFragment = templateElement.content.cloneNode(true);
				array.push(documentFragment);

				recursiveTemplateElement(documentFragment, array);
			}

			return array;
		}
	});
})