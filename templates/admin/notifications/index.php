<?php
declare(strict_types=1);
/**
 * @var array<int, array<string,mixed>>      $rows
 * @var array<string,string>                 $kitLabels
 * @var array<string,string>                 $roleLabels
 * @var array{type:string,msg:string}|null   $flash
 * @var string $csrf
 */
$pageTitle = 'Notificaciones';

$audienceLabel = static function (array $n) use ($kitLabels, $roleLabels): string {
    return match ($n['audience_type']) {
        'all'  => 'Todos los usuarios',
        'role' => $roleLabels[$n['audience_role']] ?? (string) $n['audience_role'],
        'kit'  => 'Kit: ' . ($kitLabels[$n['audience_kit_slug']] ?? (string) $n['audience_kit_slug']),
        'user' => (string) ($n['target_user_name'] ?? 'usuario eliminado'),
        default => '—',
    };
};

// La BD guarda todo en UTC (TIMESTAMPTZ) — se muestra en hora de CDMX.
$cdmx = static function (?string $raw): ?string {
    if ($raw === null) {
        return null;
    }
    return (new DateTimeImmutable($raw))->setTimezone(new DateTimeZone('America/Mexico_City'))->format('Y-m-d H:i');
};
?>
<section class="page-head">
    <p class="breadcrumb"><a href="/admin">Admin</a> &rsaquo; <span>Notificaciones</span></p>
    <div class="page-head-row">
        <h1>Notificaciones</h1>
        <a class="button button-primary" href="/admin/notificaciones/nueva">Programar notificación</a>
    </div>
    <p class="muted">Push notifications a través del navegador (PWA). Un Cron Job revisa las pendientes y las envía en cuanto llega su hora — puede tardar unos minutos.</p>
</section>

<?php if ($flash !== null): ?>
    <p class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></p>
<?php endif; ?>

<?php if ($rows === []): ?>
    <p class="empty-state">No hay notificaciones programadas todavía.</p>
<?php else: ?>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Audiencia</th>
                    <th>Programada (CDMX)</th>
                    <th>Recurrencia</th>
                    <th>Estado</th>
                    <th>Último envío</th>
                    <th class="ta-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $n): ?>
                    <tr>
                        <td><?= e($n['title']) ?></td>
                        <td><?= e($audienceLabel($n)) ?></td>
                        <td><?= e($cdmx($n['scheduled_at'])) ?></td>
                        <td>
                            <?= $n['is_recurring']
                                ? '<span class="badge badge-muted">' . ($n['recurrence_freq'] === 'weekly' ? 'semanal' : 'diaria') . '</span>'
                                : '<span class="badge badge-muted">única vez</span>' ?>
                        </td>
                        <td>
                            <?php if ($n['status'] === 'pending'): ?>
                                <span class="badge badge-success">pendiente</span>
                            <?php elseif ($n['status'] === 'sent'): ?>
                                <span class="badge badge-muted">enviada</span>
                            <?php else: ?>
                                <span class="badge badge-error">cancelada</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($n['last_sent_at'] !== null): ?>
                                <?= e($cdmx($n['last_sent_at'])) ?><br>
                                <span class="muted small"><?= e($n['last_sent_count']) ?> ok / <?= e($n['last_failed_count']) ?> fallidos</span>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="ta-right">
                            <div class="table-actions">
                                <a class="button button-ghost button-sm" href="/admin/notificaciones/<?= e($n['id']) ?>/editar">Editar</a>
                                <a class="button button-ghost button-sm button-danger" href="/admin/notificaciones/<?= e($n['id']) ?>/eliminar">Eliminar</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
