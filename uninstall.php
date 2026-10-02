<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

require_once __DIR__ . '/inc/class-labels.php';
require_once __DIR__ . '/inc/class-processor.php';

NXT_AI_Label_Processor::restore_all_and_cleanup();
