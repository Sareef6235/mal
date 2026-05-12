<?php
/** Deactivation tasks. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MLP_Deactivator { public static function deactivate(): void { flush_rewrite_rules(); } }
