    </div>
</main>
</div>
<!-- Sustituí las interacciones Bootstrap por controles accesibles propios. -->
<?php
// Agregué versión por contenido para que una actualización de ui.js no reutilice el script anterior del navegador.
$uiScriptFile = ASSETS_PATH . '/js/ui.js';
$uiScriptHash = is_file($uiScriptFile) && is_readable($uiScriptFile) ? hash_file('sha256', $uiScriptFile) : false;
$uiScriptUrl = asset('js/ui.js') . ($uiScriptHash !== false ? '?v=' . substr($uiScriptHash, 0, 16) : '');
?>
<script src="<?= e($uiScriptUrl) ?>" defer></script>
</body>
</html>
