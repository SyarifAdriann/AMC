<?php
$styles = $styles ?? [];
$scripts = $scripts ?? [];
$head = $head ?? '';
$bodyClass = $bodyClass ?? 'min-h-screen bg-slate-900 text-white';
$bodyAttributes = $bodyAttributes ?? '';

$bodyAttributesParts = [];
if (!empty($bodyAttributes)) {
    $bodyAttributesParts[] = trim($bodyAttributes);
}
$bodyClass = trim((string) $bodyClass);
if ($bodyClass !== '') {
    $bodyAttributesParts[] = 'class="' . htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') . '"';
}
$bodyAttrString = $bodyAttributesParts ? ' ' . implode(' ', $bodyAttributesParts) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title ?? 'AMC'); ?></title>
    <?php if (!empty($styles)): ?>
        <?php foreach ($styles as $href): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars($href); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <?= $head ?>
</head>
<body<?= $bodyAttrString ?>>
<?= $content ?? '' ?>
<?php if (!empty($scripts)): ?>
    <?php foreach ($scripts as $src): ?>
        <script src="<?= htmlspecialchars($src); ?>" defer></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
