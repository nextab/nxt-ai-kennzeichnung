<?php

if (!defined('ABSPATH')) {
	exit;
}

final class NXT_AI_Label_Media_Fields {
	public static function register(): void {
		add_filter('attachment_fields_to_edit', [self::class, 'fields'], 10, 2);
		add_action('wp_enqueue_media', [self::class, 'enqueue']);
		add_action('admin_enqueue_scripts', [self::class, 'enqueue_attachment_edit']);
		add_action('wp_ajax_nxt_ai_label_apply', [self::class, 'ajax_apply']);
	}

	public static function enqueue_attachment_edit(string $hook): void {
		if ($hook !== 'post.php') {
			return;
		}

		$post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
		if ($post_id <= 0 || get_post_type($post_id) !== 'attachment') {
			return;
		}

		self::enqueue();
	}

	public static function enqueue(): void {
		wp_enqueue_style(
			'nxt-ai-label-admin',
			NXT_AI_LABEL_URL . 'assets/admin.css',
			[],
			NXT_AI_LABEL_VERSION
		);
		wp_enqueue_script(
			'nxt-ai-label-admin',
			NXT_AI_LABEL_URL . 'assets/admin.js',
			[],
			NXT_AI_LABEL_VERSION,
			true
		);

		$labels = [];
		foreach (NXT_AI_Label_Labels::all() as $slug => $item) {
			[$width, $height] = NXT_AI_Label_Labels::pixel_size($slug);
			$labels[$slug] = [
				'url' => NXT_AI_LABEL_URL . 'assets/labels/' . $item['file'],
				'width' => $width,
				'height' => $height,
			];
		}

		$heights = [];
		foreach (NXT_AI_Label_Labels::scales() as $key => $item) {
			$heights[$key] = $item['height'];
		}

		wp_localize_script('nxt-ai-label-admin', 'nxtAiLabel', [
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('nxt_ai_label_apply'),
			'labels' => $labels,
			'heights' => $heights,
		]);
	}

	/**
	 * @param array<string, array<string, mixed>> $form_fields
	 * @return array<string, array<string, mixed>>
	 */
	public static function fields(array $form_fields, WP_Post $post): array {
		if (!wp_attachment_is_image($post)) {
			return $form_fields;
		}

		$enabled = get_post_meta($post->ID, '_nxt_ai_label_enabled', true) === '1';
		$slug = (string) get_post_meta($post->ID, '_nxt_ai_label_slug', true);
		$position = (string) get_post_meta($post->ID, '_nxt_ai_label_position', true);
		$scale = (string) get_post_meta($post->ID, '_nxt_ai_label_scale', true);

		if (!NXT_AI_Label_Labels::is_valid_slug($slug)) {
			$slug = NXT_AI_Label_Labels::DEFAULT_SLUG;
		}
		if (!NXT_AI_Label_Labels::is_valid_position($position)) {
			$position = NXT_AI_Label_Labels::DEFAULT_POSITION;
		}
		if (!NXT_AI_Label_Labels::is_valid_scale($scale)) {
			$scale = NXT_AI_Label_Labels::DEFAULT_SCALE;
		}

		$meta = wp_get_attachment_metadata($post->ID);
		$full_w = is_array($meta) ? (int) ($meta['width'] ?? 0) : 0;
		$full_h = is_array($meta) ? (int) ($meta['height'] ?? 0) : 0;

		$label_options = '';
		$current_group = '';
		foreach (NXT_AI_Label_Labels::all() as $key => $item) {
			if ($item['group'] !== $current_group) {
				if ($current_group !== '') {
					$label_options .= '</optgroup>';
				}
				$current_group = $item['group'];
				$label_options .= '<optgroup label="' . esc_attr($current_group) . '">';
			}
			$label_options .= sprintf(
				'<option value="%s"%s>%s</option>',
				esc_attr($key),
				selected($slug, $key, false),
				esc_html($item['label'])
			);
		}
		if ($current_group !== '') {
			$label_options .= '</optgroup>';
		}

		$position_options = '';
		foreach (NXT_AI_Label_Labels::positions() as $key => $label) {
			$position_options .= sprintf(
				'<option value="%s"%s>%s</option>',
				esc_attr($key),
				selected($position, $key, false),
				esc_html($label)
			);
		}

		$scale_options = '';
		foreach (NXT_AI_Label_Labels::scales() as $key => $item) {
			$scale_options .= sprintf(
				'<option value="%s"%s>%s (%d px)</option>',
				esc_attr($key),
				selected($scale, $key, false),
				esc_html($item['label']),
				(int) $item['height']
			);
		}

		$html = sprintf(
			'<div class="nxt-ai-label" data-id="%1$d" data-enabled="%2$s" data-full-width="%3$d" data-full-height="%4$d">
				<p class="nxt-ai-label__status">%5$s</p>
				<label class="nxt-ai-label__field">Kennzeichnung
					<select data-field="slug">%6$s</select>
				</label>
				<label class="nxt-ai-label__field">Position
					<select data-field="position">%7$s</select>
				</label>
				<label class="nxt-ai-label__field">Größe
					<select data-field="scale">%8$s</select>
				</label>
				<p class="nxt-ai-label__actions">
					<button type="button" class="button button-primary" data-action="apply">Kennzeichnung setzen</button>
					<button type="button" class="button" data-action="remove">Kennzeichnung entfernen</button>
				</p>
				<p class="nxt-ai-label__msg" aria-live="polite"></p>
			</div>',
			(int) $post->ID,
			$enabled ? '1' : '0',
			$full_w,
			$full_h,
			$enabled ? 'Kennzeichnung ist gesetzt.' : 'Keine Kennzeichnung.',
			$label_options,
			$position_options,
			$scale_options
		);

		$form_fields['nxt_ai_label'] = [
			'label' => 'KI-Kennzeichnung',
			'input' => 'html',
			'html' => $html,
		];

		return $form_fields;
	}

	public static function ajax_apply(): void {
		check_ajax_referer('nxt_ai_label_apply', 'nonce');

		$attachment_id = isset($_POST['attachment_id']) ? (int) $_POST['attachment_id'] : 0;
		if ($attachment_id <= 0 || !current_user_can('edit_post', $attachment_id) || !wp_attachment_is_image($attachment_id)) {
			wp_send_json_error(['message' => 'Keine Berechtigung oder kein Bild.'], 403);
		}

		$mode = isset($_POST['mode']) ? sanitize_key((string) $_POST['mode']) : 'apply';
		$slug = isset($_POST['slug']) ? sanitize_text_field(wp_unslash((string) $_POST['slug'])) : NXT_AI_Label_Labels::DEFAULT_SLUG;
		$position = isset($_POST['position']) ? sanitize_key((string) $_POST['position']) : NXT_AI_Label_Labels::DEFAULT_POSITION;
		$scale = isset($_POST['scale']) ? sanitize_key((string) $_POST['scale']) : NXT_AI_Label_Labels::DEFAULT_SCALE;
		$enabled = $mode !== 'remove';

		if (!NXT_AI_Label_Processor::mark($attachment_id, $enabled, $slug, $position, $scale)) {
			wp_send_json_error(['message' => 'Kennzeichnung fehlgeschlagen.'], 500);
		}

		$image = wp_get_attachment_image_src($attachment_id, 'large');
		$url = is_array($image) ? (string) $image[0] : '';

		wp_send_json_success([
			'enabled' => $enabled,
			'url' => $url,
			'message' => $enabled ? 'Kennzeichnung gesetzt.' : 'Kennzeichnung entfernt.',
		]);
	}
}
