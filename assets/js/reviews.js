/**
 * WP Reviews front-end behaviour. No dependencies.
 *
 *  - "Show more" fetches the next batch of cards from the plugin's REST
 *    endpoint (only the first batch is in the page).
 *  - "Read more" expands long reviews.
 *  - Carousels scroll with previous/next buttons, load more cards as they
 *    near the end, and can autoplay (paused on hover, focus, touch, or with
 *    the pause button; never for visitors who prefer reduced motion).
 */
(function () {
	'use strict';

	/* ------------------------------------------------------------------
	 * Loading more cards
	 * ---------------------------------------------------------------- */

	function moreState(wrap) {
		if (!wrap.hasAttribute('data-more-url')) {
			return null;
		}
		var query = {};
		try {
			query = JSON.parse(wrap.getAttribute('data-more-query')) || {};
		} catch (e) {
			query = {};
		}
		return {
			url: wrap.getAttribute('data-more-url'),
			query: query,
			offset: parseInt(wrap.getAttribute('data-more-offset'), 10) || 0,
			step: parseInt(wrap.getAttribute('data-more-step'), 10) || 24,
			total: parseInt(wrap.getAttribute('data-more-total'), 10) || 0,
			loading: false
		};
	}

	function hasMore(state) {
		return !!state && state.offset < state.total;
	}

	/**
	 * Fetch the next batch and append it to the track.
	 * Calls done(newCards) on success, or fail() on error.
	 */
	function loadMore(wrap, state, done, fail) {
		if (!hasMore(state) || state.loading) {
			return;
		}
		state.loading = true;

		var url = new URL(state.url, window.location.href);
		var key;

		for (key in state.query) {
			if (Object.prototype.hasOwnProperty.call(state.query, key)) {
				url.searchParams.set(key, state.query[key]);
			}
		}
		url.searchParams.set('offset', state.offset);
		url.searchParams.set('count', state.step);

		window.fetch(url.toString(), { credentials: 'omit', headers: { Accept: 'application/json' } })
			.then(function (response) {
				if (!response.ok) {
					throw new Error('HTTP ' + response.status);
				}
				return response.json();
			})
			.then(function (data) {
				var track = wrap.querySelector('.if-cards');
				var holder = document.createElement('div');
				var added = [];

				holder.innerHTML = data && data.html ? data.html : '';

				while (holder.firstElementChild) {
					added.push(holder.firstElementChild);
					track.appendChild(holder.firstElementChild);
				}

				state.offset = data && data.offset ? data.offset : state.total;
				state.total = data && data.total ? data.total : state.offset;
				state.loading = false;

				added.forEach(function (card) {
					initReadMore(card);
				});
				done(added);
			})
			.catch(function () {
				state.loading = false;
				if (fail) {
					fail();
				}
			});
	}

	function initShowMore(wrap, state) {
		var button = wrap.querySelector('.if-show-more');

		if (!button || !state) {
			return;
		}

		var label = button.textContent;
		var errorText = button.getAttribute('data-error') || label;

		// Screen readers hear "N more reviews loaded" or the error from here.
		// It sits outside the button's paragraph, which is removed at the end.
		var status = document.createElement('span');
		status.className = 'if-sr-only';
		status.setAttribute('role', 'status');
		wrap.appendChild(status);

		function ready(text) {
			// aria-disabled rather than disabled, so the button keeps focus
			// while it loads.
			button.removeAttribute('aria-disabled');
			button.textContent = text;
		}

		button.addEventListener('click', function () {
			if (state.loading) {
				return;
			}
			button.setAttribute('aria-disabled', 'true');
			button.textContent = button.getAttribute('data-loading') || label;
			status.textContent = '';

			loadMore(wrap, state, function (added) {
				ready(label);
				status.textContent = (button.getAttribute(1 === added.length ? 'data-loaded-one' : 'data-loaded') || '').replace('%d', added.length);

				// Keyboard and screen-reader users land on the first new card.
				if (added.length) {
					added[0].setAttribute('tabindex', '-1');
					added[0].focus();
				}

				if (!hasMore(state)) {
					var holder = button.parentNode;
					holder.parentNode.removeChild(holder);
				}
			}, function () {
				ready(errorText);
				status.textContent = errorText;
			});
		});
	}

	/* ------------------------------------------------------------------
	 * Read more
	 * ---------------------------------------------------------------- */

	// Where supported, the hidden rest of a review stays findable: the
	// browser's find in page reveals it (hidden="until-found"). Elsewhere it
	// is plain hidden, as the page sends it.
	var untilFound = 'onbeforematch' in document.documentElement;

	function initReadMore(scope) {
		var buttons = scope.querySelectorAll('.if-read-more');
		var i;

		function bind(button) {
			var paragraph = button.parentNode;
			var rest = paragraph.querySelector('.if-text-rest');
			var ellipsis = paragraph.querySelector('.if-text-ellipsis');
			var label = button.querySelector('.if-read-more-label') || button;

			if (!rest) {
				return;
			}

			function setOpen(open) {
				if (open) {
					rest.removeAttribute('hidden');
				} else if (untilFound) {
					rest.setAttribute('hidden', 'until-found');
				} else {
					rest.hidden = true;
				}
				if (ellipsis) {
					ellipsis.hidden = open;
				}
				button.setAttribute('aria-expanded', open ? 'true' : 'false');
				label.textContent = button.getAttribute(open ? 'data-less' : 'data-more');
			}

			if (untilFound) {
				rest.setAttribute('hidden', 'until-found');
				// Find in page opened it: keep the button and ellipsis in step.
				rest.addEventListener('beforematch', function () {
					setOpen(true);
				});
			}

			button.addEventListener('click', function () {
				setOpen(button.getAttribute('aria-expanded') !== 'true');
			});
		}

		for (i = 0; i < buttons.length; i++) {
			bind(buttons[i]);
		}
	}

	/* ------------------------------------------------------------------
	 * Carousel
	 * ---------------------------------------------------------------- */

	function initCarousel(wrap, state) {
		if (!wrap.classList.contains('if-layout-carousel')) {
			return;
		}

		var track = wrap.querySelector('.if-cards');
		var prev = wrap.querySelector('.if-carousel-prev');
		var next = wrap.querySelector('.if-carousel-next');
		var frame = null;

		if (!track || !prev || !next) {
			return;
		}

		// In right-to-left pages scrollLeft runs from 0 down to negative
		// values, so work with distances and a direction instead.
		var dir = window.getComputedStyle(track).direction === 'rtl' ? -1 : 1;

		function position() {
			return Math.abs(track.scrollLeft);
		}

		function maxPosition() {
			return track.scrollWidth - track.clientWidth - 1;
		}

		function stepSize() {
			var card = track.querySelector('.if-review-card');
			var styles = window.getComputedStyle(track);
			var gap = parseFloat(styles.columnGap) || parseFloat(styles.gap) || 0;

			return card ? card.getBoundingClientRect().width + gap : track.clientWidth;
		}

		function update() {
			// Read the layout first, then write, so the browser lays out once.
			var max = maxPosition();
			var pos = position();
			var step = stepSize();
			// A few pixels of slack: snapping and zoom can stop just short.
			var atStart = pos <= 2;
			var atEnd = pos >= max - 2 && !hasMore(state);

			// A disabled button loses keyboard focus: hand it to the other one.
			if (atStart && !atEnd && document.activeElement === prev) {
				next.focus();
			}
			if (atEnd && !atStart && document.activeElement === next) {
				prev.focus();
			}
			prev.disabled = atStart;
			next.disabled = atEnd;
			wrap.classList.toggle('if-carousel-scrollable', max > 0 || hasMore(state));

			// Fetch the next batch while there are still a few cards to go.
			if (hasMore(state) && max - pos < step * 3) {
				loadMore(wrap, state, update);
			}
		}

		// No behavior option: the track's CSS scroll-behavior decides, so
		// visitors who prefer reduced motion get an instant jump.
		prev.addEventListener('click', function () {
			track.scrollBy({ left: -stepSize() * dir });
		});

		next.addEventListener('click', function () {
			track.scrollBy({ left: stepSize() * dir });
		});

		// At most one update per frame, however many scroll or resize events.
		function schedule() {
			if (frame) {
				return;
			}
			frame = window.requestAnimationFrame(function () {
				frame = null;
				update();
			});
		}

		track.addEventListener('scroll', schedule, { passive: true });

		// The track can change width without the window resizing (a summary
		// moving above the cards, a theme's sidebar opening).
		if ('ResizeObserver' in window) {
			new window.ResizeObserver(schedule).observe(track);
		} else {
			window.addEventListener('resize', schedule);
		}
		update();

		initAutoplay(wrap, track, stepSize, dir, position, maxPosition, state);
	}

	function initAutoplay(wrap, track, stepSize, dir, position, maxPosition, state) {
		var seconds = parseInt(wrap.getAttribute('data-autoplay'), 10) || 0;
		var button = wrap.querySelector('.if-carousel-pause');
		var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		var timer = null;
		var userPaused = false;
		var engaged = false;
		var offscreen = false;

		if (seconds <= 0 || reduced) {
			if (button) {
				button.parentNode.removeChild(button);
			}
			return;
		}

		function tick() {
			if (position() >= maxPosition() && !hasMore(state)) {
				track.scrollTo({ left: 0, behavior: 'smooth' });
			} else {
				track.scrollBy({ left: stepSize() * dir, behavior: 'smooth' });
			}
		}

		function stop() {
			if (timer) {
				window.clearInterval(timer);
				timer = null;
			}
		}

		function start() {
			if (timer || userPaused || engaged || offscreen || document.hidden) {
				return;
			}
			timer = window.setInterval(tick, seconds * 1000);
		}

		function setPaused(paused) {
			userPaused = paused;
			if (button) {
				// The label says what the button will do, so no aria-pressed.
				button.setAttribute('aria-label', paused ? button.getAttribute('data-label-play') : button.getAttribute('data-label-pause'));
			}
			wrap.classList.toggle('if-autoplay-paused', paused);
			if (paused) {
				stop();
			} else {
				start();
			}
		}

		wrap.addEventListener('mouseenter', function () { engaged = true; stop(); });
		wrap.addEventListener('mouseleave', function () { engaged = false; start(); });
		wrap.addEventListener('focusin', function (event) {
			if (event.target !== button) {
				engaged = true;
				stop();
			}
		});
		wrap.addEventListener('focusout', function (event) {
			if (!wrap.contains(event.relatedTarget)) {
				engaged = false;
				start();
			}
		});

		// On touch screens there is no hover: touching the cards stops
		// autoplay for good (the pause button can start it again).
		track.addEventListener('pointerdown', function (event) {
			if (event.pointerType === 'touch' && !userPaused) {
				setPaused(true);
			}
		}, { passive: true });

		document.addEventListener('visibilitychange', function () {
			if (document.hidden) {
				stop();
			} else {
				start();
			}
		});

		// Off screen, nobody is watching: don't move, and don't fetch more
		// cards in the background.
		if ('IntersectionObserver' in window) {
			new window.IntersectionObserver(function (entries) {
				offscreen = !entries[entries.length - 1].isIntersecting;
				if (offscreen) {
					stop();
				} else {
					start();
				}
			}).observe(wrap);
		}

		if (button) {
			button.addEventListener('click', function () {
				setPaused(!userPaused);
			});
		}

		start();
	}

	function init() {
		var wraps = document.querySelectorAll('.if-reviews-wrap');
		var i;
		var state;

		for (i = 0; i < wraps.length; i++) {
			state = moreState(wraps[i]);
			initShowMore(wraps[i], state);
			initReadMore(wraps[i]);
			initCarousel(wraps[i], state);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
