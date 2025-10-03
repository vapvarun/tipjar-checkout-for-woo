(function ($) {
	'use strict';

	let request = null;
	let retryTimer = null;
	let bodyObserver = null;
	let observerDebounce = null;

	function getConfig() {
		return typeof window.tipjarCheckout !== 'undefined' ? window.tipjarCheckout : null;
	}

	function decodeHtml(value) {
		if (typeof value !== 'string') {
			return value;
		}

		const textarea = document.createElement('textarea');
		textarea.innerHTML = value;
		return textarea.value;
	}

	function toNumber(value) {
		const numeric = typeof value === 'number' ? value : parseFloat(value);
		return Number.isFinite(numeric) ? numeric : 0;
	}

	function getDecimals(config) {
		const parsed = parseInt(config?.decimals, 10);
		return Number.isFinite(parsed) && parsed >= 0 ? parsed : 2;
	}

	function formatValue(value, decimals) {
		if (!Number.isFinite(value) || value <= 0) {
			return '';
		}

		const fixed = value.toFixed(decimals);
		return decimals > 0
			? fixed.replace(/\.0+$/, '').replace(/\.(\d*[1-9])0+$/, '.$1')
			: fixed;
	}

	function stepAttribute(decimals) {
		if (decimals <= 0) {
			return '1';
		}

		return '0.' + '0'.repeat(Math.max(0, decimals - 1)) + '1';
	}

	function setCurrentTip(value) {
		const config = getConfig();
		if (!config) {
			return;
		}

		config.currentTip = value;
	}

	function refreshBlockTotals() {
		if (!window.wp || !wp.data || typeof wp.data.dispatch !== 'function') {
			return;
		}

		const dispatcher = wp.data.dispatch('wc/store/cart');

		if (!dispatcher) {
			return;
		}

		if (typeof dispatcher.invalidateResolutionForStore === 'function') {
			dispatcher.invalidateResolutionForStore();
		} else if (typeof dispatcher.invalidateResolution === 'function') {
			dispatcher.invalidateResolution('getCartData', []);
		}
	}

	function sendTip(rawValue) {
		const config = getConfig();

		if (!config || !config.ajaxUrl) {
			return;
		}

		if (request && request.readyState !== 4) {
			request.abort();
		}

		request = $.ajax({
			type: 'POST',
			url: config.ajaxUrl,
			data: {
				security: config.nonce,
				tip: rawValue,
			},
			complete: function () {
				request = null;
			},
			success: function (response) {
				if (!response || response.success !== true) {
					return;
				}

				const data = response.data || {};
				const numeric = typeof data.tip === 'number' ? data.tip : parseFloat(data.tip || 0);
				const confirmed = Number.isFinite(numeric) && numeric > 0 ? numeric : 0;

				setCurrentTip(confirmed);
				$(document.body).trigger('update_checkout');
				refreshBlockTotals();
			},
		});
	}

	function buildMarkup(config) {
		const decimals = getDecimals(config);
		const presetOptions = Array.isArray(config?.presetOptions) ? config.presetOptions : [];
		const labels = config?.labels || {};
		const currentTip = toNumber(config?.currentTip);
		const $wrapper = $('<div/>', {
			class: 'woo-checkout-tip form-row form-row-wide',
			'aria-live': 'polite',
			'data-woo-tip-render': 'dynamic',
		});

		$wrapper.append(
			$('<h3/>', {
				class: 'woo-checkout-tip__title',
				text: decodeHtml(labels.title || 'Add a tip'),
			})
		);

		$wrapper.append(
			$('<p/>', {
				class: 'woo-checkout-tip__description',
				text: decodeHtml(labels.description || 'Choose a tip amount for the team.'),
			})
		);

		if (presetOptions.length) {
			const $choice = $('<div/>', { class: 'woo-checkout-tip__quick-choice' });

			presetOptions.forEach(function (option) {
				const value = toNumber(option?.value);
				const isActive = Number.isFinite(currentTip) && currentTip === value;

				$choice.append(
					$('<button/>', {
						type: 'button',
						class: 'button woo-checkout-tip__button' + (isActive ? ' is-active' : ''),
						'data-tip': value,
						text: decodeHtml(option?.label || String(value)),
					})
				);
			});

			$wrapper.append($choice);
		}

		const $custom = $('<p/>', { class: 'woo-checkout-tip__custom' });
		const step = stepAttribute(decimals);
		const formatted = formatValue(currentTip, decimals);

		$custom.append(
			$('<label/>', {
				for: 'woo-checkout-tip-amount',
				text: decodeHtml(labels.customLabel || 'Custom tip amount'),
			})
		);

		$custom.append(
			$('<input/>', {
				type: 'number',
				min: '0',
				step: step,
				inputmode: 'decimal',
				id: 'woo-checkout-tip-amount',
				name: 'woo-checkout-tip-amount',
				value: formatted,
				placeholder: labels.placeholder || 'Enter amount',
			})
		);

		$wrapper.append($custom);

		return $wrapper;
	}

	function rebuildPresetButtons($wrapper, config, decimals) {
		const presetOptions = Array.isArray(config?.presetOptions) ? config.presetOptions : [];
		const $custom = $wrapper.find('.woo-checkout-tip__custom');
		let $choice = $wrapper.find('.woo-checkout-tip__quick-choice');

		if (!presetOptions.length) {
			$choice.remove();
			return;
		}

		if (!$choice.length) {
			$choice = $('<div/>', { class: 'woo-checkout-tip__quick-choice' });
			if ($custom.length) {
				$choice.insertBefore($custom);
			} else {
				$wrapper.append($choice);
			}
		} else {
			$choice.empty();
		}

		const currentTip = toNumber(config?.currentTip);

		presetOptions.forEach(function (option) {
			const value = toNumber(option?.value);
			const isActive = Number.isFinite(currentTip) && currentTip === value;
			const $button = $('<button/>', {
				type: 'button',
				class: 'button woo-checkout-tip__button' + (isActive ? ' is-active' : ''),
				'aria-pressed': isActive ? 'true' : 'false',
				'data-tip': value,
				text: option?.label || value,
			});

			$button.text(decodeHtml(option?.label || String(value)));
			$choice.append($button);
		});
	}

	function syncActiveState($wrapper, tipValue) {
		const numericTip = parseFloat(tipValue);

		$wrapper.find('.woo-checkout-tip__button').each(function () {
			const $button = $(this);
			const buttonValue = parseFloat($button.data('tip'));
			const isMatch = !Number.isNaN(buttonValue) && !Number.isNaN(numericTip) && buttonValue === numericTip;
			const isZeroMatch = (!Number.isFinite(numericTip) || numericTip === 0) && buttonValue === 0;

			if (isMatch || isZeroMatch) {
				$button.addClass('is-active').attr('aria-pressed', 'true');
			} else {
				$button.removeClass('is-active').attr('aria-pressed', 'false');
			}
		});
	}

	function bindTipEvents($wrapper) {
		const config = getConfig();

		if (!config || !$wrapper.length) {
			return;
		}

		const decimals = getDecimals(config);
		const $input = $wrapper.find('#woo-checkout-tip-amount');
		let lastSent = null;
		let debounceTimer = null;

		rebuildPresetButtons($wrapper, config, decimals);

		$input.attr('step', stepAttribute(decimals));
		if (!$input.val()) {
			$input.val(formatValue(toNumber(config.currentTip), decimals));
		}

		syncActiveState($wrapper, $input.val());

		$wrapper.off('.tipjarCheckout');

		function queueTipUpdate(rawValue) {
			const trimmed = typeof rawValue === 'string' ? rawValue.trim() : rawValue;
			let normalized = '';

			if (trimmed !== '') {
				const numeric = parseFloat(trimmed);
				normalized = Number.isFinite(numeric) && numeric > 0 ? parseFloat(numeric.toFixed(decimals)) : 0;
			}

			const signature = normalized === '' ? '' : normalized;

			if (lastSent === signature) {
				return;
			}

			lastSent = signature;
			setCurrentTip(normalized === '' ? 0 : normalized);
			sendTip(normalized === '' ? '' : normalized);
		}

		$wrapper.on('click.tipjarCheckout', '.woo-checkout-tip__button', function (event) {
			event.preventDefault();
			const preset = $(this).data('tip');
			const value = typeof preset === 'number' ? preset : parseFloat(preset || 0);
			const sanitized = Number.isFinite(value) && value > 0 ? value : 0;
			const formatted = formatValue(sanitized, decimals);

			$input.val(formatted);
			syncActiveState($wrapper, sanitized);
			queueTipUpdate(sanitized);
		});

		$wrapper.on('input.tipjarCheckout', '#woo-checkout-tip-amount', function () {
			const current = $(this).val();
			syncActiveState($wrapper, current);

			if (debounceTimer) {
				clearTimeout(debounceTimer);
			}

			debounceTimer = setTimeout(function () {
				queueTipUpdate(current);
			}, 400);
		});

		$wrapper.on('change.tipjarCheckout', '#woo-checkout-tip-amount', function () {
			const current = $(this).val();
			syncActiveState($wrapper, current);

			if (debounceTimer) {
				clearTimeout(debounceTimer);
			}

			queueTipUpdate(current);
		});
	}

	function ensureBlockObserver($target) {
		if (!$target.length || $target.data('wooTipObserver')) {
			return;
		}

		const observer = new MutationObserver(function () {
			const config = getConfig();

			if (!config) {
				return;
			}

			if (!$target.find('.woo-checkout-tip').length) {
				const $created = buildMarkup(config);
				$target.append($created);
				bindTipEvents($created);
			}
		});

		observer.observe($target[0], { childList: true });
		$target.data('wooTipObserver', observer);
	}

	function scheduleRetry() {
		if (retryTimer) {
			return;
		}

		retryTimer = window.setTimeout(function () {
			retryTimer = null;
			initTipUI($(document.body));
		}, 300);
	}

	function ensureBodyObserver() {
		if (bodyObserver || !window.MutationObserver) {
			return;
		}

		bodyObserver = new MutationObserver(function () {
			if (observerDebounce) {
				window.clearTimeout(observerDebounce);
			}

			observerDebounce = window.setTimeout(function () {
				observerDebounce = null;
				initTipUI($(document.body));
			}, 120);
		});

		bodyObserver.observe(document.body, {
			childList: true,
			subtree: true,
		});
	}

	function initTipUI(context) {
		const config = getConfig();

		if (!config) {
			return;
		}

		ensureBodyObserver();

		let $wrapper = context.find('.woo-checkout-tip');

		if (!$wrapper.length) {
			const $blockTarget = context.find('.wc-block-components-order-summary').first();

			if ($blockTarget.length) {
				ensureBlockObserver($blockTarget);
				const $created = buildMarkup(config);
				$blockTarget.append($created);
				bindTipEvents($created);
				return;
			}

			scheduleRetry();
			return;
		}

		bindTipEvents($wrapper);
	}

	$(document).ready(function () {
		initTipUI($(document.body));
	});

	$(document.body).on('updated_checkout', function () {
		initTipUI($(document.body));
	});
})(jQuery);
