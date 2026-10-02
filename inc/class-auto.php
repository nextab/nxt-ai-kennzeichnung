<?php

if (!defined('ABSPATH')) {
	exit;
}

final class NXT_AI_Label_Auto {
	public const OPTION = 'nxt_ai_label_auto';

	/**
	 * @return array{enabled: bool, slug: string, position: string, scale: string}
	 */
	public static function settings(): array {
		$stored = get_option(self::OPTION, []);
		if (!is_array($stored)) {
			$stored = [];
		}

		$slug = isset($stored['slug']) ? (string) $stored['slug'] : NXT_AI_Label_Labels::DEFAULT_SLUG;
		$position = isset($stored['position']) ? (string) $stored['position'] : NXT_AI_Label_Labels::DEFAULT_POSITION;
		$scale = isset($stored['scale']) ? (string) $stored['scale'] : NXT_AI_Label_Labels::DEFAULT_SCALE;

		if (!NXT_AI_Label_Labels::is_valid_slug($slug)) {
			$slug = NXT_AI_Label_Labels::DEFAULT_SLUG;
		}
		if (!NXT_AI_Label_Labels::is_valid_position($position)) {
			$position = NXT_AI_Label_Labels::DEFAULT_POSITION;
		}
		if (!NXT_AI_Label_Labels::is_valid_scale($scale)) {
			$scale = NXT_AI_Label_Labels::DEFAULT_SCALE;
		}

		return [
			'enabled' => ($stored['enabled'] ?? '0') === '1',
			'slug' => $slug,
			'position' => $position,
			'scale' => $scale,
		];
	}

	/**
	 * @param array{enabled?: string, slug?: string, position?: string, scale?: string} $input
	 */
	public static function save(array $input): void {
		$current = self::settings();
		$slug = isset($input['slug']) ? (string) $input['slug'] : $current['slug'];
		$position = isset($input['position']) ? (string) $input['position'] : $current['position'];
		$scale = isset($input['scale']) ? (string) $input['scale'] : $current['scale'];

		if (!NXT_AI_Label_Labels::is_valid_slug($slug)) {
			$slug = NXT_AI_Label_Labels::DEFAULT_SLUG;
		}
		if (!NXT_AI_Label_Labels::is_valid_position($position)) {
			$position = NXT_AI_Label_Labels::DEFAULT_POSITION;
		}
		if (!NXT_AI_Label_Labels::is_valid_scale($scale)) {
			$scale = NXT_AI_Label_Labels::DEFAULT_SCALE;
		}

		update_option(self::OPTION, [
			'enabled' => !empty($input['enabled']) ? '1' : '0',
			'slug' => $slug,
			'position' => $position,
			'scale' => $scale,
		], false);
	}

	/**
	 * @param array<string, mixed> $metadata
	 * @return array<string, mixed>
	 */
	public static function maybe_flag(array $metadata, int $attachment_id): array {
		$settings = self::settings();
		if (!$settings['enabled'] || !wp_attachment_is_image($attachment_id)) {
			return $metadata;
		}

		$current = get_post_meta($attachment_id, '_nxt_ai_label_enabled', true);
		if ($current === '0' || $current === '1') {
			return $metadata;
		}

		if (!self::attachment_is_generated($attachment_id, $metadata)) {
			return $metadata;
		}

		update_post_meta($attachment_id, '_nxt_ai_label_enabled', '1');
		update_post_meta($attachment_id, '_nxt_ai_label_slug', $settings['slug']);
		update_post_meta($attachment_id, '_nxt_ai_label_position', $settings['position']);
		update_post_meta($attachment_id, '_nxt_ai_label_scale', $settings['scale']);
		update_post_meta($attachment_id, '_nxt_ai_label_auto', '1');

		return $metadata;
	}

	/**
	 * @param array<string, mixed> $metadata
	 */
	public static function attachment_is_generated(int $attachment_id, array $metadata = []): bool {
		$path = get_attached_file($attachment_id);
		$declared = is_string($path) && NXT_AI_Label_Detector::file_declares_ai($path);

		return (bool) apply_filters('nxt_ai_label_attachment_is_generated', $declared, $attachment_id, $metadata);
	}

	/**
	 * @return array{checked: int, marked: int, last_id: int, done: bool, ids: list<int>}
	 */
	public static function scan_batch(int $after_id, int $limit = 25): array {
		global $wpdb;

		$limit = max(1, min(50, $limit));
		$after_id = max(0, $after_id);
		$like = $wpdb->esc_like('image/') . '%';

		$ids = $wpdb->get_col($wpdb->prepare(
			"SELECT p.ID FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} m ON p.ID = m.post_id AND m.meta_key = %s
			WHERE p.post_type = 'attachment'
				AND p.post_status = 'inherit'
				AND p.post_mime_type LIKE %s
				AND p.ID > %d
				AND m.meta_id IS NULL
			ORDER BY p.ID ASC
			LIMIT %d",
			'_nxt_ai_label_enabled',
			$like,
			$after_id,
			$limit
		));

		if (!is_array($ids)) {
			$ids = [];
		}

		$settings = self::settings();
		$marked = 0;
		$marked_ids = [];
		$last_id = $after_id;

		foreach ($ids as $attachment_id) {
			$attachment_id = (int) $attachment_id;
			$last_id = $attachment_id;
			if (!self::attachment_is_generated($attachment_id)) {
				continue;
			}

			if (!NXT_AI_Label_Processor::mark($attachment_id, true, $settings['slug'], $settings['position'], $settings['scale'])) {
				continue;
			}

			update_post_meta($attachment_id, '_nxt_ai_label_auto', '1');
			$marked++;
			$marked_ids[] = $attachment_id;
		}

		return [
			'checked' => count($ids),
			'marked' => $marked,
			'last_id' => $last_id,
			'done' => count($ids) < $limit,
			'ids' => $marked_ids,
		];
	}
}
