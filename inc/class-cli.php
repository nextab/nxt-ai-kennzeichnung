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
	 * Kennzeichnungen aus dem Backup neu erzeugen.
	 *
	 * ## OPTIONS
	 *
	 * [--id=<ids>]
	 * : Kommagetrennte Attachment-IDs. Ohne Angabe: alle mit aktiver Kennzeichnung.
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
				WP_CLI::error('Keine gültigen IDs.');
			}
		}

		$result = NXT_AI_Label_Processor::regenerate($ids);

		WP_CLI::success(sprintf(
			'Neu erzeugt: %d. Übersprungen: %d.',
			$result['processed'],
			$result['skipped']
		));

		if ($result['ids'] !== []) {
			WP_CLI::log('IDs: ' . implode(', ', $result['ids']));
		}
	}

	/**
	 * Bilder ohne Entscheidung auf IPTC-KI-Herkunft prüfen und kennzeichnen.
	 *
	 * Nutzt Kennzeichnung, Position und Größe aus der Automatik-Einstellung.
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
				WP_CLI::log('Gekennzeichnet: ' . implode(', ', $batch['ids']));
			}
		} while (!$batch['done']);

		WP_CLI::success(sprintf('Geprüft: %d. Gekennzeichnet: %d.', $checked, $marked));
	}
}
