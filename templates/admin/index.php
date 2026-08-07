<?php
declare(strict_types=1);
/**
 * @var array{users:int, programs:int, lessons:int} $totals
 * @var array<int, array<string,mixed>>             $programs
 */
$pageTitle = 'Panel admin';
?>
<section class="page-head">
    <h1>Panel de administración</h1>
    <p class="muted">Resumen y accesos rápidos.</p>
</section>

<section class="stats-row">
    <a class="stat-card stat-card-link" href="/admin/usuarios">
        <span class="stat-card-value"><?= e($totals['users']) ?></span>
        <span class="stat-card-label">usuarios</span>
    </a>
    <a class="stat-card stat-card-link" href="/admin/programas">
        <span class="stat-card-value"><?= e($totals['programs']) ?></span>
        <span class="stat-card-label">programas</span>
    </a>
    <a class="stat-card stat-card-link" href="/admin/programas">
        <span class="stat-card-value"><?= e($totals['lessons']) ?></span>
        <span class="stat-card-label">lecciones</span>
    </a>
</section>

<section class="admin-shortcuts">
    <a class="admin-shortcut" href="/admin/usuarios">
        <h2>Usuarios</h2>
        <p>Crear, editar y activar/desactivar cuentas de miembros y administradores.</p>
    </a>
    <a class="admin-shortcut" href="/admin/programas">
        <h2>Programas y lecciones</h2>
        <p>Crear y editar programas, gestionar sus lecciones, subir imágenes y publicar contenido.</p>
    </a>
    <a class="admin-shortcut" href="/admin/entrenamiento">
        <h2>Entrenamiento</h2>
        <p>Videos de YouTube/Vimeo agrupados por tema (Plan de Compensación, Clientes, etc.).</p>
    </a>
    <a class="admin-shortcut" href="/admin/materiales">
        <h2>Materiales</h2>
        <p>PDFs, imágenes y enlaces de apoyo para el equipo.</p>
    </a>
    <a class="admin-shortcut" href="/admin/eventos">
        <h2>Eventos</h2>
        <p>Calendario de talleres, entrenamientos y presentaciones con enlace para entrar.</p>
    </a>
    <a class="admin-shortcut" href="/admin/noticias">
        <h2>Noticias</h2>
        <p>Noticias e información relevante para el equipo. Soporta imagen, video y botón de enlace.</p>
    </a>
    <a class="admin-shortcut" href="/admin/promociones">
        <h2>Promociones</h2>
        <p>Ofertas, reconocimientos y avisos especiales. Soporta imagen, video y botón de enlace.</p>
    </a>
    <a class="admin-shortcut" href="/admin/normas">
        <h2>Normas y Reglamentos</h2>
        <p>Editar el texto de las reglas y la cultura del equipo.</p>
    </a>
    <a class="admin-shortcut" href="/admin/cliente">
        <h2>Soy Cliente</h2>
        <p>Editar el contenido de la sección para usuarios con rol Cliente.</p>
    </a>
    <a class="admin-shortcut" href="/admin/experience-kit">
        <h2>WCA Experience Kit</h2>
        <p>Asignar kits a clientes y ver el panel de seguimiento (parche, hidratación, ejercicio, diario).</p>
    </a>
</section>

<?php if ($programs !== []): ?>
    <section>
        <h2>Programas existentes</h2>
        <ul class="admin-list">
            <?php foreach ($programs as $program): ?>
                <li class="admin-list-item">
                    <span class="admin-list-title"><?= e($program['title']) ?></span>
                    <span class="admin-list-meta">
                        slug: <code><?= e($program['slug']) ?></code>
                        ·
                        <?= $program['is_published'] ? 'publicado' : 'borrador' ?>
                    </span>
                    <a class="button button-ghost button-sm" href="/admin/programas/<?= e($program['id']) ?>/editar">Editar</a>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>
