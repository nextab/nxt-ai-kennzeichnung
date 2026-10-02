(function () {
	function labelBox(imageWidth, imageHeight, labelHeight, aspect, position) {
		var pad = Math.max(4, Math.round(Math.min(imageWidth, imageHeight) * 0.03));
		var maxW = Math.max(1, imageWidth - (2 * pad));
		var maxH = Math.max(1, imageHeight - (2 * pad));
		var height = labelHeight;
		var width = height * aspect;
		if (width > maxW) {
			width = maxW;
			height = width / aspect;
		}
		if (height > maxH) {
			height = maxH;
			width = height * aspect;
		}
		var x = pad;
		var y = pad;
		if (position === 'top-right' || position === 'bottom-right') {
			x = imageWidth - width - pad;
		}
		if (position === 'bottom-left' || position === 'bottom-right') {
			y = imageHeight - height - pad;
		}
		return { x: x, y: y, w: width, h: height };
	}

	function focal(token, axis) {
		var map = {
			left: 0,
			top: 0,
			center: 0.5,
			right: 1,
			bottom: 1,
		};
		if (Object.prototype.hasOwnProperty.call(map, token)) {
			if (axis === 'x' && (token === 'top' || token === 'bottom')) {
				return 0.5;
			}
			if (axis === 'y' && (token === 'left' || token === 'right')) {
				return 0.5;
			}
			return map[token];
		}
		if (token.slice(-1) === '%') {
			return parseFloat(token) / 100;
		}
		return 0.5;
	}

	function objectFocal(img) {
		var value = window.getComputedStyle(img).objectPosition || '50% 50%';
		var parts = value.trim().split(/\s+/);
		if (parts.length === 1) {
			parts.push('center');
		}
		return {
			x: focal(parts[0], 'x'),
			y: focal(parts[1], 'y'),
		};
	}

	function sync(mark) {
		var cover = mark.closest('.wp-block-cover');
		if (!cover) {
			return;
		}
		var img = cover.querySelector(':scope > img.wp-block-cover__image-background, :scope > .wp-block-cover__image-background');
		if (!img || img.tagName !== 'IMG' || !img.naturalWidth || !img.naturalHeight) {
			mark.classList.remove('is-hidden');
			return;
		}
		if (window.getComputedStyle(img).objectFit === 'contain') {
			mark.classList.add('is-hidden');
			return;
		}

		var box = cover.getBoundingClientRect();
		if (box.width < 1 || box.height < 1) {
			return;
		}

		var position = 'bottom-right';
		mark.classList.forEach(function (name) {
			if (name.indexOf('nxt-ai-label-cover--') === 0) {
				position = name.slice('nxt-ai-label-cover--'.length);
			}
		});

		var labelHeight = parseFloat(mark.dataset.labelHeight) || 40;
		var aspect = parseFloat(mark.dataset.aspect) || 1;
		var burned = labelBox(img.naturalWidth, img.naturalHeight, labelHeight, aspect, position);
		var scale = Math.max(box.width / img.naturalWidth, box.height / img.naturalHeight);
		var point = objectFocal(img);
		var offsetX = (box.width - (img.naturalWidth * scale)) * point.x;
		var offsetY = (box.height - (img.naturalHeight * scale)) * point.y;
		var x = offsetX + (burned.x * scale);
		var y = offsetY + (burned.y * scale);
		var w = burned.w * scale;
		var h = burned.h * scale;
		var visible = x >= -1 && y >= -1 && (x + w) <= (box.width + 1) && (y + h) <= (box.height + 1);
		mark.classList.toggle('is-hidden', visible);
	}

	function syncAll() {
		document.querySelectorAll('.nxt-ai-label-cover').forEach(sync);
	}

	function watch() {
		syncAll();
		if (!window.ResizeObserver) {
			return;
		}
		var observer = new ResizeObserver(syncAll);
		document.querySelectorAll('.wp-block-cover:has(.nxt-ai-label-cover)').forEach(function (cover) {
			observer.observe(cover);
		});
	}

	if (document.readyState === 'complete') {
		watch();
	} else {
		window.addEventListener('load', watch);
	}
	window.addEventListener('resize', syncAll);
}());
