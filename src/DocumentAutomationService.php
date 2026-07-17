<?php
declare(strict_types=1);

namespace DocumentAutomation;

use PDO;
use RuntimeException;
use ZipArchive;
use PhpOffice\PhpWord\TemplateProcessor;

/** Attendance-aware DOCX mail merge service. Existing tables are never written. */
final class DocumentAutomationService
{
    private const MAP = [
        'full_name' => ['members', 'full_name'], 'member_uid' => ['members', 'member_uid'],
        'verify_token' => ['members', 'verify_token'], 'photo' => ['members', 'photo'],
        'department' => ['departments', 'name'], 'class' => ['classes', 'class_name'],
        'scan_time' => ['attendance_logs', 'scan_time'], 'attendance_date' => ['attendance_logs', 'scan_time'],
        'attendance_status' => ['attendance_logs', 'status'], 'operator' => ['attendance_logs', 'operator'],
        'device' => ['attendance_logs', 'device'], 'remarks' => ['attendance_logs', 'remarks'],
        'today_date' => ['system', 'today_date'], 'current_year' => ['system', 'current_year'],
    ];

    public function __construct(private readonly PDO $pdo, private readonly string $storageRoot) {}

    /** Scans all Word XML parts, including headers, footers, text boxes, and tables. */
    public function scanPlaceholders(string $docxPath): array
    {
        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) throw new RuntimeException('The uploaded file is not a valid DOCX archive.');
        $found = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!preg_match('#^word/(document|header\d+|footer\d+|footnotes|endnotes)\.xml$#', $name)) continue;
            $xml = $zip->getFromIndex($i) ?: '';
            // Word may split a token across runs; strip tags before matching text.
            $text = html_entity_decode(strip_tags(str_replace('</w:t>', '</w:t> ', $xml)), ENT_QUOTES | ENT_XML1, 'UTF-8');
            preg_match_all('/\{\{\s*([A-Za-z][A-Za-z0-9_]*)\s*\}\}/u', $text, $matches);
            foreach ($matches[1] as $placeholder) $found[strtolower($placeholder)] = true;
        }
        $zip->close();
        return array_keys($found);
    }

    public function upload(array $file, array $input, int $actorId): int
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 25 * 1024 * 1024) throw new RuntimeException('Upload a DOCX file smaller than 25 MB.');
        $extension = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if ($extension !== 'docx' || !is_uploaded_file($file['tmp_name'])) throw new RuntimeException('Only DOCX files are accepted.');
        if (!is_dir($this->storageRoot) && !mkdir($this->storageRoot, 0750, true) && !is_dir($this->storageRoot)) throw new RuntimeException('Storage directory is unavailable.');
        $name = bin2hex(random_bytes(18)) . '.docx'; $path = $this->storageRoot . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $path)) throw new RuntimeException('Could not store uploaded template.');
        try { $placeholders = $this->scanPlaceholders($path); } catch (\Throwable $e) { @unlink($path); throw $e; }
        $this->pdo->beginTransaction();
        try {
            $q = $this->pdo->prepare('INSERT INTO document_templates (name,description,version,original_name,storage_path,created_by) VALUES (?,?,?,?,?,?)');
            $q->execute([trim((string)($input['name'] ?? pathinfo($file['name'], PATHINFO_FILENAME))), trim((string)($input['description'] ?? '')), '1.0', basename((string)$file['name']), $name, $actorId]);
            $id = (int)$this->pdo->lastInsertId();
            foreach ($placeholders as $key) { [$table,$column] = self::MAP[$key] ?? [null,null]; $this->saveMapping($id, $key, $table, $column, $table ? ($table === 'system' ? 'system' : 'mapped') : 'unmapped'); }
            $this->audit($actorId, 'template.uploaded', 'document_template', $id, ['placeholder_count'=>count($placeholders)]);
            $this->pdo->commit(); return $id;
        } catch (\Throwable $e) { $this->pdo->rollBack(); @unlink($path); throw $e; }
    }

    public function saveMapping(int $templateId, string $placeholder, ?string $table, ?string $column, string $status='custom'): void
    {
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $placeholder) || ($table && !in_array("$table.$column", array_map(fn($x)=>"$x[0].$x[1]", self::MAP), true))) throw new RuntimeException('Unsupported mapping field.');
        $q=$this->pdo->prepare('INSERT INTO document_placeholders (template_id,placeholder,source_table,source_column,mapping_status) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE source_table=VALUES(source_table),source_column=VALUES(source_column),mapping_status=VALUES(mapping_status)');
        $q->execute([$templateId,$placeholder,$table,$column,$status]);
    }

    public function previewRows(int $templateId, array $filters=[], int $limit=20): array
    {
        $limit=max(1,min(100,$limit)); $where=['1=1']; $params=[];
        if (!empty($filters['member_uid'])) { $where[]='l.member_id=?'; $params[]=$filters['member_uid']; }
        if (!empty($filters['date'])) { $where[]='DATE(l.scan_time)=?'; $params[]=$filters['date']; }
        $sql='SELECT l.*,m.full_name,m.verify_token,m.photo,d.name AS department,c.class_name AS class FROM attendance_logs l LEFT JOIN members m ON m.member_uid=l.member_id LEFT JOIN departments d ON d.id=m.department_id LEFT JOIN classes c ON c.id=m.class_id WHERE '.implode(' AND ',$where).' ORDER BY l.scan_time DESC LIMIT '.$limit;
        $q=$this->pdo->prepare($sql); $q->execute($params); return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    public function generate(int $templateId, array $recordIds, int $actorId): array
    {
        if (!class_exists(TemplateProcessor::class)) throw new RuntimeException('PHPWord is unavailable. Run composer install first.');
        $q=$this->pdo->prepare('SELECT * FROM document_templates WHERE id=? AND status="active"'); $q->execute([$templateId]); $template=$q->fetch(PDO::FETCH_ASSOC);
        if (!$template) throw new RuntimeException('Active template not found.');
        $rows=$this->previewRows($templateId, [], 100); if ($recordIds) $rows=array_values(array_filter($rows, fn($r)=>in_array((string)$r['id'], array_map('strval',$recordIds),true)));
        if (!$rows) throw new RuntimeException('No attendance records selected.');
        $outDir=$this->storageRoot.'/generated'; if (!is_dir($outDir)) mkdir($outDir,0750,true); $output=bin2hex(random_bytes(18)).'.docx';
        // One merged DOCX is produced per selected record; callers can queue/chunk this method for large batches.
        $processor=new TemplateProcessor($this->storageRoot.'/'.$template['storage_path']); $values=$this->values($rows[0]);
        foreach ($values as $key=>$value) $processor->setValue($key,$value);
        $processor->saveAs($outDir.'/'.$output); $size=(int)filesize($outDir.'/'.$output);
        $q=$this->pdo->prepare('INSERT INTO generated_documents (template_id,file_name,storage_path,member_count,file_size,status,generated_by) VALUES (?,?,?,?,?,?,?)'); $q->execute([$templateId,$output,'generated/'.$output,count($rows),$size,'completed',$actorId]); $id=(int)$this->pdo->lastInsertId();
        $this->audit($actorId,'document.generated','generated_document',$id,['template_id'=>$templateId,'members'=>count($rows)]); return ['id'=>$id,'file'=>$output,'count'=>count($rows)];
    }

    private function values(array $row): array { $values=['today_date'=>date('Y-m-d'),'current_year'=>date('Y')]; foreach (self::MAP as $key=>[$table,$column]) { if ($table!=='system') $values[$key]=(string)($row[$column] ?? ''); } return $values; }
    private function audit(int $actor,string $action,string $type,int $id,array $details): void { $q=$this->pdo->prepare('INSERT INTO document_audit_log (actor_id,action,entity_type,entity_id,details) VALUES (?,?,?,?,?)'); $q->execute([$actor,$action,$type,$id,json_encode($details,JSON_THROW_ON_ERROR)]); }
}
