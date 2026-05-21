<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\BatchImporter;
use App\Csrf;
use App\Database\Connection;
use App\View;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderName;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\BorderStyle;
use OpenSpout\Common\Entity\Style\BorderWidth;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use OpenSpout\Writer\XLSX\Options as XlsxOptions;
use PDO;
use Throwable;

final class AdminBatchController
{
    private const MAX_UPLOAD_BYTES = 10 * 1024 * 1024; // 10 MB
    private const ALLOWED_MIME = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip',          // XLSX es un ZIP internamente; algunos sistemas detectan ZIP.
        'application/octet-stream', // Fallback raro pero válido.
    ];

    /** @param array<string,string> $params */
    public function show(array $params): void
    {
        Auth::requireAdmin();

        $programId = self::parseId($params['id'] ?? '');
        $program   = self::findProgram($programId);
        if (!$program) {
            self::redirect404();
            return;
        }

        View::render('admin/batch/upload', [
            'program'         => $program,
            'errors'          => [],
            'max_upload_mb'   => (int) (self::MAX_UPLOAD_BYTES / 1024 / 1024),
            'header_count'    => count(BatchImporter::HEADERS_HUMAN),
            'headers_human'   => BatchImporter::HEADERS_HUMAN,
        ]);
    }

    /** @param array<string,string> $params */
    public function downloadTemplate(array $params): void
    {
        Auth::requireAdmin();

        $programId = self::parseId($params['id'] ?? '');
        $program   = self::findProgram($programId);
        if (!$program) {
            self::redirect404();
            return;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'wca_template_') . '.xlsx';

        $options = new XlsxOptions();
        $writer  = new XlsxWriter($options);
        $writer->openToFile($tmp);

        // Estilo de header. openspout 5.x usa la API inmutable withXxx().
        $borderPart  = new BorderPart(BorderName::BOTTOM, Color::BLACK, BorderWidth::THIN, BorderStyle::SOLID);
        $border      = new Border($borderPart);
        $headerStyle = (new Style())
            ->withFontBold(true)
            ->withBackgroundColor('D6EAF6')
            ->withBorder($border);

        $writer->addRow(Row::fromValuesWithStyle(BatchImporter::HEADERS_HUMAN, $headerStyle));

        // Ejemplo: Día 1 del programa Arranque (los mismos textos del seed).
        $exampleRows = [
            [
                1,
                'Día 1 — Presentación natural',
                'Presentarte de forma natural en redes.',
                "Muchas veces creemos que el bienestar es solamente ejercicio o alimentación…\n\nPero también es energía, descanso, enfoque mental y sentirte bien contigo mismo.\n\nEstoy aprendiendo muchísimo sobre tecnologías de bienestar y biohacking natural y me emociona compartir este proceso.",
                'Algo grande está cambiando en mi vida.',
                "Amiga, últimamente he estado aprendiendo muchísimo sobre bienestar celular y energía natural.\n\nY sinceramente me ha sorprendido muchísimo cómo pequeños cambios pueden ayudarte a sentirte mejor.",
                "- Publicar el post\n- Subir 2 stories\n- Hablar con 3 personas",
                'Cuanto más natural sea tu publicación, más conexión genera. No fuerces el tono comercial.',
                'Ya publiqué|Ya subí stories|Ya hablé con 3 personas|Ya vi el entrenamiento',
                1,
            ],
            [
                2,
                'Día 2 — (ejemplo: borrar esta fila y empezar)',
                'Describe aquí el objetivo del día.',
                'Texto principal que el miembro copiará para publicar en redes.',
                'Texto corto para story.',
                'Conversación ejemplo para mensajes directos.',
                "- Acción 1\n- Acción 2\n- Acción 3",
                'Tip o consejo para el día.',
                'Item 1|Item 2|Item 3',
                0,
            ],
        ];

        foreach ($exampleRows as $rowValues) {
            $writer->addRow(Row::fromValues($rowValues));
        }

        $writer->close();

        $filename = 'plantilla-wca-' . $program['slug'] . '.xlsx';

        if (headers_sent()) {
            unlink($tmp);
            throw new \RuntimeException('No se pueden enviar headers; la salida ya empezó.');
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tmp));
        header('Cache-Control: private, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');

        readfile($tmp);
        @unlink($tmp);
        exit;
    }

    /** @param array<string,string> $params */
    public function process(array $params): void
    {
        Auth::requireAdmin();
        Csrf::requireValid();

        $programId = self::parseId($params['id'] ?? '');
        $program   = self::findProgram($programId);
        if (!$program) {
            self::redirect404();
            return;
        }

        $file   = $_FILES['xlsx'] ?? null;
        $errors = [];

        if (!is_array($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'No subiste ningún archivo.';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = self::uploadErrorMessage((int) $file['error']);
        } elseif (!is_uploaded_file((string) $file['tmp_name'])) {
            error_log('[wca] batch: tmp_name no es uploaded file');
            $errors[] = 'Archivo de subida inválido.';
        } elseif ((int) $file['size'] > self::MAX_UPLOAD_BYTES) {
            $errors[] = 'El archivo supera ' . (int) (self::MAX_UPLOAD_BYTES / 1024 / 1024) . ' MB.';
        } else {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file((string) $file['tmp_name']) ?: '';
            if (!in_array($mime, self::ALLOWED_MIME, true)) {
                error_log('[wca] batch: MIME no permitido: ' . $mime);
                $errors[] = 'Formato no permitido. Debe ser un archivo .xlsx.';
            }
        }

        if ($errors !== []) {
            View::render('admin/batch/upload', [
                'program'       => $program,
                'errors'        => $errors,
                'max_upload_mb' => (int) (self::MAX_UPLOAD_BYTES / 1024 / 1024),
                'header_count'  => count(BatchImporter::HEADERS_HUMAN),
                'headers_human' => BatchImporter::HEADERS_HUMAN,
            ]);
            return;
        }

        $importer = new BatchImporter();
        $result   = $importer->read((string) $file['tmp_name']);

        if ($result['errors'] !== []) {
            View::render('admin/batch/result', [
                'program' => $program,
                'success' => false,
                'errors'  => $result['errors'],
                'created' => [],
                'updated' => [],
            ]);
            return;
        }

        $pdo = Connection::get();
        $created = [];
        $updated = [];

        try {
            $pdo->beginTransaction();

            $existsStmt = $pdo->prepare('SELECT id FROM lessons WHERE program_id = :pid AND day_number = :day');
            $insertStmt = $pdo->prepare(
                'INSERT INTO lessons (program_id, day_number, title, objective,
                    post_text, story_text, conversation_text, action_text, tip_text,
                    checklist_items, is_published)
                 VALUES (:pid, :day, :t, :obj, :post, :story, :conv, :act, :tip, :chk::jsonb, :pub)'
            );
            $updateStmt = $pdo->prepare(
                'UPDATE lessons
                    SET title = :t,
                        objective = :obj,
                        post_text = :post,
                        story_text = :story,
                        conversation_text = :conv,
                        action_text = :act,
                        tip_text = :tip,
                        checklist_items = :chk::jsonb,
                        is_published = :pub
                  WHERE id = :id'
            );

            foreach ($result['rows'] as $row) {
                $data = $row['data'];
                $existsStmt->execute([':pid' => $programId, ':day' => $data['day_number']]);
                $existingId = $existsStmt->fetchColumn();

                $payload = [
                    ':t'     => $data['title'],
                    ':obj'   => self::nullable($data['objective']),
                    ':post'  => self::nullable($data['post_text']),
                    ':story' => self::nullable($data['story_text']),
                    ':conv'  => self::nullable($data['conversation_text']),
                    ':act'   => self::nullable($data['action_text']),
                    ':tip'   => self::nullable($data['tip_text']),
                    ':chk'   => json_encode($data['checklist_items'], JSON_UNESCAPED_UNICODE),
                    ':pub'   => $data['is_published'] ? 't' : 'f',
                ];

                if ($existingId !== false) {
                    $updateStmt->execute(array_merge($payload, [':id' => (int) $existingId]));
                    $updated[] = ['day' => $data['day_number'], 'title' => $data['title']];
                } else {
                    $insertStmt->execute(array_merge(
                        $payload,
                        [':pid' => $programId, ':day' => $data['day_number']]
                    ));
                    $created[] = ['day' => $data['day_number'], 'title' => $data['title']];
                }
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[wca] batch: fallo upsert: ' . $e->getMessage());
            View::render('admin/batch/result', [
                'program' => $program,
                'success' => false,
                'errors'  => ['Error de base de datos durante el upsert. Ninguna lección fue creada ni modificada.'],
                'created' => [],
                'updated' => [],
            ]);
            return;
        }

        View::render('admin/batch/result', [
            'program' => $program,
            'success' => true,
            'errors'  => [],
            'created' => $created,
            'updated' => $updated,
        ]);
    }

    // ----------------------------------------------------------------

    private static function nullable(string $v): ?string
    {
        return $v === '' ? null : $v;
    }

    private static function parseId(string $raw): int
    {
        return (preg_match('/^[1-9][0-9]{0,9}$/', $raw) === 1) ? (int) $raw : 0;
    }

    /** @return array<string,mixed>|null */
    private static function findProgram(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = Connection::get()->prepare('SELECT id, slug, title FROM programs WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private static function redirect404(): void
    {
        http_response_code(404);
        require dirname(__DIR__, 2) . '/templates/errors/404.php';
    }

    private static function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño permitido por el servidor.',
            UPLOAD_ERR_PARTIAL   => 'La subida se interrumpió. Intenta de nuevo.',
            UPLOAD_ERR_NO_TMP_DIR=> 'Falta carpeta temporal del servidor.',
            UPLOAD_ERR_CANT_WRITE=> 'El servidor no pudo escribir el archivo.',
            default              => 'Error desconocido en la subida (code ' . $code . ').',
        };
    }
}
