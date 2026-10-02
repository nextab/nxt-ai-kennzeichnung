(function () {
	if (!window.nxtAiLabel) {
		return;
	}

	var cfg = window.nxtAiLabel;

	function findImage() {
		var modal = document.querySelector('.media-modal');
		var root = modal || document;
		return root.querySelector('.attachment-media-view img, .wp_attachment_holder img, .wp_attachment_image img');
	}

	function overlayHost() {
		return document.querySelector('.media-modal') || document.body;
	}

	function showOverlay(text) {
		hideOverlay();
		var host = overlayHost();
		var el = document.createElement('div');
		el.className = 'nxt-ai-label-overlay';
		if (host === document.body) {
			el.classList.add('nxt-ai-label-overlay--screen');
		}
		var box = document.createElement('div');
		box.className = 'nxt-ai-label-overlay__box';
		var spin = document.createElement('span');
		spin.className = 'nxt-ai-label-overlay__spin';
		var msg = document.createElement('p');
		msg.textContent = text;
		box.appendChild(spin);
		box.appendChild(msg);
		el.appendChild(box);
		host.appendChild(el);
	}

	function hideOverlay() {
		document.querySelectorAll('.nxt-ai-label-overlay').forEach(function (node) {
			node.remove();
		});
	}

	function clearBadge() {
		document.querySelectorAll('.nxt-ai-label-badge').forEach(function (node) {
			node.remove();
		});
	}

	function readPanel(panel) {
		var slug = panel.querySelector('[data-field="slug"]');
		var position = panel.querySelector('[data-field="position"]');
		var scale = panel.querySelector('[data-field="scale"]');
		return {
			slug: slug ? slug.value : '',
			position: position ? position.value : 'bottom-right',
			scale: scale ? scale.value : 'medium',
		};
	}

	function drawPreview(panel) {
		clearBadge();
		var img = findImage();
		var choice = readPanel(panel);
		var label = cfg.labels[choice.slug];
		var targetH = cfg.heights[choice.scale] || 40;
		if (!img || !label || !img.clientWidth) {
			return;
		}

		var fullW = parseInt(panel.dataset.fullWidth, 10) || img.naturalWidth || img.clientWidth;
		var fullH = parseInt(panel.dataset.fullHeight, 10) || img.naturalHeight || img.clientHeight;
		if (!fullW || !fullH) {
			return;
		}

		var pad = Math.max(4, Math.round(Math.min(fullW, fullH) * 0.03));
		var maxW = Math.max(1, fullW - (2 * pad));
		var maxH = Math.max(1, fullH - (2 * pad));
		var labelH = targetH;
		var labelW = labelH * (label.width / label.height);
		if (labelW > maxW) {
			labelW = maxW;
			labelH = labelW * (label.height / label.width);
		}
		if (labelH > maxH) {
			labelH = maxH;
			labelW = labelH * (label.width / label.height);
		}

		var viewScale = img.clientWidth / fullW;
		var cssW = labelW * viewScale;
		var cssH = labelH * viewScale;
		var cssPad = pad * viewScale;
		var left = cssPad;
		var top = cssPad;
		if (choice.position === 'top-right' || choice.position === 'bottom-right') {
			left = img.clientWidth - cssW - cssPad;
		}
		if (choice.position === 'bottom-left' || choice.position === 'bottom-right') {
			top = img.clientHeight - cssH - cssPad;
		}

		var parent = img.parentElement;
		if (!parent) {
			return;
		}
		if (window.getComputedStyle(parent).position === 'static') {
			parent.style.position = 'relative';
		}

		var badge = document.createElement('img');
		badge.className = 'nxt-ai-label-badge';
		badge.alt = '';
		badge.src = label.url;
		badge.style.left = (img.offsetLeft + Math.max(0, left)) + 'px';
		badge.style.top = (img.offsetTop + Math.max(0, top)) + 'px';
		badge.style.width = Math.max(1, cssW) + 'px';
		badge.style.height = Math.max(1, cssH) + 'px';
		parent.appendChild(badge);
	}

	function setStatus(panel, enabled, message) {
		panel.dataset.enabled = enabled ? '1' : '0';
		var status = panel.querySelector('.nxt-ai-label__status');
		var msg = panel.querySelector('.nxt-ai-label__msg');
		var i18n = cfg.i18n || {};
		if (status) {
			status.textContent = enabled ? (i18n.applied || 'Label is applied.') : (i18n.none || 'No label.');
		}
		if (msg) {
			msg.textContent = message || '';
		}
	}

	function refreshImage(url) {
		var img = findImage();
		if (!img) {
			return;
		}
		var next = url || img.currentSrc || img.src;
		var parsed = new URL(next, window.location.href);
		parsed.searchParams.set('nxt', String(Date.now()));
		img.src = parsed.toString();
	}

	function apply(panel, mode) {
		var choice = readPanel(panel);
		var buttons = panel.querySelectorAll('button');
		buttons.forEach(function (button) {
			button.disabled = true;
		});
		var i18n = cfg.i18n || {};
		showOverlay(mode === 'remove' ? (i18n.removing || 'Removing label') : (i18n.writing || 'Writing label'));

		var body = new FormData();
		body.append('action', 'nxt_ai_label_apply');
		body.append('nonce', cfg.nonce);
		body.append('attachment_id', panel.dataset.id || '');
		body.append('mode', mode);
		body.append('slug', choice.slug);
		body.append('position', choice.position);
		body.append('scale', choice.scale);

		fetch(cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body,
		}).then(function (response) {
			return response.json();
		}).then(function (payload) {
			if (!payload || !payload.success) {
				var err = payload && payload.data && payload.data.message ? payload.data.message : ((cfg.i18n && cfg.i18n.failed) || 'Failed.');
				setStatus(panel, panel.dataset.enabled === '1', err);
				return;
			}
			setStatus(panel, !!payload.data.enabled, payload.data.message || '');
			clearBadge();
			refreshImage(payload.data.url || '');
		}).catch(function () {
			setStatus(panel, panel.dataset.enabled === '1', (cfg.i18n && cfg.i18n.requestFailed) || 'Request failed.');
		}).finally(function () {
			hideOverlay();
			buttons.forEach(function (button) {
				button.disabled = false;
			});
		});
	}

	document.addEventListener('change', function (event) {
		var field = event.target.closest('.nxt-ai-label [data-field]');
		if (!field) {
			return;
		}
		var panel = field.closest('.nxt-ai-label');
		if (panel) {
			drawPreview(panel);
		}
	});

	document.addEventListener('click', function (event) {
		var button = event.target.closest('.nxt-ai-label [data-action]');
		if (!button) {
			return;
		}
		event.preventDefault();
		var panel = button.closest('.nxt-ai-label');
		if (!panel) {
			return;
		}
		apply(panel, button.dataset.action === 'remove' ? 'remove' : 'apply');
	});

	function bootVisible() {
		document.querySelectorAll('.nxt-ai-label').forEach(function (panel) {
			var img = findImage();
			if (!img) {
				return;
			}
			if (img.complete) {
				drawPreview(panel);
				return;
			}
			img.addEventListener('load', function () {
				drawPreview(panel);
			}, { once: true });
		});
	}

	var timer = 0;
	var observer = new MutationObserver(function () {
		window.clearTimeout(timer);
		timer = window.setTimeout(bootVisible, 80);
	});
	observer.observe(document.body, { childList: true, subtree: true });
	bootVisible();
}());
