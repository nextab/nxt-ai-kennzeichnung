<?php

if (!defined('ABSPATH')) {
	exit;
}

final class NXT_AI_Label_Detector {
	private const MARKERS = [
		'trainedAlgorithmicMedia',
		'compositeWithTrainedAlgorithmicMedia',
	];

	public static function file_declares_ai(string $path): bool {
		if ($path === '' || !is_readable($path)) {
			return false;
		}

		$size = filesize($path);
		if ($size === false || $size <= 0) {
			return false;
		}

		$handle = fopen($path, 'rb');
		if ($handle === false) {
			return false;
		}

		$head_len = (int) min($size, 524288);
		$blob = (string) fread($handle, $head_len);
		if ($size > 524288) {
			$tail_len = (int) min(262144, $size);
			fseek($handle, -$tail_len, SEEK_END);
			$blob .= (string) fread($handle, $tail_len);
		}
		fclose($handle);

		foreach (self::MARKERS as $marker) {
			if (str_contains($blob, $marker)) {
				return true;
			}
		}

		return false;
	}
}
