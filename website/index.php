<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (is_file(__DIR__ . $uri)) {
    return false;
}

$storyDir = __DIR__ . '/story/';
$imgDir   = __DIR__ . '/img/';
$slug     = trim($uri, '/');

$chapter     = null;
$contentFile = null;
$pageType    = 'home';

if ($slug === 'photo-gallery') {
    $pageType    = 'gallery';
    $contentFile = $imgDir . 'photo-gallery.md';
} elseif ($slug === 'map-gallery') {
    $pageType    = 'gallery';
    $contentFile = $imgDir . 'map-gallery.md';
} elseif ($slug !== '') {
    if (preg_match('/^chapter\d+(-[a-z0-9]+)*$/', $slug) && file_exists($storyDir . $slug . '.md')) {
        $chapter     = $slug;
        $pageType    = 'chapter';
        $contentFile = $storyDir . $slug . '.md';
    }
}

$files = glob($storyDir . 'chapter*.md');
usort($files, function ($a, $b) {
    preg_match('/(\d+)/', basename($a), $ma);
    preg_match('/(\d+)/', basename($b), $mb);
    return (int) $ma[1] - (int) $mb[1];
});
$chapters = array_map(fn($f) => basename($f, '.md'), $files);

function chapterInfo(string $file): array {
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $h2 = $h1 = $loc = '';
    foreach ($lines as $line) {
        if (!$h2 && str_starts_with($line, '## ')) { $h2 = substr($line, 3); continue; }
        if (!$h1 && str_starts_with($line, '# '))  { $h1 = substr($line, 2); continue; }
        if ($h1 && !$loc && $line !== '---' && !str_starts_with($line, '!')) {
            $loc = str_replace('**', '', $line);
            break;
        }
    }
    return ['label' => $h2, 'title' => $h1, 'location' => $loc];
}

$prevChapter = $nextChapter = null;
$prevTitle   = $nextTitle   = '';
$pageTitle   = 'IsaakStory.org';
if ($chapter !== null) {
    $info      = chapterInfo($storyDir . $chapter . '.md');
    $pageTitle = $info['title'] . ' | IsaakStory.org';
    $idx = array_search($chapter, $chapters);
    if ($idx > 0) {
        $prevChapter = $chapters[$idx - 1];
        $prevTitle   = chapterInfo($storyDir . $prevChapter . '.md')['title'];
    }
    if ($idx < count($chapters) - 1) {
        $nextChapter = $chapters[$idx + 1];
        $nextTitle   = chapterInfo($storyDir . $nextChapter . '.md')['title'];
    }
} elseif ($slug === 'photo-gallery') {
    $pageTitle = 'Photo Gallery | IsaakStory.org';
} elseif ($slug === 'map-gallery') {
    $pageTitle = 'Map Gallery | IsaakStory.org';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<?php if ($pageType !== 'home'): ?>
    <nav class="top-nav">
        <a class="home-link" href="/">IsaakStory.org</a>
    </nav>
    <div id="content"></div>
    <?php if ($pageType === 'chapter'): ?>
    <nav class="bottom-nav">
        <?php if ($prevChapter): ?>
            <a class="nav-btn prev" href="/<?= $prevChapter ?>">
                <span class="nav-label">Previous chapter</span>
                <span class="nav-title"><?= htmlspecialchars($prevTitle) ?></span>
            </a>
        <?php endif; ?>
        <?php if ($nextChapter): ?>
            <a class="nav-btn next" href="/<?= $nextChapter ?>">
                <span class="nav-label">Next chapter</span>
                <span class="nav-title"><?= htmlspecialchars($nextTitle) ?></span>
            </a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script>
        const md = <?= json_encode(file_get_contents($contentFile)) ?>;
        document.getElementById('content').innerHTML = marked.parse(md);
        <?php if ($pageType === 'chapter'): ?>
        const hr = document.querySelector('#content hr');
        if (hr) {
            let el = hr.nextElementSibling;
            while (el && el.tagName !== 'P') el = el.nextElementSibling;
            if (el && !(el.children.length === 1 && el.firstElementChild.tagName === 'EM')) {
                el.classList.add('drop-cap');
            }
        }
        <?php endif; ?>
    </script>
<?php else: ?>
    <header class="site-header">
        <h1 class="site-title">IsaakStory.org</h1>
        <p class="subtitle">The Ancestral Journey of Allegra McBirney and the Isaak Family</p>
    </header>
    <ul>
    <?php foreach ($chapters as $name):
        $info = chapterInfo($storyDir . $name . '.md');
    ?>
        <li>
            <a href="/<?= $name ?>">
                <span class="chapter-label"><?= htmlspecialchars($info['label']) ?></span>
                <span class="chapter-title"><?= htmlspecialchars($info['title']) ?></span>
                <?php if ($info['location']): ?>
                <span class="chapter-location"><?= htmlspecialchars($info['location']) ?></span>
                <?php endif; ?>
            </a>
        </li>
    <?php endforeach; ?>
    </ul>
    <hr class="gallery-divider">
    <ul>
        <li><a href="/photo-gallery">
            <span class="chapter-title">Photo Gallery</span>
        </a></li>
        <li><a href="/map-gallery">
            <span class="chapter-title">Map Gallery</span>
        </a></li>
    </ul>
<?php endif; ?>
</body>
</html>
