<?php
// Error page template
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Error <?= $statusCode ?? 500 ?></title>
  <style>
    :root {
      --primary-color: #d32f2f;
      --text-color: #333;
      --bg-color: #f5f5f5;
      --card-bg: #fff;
      --border-color: #ddd;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: var(--bg-color);
      color: var(--text-color);
      line-height: 1.6;
    }

    .container {
      max-width: 800px;
      margin: 40px auto;
      padding: 0 20px;
    }

    .error-card {
      background: var(--card-bg);
      border-radius: 8px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      padding: 30px;
      border: 1px solid var(--border-color);
    }

    .error-header {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 1px solid var(--border-color);
    }

    .error-icon {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      background: var(--primary-color);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      font-weight: bold;
      margin-right: 20px;
    }

    .error-title {
      font-size: 24px;
      font-weight: bold;
      color: var(--primary-color);
      margin: 0;
    }

    .error-message {
      font-size: 16px;
      margin-bottom: 20px;
      padding: 15px;
      background: #fff3cd;
      border: 1px solid #ffeaa7;
      border-radius: 4px;
      color: #856404;
    }

    .error-details {
      background: #f8f9fa;
      border: 1px solid #e9ecef;
      border-radius: 4px;
      padding: 15px;
      margin-bottom: 20px;
    }

    .error-details h3 {
      margin-top: 0;
      color: #495057;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .error-info {
      font-family: 'Courier New', monospace;
      font-size: 12px;
      color: #6c757d;
      margin-bottom: 10px;
    }

    .error-actions {
      display: flex;
      gap: 10px;
      margin-top: 20px;
    }

    .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      text-decoration: none;
      font-size: 14px;
      cursor: pointer;
      transition: background-color 0.2s;
    }

    .btn-primary {
      background-color: var(--primary-color);
      color: white;
    }

    .btn-primary:hover {
      background-color: #b71c1c;
    }

    .btn-secondary {
      background-color: #6c757d;
      color: white;
    }

    .btn-secondary:hover {
      background-color: #5a6268;
    }

    .trace-container {
      margin-top: 30px;
      border-top: 1px solid var(--border-color);
      padding-top: 20px;
    }

    .trace-title {
      font-size: 14px;
      color: #6c757d;
      margin-bottom: 10px;
      font-weight: bold;
    }

    .trace-content {
      background: #1e1e1e;
      color: #d4d4d4;
      padding: 15px;
      border-radius: 4px;
      font-family: 'Courier New', monospace;
      font-size: 12px;
      line-height: 1.4;
      overflow-x: auto;
      max-height: 300px;
      overflow-y: auto;
    }

    .trace-content span.line {
      color: #9cdcfe;
    }

    .trace-content span.file {
      color: #ce9178;
    }

    .trace-content span.method {
      color: #4ec9b0;
    }

    .debug-info {
      background: #e7f3ff;
      border: 1px solid #b3d9ff;
      border-radius: 4px;
      padding: 15px;
      margin-bottom: 20px;
      font-size: 12px;
      color: #003d66;
    }

    .debug-info strong {
      color: #0056b3;
    }

    @media (max-width: 600px) {
      .container {
        padding: 0 15px;
      }

      .error-card {
        padding: 20px;
      }

      .error-header {
        flex-direction: column;
        align-items: flex-start;
      }

      .error-icon {
        margin-bottom: 10px;
        margin-right: 0;
      }

      .error-actions {
        flex-direction: column;
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="error-card">
      <div class="error-header">
        <div class="error-icon">!</div>
        <div>
          <h1 class="error-title">Error <?= $statusCode ?? 500 ?></h1>
          <p style="margin: 0; color: #666;">Something went wrong</p>
        </div>
      </div>

      <div class="error-message">
        <strong><?= htmlspecialchars($message ?? 'An unexpected error occurred', ENT_QUOTES, 'UTF-8') ?></strong>
      </div>

      <?php if ($isDebug): ?>
        <div class="debug-info">
          <strong>Debug Information:</strong><br>
          File: <?= htmlspecialchars($file ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?><br>
          Line: <?= $line ?? 'Unknown' ?><br>
          Time: <?= date('Y-m-d H:i:s') ?>
        </div>

        <div class="error-details">
          <h3>Error Details</h3>
          <div class="error-info">
            <strong>File:</strong> <?= htmlspecialchars($file ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?><br>
            <strong>Line:</strong> <?= $line ?? 'Unknown' ?><br>
            <strong>Exception:</strong> <?= get_class($exception ?? new \Exception()) ?>
          </div>
        </div>

        <div class="trace-container">
          <div class="trace-title">Stack Trace</div>
          <div class="trace-content">
            <?php
            $trace = explode("\n", $trace ?? '');
            foreach ($trace as $lineNum => $traceLine) {
              if (trim($traceLine)) {
                echo '<div>';
                echo '<span class="line">#' . $lineNum . '</span> ';
                echo htmlspecialchars($traceLine, ENT_QUOTES, 'UTF-8');
                echo '</div>';
              }
            }
            ?>
          </div>
        </div>
      <?php else: ?>
        <div class="error-details">
          <h3>What happened?</h3>
          <p>We're sorry, but an error occurred while processing your request. Our technical team has been notified and will investigate the issue.</p>
        </div>
      <?php endif; ?>

      <div class="error-actions">
        <a href="/" class="btn btn-primary">Return to Home</a>
        <button onclick="window.history.back()" class="btn btn-secondary">Go Back</button>
        <?php if ($isDebug): ?>
          <button onclick="location.reload()" class="btn btn-secondary">Reload Page</button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>
    // Add some interactivity
    document.addEventListener('DOMContentLoaded', function() {
      const traceContent = document.querySelector('.trace-content');
      if (traceContent) {
        // Highlight file paths and methods in stack trace
        const text = traceContent.innerHTML;
        const highlighted = text
          .replace(/(.*)(\/[\w\/\.-]+:\d+)/g, '$1<span class="file">$2</span>')
          .replace(/(\-\>[\w]+)/g, '<span class="method">$1</span>');
        traceContent.innerHTML = highlighted;
      }
    });
  </script>
</body>

</html>