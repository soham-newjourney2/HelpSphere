<?php
//scripts/run_escalation_loop.php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/escalation.php';

while (true) {
    try {
        runEscalationEngine($pdo);
    } catch (Throwable $e) {
        error_log('Escalation loop: ' . $e->getMessage());
    }
    sleep(30);   // checks twice a minute
}
