/**
 * Main Javascript.
 * This file is for who want to make this theme as a new parent theme and you are ready to code your js here.
 */

var smoothState = undefined
var wpmaSmooth, fRefresh, currentUrl;

readyDOM(() => {
	jQuery(document).ready(jQuery => {
		'use strict';

		// Init effect by setting
		initSmoothStage();
		initProgressBar();

		// Function for wpmaAPI.refreshMode
		fRefresh = () => {
			if (typeof smoothState === 'undefined') {
				console.log('TEMPLATE: smoothState not running.')
				return;
			}

			console.log('TEMPLATE: smoothState running.')

			// Load new content
			smoothState.clear(currentUrl);
			smoothState.load(currentUrl);
		}

		// enable modules WPMA API
		if (typeof wpmaAPI !== 'undefined') {
			initTemplate();			
		}
	})
});

function initTemplate() {
	currentUrl = window.location.href;

	// Add a custom component loading for list post (by raw html)
	wpmaAPI.template.addComponent('wpma-loading-feature', {
		template: `
    <div class="animated-background post">
      <div class="background-masker post-white-01"></div>
      <div class="background-masker post-black-01"></div>
      <div class="background-masker post-white-02"></div>
      <div class="background-masker post-black-02"></div>
      <div class="background-masker post-white-03"></div>
    </div>`
	});

	// Component: Button Load More
	wpmaAPI.template.addComponent('wpma-load-more', {
		props: ['currentDatas', 'currentUrl', 'amount'],

		data: function() {
			var object = {}
			object['currentPage'] = 1;
			object['isMore'] = true;
			object['isLoading'] = false;
			object['sLoadMore'] = 'Load More';
			return object;
		},

		template: `<div>
		<template v-for="n in amount">
			<wpma-loading-feature v-if="isLoading"></wpma-loading-feature>
		</template>
		<div class="loadmore" v-on:click="addmore">{{sLoadMore}}</div>
		</div>`,

		methods: {
			addmore: function() {
				let self = this;

				if (!self.isMore) {
					return;
				}

				// Set offset
				offset = (self.currentPage++) * self.amount;
				let sUrl = self.currentUrl + '&offset=' + offset;

				console.log('TEMPLATE: load more...', self.currentDatas, sUrl, self.amount, offset)

				// Get data
				self.isLoading = true;
				jQuery.getJSON(sUrl, function(jsonData) {
					console.log('TEMPLATE: component json data', jsonData);

					// Update data
					self.isMore = false;
					if (jsonData.length) {
						jQuery.each(jsonData, (i, data) => {
							self.currentDatas.push(data)
						})
						self.isMore = true;
					} else {
						self.sLoadMore = 'No more posts';
					}
					self.isLoading = false;
					console.log('TEMPLATE: update component data', self.currentDatas);
				})
			}
		}
	});

	// Add a custom component loading for list post (by wpmaData in wpma-template.php:113)
	jQuery.each(wpmaData.template.component, (id, agrs) => {
		wpmaAPI.template.addComponent(id, agrs);
	})

	// Initialize wpmaAPI Template
	wpmaAPI.template.init();

	initModuleAPI();
}

function initModuleAPI() {
	wpmaAPI.enableOfflineAlertSticky();
	wpmaAPI.enableShareLink();
	wpmaAPI.refreshMode('body', fRefresh);
}

function initProgressBar() {
	if (typeof NProgress === 'undefined') {
		return;
	}

	console.log('TEMPLATE: ProgressBar initialize..');

	// Check progress bar
	if (NProgress.isStarted() && navigator.serviceWorker.controller !== null) {
		NProgress.inc();
	}

	// Add Progress
	if (typeof curProgress === 'undefined') {
		jQuery('body').append('<div id="ProgressBar"></div>');
		curProgress = jQuery('#ProgressBar');
		NProgress.configure({
			parent: '#ProgressBar',
			easing: 'ease',
			speed: 500,
		});
	}
}

function initSmoothStage() {
	wpmaSmooth = jQuery('#SmoothPage');

	if (typeof wpmaSmooth.smoothState === 'undefined') {
		console.warn('TEMPLATE: smoothState not running.')
		return;
	}

	window.onpopstate = reCache;

	console.log('TEMPLATE: SmoothStage initialize..');

	smoothState = wpmaSmooth.smoothState({
		cacheLength: 30,
		allowFormCaching: false,
		debug: false,
		prefetch: false,
		scroll: false,

		alterRequest: function(request) {
			return request;
		},
		onBefore: function(currentTarget, sContainer) {
			reCache(null);
		},
		onStart: {
			duration: 0,
			render: function(sContainer) {
				if (typeof NProgress !== 'undefined') {
					console.log('TEMPLATE: Progress runing')
					NProgress.start();
					NProgress.inc();
				}
			}
		},

		onReady: {
			duration: 0,
			render: function(oBody, sContainer) {
				oBody.html(sContainer);
			}
		},

		onAfter: function(sContainer, sNewContent) {

			if (typeof wpmaAPI !== 'undefined') {
				// Parse new content
				initTemplate();
			}

			if (typeof NProgress !== 'undefined') {
				console.log('TEMPLATE: Complete Progress');
				NProgress.done(true);
			}
		},
	}).data('smoothState');
}

function reCache(e) {
	if (smoothState.cache[currentUrl]) {
		// Save cache for current page rended
		console.log('TEMPLATE: Save cache for current page rended', currentUrl);
		smoothState.cache[currentUrl].html.html(wpmaSmooth.html());
	}

	smoothState.load(window.location.href);
}