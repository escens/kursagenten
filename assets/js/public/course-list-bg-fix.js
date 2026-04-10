/**
 * Restores course list thumbnail backgrounds after third-party lazy-load scripts.
 * Some optimizers parse background-image with regexes that break on ")" inside filenames
 * (e.g. IMG_3381(1).jpg), truncating the URL and clearing the image.
 * Full URL is stored on data-ka-bg-url; we re-apply and sync data-bg.
 * When data-ka-bg-url-mobile is set, we restore --ka-bg-wide / --ka-bg-narrow (medium on narrow viewports).
 * MutationObserver catches late overwrites (e.g. url("...") nested inside style="...").
 */
(function () {
	'use strict';

	var KA_BG_MQ = '(max-width: 768px)';

	function escapeForCssUrl(u) {
		return u.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
	}

	function getActiveDataBgUrl(el) {
		var wide = el.getAttribute('data-ka-bg-url');
		if (!wide) {
			return '';
		}
		var narrow = el.getAttribute('data-ka-bg-url-mobile');
		if (narrow && narrow !== wide && window.matchMedia(KA_BG_MQ).matches) {
			return narrow;
		}
		return wide;
	}

	/**
	 * True if inline background-image looks broken vs. expected absolute URL (legacy single-URL mode).
	 */
	function needsFixLegacy(el, url) {
		if (!url) {
			return true;
		}
		var bi = el.style.getPropertyValue('background-image') || '';
		if (!bi || bi === 'none') {
			return true;
		}
		if (bi.indexOf('var(--ka-bg') !== -1) {
			return false;
		}
		if (bi.indexOf(url) === -1) {
			return true;
		}
		return false;
	}

	function needsFixResponsive(el, wide, narrow) {
		var w = el.style.getPropertyValue('--ka-bg-wide') || '';
		var n = el.style.getPropertyValue('--ka-bg-narrow') || '';
		if (!w || w.indexOf(wide) === -1) {
			return true;
		}
		if (narrow && narrow !== wide && (!n || n.indexOf(narrow) === -1)) {
			return true;
		}
		return false;
	}

	function applyOne(el) {
		var wide = el.getAttribute('data-ka-bg-url');
		if (!wide) {
			return;
		}
		var narrow = el.getAttribute('data-ka-bg-url-mobile');
		var responsive = narrow && narrow !== wide;

		if (responsive) {
			var styleBroken = needsFixResponsive(el, wide, narrow);
			var activeUrl = getActiveDataBgUrl(el);
			var db = el.getAttribute('data-bg');
			var dataBgBroken = db !== activeUrl;
			if (!styleBroken && !dataBgBroken) {
				return;
			}
			var safeW = escapeForCssUrl(wide);
			var safeN = escapeForCssUrl(narrow);
			el.style.setProperty('--ka-bg-wide', "url('" + safeW + "')", 'important');
			el.style.setProperty('--ka-bg-narrow', "url('" + safeN + "')", 'important');
			el.style.removeProperty('background-image');
			el.setAttribute('data-bg', activeUrl);
			return;
		}

		var styleBroken = needsFixLegacy(el, wide);
		var db = el.getAttribute('data-bg');
		var dataBgBroken = db !== wide;
		if (!styleBroken && !dataBgBroken) {
			return;
		}
		if (styleBroken) {
			var safe = escapeForCssUrl(wide);
			el.style.setProperty('background-image', "url('" + safe + "')", 'important');
		}
		if (dataBgBroken) {
			el.setAttribute('data-bg', wide);
		}
	}

	function applyAll() {
		var els = document.querySelectorAll('[data-ka-bg-url]');
		for (var i = 0; i < els.length; i++) {
			applyOne(els[i]);
		}
	}

	function observe() {
		var els = document.querySelectorAll('[data-ka-bg-url]');
		for (var i = 0; i < els.length; i++) {
			(function (el) {
				var obs = new MutationObserver(function () {
					applyOne(el);
				});
				obs.observe(el, {
					attributes: true,
					attributeFilter: ['style', 'data-bg', 'class'],
				});
			})(els[i]);
		}
	}

	var resizeTimer;
	window.addEventListener('resize', function () {
		clearTimeout(resizeTimer);
		resizeTimer = setTimeout(applyAll, 150);
	});

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			applyAll();
			observe();
		});
	} else {
		applyAll();
		observe();
	}
	window.addEventListener('load', applyAll);

	[0, 50, 200, 600, 2000].forEach(function (ms) {
		setTimeout(applyAll, ms);
	});
})();
