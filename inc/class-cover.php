<?php

if (!defined('ABSPATH')) {
	exit;
}

final class NXT_AI_Label_Cover {
	private static bool $used = false;

	public static function register(): void {
		add_filter('render_block_core/cover', [self::class, 'render'], 10, 2);
		add_action('wp_enqueue_scripts', [self::class, 'register_assets']);
	}

	public static function register_assets(): void {
		wp_register_style(
			'nxt-ai-label-cover',
			NXT_AI_LABEL_URL . 'assets/cover.css',
			[],
			NXT_AI_LABEL_VERSION
		);
		wp_register_script(
			'nxt-ai-label-cover',
			NXT_AI_LABEL_URL . 'assets/cover.js',
			[],
			NXT_AI_LABEL_VERSION,
			true
		);
	}

	/**
	 * @param array<string, mixed> $block
	 */
	public static function render(string $content, array $block): string {
		if ($content === '' || str_contains($content, 'nxt-ai-label-cover')) {
			return $content;
		}

		$attrs = isset($block['attrs']) && is_array($block['attrs']) ? $block['attrs'] : [];
		if (($attrs['backgroundType'] ?? 'image') === 'video') {
			return $content;
		}

		$attachment_id = self::attachment_id($content, $attrs);
		if ($attachment_id <= 0 || get_post_meta($attachment_id, '_nxt_ai_label_enabled', true) !== '1') {
			return $content;
		}

		$slug = (string) get_post_meta($attachment_id, '_nxt_ai_label_slug', true);
		$position = (string) get_post_meta($attachment_id, '_nxt_ai_label_position', true);
		$scale = (string) get_post_meta($attachment_id, '_nxt_ai_label_scale', true);

		if (!NXT_AI_Label_Labels::is_valid_slug($slug)) {
			$slug = NXT_AI_Label_Labels::DEFAULT_SLUG;
		}
		if (!NXT_AI_Label_Labels::is_valid_position($position)) {
			$position = NXT_AI_Label_Labels::DEFAULT_POSITION;
		}
		if (!NXT_AI_Label_Labels::is_valid_scale($scale)) {
			$scale = NXT_AI_Label_Labels::DEFAULT_SCALE;
		}

		$path = NXT_AI_Label_Labels::path($slug);
		if ($path === null) {
			return $content;
		}

		$all = NXT_AI_Label_Labels::all();
		[$label_w, $label_h] = NXT_AI_Label_Labels::pixel_size($slug);
		$height = NXT_AI_Label_Labels::scale_height($scale);
		$url = NXT_AI_LABEL_URL . 'assets/labels/' . $all[$slug]['file'];
		$aspect = $label_h > 0 ? $label_w / $label_h : 1;

		$display_width = (int) round($height * $aspect);
		$markup = sprintf(
			'<img class="nxt-ai-label-cover nxt-ai-label-cover--%1$s" src="%2$s" alt="%3$s" style="width:%4$dpx" data-label-height="%5$d" data-aspect="%6$s" decoding="async" />',
			esc_attr($position),
			esc_url($url),
			esc_attr($all[$slug]['group']),
			max(1, $display_width),
			$height,
			esc_attr((string) $aspect)
		);

		self::enqueue();

		$updated = preg_replace(
			'/(<div\b[^>]*\bwp-block-cover__inner-container\b)/',
			$markup . '$1',
			$content,
			1
		);

		if (!is_string($updated) || $updated === $content) {
			$updated = preg_replace(
				'/<\/div>\s*$/',
				$markup . '</div>',
				$content,
				1
			);
		}

		return is_string($updated) ? $updated : $content;
	}

	public static function enqueue(): void {
		if (self::$used) {
			return;
		}

		self::$used = true;
		if (!wp_style_is('nxt-ai-label-cover', 'registered')) {
			self::register_assets();
		}
		wp_enqueue_style('nxt-ai-label-cover');
		wp_enqueue_script('nxt-ai-label-cover');
	}

	/**
	 * @param array<string, mixed> $attrs
	 */
	private static function attachment_id(string $content, array $attrs): int {
		if (preg_match('/wp-block-cover__image-background[^>]*\bwp-image-(\d+)/', $content, $match) === 1) {
			return (int) $match[1];
		}
		if (preg_match('/\bwp-image-(\d+)[^>]*wp-block-cover__image-background/', $content, $match) === 1) {
			return (int) $match[1];
		}

		if (!empty($attrs['id'])) {
			return (int) $attrs['id'];
		}

		if (!empty($attrs['useFeaturedImage'])) {
			$post_id = get_the_ID();
			if (!$post_id) {
				$post_id = get_queried_object_id();
			}
			if ($post_id) {
				return (int) get_post_thumbnail_id($post_id);
			}
		}

		if (!empty($attrs['url']) && is_string($attrs['url'])) {
			return (int) attachment_url_to_postid($attrs['url']);
		}

		return 0;
	}
}
