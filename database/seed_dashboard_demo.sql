INSERT INTO uploads (user_id, original_name, mime_type, extension, size_bytes, storage_path, status, created_at) VALUES
(1, 'brand-assets.zip', 'application/zip', 'zip', 9437184, 'storage/uploads/brand-assets.zip', 'uploaded', NOW()),
(1, 'sales-deck.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'docx', 1245184, 'storage/uploads/sales-deck.docx', 'uploaded', NOW()),
(1, 'launch-video.mov', 'video/quicktime', 'mov', 52428800, 'storage/uploads/launch-video.mov', 'processing', NOW());

INSERT INTO conversions (user_id, upload_id, tool_type, source_format, target_format, options_json, output_path, status, created_at, completed_at) VALUES
(1, 1, 'converter', 'zip', 'webp', JSON_OBJECT('bulk', true, 'preset', 'marketing-assets'), 'storage/exports/brand-assets-webp.zip', 'completed', NOW(), NOW()),
(1, 2, 'document', 'docx', 'pdf', JSON_OBJECT('ocr', false), 'storage/exports/sales-deck.pdf', 'completed', NOW(), NOW()),
(1, 3, 'video', 'mov', 'mp4', JSON_OBJECT('extract_audio', true), 'storage/exports/launch-video.mp4', 'processing', NOW(), NULL);

INSERT INTO seo_tools_logs (user_id, tool_name, input_url, keyword_phrase, result_json, created_at) VALUES
(1, 'meta_generator', 'https://example.com', 'homepage metadata', JSON_OBJECT('score', 91, 'status', 'improved'), NOW()),
(1, 'sitemap_builder', 'https://example.com/black-friday', 'black friday sitemap', JSON_OBJECT('pages', 124), NOW()),
(1, 'internal_link_analyzer', 'https://example.com/blog', 'internal linking audit', JSON_OBJECT('opportunities', 42), NOW());

INSERT INTO file_history (user_id, upload_id, conversion_id, action_type, metadata_json, created_at) VALUES
(1, 1, 1, 'download', JSON_OBJECT('result', 'optimized WEBP set'), NOW()),
(1, 2, 2, 'download', JSON_OBJECT('result', 'PDF export'), NOW()),
(1, 3, 3, 'processing', JSON_OBJECT('result', 'MP4 + MP3 extract'), NOW());

INSERT INTO subscriptions (user_id, plan_code, status, started_at, ends_at) VALUES
(1, 'starter', 'active', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY));

INSERT INTO analytics_events (user_id, event_name, payload_json, created_at) VALUES
(1, 'dashboard_visit', JSON_OBJECT('section', 'overview'), NOW()),
(1, 'conversion_started', JSON_OBJECT('files', 3, 'tool', 'converter'), NOW()),
(1, 'seo_report_generated', JSON_OBJECT('reports', 3), NOW());
