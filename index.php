<?php
// Funkcja do konwersji polskich znaków w URL
function convertToPolishUrl($string) {
    $polishChars = array('ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ź', 'ż', 'Ą', 'Ć', 'Ę', 'Ł', 'Ń', 'Ó', 'Ś', 'Ź', 'Ż');
    $latinChars = array('a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z', 'A', 'C', 'E', 'L', 'N', 'O', 'S', 'Z', 'Z');
    $string = str_replace($polishChars, $latinChars, $string);
    return urlencode($string);
}

// Pobierz ścieżkę do katalogu
$baseDir = __DIR__;
$dir = isset($_GET['sciezka']) ? $_GET['sciezka'] : $baseDir;
$fullPath = realpath($dir);

// Sprawdź, czy ścieżka jest poprawna
if ($fullPath === false || !is_dir($fullPath) || strpos($fullPath, $baseDir) !== 0) {
    die('Niepoprawna ścieżka!');
}

// Nazwa pliku do ukrycia
$ignoreFile = basename(__FILE__);

// Funkcja do listowania plików i folderów
function listFilesAndFolders($dir, $ignoreFile, $baseDir) {
    $items = scandir($dir);
    $result = [];

    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || ($item === $ignoreFile && $dir === $baseDir)) {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        $result[] = [
            'type' => is_dir($path) ? 'folder' : 'file',
            'name' => $item,
            'path' => $path,
            'size' => is_file($path) ? filesize($path) : null,
            'modified' => filemtime($path)
        ];
    }

    usort($result, function($a, $b) {
        if ($a['type'] != $b['type']) {
            return $a['type'] == 'folder' ? -1 : 1;
        }
        return strcasecmp($a['name'], $b['name']);
    });

    return $result;
}

// Funkcja do formatowania rozmiaru pliku
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Pobierz listę plików i folderów
$filesAndFolders = listFilesAndFolders($fullPath, $ignoreFile, $baseDir);

// Generuj ścieżkę nawigacji
$pathParts = explode(DIRECTORY_SEPARATOR, str_replace($baseDir, '', $fullPath));
$breadcrumbs = [];
$currentPath = $baseDir;
foreach ($pathParts as $part) {
    if ($part !== '') {
        $currentPath .= DIRECTORY_SEPARATOR . $part;
        $breadcrumbs[] = [
            'name' => $part,
            'path' => $currentPath
        ];
    }
}

?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FiliZ</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4CAF50;
            --secondary-color: #FFC107;
            --background-color: #1E1E1E;
            --surface-color: #2D2D2D;
            --on-surface-color: #E0E0E0;
            --text-color: #FFFFFF;
            --hover-color: #3A3A3A;
            --border-color: #424242;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background-color: var(--primary-color);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 500;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .breadcrumb {
            background-color: var(--surface-color);
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

            .breadcrumb a {
                color: var(--secondary-color);
                text-decoration: none;
                transition: color 0.3s ease;
            }

                .breadcrumb a:hover {
                    color: var(--primary-color);
                }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background-color: var(--surface-color);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 1px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background-color: var(--hover-color);
        }

        .icon {
            margin-right: 10px;
            color: var(--secondary-color);
        }

        .file-link, .folder-link {
            text-decoration: none;
            color: var(--on-surface-color);
            display: flex;
            align-items: center;
            transition: color 0.3s ease;
        }

            .file-link:hover, .folder-link:hover {
                color: var(--secondary-color);
            }

        .preview img {
            max-width: 50px;
            max-height: 50px;
            border-radius: 4px;
            transition: transform 0.3s ease;
        }

            .preview img:hover {
                transform: scale(1.1);
            }

        .action-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

            .action-button:hover {
                background-color: #45a049;
            }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            th, td {
                padding: 10px 15px;
            }

            .hidden-mobile {
                display: none;
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>FiliZ</h1>
    </header>

    <div class="container">
        <nav class="breadcrumb">
            <a href="?sciezka=<?php echo convertToPolishUrl($baseDir); ?>"><i class="fas fa-home"></i> Główna</a>
            <?php foreach ($breadcrumbs as $crumb): ?>
            / <a href="?sciezka=<?php echo convertToPolishUrl($crumb['path']); ?>"><?php echo htmlspecialchars($crumb['name']); ?></a>
            <?php endforeach; ?>
        </nav>

        <table>
            <thead>
                <tr>
                    <th>Nazwa</th>
                    <th class="hidden-mobile">Rozmiar</th>
                    <th class="hidden-mobile">Data modyfikacji</th>
                    <th>Akcje</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($fullPath !== $baseDir): ?>
                <tr>
                    <td colspan="4">
                        <a href="?sciezka=<?php echo convertToPolishUrl(dirname($fullPath)); ?>" class="folder-link">
                            <i class="fas fa-level-up-alt icon"></i> Katalog nadrzędny
                        </a>
                    </td>
                </tr>
                <?php endif; ?>

                <?php foreach ($filesAndFolders as $item): ?>
                <tr>
                    <td>
                        <?php if ($item['type'] === 'folder'): ?>
                        <a href="?sciezka=<?php echo convertToPolishUrl($item['path']); ?>" class="folder-link">
                            <i class="fas fa-folder icon"></i><?php echo htmlspecialchars($item['name']); ?>
                        </a>
                        <?php else: ?>
                        <a href="<?php echo htmlspecialchars($item['path']); ?>" target="_blank" class="file-link">
                            <i class="fas fa-file icon"></i><?php echo htmlspecialchars($item['name']); ?>
                        </a>
                        <?php endif; ?>
                    </td>
                    <td class="hidden-mobile">
                        <?php echo $item['size'] !== null ? formatFileSize($item['size']) : '-'; ?>
                    </td>
                    <td class="hidden-mobile">
                        <?php echo date('Y-m-d H:i:s', $item['modified']); ?>
                    </td>
                    <td>
                        <?php if ($item['type'] === 'file'): ?>
                        <?php
                        $ext = strtolower(pathinfo($item['path'], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                        <a href="<?php echo htmlspecialchars($item['path']); ?>" target="_blank" class="preview">
                            <img src="<?php echo htmlspecialchars($item['path']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        </a>
                        <?php elseif ($ext === 'pdf'): ?>
                        <a href="<?php echo htmlspecialchars($item['path']); ?>" target="_blank" class="action-button">
                            <i class="far fa-file-pdf"></i> Podgląd PDF
                        </a>
                        <?php else: ?>
                        <a href="<?php echo htmlspecialchars($item['path']); ?>" target="_blank" class="action-button">
                            <i class="fas fa-external-link-alt"></i> Otwórz
                        </a>
                        <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Możesz dodać tutaj dodatkowy JavaScript, jeśli jest potrzebny
    </script>
</body>
</html>