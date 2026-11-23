<?php // Agregué navegación del módulo sin mezclar la designación de líderes con la calificación. ?>
<?php if (auth_rol_activo()==='ADMIN'): ?>
<nav class="rrhh-tabs rrhh-notas-nav" aria-label="Secciones de Notas">
    <?php foreach (['evaluaciones'=>['admin/notas-evaluaciones.php','Evaluaciones'],'lideres'=>['admin/notas.php','Áreas y líderes'],'criterios'=>['admin/notas-rubrica.php','Criterios']] as $clave=>$enlace): ?>
        <a class="rrhh-tab <?= ($seccionNotas??'')===$clave?'active':'' ?>" href="<?= e(url($enlace[0])) ?>" <?= ($seccionNotas??'')===$clave?'aria-current="page"':'' ?>><?= e($enlace[1]) ?></a>
    <?php endforeach; ?>
</nav>
<?php elseif (auth_rol_activo()==='PRACTICANTE'): ?>
<?php // Conservé Mis notas para todos y mostré la evaluación del área únicamente ante liderazgo vigente. ?>
<nav class="rrhh-tabs rrhh-notas-nav" aria-label="Secciones de Notas">
    <a class="rrhh-tab <?= ($seccionNotas??'')==='propias'?'active':'' ?>" href="<?= e(url('practicante/mis-notas.php')) ?>" <?= ($seccionNotas??'')==='propias'?'aria-current="page"':'' ?>>Mis notas</a>
    <?php if (!empty($mostrarNotasLider)): ?>
        <a class="rrhh-tab <?= ($seccionNotas??'')==='evaluaciones'?'active':'' ?>" href="<?= e(url('practicante/notas.php')) ?>" <?= ($seccionNotas??'')==='evaluaciones'?'aria-current="page"':'' ?>>Evaluar mi área</a>
    <?php endif; ?>
</nav>
<?php endif; ?>
