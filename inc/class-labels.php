<?php

if (!defined('ABSPATH')) {
	exit;
}

final class NXT_AI_Label_Labels {
	public const DEFAULT_SLUG = 'ai-generated-black-transparent';
	public const DEFAULT_POSITION = 'bottom-right';
	public const DEFAULT_SCALE = 'medium';

	/**
	 * @return array<string, array{file: string, label: string, group: string, shape: string}>
	 */
	public static function all(): array {
		return [
			'ai-generated-black-transparent' => [
				'file' => 'ai-generated-black-transparent.png',
				'label' => 'AI Generated · schwarz · halbtransparent',
				'group' => 'AI Generated',
				'shape' => 'wide',
			],
			'ai-generated-black' => [
				'file' => 'ai-generated-black.png',
				'label' => 'AI Generated · schwarz · deckend',
				'group' => 'AI Generated',
				'shape' => 'wide',
			],
			'ai-generated-white-transparent' => [
				'file' => 'ai-generated-white-transparent.png',
				'label' => 'AI Generated · weiß · halbtransparent',
				'group' => 'AI Generated',
				'shape' => 'wide',
			],
			'ai-generated-white' => [
				'file' => 'ai-generated-white.png',
				'label' => 'AI Generated · weiß · deckend',
				'group' => 'AI Generated',
				'shape' => 'wide',
			],
			'ai-modified-black-transparent' => [
				'file' => 'ai-modified-black-transparent.png',
				'label' => 'AI Modified · schwarz · halbtransparent',
				'group' => 'AI Modified',
				'shape' => 'wide',
			],
			'ai-modified-black' => [
				'file' => 'ai-modified-black.png',
				'label' => 'AI Modified · schwarz · deckend',
				'group' => 'AI Modified',
				'shape' => 'wide',
			],
			'ai-modified-white-transparent' => [
				'file' => 'ai-modified-white-transparent.png',
				'label' => 'AI Modified · weiß · halbtransparent',
				'group' => 'AI Modified',
				'shape' => 'wide',
			],
			'ai-modified-white' => [
				'file' => 'ai-modified-white.png',
				'label' => 'AI Modified · weiß · deckend',
				'group' => 'AI Modified',
				'shape' => 'wide',
			],
			'ai-black-transparent' => [
				'file' => 'ai-black-transparent.png',
				'label' => 'AI · schwarz · halbtransparent',
				'group' => 'AI',
				'shape' => 'square',
			],
			'ai-black' => [
				'file' => 'ai-black.png',
				'label' => 'AI · schwarz · deckend',
				'group' => 'AI',
				'shape' => 'square',
			],
			'ai-white-transparent' => [
				'file' => 'ai-white-transparent.png',
				'label' => 'AI · weiß · halbtransparent',
				'group' => 'AI',
				'shape' => 'square',
			],
			'ai-white' => [
				'file' => 'ai-white.png',
				'label' => 'AI · weiß · deckend',
				'group' => 'AI',
				'shape' => 'square',
			],
		];
	}

	public static function is_valid_slug(string $slug): bool {
		return isset(self::all()[$slug]);
	}

	public static function path(string $slug): ?string {
		$all = self::all();
		if (!isset($all[$slug])) {
			return null;
		}

		$path = NXT_AI_LABEL_DIR . 'assets/labels/' . $all[$slug]['file'];
		return is_readable($path) ? $path : null;
	}

	/**
	 * @return array<string, string>
	 */
	public static function positions(): array {
		return [
			'top-left' => 'Oben links',
			'top-right' => 'Oben rechts',
			'bottom-left' => 'Unten links',
			'bottom-right' => 'Unten rechts',
		];
	}

	public static function is_valid_position(string $position): bool {
		return isset(self::positions()[$position]);
	}

	/**
	 * @return array<string, array{label: string, height: int}>
	 */
	public static function scales(): array {
		return [
			'small' => [
				'label' => 'Klein',
				'height' => 30,
			],
			'medium' => [
				'label' => 'Mittel',
				'height' => 40,
			],
			'large' => [
				'label' => 'Groß',
				'height' => 50,
			],
		];
	}

	public static function is_valid_scale(string $scale): bool {
		return isset(self::scales()[$scale]);
	}

	public static function scale_height(string $scale): int {
		$scales = self::scales();
		return $scales[$scale]['height'] ?? 40;
	}

	/**
	 * @return array{0: int, 1: int}
	 */
	public static function pixel_size(string $slug): array {
		$shape = self::shape($slug);
		if ($shape === 'square') {
			return [2363, 2363];
		}
		if (str_contains($slug, 'modified')) {
			return [7087, 2363];
		}

		return [7459, 2363];
	}

	public static function shape(string $slug): string {
		$all = self::all();
		return $all[$slug]['shape'] ?? 'wide';
	}
}
