<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
require_csrf();
$id = (int)($_POST['import_id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM ocr_imports WHERE id = ?');
$stmt->execute([$id]);
$import = $stmt->fetch();
if (!$import) json_response(['success' => false, 'message' => 'Import not found.'], 404);
$absolute = __DIR__ . '/' . $import['file_path'];
try {
    if ($import['mime_type'] === 'application/pdf') {
        $parser = new Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($absolute);
        $text = $pdf->getText();
        if (trim($text) === '' && class_exists('Imagick')) {
            $text = ocr_image_pdf($absolute);
        }
    } else {
        $text = ocr_image($absolute);
    }
    $text = normalize_ocr_text($text);
    db()->prepare('UPDATE ocr_imports SET extracted_text = ?, status = ? WHERE id = ?')->execute([$text, 'extracted', $id]);
    log_event($id, 'info', 'Text extracted', ['characters' => mb_strlen($text)]);
    json_response(['success' => true, 'text' => $text, 'characters' => mb_strlen($text), 'lines' => substr_count($text, "\n") + 1]);
} catch (Throwable $e) {
    db()->prepare('UPDATE ocr_imports SET status = ?, error_message = ? WHERE id = ?')->execute(['failed', $e->getMessage(), $id]);
    log_event($id, 'error', 'Extraction failed', ['error' => $e->getMessage()]);
    json_response(['success' => false, 'message' => $e->getMessage()], 500);
}
function ocr_image(string $path): string {
    $cmd = 'tesseract ' . escapeshellarg($path) . ' stdout --dpi 300 2>&1';
    exec($cmd, $out, $code);
    if ($code !== 0) throw new RuntimeException('Tesseract OCR failed: ' . implode("\n", $out));
    return implode("\n", $out);
}
function ocr_image_pdf(string $path): string {
    $imagick = new Imagick();
    $imagick->setResolution(220, 220);
    $imagick->readImage($path);
    $text = '';
    foreach ($imagick as $i => $page) {
        $tmp = tempnam(sys_get_temp_dir(), 'ocr_pdf_') . '.png';
        $page->setImageFormat('png');
        $page->writeImage($tmp);
        $text .= "\n" . ocr_image($tmp);
        @unlink($tmp);
    }
    return $text;
}
