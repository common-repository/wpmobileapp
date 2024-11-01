const mainCache = [WPMA_CACHE_MAIN];
const versionCache = [WPMA_CACHE_VERSION];
const offlineUrl = [WPMA_OFFLINE_URL];
const urlsToCache = JSON.parse([WPMA_CACHE_URLS]);
const urlsToIgnored = JSON.parse([WPMA_IGNORE_URLS]);
const urlsForImportScript = JSON.parse([WPMA_IMPORT_SCRIPTS]);
const alwaysFetch = [WPMA_ALWAYS_FETCH];
const sCurrentDevice = [WPMA_CURRENT_DEVICE];
const debug = [WPMA_ENABLE_DEBUG]

var logFetch = [];
var aListNotification = [];

// DEBUG
if (debug !== 1) {
	console.warn('WORKER: disable debug');
	console.log = function() {}
	console.warn = function() {}
	console.error = function() {}
	console.table = function() {}
}

/////////////////////
self.addEventListener('beforeinstallprompt', e => {
	console.log('WORKER: before install prompt event fired.');
	e.userChoice.then(choiceResult => {
		if (choiceResult.outcome == 'dismissed') {
			console.warn('WORKER: client cancelled home screen install.');
		} else {
			console.log('WORKER: client added to home screen.');
		}
	});
});

/////////////////////
self.addEventListener('install', e => {
	console.log('WORKER: instaling.');

	// Merge all url to cache
	var allURLCache = [];
	allURLCache = urlsToCache.concat(urlsForImportScript);
	console.log('WORKER: caching app shell.', allURLCache);

	e.waitUntil(
		caches.open(versionCache + '::' + mainCache).then(cache => {
			cache.addAll(allURLCache.map(urlToPrefetch => {
				return new Request(urlToPrefetch, {
					mode: 'no-cors'
				});
			})).then(() => {
				console.log('WORKER: All resources have been fetched and cached.');

				cache.match(offlineUrl).then(matching => {
					console.log('WORKER: check offline page.', offlineUrl);

					// Check offline page is cached
					if (matching) return;

					console.log('WORKER: caching offline page...');

					e.target.clients.matchAll().then(clients => {
						for (i in clients) {
							clients[i].postMessage({
								action: 'createCache',
								url: offlineUrl
							});
						}
					})
				})

			});
		}).then(() => {
			console.log('WORKER: install completed.');
			return self.skipWaiting();
		}).catch(error => {
			console.error('WORKER: Pre-fetching failed:', error);
		})
	);
});

/////////////////////
self.addEventListener('activate', e => {
	console.log('WORKER: activating.');

	e.waitUntil(
		caches.keys().then(cacheNames => {
			console.log('WORKER: current cache storage', cacheNames);
			// Return a promise that settles when all outdated caches are deleted.
			return Promise.all(cacheNames.filter(cache => {
				// Filter by keys that don't start with the latest version prefix.
				return !cache.startsWith(versionCache);
			}).map(cache => {
				console.log('WORKER: delete cached.', cache);
				return caches.delete(cache);
			}));
		}).then(() => {
			console.log('WORKER: activate completed.');

			// importScripts only run when online
			if (navigator.onLine) {
				// Import Scripts
				urlsForImportScript.map(url => {
					self.importScripts(url);
				});
			}

			return self.clients.claim();
		}));
});

/////////////////////
self.addEventListener('fetch', e => {
	// Init log
	logFetch[e.request.url] = {};
	logFetch[e.request.url].cache = false;
	logFetch[e.request.url].update = false;
	logFetch[e.request.url].network = false;
	logFetch[e.request.url].error = null;
	logFetch[e.request.url].offline = null;

	if (sCurrentDevice === 'computer') {
		console.warn('WORKER: fetch on platform ignored.', e.request.url);
		return;
	}

	// Ignore url by wpma setting
	var bIgnored = false;

	urlsToIgnored.map(string => {
		var regexp = new RegExp(string);
		bIgnored = !bIgnored ? regexp.test(e.request.url) : bIgnored;
	});

	if (bIgnored === true) {
		console.warn('WORKER: fetch url ignored by Setting.', e.request.url);
		return;
	}

	if (e.request.method !== 'GET') {
		console.warn('WORKER: fetch event ignored by Method.', e.request.url);
		return;
	}

	// alwaysFetch by admin setting
	if (alwaysFetch === 1 && navigator.onLine) {
		e.respondWith(getFromNetwork(e.request));
	} else {
		e.respondWith(getFromCache(e.request));

		// Only update cache when online
		if (navigator.onLine) {
			logFetch[e.request.url].update = true;
			e.waitUntil(getFromNetwork(e.request));
		}
	}
});

// Get from cache
function getFromCache(request) {
	return caches.open(versionCache + '::' + mainCache).then(cache => {
		return cache.match(request).then(matching => {

			if (matching) {
				logFetch[request.url].cache = true;
			}

			return matching || Promise.reject('no-match');

		}).catch(error => {
			logFetch[request.url].error = error;
			if (!navigator.onLine) {
				return responseOffline(request);
			} else {
				return getFromNetwork(request);
			}
		});
	});
}

// Get new data and update cache frome network
function getFromNetwork(request) {
	return caches.open(versionCache + '::' + mainCache).then(cache => {
		var custom = {};

		if (request.url.indexOf(self.registration.scope) >= 0) {
			request = new Request(request.url, {
				method: request.method,
				headers: request.headers,
				mode: 'same-origin', // need to set this properly
				credentials: request.credentials,
				redirect: 'manual' // let browser handle redirects
			});

			custom = {
				headers: {
					'Cache-Control': 'no-cache'
				}
			}
		}

		return fetch(request, custom).then(response => {
			logFetch[request.url].network = true;

			var cacheCopy = response.clone();
			cache.put(request, cacheCopy);
			return response || Promise.reject('no-network');

		}).catch(error => {
			logFetch[request.url].error = error;
			console.error('WORKER: fetch error', error)
			return responseOffline(request)
		});
	});
}

// Response when failed both cache and network
function responseOffline(request) {
	logFetch[request.url].offline = true;

	// Response page with url offline
	if (request.headers.get('Accept').includes('text/html') && offlineUrl) {
		logFetch[request.url].offline = offlineUrl;
		return caches.open(versionCache + '::' + mainCache).then(cache => {
			return caches.match(offlineUrl, {
				ignoreVary: true
			}).then(matching => {
				console.log('WORKER: matching offline:', matching)
				return matching;
			});
		})
	}

	return new Response('Request failed!', {
		headers: {
			'Status': '200 OK',
		}
	});
}

/////////////////////
self.addEventListener('push', e => {
	console.log('WORKER: push event in progress.', e);

	if (!(self.Notification && self.Notification.permission === 'granted')) {
		return;
	}

	var data = {};
	if (e.data) {
		console.log(e.data.text())
		try {
			data = e.data.json();
		} catch (error) {
			console.error('WORKER: can\'t parse data of push notification.', error);
			return;
		}
	}

	const title = data.title;
	const options = {
		body: data.excerpt,
		icon: data.thumbnail != false ? data.thumbnail : '',
		badge: data.permalink
	};

	const notificationPromise = self.registration.showNotification(title, options).catch(error => {
		console.error('WORKER: showNotification error,', error);
	});

	e.waitUntil(notificationPromise);
});

/////////////////////
self.addEventListener('notificationclick', e => {
	console.log('WORKER: notificationclick event in progress.', e);

	e.notification.close(); // Android needs explicit close.
	e.waitUntil(clients.openWindow(e.notification.badge));
});

/////////////////////
self.addEventListener('sync', e => {
	console.log('WORKER: sync event in progress.', e.tag);

	if (typeof wpmaAPI !== 'undefined') {
		wpmaAPI[e.tag]();
	}
});

/////////////////////
self.addEventListener('message', e => {
	console.log('WORKER: postMessage received.', e);

	if (!e.data) {
		console.warn('WORKER: no message data.');
		return;
	}

	if (e.data.action === 'transferObject') {
		var sObject, sFunction, sFEval;

		for (object in e.data) {
			if (e.data[object].constructor.name === 'ArrayBuffer' && this[object] === undefined) {
				console.log('WORKER: an object initialize..', object);

				// String to Object
				sObject = transferArrayBuffer(e.data[object]);
				eval(object + '=' + sObject);

				// Object string function to Function			
				for (proto in this[object]) {
					if (proto.constructor.name === 'String' && this[object][proto].toString().indexOf('=> {') !== -1) {
						sFEval = eval(proto + '=' + this[object][proto]);
						this[object][proto] = sFEval;
					}
				}

				console.log('WORKER: object initialize success', this[object]);
			}
		}

		if (typeof wpmaAPI.showLog === 'function' && typeof wpmaData.contentLog === 'object' &&
			console.log.toString().indexOf('[native code]') > 0 &&
			console.warn.toString().indexOf('[native code]') > 0 &&
			console.error.toString().indexOf('[native code]') > 0
		) {
			console.log = wpmaAPI.showLog(console, console.log, '<p class="log">')
			console.warn = wpmaAPI.showLog(console, console.warn, '<p class="warn">')
			console.error = wpmaAPI.showLog(console, console.error, '<p class="error">')
		}
	}

	if (e.data.action === 'removeCache') {
		console.log('WORKER: remove cache of', e.data.request);
		caches.open(versionCache + '::' + mainCache).then(cache => {
			cache.delete(e.data.request).then(response => {
				console.log('WORKER: remove cache is', response);
			});
		});
	}

	console.table(logFetch);
})
////////////////////////////////////////////////////////////////////////////////
function transferArrayBuffer(string) {
	var aBuffer = new Uint8Array(string).buffer;
	var oDecoder = new TextDecoder("utf-8");
	var oView = new DataView(aBuffer, 0, aBuffer.byteLength);
	var sJson = oDecoder.decode(oView);
	return sJson;
}

function getRandomColor() {
	var letters = '0123456789ABCDEF';
	var color = '#';
	for (var i = 0; i < 6; i++) {
		color += letters[Math.floor(Math.random() * 16)];
	}
	return color;
}