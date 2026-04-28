<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($uri, '/img/')) {
    $imgDir = realpath(__DIR__ . '/../img/');
    $imgFile = realpath(__DIR__ . '/..' . $uri);
    if ($imgFile && $imgDir && str_starts_with($imgFile, $imgDir)) {
        $mimes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
        $ext = strtolower(pathinfo($imgFile, PATHINFO_EXTENSION));
        if (isset($mimes[$ext])) {
            header('Content-Type: ' . $mimes[$ext]);
            readfile($imgFile);
            exit;
        }
    }
    http_response_code(404);
    exit;
}

$chapter = $_GET['chapter'] ?? null;

if ($chapter !== null && !preg_match('/^chapter\d+(-[a-z0-9]+)*$/', $chapter)) {
    $chapter = null;
}

$storyDir = __DIR__ . '/../story/';

if ($chapter !== null && !file_exists($storyDir . $chapter . '.md')) {
    $chapter = null;
}

function chapterTitle(string $file): string {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $h2 = $h1 = '';
    foreach ($lines as $line) {
        if (!$h2 && str_starts_with($line, '## ')) $h2 = ltrim($line, '# ');
        elseif (!$h1 && str_starts_with($line, '# '))  $h1 = ltrim($line, '# ');
        if ($h2 && $h1) break;
    }
    return $h2 && $h1 ? "$h2: $h1" : ($h2 ?: $h1 ?: basename($file, '.md'));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IsaakStory.org</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php if ($chapter !== null): ?>
    <a class="back" href="/">← All chapters</a>
    <div id="content"></div>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script>
        const md = <?= json_encode(file_get_contents($storyDir . $chapter . '.md')) ?>;
        document.getElementById('content').innerHTML = marked.parse(md);
    </script>
<?php else: ?>
    <h1>IsaakStory.org</h1>
    <p class="subtitle">The Ancestral Journey of Allegra McBirney and the Isaak Family</p>
    <ul>
    <?php
    $files = glob($storyDir . 'chapter*.md');
    usort($files, function ($a, $b) {
        preg_match('/(\d+)/', basename($a), $ma);
        preg_match('/(\d+)/', basename($b), $mb);
        return (int) $ma[1] - (int) $mb[1];
    });
    foreach ($files as $file):
        $name = basename($file, '.md');
        $title = chapterTitle($file);
    ?>
        <li><a href="?chapter=<?= $name ?>"><?= htmlspecialchars($title) ?></a></li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>
</body>
</html>
