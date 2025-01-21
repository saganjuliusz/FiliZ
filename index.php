<?php
// Konfiguracja podstawowa
$config = [
    'app_name' => 'FiliZ Pro',
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar'],
    'max_preview_size' => 10485760, // 10MB w bajtach
    'timezone' => 'Europe/Warsaw',
    'max_upload_size' => 52428800, // 50MB
    'theme' => [
        'primary' => '#2196F3',
        'secondary' => '#FFC107',
        'success' => '#4CAF50',
        'danger' => '#F44336',
        'background' => '#121212',
        'surface' => '#1E1E1E',
        'card' => '#252525'
    ]
];

// Ustawienie strefy czasowej
date_default_timezone_set($config['timezone']);

// Funkcje pomocnicze
function convertToPolishUrl($string) {
    $polishChars = ['ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ź', 'ż', 'Ą', 'Ć', 'Ę', 'Ł', 'Ń', 'Ó', 'Ś', 'Ź', 'Ż'];
    $latinChars = ['a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z', 'A', 'C', 'E', 'L', 'N', 'O', 'S', 'Z', 'Z'];
    return urlencode(str_replace($polishChars, $latinChars, $string));
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

function getFileTypeInfo($extension, $mimeType) {
    $types = [
        'image' => [
            'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'],
            'icon' => 'fa-file-image',
            'color' => '#4CAF50'
        ],
        'document' => [
            'extensions' => ['pdf', 'doc', 'docx', 'txt', 'rtf'],
            'icon' => 'fa-file-pdf',
            'color' => '#F44336'
        ],
        'spreadsheet' => [
            'extensions' => ['xls', 'xlsx', 'csv'],
            'icon' => 'fa-file-excel',
            'color' => '#4CAF50'
        ],
        'archive' => [
            'extensions' => ['zip', 'rar', '7z', 'tar', 'gz'],
            'icon' => 'fa-file-archive',
            'color' => '#FFC107'
        ],
        'code' => [
            'extensions' => ['html', 'css', 'js', 'php', 'py', 'java'],
            'icon' => 'fa-file-code',
            'color' => '#2196F3'
        ]
    ];

    foreach ($types as $type => $info) {
        if (in_array(strtolower($extension), $info['extensions'])) {
            return $info;
        }
    }

    return [
        'icon' => 'fa-file',
        'color' => '#9E9E9E'
    ];
}

// Obsługa ścieżek i bezpieczeństwa
$baseDir = __DIR__;
$dir = isset($_GET['sciezka']) ? $_GET['sciezka'] : $baseDir;
$fullPath = realpath($dir);

if ($fullPath === false || !is_dir($fullPath) || strpos($fullPath, $baseDir) !== 0) {
    die('Niepoprawna ścieżka! Dostęp zabroniony.');
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
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeType = is_file($path) ? mime_content_type($path) : null;
        $typeInfo = getFileTypeInfo($ext, $mimeType);
        
        $result[] = [
            'type' => is_dir($path) ? 'folder' : 'file',
            'name' => $item,
            'path' => $path,
            'size' => is_file($path) ? filesize($path) : null,
            'modified' => filemtime($path),
            'extension' => $ext,
            'is_readable' => is_readable($path),
            'is_writable' => is_writable($path),
            'mime_type' => $mimeType,
            'icon' => $typeInfo['icon'],
            'color' => $typeInfo['color']
        ];
    }

    usort($result, function($a, $b) {
        if ($a['type'] !== $b['type']) {
            return $a['type'] === 'folder' ? -1 : 1;
        }
        return strcasecmp($a['name'], $b['name']);
    });

    return $result;
}

// Generowanie ścieżki nawigacji
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

// Pobierz listę plików i folderów
$filesAndFolders = listFilesAndFolders($fullPath, $ignoreFile, $baseDir);

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $config['app_name']; ?> - System Zarządzania Plikami</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: <?php echo $config['theme']['primary']; ?>;
            --secondary: <?php echo $config['theme']['secondary']; ?>;
            --success: <?php echo $config['theme']['success']; ?>;
            --danger: <?php echo $config['theme']['danger']; ?>;
            --background: <?php echo $config['theme']['background']; ?>;
            --surface: <?php echo $config['theme']['surface']; ?>;
            --card: <?php echo $config['theme']['card']; ?>;
            --text-primary: rgba(255, 255, 255, 0.87);
            --text-secondary: rgba(255, 255, 255, 0.6);
            --border: rgba(255, 255, 255, 0.12);
            --hover: rgba(255, 255, 255, 0.08);
            --shadow: rgba(0, 0, 0, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background);
            color: var(--text-primary);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* Nagłówek */
        header {
            background: linear-gradient(135deg, var(--primary), #1565C0);
            padding: 20px 0;
            box-shadow: 0 4px 12px var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            text-decoration: none;
        }

        .logo i {
            font-size: 2rem;
        }

        .logo h1 {
            font-size: 2rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            background: linear-gradient(45deg, #fff, #f0f0f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Wyszukiwarka */
        .search-box {
            position: relative;
            max-width: 400px;
            width: 100%;
        }

        .search-input {
            width: 100%;
            padding: 12px 20px;
            padding-left: 45px;
            background-color: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 30px;
            color: white;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            background-color: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
            outline: none;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.7);
            pointer-events: none;
        }

        /* Breadcrumbs */
        .breadcrumb {
            background-color: var(--surface);
            padding: 15px 20px;
            border-radius: 10px;
            margin: 20px auto;
            box-shadow: 0 2px 8px var(--shadow);
            max-width: 1400px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .breadcrumb a {
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 6px 12px;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .breadcrumb a:hover {
            background-color: var(--hover);
            color: var(--secondary);
        }

        .breadcrumb .separator {
            color: var(--text-secondary);
            margin: 0 5px;
        }

        /* Zawartość główna */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .content-wrapper {
            background-color: var(--surface);
            border-radius: 10px;
            box-shadow: 0 4px 12px var(--shadow);
            overflow: hidden;
            animation: fadeIn 0.5s ease;
        }

        /* Tabela */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        th, td {
            padding: 16px 20px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th {
            background-color: var(--card);
            color: var(--text-primary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 1px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        tr:hover {
            background-color: var(--hover);
        }

        .file-link, .folder-link {
            text-decoration: none;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s ease;
            padding: 8px 12px;
            border-radius: 6px;
        }

        .file-link:hover, .folder-link:hover {
            background-color: var(--hover);
            transform: translateX(5px);
        }

        .icon {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .folder-icon {
            color: var(--secondary);
        }

        .file-icon {
            color: var(--primary);
        }

        /* Podgląd plików */
        .preview-container {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .preview img {
            max-width: 60px;
            max-height: 60px;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px var(--shadow);
        }

        .preview img:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px var(--shadow);
        }

        /* Przyciski akcji */
        .action-button {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }

        .action-button:hover {
            background-color: #1976D2;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
        }

        .action-button::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 120%;
            height: 120%;
            background: rgba(255, 255, 255, 0.1);
            transform: translate(-50%, -50%) scale(0);
            border-radius: 50%;
            transition: transform 0.5s ease;
        }

        .action-button:active::after {
            transform: translate(-50%, -50%) scale(1);
        }

        /* Informacje o pliku */
        .file-info {
            color: var(--text-secondary);
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .file-info i {
            font-size: 0.75rem;
        }

        /* Tooltip */
        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 120%;
            left: 50%;
            transform: translateX(-50%);
            padding: 8px 12px;
            background-color: var(--card);
            color: var(--text-primary);
            border-radius: 6px;
            font-size: 0.75rem;
            white-space: nowrap;
            z-index: 1000;
            box-shadow: 0 4px 12px var(--shadow);
            animation: fadeIn 0.2s ease;
        }

        /* Responsywność */
        @media (max-width: 1200px) {
            .container {
                padding: 15px;
            }

            .hidden-laptop {
                display: none;
            }
        }

        @media (max-width: 1024px) {
            .container {
                padding: 10px;
            }

            .hidden-tablet {
                display: none;
            }

            .header-content {
                flex-direction: column;
                gap: 15px;
            }

            .search-box {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                text-align: center;
            }

            .hidden-mobile {
                display: none;
            }

            th, td {
                padding: 12px 15px;
            }

            .breadcrumb {
                padding: 10px 15px;
                font-size: 0.875rem;
            }

            .logo h1 {
                font-size: 1.5rem;
            }
        }

        /* Animacje */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideIn {
            from {
                transform: translateX(-100%);
            }
            to {
                transform: translateX(0);
            }
        }

        /* Dodatkowe ulepszenia wizualne */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
            background-color: var(--hover);
            color: var(--text-secondary);
        }

        .status-badge.success {
            background-color: rgba(76, 175, 80, 0.1);
            color: #4CAF50;
        }

        .status-badge.warning {
            background-color: rgba(255, 193, 7, 0.1);
            color: #FFC107;
        }

        .status-badge.error {
            background-color: rgba(244, 67, 54, 0.1);
            color: #F44336;
        }

        /* Dodatkowe animacje dla ikon */
        .folder-link:hover .icon {
            transform: scale(1.1) translateX(2px);
        }

        .file-link:hover .icon {
            transform: rotate(5deg);
        }

        /* Stylizacja paska przewijania */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--surface);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #1976D2;
        }
    </style>
</head>
<body>
    <header class="animate__animated animate__slideInDown">
        <div class="header-content">
            <a href="?sciezka=<?php echo convertToPolishUrl($baseDir); ?>" class="logo">
                <i class="fas fa-folder-open"></i>
                <h1><?php echo $config['app_name']; ?></h1>
            </a>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Szukaj plików i folderów..." class="search-input">
            </div>
        </div>
    </header>

    <div class="container">
        <nav class="breadcrumb animate__animated animate__fadeIn">
            <a href="?sciezka=<?php echo convertToPolishUrl($baseDir); ?>" class="tooltip" data-tooltip="Strona główna">
                <i class="fas fa-home"></i>
                <span class="hidden-mobile">Główna</span>
            </a>
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
            <span class="separator">/</span>
            <a href="?sciezka=<?php echo convertToPolishUrl($crumb['path']); ?>" 
               class="tooltip" 
               data-tooltip="<?php echo htmlspecialchars($crumb['path']); ?>">
                <?php echo htmlspecialchars($crumb['name']); ?>
            </a>
            <?php endforeach; ?>
        </nav>

        <div class="content-wrapper animate__animated animate__fadeInUp">
            <table>
                <thead>
                    <tr>
                        <th>Nazwa</th>
                        <th class="hidden-mobile">Rozmiar</th>
                        <th class="hidden-tablet">Data modyfikacji</th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($fullPath !== $baseDir): ?>
                    <tr>
                        <td colspan="4">
                            <a href="?sciezka=<?php echo convertToPolishUrl(dirname($fullPath)); ?>" class="folder-link">
                                <i class="fas fa-level-up-alt icon folder-icon"></i>
                                <span>Katalog nadrzędny</span>
                            </a>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php foreach ($filesAndFolders as $item): ?>
                    <tr class="item-row">
                        <td>
                            <?php if ($item['type'] === 'folder'): ?>
                            <a href="?sciezka=<?php echo convertToPolishUrl($item['path']); ?>" class="folder-link">
                                <i class="fas fa-folder icon folder-icon"></i>
                                <span><?php echo htmlspecialchars($item['name']); ?></span>
                            </a>
                            <?php else: ?>
                            <a href="<?php echo htmlspecialchars($item['path']); ?>" 
                               target="_blank" 
                               class="file-link"
                               data-type="<?php echo $item['extension']; ?>">
                                <i class="fas <?php echo $item['icon']; ?> icon file-icon" 
                                   style="color: <?php echo $item['color']; ?>">
                                </i>
                                <span><?php echo htmlspecialchars($item['name']); ?></span>
                            </a>
                            <?php endif; ?>
                        </td>
                        <td class="hidden-mobile">
                            <?php if ($item['type'] === 'file'): ?>
                                <span class="file-info">
                                    <i class="fas fa-hdd"></i>
                                    <?php echo formatFileSize($item['size']); ?>
                                </span>
                            <?php else: ?>
                                <span class="status-badge">Folder</span>
                            <?php endif; ?>
                        </td>
                        <td class="hidden-tablet">
                            <span class="file-info">
                                <i class="fas fa-clock"></i>
                                <?php echo date('d.m.Y H:i', $item['modified']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="preview-container">
                                <?php if ($item['type'] === 'file'): ?>
                                    <?php
                                    $ext = strtolower($item['extension']);
                                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif']) && $item['size'] <= $config['max_preview_size']): ?>
                                        <a href="<?php echo htmlspecialchars($item['path']); ?>" 
                                           target="_blank" 
                                           class="preview tooltip" 
                                           data-tooltip="Kliknij, aby powiększyć">
                                            <img src="<?php echo htmlspecialchars($item['path']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 loading="lazy">
                                        </a>
                                    <?php else: ?>
                                        <a href="<?php echo htmlspecialchars($item['path']); ?>" 
                                           target="_blank" 
                                           class="action-button tooltip" 
                                           data-tooltip="Otwórz plik">
                                            <i class="fas fa-external-link-alt"></i>
                                            <span class="hidden-mobile">Otwórz</span>
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Zaawansowane wyszukiwanie
        document.getElementById('searchInput')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.item-row');
            
            rows.forEach(row => {
                const fileName = row.querySelector('.file-link, .folder-link span').textContent.toLowerCase();
                const fileType = row.querySelector('.file-link')?.dataset.type?.toLowerCase() || '';
                
                const matchesSearch = fileName.includes(searchTerm) || fileType.includes(searchTerm);
                row.style.display = matchesSearch ? '' : 'none';
                
                if (matchesSearch) {
                    row.classList.add('animate__animated', 'animate__fadeIn');
                }
            });
        });

        // Obsługa sortowania
        document.querySelectorAll('th').forEach((header, index) => {
            if (index < 3) { // Nie sortujemy kolumny z akcjami
                header.style.cursor = 'pointer';
                header.addEventListener('click', function() {
                    sortTable(index);
                });
            }
        });

        function sortTable(column) {
            const table = document.querySelector('table');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr:not(:first-child)'));
            
            const sortedRows = rows.sort((a, b) => {
                const aValue = a.children[column].textContent.trim();
                const bValue = b.children[column].textContent.trim();
                
                if (column === 1) { // Sortowanie rozmiaru
                    const aSize = parseFloat(aValue) || 0;
                    const bSize = parseFloat(bValue) || 0;
                    return aSize - bSize;
                }
                
                return aValue.localeCompare(bValue, 'pl', { numeric: true });
            });
            
            tbody.append(...sortedRows);
        }

        // Lazy loading dla obrazków
        if ('loading' in HTMLImageElement.prototype) {
            const images = document.querySelectorAll('img[loading="lazy"]');
            images.forEach(img => img.src = img.src);
        } else {
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/lozad.js/1.16.0/lozad.min.js';
            script.async = true;
            
            script.onload = function() {
                const observer = lozad();
                observer.observe();
            }
            
            document.body.appendChild(script);
        }

        // Animacje przy przewijaniu
        const animateOnScroll = () => {
            const elements = document.querySelectorAll('.item-row');
            elements.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const elementBottom = element.getBoundingClientRect().bottom;
                
                if (elementTop < window.innerHeight && elementBottom > 0) {
                    element.classList.add('animate__animated', 'animate__fadeInUp');
                }
            });
        };

        window.addEventListener('scroll', animateOnScroll);
        animateOnScroll();
    </script>
</body>
</html>