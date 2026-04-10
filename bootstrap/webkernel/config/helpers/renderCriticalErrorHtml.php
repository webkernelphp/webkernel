<?php

/**
 * Render critical error page and terminate execution
 *
 * @param string $title
 * @param string $message
 * @param int $code
 * @param string $severity
 * @param string|null $exception
 * @param string|null $logBasePath  Base path for logging (optional)
 *
 * @return never
 */
if (!function_exists('renderCriticalErrorHtml')) {
  function renderCriticalErrorHtml(
    string $title,
    string $message,
    int $code = 500,
    string $severity = 'CRITICAL',
    ?string $exception = null,
    ?string $logBasePath = null,
  ): never {
    $incidentId = 'INC-' . strtoupper(substr(hash('sha256', $message . microtime(true)), 0, 7));
    $timestamp = gmdate('Y-m-d\TH:i:s\Z');

    // Log if a base path is provided
    if ($logBasePath !== null) {
      logCriticalIncident($incidentId, $severity, $title, $message, $code, $logBasePath);
    }

    if (PHP_SAPI === 'cli') {
      $cliMessage = sprintf(
        "SYSTEM STATE: SEALED\nINCIDENT: %s\nSEVERITY: %s\nTIMESTAMP (UTC): %s\n\n%s\n%s\n\nNo further action is permitted.\n",
        $incidentId,
        $severity,
        $timestamp,
        strtoupper($title),
        $message,
      );

      fwrite(STDERR, $cliMessage);
      throw new \RuntimeException($message, $code);
    }

    http_response_code($code);

    $escapedMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $escapedSeverity = htmlspecialchars($severity, ENT_QUOTES, 'UTF-8');
    $escapedTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

    echo "<!DOCTYPE HTML><html lang='en'><head><meta charset='UTF-8'/><meta name='viewport' content='width=device-width,initial-scale=1'/><title>SYSTEM SEALED</title><link rel='preconnect' href='https://fonts.googleapis.com'/><link rel='preconnect' href='https://fonts.gstatic.com' crossorigin/><link href='https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&display=swap' rel='stylesheet'/><style>*{margin:0;padding:0;box-sizing:border-box}::selection,::-moz-selection{background:transparent}html,body{user-select:none;pointer-events:none;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;-webkit-touch-callout:none}body{font-family:'Space Grotesk',system-ui,sans-serif;background:#000;color:#d0d0d0;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:.75rem}.card{max-width:680px;width:100%;background:#0d0d0d;border:1px solid #1a1a1a;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.8)}.header{background:#080808;border-bottom:1px solid #1a1a1a;padding:.65rem 1rem;display:flex;align-items:center;gap:.75rem}.logo{flex:1;display:flex;justify-content:flex-start}.incident-id{flex:1;text-align:center;color:#888;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;display:flex;align-items:center;justify-content:center}.severity-container{flex:1;text-align:right;display:flex;align-items:center;justify-content:flex-end;gap:.5rem}.severity-icon{width:16px;height:16px;display:inline-block}.severity-icon svg{width:100%;height:100%;display:block}.severity{color:#ff3333;font-weight:700;font-size:.7rem;text-transform:uppercase;letter-spacing:.08em}.system-state{font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:#fff}.content{padding:1rem}.title-container{margin-bottom:1rem;text-align:center}.title-container .system-state img{max-width:290px;width:100%;padding-bottom:1rem;height:auto;display:block;margin:0 auto;opacity:.9}.boundary-violation{font-size:.8rem;font-weight:500;color:#ff3333;text-transform:uppercase;letter-spacing:.1em}.msg{background:rgba(255,0,0,.08);border-left:2px solid #ff3333;padding:.75rem;margin:.75rem 0;font-size:.8rem;line-height:1.5;white-space:pre-wrap;word-break:break-word;color:#ff6666}.footer{padding:.65rem 1rem;text-align:center;font-size:.7rem;color:#666;text-transform:uppercase;letter-spacing:.08em;background:#080808;border-top:1px solid #1a1a1a}.timestamp-outer{margin-top:1.25rem;text-align:center;font-size:.65rem;color:rgba(255,255,255,.5);font-family:'Courier New',monospace;letter-spacing:.05em}@media(max-width:640px){.title-container .system-state img{max-width:48px}.content{padding:.85rem}.msg{font-size:.75rem;padding:.65rem}.footer{font-size:.65rem}.timestamp-outer{font-size:.6rem;margin-top:1rem}.severity-icon{width:14px;height:14px}}</style></head><body><div class='card'><div class='header'><div class='logo'><div class='system-state'>SYSTEM STATE: SEALED</div></div><div class='incident-id' id='incident-id'>{$incidentId}</div><div class='severity-container'><div class='severity-icon'><svg viewBox='0 0 24 24' fill='none' stroke='#ff3333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z'/><line x1='12' y1='9' x2='12' y2='13'/><line x1='12' y1='17' x2='12.01' y2='17'/></svg></div><div class='severity' id='severity'>{$escapedSeverity}</div></div></div><div class='content'><div class='title-container'><div class='system-state'><picture><source srcset='/src-app/logo-dark-mode.png' media='(prefers-color-scheme:dark)'/><img src='/src-app/logo-light-mode.png' alt='SYSTEM' loading='eager'/></picture></div><div class='boundary-violation' id='title'>{$escapedTitle}</div></div><div class='msg' id='msg'>{$escapedMessage}</div></div><div class='footer'>NO FURTHER ACTION IS PERMITTED</div></div><div class='timestamp-outer' id='timestamp'>TIMESTAMP (UTC): {$timestamp}</div></body></html>";

    exit(1);
  }
}

/**
 * Log critical incident to system storage
 *
 * @param string $incidentId
 * @param string $severity
 * @param string $title
 * @param string $message
 * @param int $code
 * @param string $basePath
 *
 * @return void
 */
if (!function_exists('logCriticalIncident')) {
  function logCriticalIncident(
    string $incidentId,
    string $severity,
    string $title,
    string $message,
    int $code,
    string $basePath,
  ): void {
    $logDir = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'logs';

    if (!is_dir($logDir)) {
      @mkdir($logDir, 0755, true);
    }

    $logFile = "{$logDir}/critical-incidents.log";
    $timestamp = gmdate('Y-m-d\TH:i:s\Z');

    $logEntry = sprintf(
      "[%s] INCIDENT: %s | SEVERITY: %s | CODE: %d | STATE: %s | DETAILS: %s | USER_AGENT: %s | IP: %s\n",
      $timestamp,
      $incidentId,
      $severity,
      $code,
      $title,
      $message,
      $_SERVER['HTTP_USER_AGENT'] ?? 'CLI',
      $_SERVER['REMOTE_ADDR'] ?? 'INTERNAL',
    );

    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
  }
}
