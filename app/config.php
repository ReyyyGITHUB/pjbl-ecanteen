<?php
declare(strict_types=1);

// Local Laragon defaults
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'e_canteen');

// Auth/session
define('SESSION_KEY', 'ecanteen_user');

// Payment proof upload
define('PAYMENT_PROOF_DIR', dirname(__DIR__) . '/storage/payment-proofs');
define('PAYMENT_PROOF_PUBLIC_PATH', 'storage/payment-proofs');
define('PAYMENT_PROOF_MAX_BYTES', 5 * 1024 * 1024);

// Local WhatsApp bot
define('WA_BOT_ENDPOINT', 'http://127.0.0.1:3055/send-order');
define('WA_BOT_TOKEN', 'ecanteen-local-demo-token');
define('WA_BOT_TIMEOUT_SECONDS', 8);
