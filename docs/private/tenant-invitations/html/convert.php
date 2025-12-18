<?php
/**
 * Markdown to HTML Converter
 * Converts all markdown files in parent directory to HTML
 */

function markdownToHtml($markdown) {
    $html = $markdown;
    
    // Code blocks (must be done before inline code)
    $html = preg_replace_callback('/```(\w+)?\n(.*?)```/s', function($matches) {
        $lang = $matches[1] ?? '';
        $code = htmlspecialchars($matches[2]);
        return '<pre><code class="language-' . $lang . '">' . $code . '</code></pre>';
    }, $html);
    
    // Headers (in reverse order to avoid conflicts)
    $html = preg_replace('/^#### (.+)$/m', '<h4>$1</h4>', $html);
    $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
    $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
    $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
    
    // Horizontal rules
    $html = preg_replace('/^---$/m', '<hr style="margin: 2rem 0; border: none; border-top: 2px solid #e0e0e0;">', $html);
    
    // Bold
    $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
    
    // Italic
    $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);
    
    // Inline code (after code blocks)
    $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);
    
    // Links
    $html = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2">$1</a>', $html);
    
    // Tables
    $html = preg_replace_callback('/\|(.+)\|\n\|[-\s\|]+\|\n((?:\|.+\|\n?)+)/', function($matches) {
        $headers = array_map('trim', explode('|', trim($matches[1], '|')));
        $rows = array_filter(explode("\n", trim($matches[2])));
        
        $table = '<table><thead><tr>';
        foreach ($headers as $header) {
            if (!empty(trim($header))) {
                $table .= '<th>' . trim($header) . '</th>';
            }
        }
        $table .= '</tr></thead><tbody>';
        
        foreach ($rows as $row) {
            $row = trim($row);
            if (empty($row) || !strpos($row, '|')) continue;
            $cells = array_map('trim', explode('|', trim($row, '|')));
            $table .= '<tr>';
            foreach ($cells as $cell) {
                $table .= '<td>' . $cell . '</td>';
            }
            $table .= '</tr>';
        }
        
        $table .= '</tbody></table>';
        return $table;
    }, $html);
    
    // Blockquotes
    $html = preg_replace('/^> (.+)$/m', '<blockquote>$1</blockquote>', $html);
    
    // Lists - unordered
    $lines = explode("\n", $html);
    $html = '';
    $inList = false;
    $inOrderedList = false;
    $inParagraph = false;
    
    foreach ($lines as $i => $line) {
        $originalLine = $line;
        $line = trim($line);
        
        if (empty($line)) {
            if ($inList) {
                $html .= "</ul>\n";
                $inList = false;
            }
            if ($inOrderedList) {
                $html .= "</ol>\n";
                $inOrderedList = false;
            }
            if ($inParagraph) {
                $html .= "</p>\n";
                $inParagraph = false;
            }
            $html .= "\n";
            continue;
        }
        
        // Check for list items
        if (preg_match('/^[-*] (.+)$/', $line, $matches)) {
            if ($inOrderedList) {
                $html .= "</ol>\n";
                $inOrderedList = false;
            }
            if ($inParagraph) {
                $html .= "</p>\n";
                $inParagraph = false;
            }
            if (!$inList) {
                $html .= "<ul>\n";
                $inList = true;
            }
            $html .= "<li>" . $matches[1] . "</li>\n";
            continue;
        }
        
        if (preg_match('/^(\d+)\. (.+)$/', $line, $matches)) {
            if ($inList) {
                $html .= "</ul>\n";
                $inList = false;
            }
            if ($inParagraph) {
                $html .= "</p>\n";
                $inParagraph = false;
            }
            if (!$inOrderedList) {
                $html .= "<ol>\n";
                $inOrderedList = true;
            }
            $html .= "<li>" . $matches[2] . "</li>\n";
            continue;
        }
        
        // Close lists if starting new content
        if ($inList || $inOrderedList) {
            if ($inList) {
                $html .= "</ul>\n";
                $inList = false;
            }
            if ($inOrderedList) {
                $html .= "</ol>\n";
                $inOrderedList = false;
            }
        }
        
        // Check if line is already HTML
        if (preg_match('/^<[^>]+>/', $line)) {
            if ($inParagraph) {
                $html .= "</p>\n";
                $inParagraph = false;
            }
            $html .= $originalLine . "\n";
        } else {
            // Regular paragraph
            if (!$inParagraph) {
                $html .= "<p>";
                $inParagraph = true;
            } else {
                $html .= " ";
            }
            $html .= $line;
        }
    }
    
    // Close any open tags
    if ($inList) {
        $html .= "</ul>\n";
    }
    if ($inOrderedList) {
        $html .= "</ol>\n";
    }
    if ($inParagraph) {
        $html .= "</p>\n";
    }
    
    // Badges and alerts (custom markdown extensions)
    $html = preg_replace('/✅ (.+)/', '<span class="badge badge-success">✅</span> $1', $html);
    $html = preg_replace('/❌ (.+)/', '<span class="badge badge-warning">❌</span> $1', $html);
    
    return trim($html);
}

// Get all markdown files
$mdDir = dirname(__DIR__);
$htmlDir = __DIR__;
$files = [
    '00-README.md' => '00-README.html',
    '01-overview.md' => '01-overview.html',
    '02-database-schema.md' => '02-database-schema.html',
    '03-api-endpoints-owner.md' => '03-api-endpoints-owner.html',
    '04-api-endpoints-public.md' => '04-api-endpoints-public.html',
    '05-workflow-owner.md' => '05-workflow-owner.html',
    '06-workflow-tenant.md' => '06-workflow-tenant.html',
    '07-invitation-types.md' => '07-invitation-types.html',
    '08-mail-configuration.md' => '08-mail-configuration.html',
    '09-permissions-security.md' => '09-permissions-security.html',
    '10-user-registration-flow.md' => '10-user-registration-flow.html',
    '11-testing-guide.md' => '11-testing-guide.html',
    '12-troubleshooting.md' => '12-troubleshooting.html',
];

$template = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{TITLE}} - Tenant Invitation Documentation</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <h1>📋 Tenant Invitation Feature</h1>
        <p>Complete Documentation - Tenant Self-Registration via Invitation</p>
    </header>
    
    <div class="container">
        <aside class="sidebar">
            <h2>Navigation</h2>
            <nav>
                <ul>
                    <li><a href="index.html" data-route="">📖 Overview</a></li>
                    <li><a href="#" data-route="overview">📋 Feature Overview</a></li>
                    <li><a href="#" data-route="database">🗄️ Database Schema</a></li>
                    <li><a href="#" data-route="api-owner">🔐 API - Owner</a></li>
                    <li><a href="#" data-route="api-public">🌐 API - Public</a></li>
                    <li><a href="#" data-route="workflow-owner">👤 Owner Workflow</a></li>
                    <li><a href="#" data-route="workflow-tenant">🏠 Tenant Workflow</a></li>
                    <li><a href="#" data-route="invitation-types">🔄 Invitation Types</a></li>
                    <li><a href="#" data-route="mail-config">📧 Mail Configuration</a></li>
                    <li><a href="#" data-route="permissions">🔒 Permissions & Security</a></li>
                    <li><a href="#" data-route="registration">👥 User Registration</a></li>
                    <li><a href="#" data-route="testing">🧪 Testing Guide</a></li>
                    <li><a href="#" data-route="troubleshooting">🔧 Troubleshooting</a></li>
                </ul>
            </nav>
        </aside>
        
        <main class="main-content">
            {{CONTENT}}
        </main>
    </div>
    
    <button class="print-btn" id="printBtn" title="Print Documentation">
        🖨️ Print
    </button>
    
    <script src="app.js"></script>
</body>
</html>
HTML;

foreach ($files as $mdFile => $htmlFile) {
    $mdPath = $mdDir . '/' . $mdFile;
    $htmlPath = $htmlDir . '/' . $htmlFile;
    
    if (!file_exists($mdPath)) {
        echo "Skipping $mdFile (not found)\n";
        continue;
    }
    
    $markdown = file_get_contents($mdPath);
    $htmlContent = markdownToHtml($markdown);
    
    // Extract title from first h1
    preg_match('/<h1>(.+?)<\/h1>/', $htmlContent, $matches);
    $title = $matches[1] ?? 'Documentation';
    
    $finalHtml = str_replace(['{{TITLE}}', '{{CONTENT}}'], [$title, $htmlContent], $template);
    
    file_put_contents($htmlPath, $finalHtml);
    echo "Converted: $mdFile → $htmlFile\n";
}

echo "\n✅ Conversion complete!\n";
