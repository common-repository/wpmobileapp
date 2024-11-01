readyDOM(() => {
	jQuery(document).ready(jQuery => {
		console.log(wpmaData);

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
			console.log('API: plugin is stoped.');
			unregisterServiceWorker();
			return;
		}

		//////////// Service Worker ////////////
		if ('serviceWorker' in navigator) {

			if (wpmaData.serviceWorker.enable != 1) {
				console.log('CLIENT: serviceWorker is disable.');
				unregisterServiceWorker();
				return;
			}

			console.log('CLIENT: serviceWorker initialize..');			

			window.addEventListener('load', () => {
				if (wpmaData.core.device === 'computer') {
					console.log('CLIENT: serviceWorker not run on platform.');
				} else if (wpmaData.serviceWorker.enable === 1) {
					if (navigator.serviceWorker.controller === null) {

						console.log('CLIENT: service worker registration in progress.');

						navigator.serviceWorker.register(wpmaData.serviceWorker.path + 'service-worker.js', {
							scope: wpmaData.serviceWorker.scope,
							useCache: true
						}).then(registration => {
							console.log('CLIENT: service worker registration complete.', registration);
						}).catch(error => {
							console.error('CLIENT: service worker registration failure.', error);
						});
					} else {

						// Transfer object by object name
						transferObjectToServiceWorker(['wpmaData', 'wpmaAPI']);
					}

					// Event message from Service-Worker
					navigator.serviceWorker.addEventListener('message', receivingMessage);
				}
			});
		} else

			//////////// AppCache ////////////
			if ('applicationCache' in window) {

				if (wpmaData.appCache.enable != 1) {
					console.log('CLIENT: applicationCache is disable.');
					return;
				}

				// applicationCache on iOS
				console.log('CLIENT: applicationCache initialize..');

				// Check if a new cache is available on page load.
				window.addEventListener('load', () => {
					appCache = window.applicationCache;

					console.log('CLIENT: applicationCache loaded.', appCache);

					// Fired after the first cache of the manifest.
					appCache.addEventListener('cached', e => {
						console.log('CLIENT: applicationCache cached', e.returnValue);
					});

					// Checking for an update. Always the first event fired in the sequence.
					appCache.addEventListener('checking', e => {
						console.log('CLIENT: applicationCache checking', e.returnValue);
					});

					// An update was found. The browser is fetching resources.
					appCache.addEventListener('downloading', e => {
						console.log('CLIENT: applicationCache downloading', e.returnValue);
					});

					// The manifest returns 404 or 410, the download failed,
					// or the manifest changed while the download was in progress.
					appCache.addEventListener('error', e => {
						console.error('CLIENT: applicationCache error', e);
					});

					// Fired after the first download of the manifest.
					appCache.addEventListener('noupdate', e => {
						console.log('CLIENT: applicationCache noupdate', e);
					});

					// Fired if the manifest file returns a 404 or 410.
					// This results in the application cache being deleted.
					appCache.addEventListener('obsolete', e => {
						console.warn('CLIENT: applicationCache obsolete', e);
					});

					// Fired for each resource listed in the manifest as it is being fetched.
					appCache.addEventListener('progress', e => {
						console.log('CLIENT: applicationCache progress: ' + e.loaded + '/' + e.total);
					});

					// Fired when the manifest resources have been newly redownloaded.
					appCache.addEventListener('updateready', e => {
						console.log('CLIENT: applicationCache updateready', e.returnValue);
						if (e.status == appCache.UPDATEREADY) {
							appCache.swapCache();
						}
					});

				});
			}

		function unregisterServiceWorker() {
			if ('serviceWorker' in navigator && navigator.serviceWorker.controller !== null) {
				navigator.serviceWorker.getRegistration(wpmaData.serviceWorker.scope).then(register => {
					register.unregister();
				});
			}
		}

		//////////// Receiving message ////////////
		function receivingMessage(e){
			if(e.data.action === 'createCache' && e.data.url){
				wpmaAPI.createCache(e.data.url);
			}			
		}
		

		//////////// Transfer Object ////////////

		// Send objects to service-worker
		function transferObjectToServiceWorker(aObjectName) {
			let aBuffer = [];
			let oBuffer = {
				'action': 'transferObject'
			};

			aObjectName.map(objectName => {
				if (typeof window[objectName] === 'object') {
					oBuffer[objectName] = transferObject(window[objectName]);
					aBuffer.push(oBuffer[objectName]);
				}
			});

			// Send Object with method to ServiceWorker
			navigator.serviceWorker.controller.postMessage(oBuffer, aBuffer);
		}

		function transferObject(object) {
			var sJson = JSON.stringify(object, function(key, value) {
				if (typeof value === 'function') {
					//conver function to string
					return value.toString();
				} else {
					return value;
				}
			});

			var aUint8 = new TextEncoder(document.characterSet.toLowerCase()).encode(sJson);
			var aBuffer = aUint8.buffer;
			return aBuffer;
		}
	});
}, false);

function readyDOM(callback) {
	// in case the document is already rendered
	if (document.readyState != 'loading') callback();
	// modern browsers
	else if (document.addEventListener) document.addEventListener('DOMContentLoaded', callback);
	// IE <= 8
	else document.attachEvent('onreadystatechange', function() {
		if (document.readyState == 'complete') callback();
	});
}