# Madrasa ERP – Intelligent OCR PDF & Image Import Center

A cPanel-ready PHP 8+, MySQL, Bootstrap 5.3, JavaScript, and AJAX OCR import center for PDF/JPG/JPEG/PNG lesson-plan imports.

## Deployment

1. Upload the project files to the cPanel web root or subdirectory.
2. Install Composer dependencies so `/vendor/autoload.php` contains `smalot/pdfparser`.
3. Ensure the hosting account has Tesseract OCR and Imagick enabled.
4. Create a MySQL database and import `schema.sql`.
5. Update `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` in `config.php`.
6. Make `uploads/` writable by PHP.

## Regex capture names

For automatic monthly plan parsing, create regex rules with named capture groups such as `month`, `class` or `class_name`, `week`, `total_period`, `subject`, `lesson_name`, `lesson_details`, `activities`, `smart_date`, and `exam_date`.
