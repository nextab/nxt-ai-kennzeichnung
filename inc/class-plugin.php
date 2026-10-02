<?php

if (!defined('ABSPATH')) {
	exit;
}

final class NXT_AI_Label_Plugin {
	public static function register(): void {
		add_action('plugins_loaded', [self::class, 'boot']);
	}

	public static function boot(): void {
		add_action('init', [self::class, 'load_textdomain']);
		NXT_AI_Label_Media_Fields::register();
		NXT_AI_Label_Admin::register();
		NXT_AI_Label_Cover::register();

		add_filter('wp_generate_attachment_metadata', [NXT_AI_Label_Auto::class, 'maybe_flag'], 20, 2);
		add_filter('wp_generate_attachment_metadata', [self::class, 'on_generate_metadata'], 100, 2);
		add_action('delete_attachment', [self::class, 'on_delete_attachment'], 10, 1);

		if (defined('WP_CLI') && WP_CLI) {
			require_once NXT_AI_LABEL_DIR . 'inc/class-cli.php';
			NXT_AI_Label_CLI::register();
		}
	}

	/**
	 * @param array<string, mixed> $metadata
	 * @return array<string, mixed>
	 */
	public static function on_generate_metadata(array $metadata, int $attachment_id): array {
		if (!wp_attachment_is_image($attachment_id)) {
			return $metadata;
		}

		$enabled = get_post_meta($attachment_id, '_nxt_ai_label_enabled', true) === '1';
		if (!$enabled) {
			return $metadata;
		}

		NXT_AI_Label_Processor::process($attachment_id);

		return $metadata;
	}

	public static function on_delete_attachment(int $attachment_id): void {
		NXT_AI_Label_Processor::delete_backup($attachment_id);
	}

	public static function load_textdomain(): void {
		load_plugin_textdomain(
			'nxt-ai-label',
			false,
			dirname(plugin_basename(NXT_AI_LABEL_FILE)) . '/languages'
		);
	}
}
