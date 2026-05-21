<?php
declare(strict_types=1);
/**
 * @var string $name
 * @var string $link
 * @var int    $ttl_minutes
 *
 * Plantilla TEXTO plano del email. NO usar layout.
 */
?>
Hola <?= $name ?>,

Recibimos una solicitud para recuperar el acceso de administrador a
Wellness Circle Academy. Si fuiste tú, abre este link para definir una
nueva contraseña:

<?= $link ?>


El link expira en <?= $ttl_minutes ?> minutos y solo se puede usar una vez.

Si no fuiste tú quien solicitó esto, ignora este email; nadie obtendrá
acceso solo por recibirlo.

— Wellness Circle Academy
