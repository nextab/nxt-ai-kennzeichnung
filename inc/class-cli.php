<?php

if (!defined('ABSPATH')) {
	exit;
}

final class NXT_AI_Label_CLI {
	public static function register(): void {
		if (!class_exists('WP_CLI')) {
			return;
		}

		WP_CLI::add_command('nxt-ai-label', self::class);
	}

	/**
	 * Rewrite applied labels from the unmarked backup.
	 *
	 * ## OPTIONS
	 *
	 * [--id=<ids>]
	 * : Comma-separated attachment IDs. Default: every attachment that already has a label.
	 *
	 * ## EXAMPLES
	 *
	 *     wp nxt-ai-label regenerate
	 *     wp nxt-ai-label regenerate --id=12,34
	 *
	 * @when after_wp_load
	 *
	 * @param list<string> $args
	 * @param array<string, string> $assoc_args
	 */
	public function regenerate(array $args, array $assoc_args): void {
		$ids = null;
		if (isset($assoc_args['id']) && $assoc_args['id'] !== '') {
			$ids = array_values(array_filter(array_map('intval', explode(',', $assoc_args['id']))));
			if ($ids === []) {
				WP_CLI::error(__('No valid IDs.', 'nxt-ai-label'));
			}
		}

		$result = NXT_AI_Label_Processor::regenerate($ids);

		WP_CLI::success(sprintf(
			/* translators: 1: images regenerated, 2: images skipped */
			__('Regenerated: %1$d. Skipped: %2$d.', 'nxt-ai-label'),
			$result['processed'],
			$result['skipped']
		));

		if ($result['ids'] !== []) {
			WP_CLI::log('IDs: ' . implode(', ', $result['ids']));
		}
	}

	/**
	 * Check attachments with no decision yet for an IPTC AI origin and label matches.
	 *
	 * Uses the label, position and size stored for automatic labeling.
	 *
	 * ## EXAMPLES
	 *
	 *     wp nxt-ai-label detect
	 *
	 * @when after_wp_load
	 *
	 * @param list<string> $args
	 * @param array<string, string> $assoc_args
	 */
	public function detect(array $args, array $assoc_args): void {
		$after = 0;
		$checked = 0;
		$marked = 0;

		do {
			$batch = NXT_AI_Label_Auto::scan_batch($after, 50);
			$checked += $batch['checked'];
			$marked += $batch['marked'];
			$after = $batch['last_id'];
			if ($batch['ids'] !== []) {
				WP_CLI::log(sprintf(
					/* translators: %s: comma-separated attachment IDs */
					__('Labeled: %s', 'nxt-ai-label'),
					implode(', ', $batch['ids'])
				));
			}
		} while (!$batch['done']);

		WP_CLI::success(sprintf(
			/* translators: 1: images checked, 2: images labeled */
			__('Checked: %1$d. Labeled: %2$d.', 'nxt-ai-label'),
			$checked,
			$marked
		));
	}
}
