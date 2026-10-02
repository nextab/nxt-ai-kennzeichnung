<?php

if (!defined('ABSPATH')) {
	exit;
}

final class NXT_AI_Label_Admin {
	public static function register(): void {
		add_action('admin_menu', [self::class, 'menu']);
		add_action('admin_post_nxt_ai_label_regenerate', [self::class, 'handle_regenerate']);
		add_action('admin_post_nxt_ai_label_apply', [self::class, 'handle_apply']);
		add_action('admin_post_nxt_ai_label_auto_save', [self::class, 'handle_auto_save']);
		add_action('admin_post_nxt_ai_label_scan', [self::class, 'handle_scan']);
		add_filter('bulk_actions-upload', [self::class, 'bulk_actions']);
		add_filter('handle_bulk_actions-upload', [self::class, 'handle_bulk'], 10, 3);
		add_action('admin_notices', [self::class, 'notices']);
	}

	public static function menu(): void {
		add_management_page(
			__('AI label', 'nxt-ai-label'),
			__('AI label', 'nxt-ai-label'),
			'upload_files',
			'nxt-ai-label',
			[self::class, 'render_page']
		);
	}

	public static function render_page(): void {
		if (!current_user_can('upload_files')) {
			wp_die(esc_html__('You are not allowed to view this page.', 'nxt-ai-label'));
		}

		$bulk_ids = self::bulk_ids_from_request();
		if ($bulk_ids !== []) {
			self::render_apply_form($bulk_ids);
			return;
		}

		$count = count(NXT_AI_Label_Processor::labeled_ids());
		$auto = NXT_AI_Label_Auto::settings();
		?>
		<div class="wrap">
			<h1><?php esc_html_e('AI label', 'nxt-ai-label'); ?></h1>
			<p><?php esc_html_e('Only images you mark in the library are changed. Every other file stays as it is.', 'nxt-ai-label'); ?></p>
			<h2><?php esc_html_e('Automatic when the file declares AI origin', 'nxt-ai-label'); ?></h2>
			<p><?php esc_html_e('New uploads are labeled only when the file itself contains trainedAlgorithmicMedia or compositeWithTrainedAlgorithmicMedia (IPTC Digital Source Type, often via Content Credentials). A label you remove stays removed.', 'nxt-ai-label'); ?></p>
			<p><?php esc_html_e('Generators without that metadata should call nxt_ai_label_mark( $attachment_id ) after the attachment is created. The filter nxt_ai_label_attachment_is_generated runs only while automatic labeling is on.', 'nxt-ai-label'); ?></p>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<input type="hidden" name="action" value="nxt_ai_label_auto_save" />
				<?php wp_nonce_field('nxt_ai_label_auto_save'); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e('New uploads', 'nxt-ai-label'); ?></th>
						<td>
							<label><input type="checkbox" name="nxt_ai_label_auto_enabled" value="1" <?php checked($auto['enabled']); ?> /> <?php esc_html_e('Apply a label when the file declares an AI origin', 'nxt-ai-label'); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="nxt_ai_label_auto_slug"><?php esc_html_e('Label', 'nxt-ai-label'); ?></label></th>
						<td>
							<select name="nxt_ai_label_slug" id="nxt_ai_label_auto_slug">
								<?php echo self::label_options($auto['slug']); ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="nxt_ai_label_auto_position"><?php esc_html_e('Position', 'nxt-ai-label'); ?></label></th>
						<td>
							<select name="nxt_ai_label_position" id="nxt_ai_label_auto_position">
								<?php foreach (NXT_AI_Label_Labels::positions() as $key => $label) : ?>
									<option value="<?php echo esc_attr($key); ?>"<?php selected($auto['position'], $key); ?>><?php echo esc_html($label); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="nxt_ai_label_auto_scale"><?php esc_html_e('Size', 'nxt-ai-label'); ?></label></th>
						<td>
							<select name="nxt_ai_label_scale" id="nxt_ai_label_auto_scale">
								<?php foreach (NXT_AI_Label_Labels::scales() as $key => $item) : ?>
									<option value="<?php echo esc_attr($key); ?>"<?php selected($auto['scale'], $key); ?>><?php echo esc_html($item['label']); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>
				<?php submit_button(__('Save automatic labeling', 'nxt-ai-label'), 'secondary'); ?>
			</form>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<input type="hidden" name="action" value="nxt_ai_label_scan" />
				<?php wp_nonce_field('nxt_ai_label_scan'); ?>
				<?php submit_button(__('Scan existing images for AI origin', 'nxt-ai-label'), 'secondary'); ?>
			</form>
			<p class="description"><?php esc_html_e('Checks only images with no decision yet and writes the label using the settings above. WP-CLI: wp nxt-ai-label detect', 'nxt-ai-label'); ?></p>
			<p><?php esc_html_e('Single image: open it in the library, choose label, position and size, then Apply label.', 'nxt-ai-label'); ?></p>
			<p><?php esc_html_e('Several images: select them in the list and choose the bulk action Apply AI label.', 'nxt-ai-label'); ?></p>
			<p><strong><?php echo esc_html((string) $count); ?></strong> <?php echo esc_html(_n('image with an active label.', 'images with an active label.', $count, 'nxt-ai-label')); ?></p>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<input type="hidden" name="action" value="nxt_ai_label_regenerate" />
				<?php wp_nonce_field('nxt_ai_label_regenerate'); ?>
				<?php submit_button(__('Regenerate applied labels', 'nxt-ai-label'), 'secondary', 'submit', false, $count === 0 ? ['disabled' => 'disabled'] : null); ?>
			</form>
			<p class="description"><?php esc_html_e('Rewrites labels that are already applied, using the unmarked backup. It does not add new ones. WP-CLI: wp nxt-ai-label regenerate', 'nxt-ai-label'); ?></p>
		</div>
		<?php
	}

	/**
	 * @param list<int> $ids
	 */
	private static function render_apply_form(array $ids): void {
		$token = isset($_GET['bulk']) ? sanitize_key((string) $_GET['bulk']) : '';
		?>
		<div class="wrap">
			<h1><?php esc_html_e('Apply AI label', 'nxt-ai-label'); ?></h1>
			<p><?php echo esc_html(sprintf(
				/* translators: %d: number of selected images */
				_n('Only this %d image. The rest of the library stays untouched.', 'Only these %d images. The rest of the library stays untouched.', count($ids), 'nxt-ai-label'),
				count($ids)
			)); ?></p>
			<ul>
				<?php foreach ($ids as $id) : ?>
					<li><?php echo esc_html(get_the_title($id) !== '' ? get_the_title($id) : ('#' . $id)); ?> <code>#<?php echo esc_html((string) $id); ?></code></li>
				<?php endforeach; ?>
			</ul>
			<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
				<input type="hidden" name="action" value="nxt_ai_label_apply" />
				<input type="hidden" name="bulk" value="<?php echo esc_attr($token); ?>" />
				<?php wp_nonce_field('nxt_ai_label_apply'); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e('Action', 'nxt-ai-label'); ?></th>
						<td>
							<label><input type="radio" name="nxt_ai_label_mode" value="set" checked="checked" /> <?php esc_html_e('Apply label', 'nxt-ai-label'); ?></label><br />
							<label><input type="radio" name="nxt_ai_label_mode" value="remove" /> <?php esc_html_e('Remove label', 'nxt-ai-label'); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="nxt_ai_label_slug"><?php esc_html_e('Label', 'nxt-ai-label'); ?></label></th>
						<td>
							<select name="nxt_ai_label_slug" id="nxt_ai_label_slug">
								<?php
								$current_group = '';
								foreach (NXT_AI_Label_Labels::all() as $key => $item) {
									if ($item['group'] !== $current_group) {
										if ($current_group !== '') {
											echo '</optgroup>';
										}
										$current_group = $item['group'];
										echo '<optgroup label="' . esc_attr($current_group) . '">';
									}
									printf(
										'<option value="%s"%s>%s</option>',
										esc_attr($key),
										selected(NXT_AI_Label_Labels::DEFAULT_SLUG, $key, false),
										esc_html($item['label'])
									);
								}
								if ($current_group !== '') {
									echo '</optgroup>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="nxt_ai_label_position"><?php esc_html_e('Position', 'nxt-ai-label'); ?></label></th>
						<td>
							<select name="nxt_ai_label_position" id="nxt_ai_label_position">
								<?php foreach (NXT_AI_Label_Labels::positions() as $key => $label) : ?>
									<option value="<?php echo esc_attr($key); ?>"<?php selected(NXT_AI_Label_Labels::DEFAULT_POSITION, $key); ?>><?php echo esc_html($label); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="nxt_ai_label_scale"><?php esc_html_e('Size', 'nxt-ai-label'); ?></label></th>
						<td>
							<select name="nxt_ai_label_scale" id="nxt_ai_label_scale">
								<?php foreach (NXT_AI_Label_Labels::scales() as $key => $item) : ?>
									<option value="<?php echo esc_attr($key); ?>"<?php selected(NXT_AI_Label_Labels::DEFAULT_SCALE, $key); ?>><?php echo esc_html($item['label']); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>
				<?php submit_button(__('Apply to selected images', 'nxt-ai-label')); ?>
			</form>
		</div>
		<?php
	}

	public static function handle_regenerate(): void {
		if (!current_user_can('upload_files')) {
			wp_die(esc_html__('You are not allowed to do this.', 'nxt-ai-label'));
		}

		check_admin_referer('nxt_ai_label_regenerate');

		$result = NXT_AI_Label_Processor::regenerate();
		self::redirect_with_result($result, 'regenerated');
	}

	public static function handle_apply(): void {
		if (!current_user_can('upload_files')) {
			wp_die(esc_html__('You are not allowed to do this.', 'nxt-ai-label'));
		}

		check_admin_referer('nxt_ai_label_apply');

		$token = isset($_POST['bulk']) ? sanitize_key((string) $_POST['bulk']) : '';
		$ids = self::bulk_ids($token);
		if ($ids === []) {
			wp_die(esc_html__('That selection has expired. Select the images in the library again.', 'nxt-ai-label'));
		}

		$mode = isset($_POST['nxt_ai_label_mode']) ? sanitize_key((string) $_POST['nxt_ai_label_mode']) : 'set';
		$enabled = $mode !== 'remove';
		$slug = isset($_POST['nxt_ai_label_slug']) ? sanitize_text_field(wp_unslash((string) $_POST['nxt_ai_label_slug'])) : NXT_AI_Label_Labels::DEFAULT_SLUG;
		$position = isset($_POST['nxt_ai_label_position']) ? sanitize_key((string) $_POST['nxt_ai_label_position']) : NXT_AI_Label_Labels::DEFAULT_POSITION;
		$scale = isset($_POST['nxt_ai_label_scale']) ? sanitize_key((string) $_POST['nxt_ai_label_scale']) : NXT_AI_Label_Labels::DEFAULT_SCALE;

		$result = NXT_AI_Label_Processor::apply_settings($ids, $enabled, $slug, $position, $scale);
		delete_transient(self::transient_key($token));
		self::redirect_with_result($result, $enabled ? 'applied' : 'removed');
	}

	/**
	 * @param array<string, string> $actions
	 * @return array<string, string>
	 */
	public static function bulk_actions(array $actions): array {
		$actions['nxt_ai_label_apply'] = __('Apply AI label', 'nxt-ai-label');
		$actions['nxt_ai_label_regenerate'] = __('Regenerate AI label', 'nxt-ai-label');
		return $actions;
	}

	/**
	 * @param list<int> $post_ids
	 */
	public static function handle_bulk(string $redirect_to, string $doaction, array $post_ids): string {
		if (!current_user_can('upload_files')) {
			return $redirect_to;
		}

		$ids = [];
		foreach ($post_ids as $post_id) {
			$post_id = (int) $post_id;
			if ($post_id > 0 && current_user_can('edit_post', $post_id) && wp_attachment_is_image($post_id)) {
				$ids[] = $post_id;
			}
		}

		if ($doaction === 'nxt_ai_label_apply') {
			if ($ids === []) {
				return add_query_arg('nxt_ai_label_skipped', count($post_ids), $redirect_to);
			}

			$token = wp_generate_password(12, false, false);
			set_transient(self::transient_key($token), $ids, 15 * MINUTE_IN_SECONDS);

			return add_query_arg(
				[
					'page' => 'nxt-ai-label',
					'bulk' => $token,
				],
				admin_url('tools.php')
			);
		}

		if ($doaction === 'nxt_ai_label_regenerate') {
			$result = NXT_AI_Label_Processor::regenerate($ids);
			return add_query_arg(
				[
					'nxt_ai_label_notice' => 'regenerated',
					'nxt_ai_label_done' => (int) $result['processed'],
					'nxt_ai_label_skipped' => (int) $result['skipped'],
				],
				$redirect_to
			);
		}

		return $redirect_to;
	}

	public static function notices(): void {
		if (!isset($_GET['nxt_ai_label_done'])) {
			return;
		}

		if (!current_user_can('upload_files')) {
			return;
		}

		$done = (int) $_GET['nxt_ai_label_done'];
		$skipped = isset($_GET['nxt_ai_label_skipped']) ? (int) $_GET['nxt_ai_label_skipped'] : 0;
		$notice = isset($_GET['nxt_ai_label_notice']) ? sanitize_key((string) $_GET['nxt_ai_label_notice']) : 'regenerated';

		$checked = isset($_GET['nxt_ai_label_checked']) ? (int) $_GET['nxt_ai_label_checked'] : 0;

		$message = match ($notice) {
			'applied' => sprintf(
				/* translators: 1: images changed, 2: images skipped */
				__('AI label applied: %1$d. Skipped: %2$d.', 'nxt-ai-label'),
				$done,
				$skipped
			),
			'removed' => sprintf(
				/* translators: 1: images changed, 2: images skipped */
				__('AI label removed: %1$d. Skipped: %2$d.', 'nxt-ai-label'),
				$done,
				$skipped
			),
			'detected' => sprintf(
				/* translators: 1: images checked, 2: images labeled */
				__('AI origin checked: %1$d. Labeled: %2$d.', 'nxt-ai-label'),
				$checked,
				$done
			),
			'saved' => __('Automatic labeling saved.', 'nxt-ai-label'),
			default => sprintf(
				/* translators: 1: images regenerated, 2: images skipped */
				__('AI label regenerated: %1$d. Skipped: %2$d. Images without a label stay unchanged.', 'nxt-ai-label'),
				$done,
				$skipped
			),
		};

		echo '<div class="notice notice-success is-dismissible"><p>';
		echo esc_html($message);
		echo '</p></div>';
	}

	/**
	 * @param array{processed: int, skipped: int, ids: list<int>} $result
	 */
	private static function redirect_with_result(array $result, string $notice): void {
		$redirect = add_query_arg(
			[
				'page' => 'nxt-ai-label',
				'nxt_ai_label_notice' => $notice,
				'nxt_ai_label_done' => (int) $result['processed'],
				'nxt_ai_label_skipped' => (int) $result['skipped'],
			],
			admin_url('tools.php')
		);

		wp_safe_redirect($redirect);
		exit;
	}

	/**
	 * @return list<int>
	 */
	private static function bulk_ids_from_request(): array {
		if (!isset($_GET['bulk'])) {
			return [];
		}

		return self::bulk_ids(sanitize_key((string) $_GET['bulk']));
	}

	/**
	 * @return list<int>
	 */
	private static function bulk_ids(string $token): array {
		if ($token === '') {
			return [];
		}

		$stored = get_transient(self::transient_key($token));
		if (!is_array($stored)) {
			return [];
		}

		$ids = [];
		foreach ($stored as $id) {
			$id = (int) $id;
			if ($id > 0 && current_user_can('edit_post', $id) && wp_attachment_is_image($id)) {
				$ids[] = $id;
			}
		}

		return $ids;
	}

	private static function transient_key(string $token): string {
		return 'nxt_ai_label_bulk_' . get_current_user_id() . '_' . $token;
	}

	private static function label_options(string $selected): string {
		$html = '';
		$current_group = '';
		foreach (NXT_AI_Label_Labels::all() as $key => $item) {
			if ($item['group'] !== $current_group) {
				if ($current_group !== '') {
					$html .= '</optgroup>';
				}
				$current_group = $item['group'];
				$html .= '<optgroup label="' . esc_attr($current_group) . '">';
			}
			$html .= sprintf(
				'<option value="%s"%s>%s</option>',
				esc_attr($key),
				selected($selected, $key, false),
				esc_html($item['label'])
			);
		}
		if ($current_group !== '') {
			$html .= '</optgroup>';
		}

		return $html;
	}

	public static function handle_auto_save(): void {
		if (!current_user_can('upload_files')) {
			wp_die(esc_html__('You are not allowed to do this.', 'nxt-ai-label'));
		}

		check_admin_referer('nxt_ai_label_auto_save');

		NXT_AI_Label_Auto::save([
			'enabled' => isset($_POST['nxt_ai_label_auto_enabled']) ? '1' : '0',
			'slug' => isset($_POST['nxt_ai_label_slug']) ? sanitize_text_field(wp_unslash((string) $_POST['nxt_ai_label_slug'])) : '',
			'position' => isset($_POST['nxt_ai_label_position']) ? sanitize_key((string) $_POST['nxt_ai_label_position']) : '',
			'scale' => isset($_POST['nxt_ai_label_scale']) ? sanitize_key((string) $_POST['nxt_ai_label_scale']) : '',
		]);

		self::redirect_with_result([
			'processed' => 0,
			'skipped' => 0,
			'ids' => [],
		], 'saved');
	}

	public static function handle_scan(): void {
		if (!current_user_can('upload_files')) {
			wp_die(esc_html__('You are not allowed to do this.', 'nxt-ai-label'));
		}

		check_admin_referer('nxt_ai_label_scan');

		$after = 0;
		if (isset($_POST['after'])) {
			$after = (int) $_POST['after'];
		} elseif (isset($_GET['after'])) {
			$after = (int) $_GET['after'];
		}

		$batch = NXT_AI_Label_Auto::scan_batch($after, 25);
		$stats_key = 'nxt_ai_label_scan_' . get_current_user_id();
		$stats = $after === 0 ? null : get_transient($stats_key);
		if (!is_array($stats)) {
			$stats = [
				'checked' => 0,
				'marked' => 0,
			];
		}
		$stats['checked'] = (int) ($stats['checked'] ?? 0) + $batch['checked'];
		$stats['marked'] = (int) ($stats['marked'] ?? 0) + $batch['marked'];
		set_transient($stats_key, $stats, HOUR_IN_SECONDS);

		if (!$batch['done']) {
			$url = wp_nonce_url(
				add_query_arg(
					[
						'action' => 'nxt_ai_label_scan',
						'after' => $batch['last_id'],
					],
					admin_url('admin-post.php')
				),
				'nxt_ai_label_scan'
			);
			wp_safe_redirect($url);
			exit;
		}

		delete_transient($stats_key);
		$redirect = add_query_arg(
			[
				'page' => 'nxt-ai-label',
				'nxt_ai_label_notice' => 'detected',
				'nxt_ai_label_done' => (int) $stats['marked'],
				'nxt_ai_label_checked' => (int) $stats['checked'],
				'nxt_ai_label_skipped' => 0,
			],
			admin_url('tools.php')
		);
		wp_safe_redirect($redirect);
		exit;
	}
}
