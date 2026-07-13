<?php
$content = file_get_contents('all_migrations.txt');
preg_match_all('/Schema::(?:create|table)\(.*?\n\s*}\);/sm', $content, $matches);

$output = "";
foreach ($matches[0] as $match) {
    $output .= "```php\n" . $match . "\n```\n\n";
}

file_put_contents('database_schema_highlights.md', $output);
echo "Extracted " . count($matches[0]) . " tables.";
?>
