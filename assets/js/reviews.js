/**
 * WP Reviews front-end behaviour.
 *
 * Two small features, no dependencies:
 *  - "Show more" reveals cards that were rendered with the `hidden` attribute.
 *  - Carousel previous/next buttons scroll the card track.
 */
(function () {
	'use strict';

	function initShowMore(wrap) {
		var button = wrap.querySelector('.if-show-more');

		if (!button) {
			return;
		}

		button.addEventListener('click', function () {
			var step = parseInt(button.getAttribute('data-step'), 10) || 6;
			var hidden = wrap.querySelectorAll('.if-review-card[hidden]');
			var i;

			for (i = 0; i < hidden.length && i < step; i++) {
				hidden[i].removeAttribute('hidden');
			}

			// Keyboard users land on the first newly revealed card.
			if (hidden.length) {
				hidden[0].setAttribute('tabindex', '-1');
				hidden[0].focus({ preventScroll: true });
			}

			if (wrap.querySelectorAll('.if-review-card[hidden]').length === 0) {
				button.parentNode.removeChild(button);
			}
		});
	}

	function initReadMore(wrap) {
		var buttons = wrap.querySelectorAll('.if-read-more');
		var i;

		function bind(button) {
			var paragraph = button.parentNode;
			var rest = paragraph.querySelector('.if-text-rest');
			var ellipsis = paragraph.querySelector('.if-text-ellipsis');

			if (!rest) {
				return;
			}

			button.addEventListener('click', function () {
				var expanded = button.getAttribute('aria-expanded') === 'true';

				rest.hidden = expanded;
				if (ellipsis) {
					ellipsis.hidden = !expanded;
				}
				button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
				button.textContent = expanded ? button.getAttribute('data-more') : button.getAttribute('data-less');
			});
		}

		for (i = 0; i < buttons.length; i++) {
			bind(buttons[i]);
		}
	}

	function initCarousel(wrap) {
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

		function stepSize() {
			var card = track.querySelector('.if-review-card:not([hidden])');
			var styles = window.getComputedStyle(track);
			var gap = parseFloat(styles.columnGap) || parseFloat(styles.gap) || 0;

			return card ? card.getBoundingClientRect().width + gap : track.clientWidth;
		}

		function update() {
			var max = track.scrollWidth - track.clientWidth - 1;

			prev.disabled = track.scrollLeft <= 0;
			next.disabled = track.scrollLeft >= max;
			wrap.classList.toggle('if-carousel-scrollable', max > 0);
		}

		prev.addEventListener('click', function () {
			track.scrollBy({ left: -stepSize(), behavior: 'smooth' });
		});

		next.addEventListener('click', function () {
			track.scrollBy({ left: stepSize(), behavior: 'smooth' });
		});

		track.addEventListener('scroll', function () {
			if (frame) {
				return;
			}
			frame = window.requestAnimationFrame(function () {
				frame = null;
				update();
			});
		}, { passive: true });

		window.addEventListener('resize', update);
		update();

		initAutoplay(wrap, track, stepSize);
	}

	function initAutoplay(wrap, track, stepSize) {
		var seconds = parseInt(wrap.getAttribute('data-autoplay'), 10) || 0;
		var button = wrap.querySelector('.if-carousel-pause');
		var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		var timer = null;
		var userPaused = false;
		var engaged = false;

		if (seconds <= 0 || reduced) {
			if (button) {
				button.parentNode.removeChild(button);
			}
			return;
		}

		function tick() {
			var max = track.scrollWidth - track.clientWidth - 1;

			if (track.scrollLeft >= max) {
				track.scrollTo({ left: 0, behavior: 'smooth' });
			} else {
				track.scrollBy({ left: stepSize(), behavior: 'smooth' });
			}
		}

		function stop() {
			if (timer) {
				window.clearInterval(timer);
				timer = null;
			}
		}

		function start() {
			if (timer || userPaused || engaged || document.hidden) {
				return;
			}
			timer = window.setInterval(tick, seconds * 1000);
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
		document.addEventListener('visibilitychange', function () {
			if (document.hidden) {
				stop();
			} else {
				start();
			}
		});

		if (button) {
			button.addEventListener('click', function () {
				userPaused = !userPaused;
				button.setAttribute('aria-pressed', userPaused ? 'true' : 'false');
				button.setAttribute('aria-label', userPaused ? button.getAttribute('data-label-play') : button.getAttribute('data-label-pause'));
				wrap.classList.toggle('if-autoplay-paused', userPaused);
				if (userPaused) {
					stop();
				} else {
					start();
				}
			});
		}

		start();
	}

	function init() {
		var wraps = document.querySelectorAll('.if-reviews-wrap');
		var i;

		for (i = 0; i < wraps.length; i++) {
			initShowMore(wraps[i]);
			initReadMore(wraps[i]);
			initCarousel(wraps[i]);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
