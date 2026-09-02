<?php
declare(strict_types=1);
$path = $argv[1] ?? 'coverage.xml';
$document = new DOMDocument();
if (!$document->load($path)) {
    fwrite(STDERR, sprintf("Unable to read coverage report %s.\n", $path));
    exit(1);
}
$metrics = (new DOMXPath($document))->query('/coverage/project/metrics')->item(0);
if (!$metrics instanceof DOMElement) {
    fwrite(STDERR, "Coverage report does not contain project metrics.\n");
    exit(1);
}
$statements = (int) $metrics->getAttribute('statements');
$covered = (int) $metrics->getAttribute('coveredstatements');
$percentage = $statements === 0 ? 0.0 : ($covered / $statements) * 100;
printf("Line coverage: %.2f%% (%d/%d)\n", $percentage, $covered, $statements);
if ($covered !== $statements) {
    fwrite(STDERR, "Line coverage must remain at 100%.\n");
    exit(1);
}
