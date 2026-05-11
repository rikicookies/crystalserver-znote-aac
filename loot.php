<?php 
require_once 'engine/init.php'; 
include 'layout/overall/header.php';

$otdir = 'C:\crystalserver'; // set your server directory here

$rarity = array(
    'Not Rare'  => 7,
    'Semi Rare' => 2,
    'Rare'      => 0.5,
    'Very Rare' => 0
);
?>

<script language="javascript">
    function toggleVisibility(obj) {
        var el = document.getElementById('d' + obj.id);
        var name = obj.innerHTML.substring(4);

        if(el.style.display == 'none') {
            obj.innerHTML = '[ -]';
            el.style.display = 'block';
        } else {
            obj.innerHTML = '[+]';
            el.style.display = 'none';
        }
        obj.innerHTML += ' ' + name;
    }
    
    function toggleCategory(obj) {
        var el = document.getElementById('cat' + obj.id);
        var name = obj.innerHTML.substring(4);

        if(el.style.display == 'none') {
            obj.innerHTML = '[ -]';
            el.style.display = 'block';
        } else {
            obj.innerHTML = '[+]';
            el.style.display = 'none';
        }
        obj.innerHTML += ' ' + name;
    }
</script>
<br><br>

<?php

    $cacheFile = $otdir . '/loot_cache.json';
    $cacheTime = 3600;
    $monstersPerPage = 30;

    if (file_exists($otdir . '/data/items/items.xml')) {
        $items = simplexml_load_file($otdir . '/data/items/items.xml');
        if ($items) {
            foreach($items->item as $v) {
                $itemList[(int)$v['id']] = (string)$v['name'];
            }
        } else {
            die('<b>Could not load items!</b>');
        }
    } else {
        die('<b>Items file not found!</b>');
    }

    $recursiveCategories = ['quest creatures'];

    $monsterDirs = array(
        'amphibics'       => $otdir . '/data-global/monster/amphibics/',
        'aquatics'        => $otdir . '/data-global/monster/aquatics/',
        'birds'           => $otdir . '/data-global/monster/birds/',
        'bosses'          => $otdir . '/data-global/monster/bosses/',
        'constructs'      => $otdir . '/data-global/monster/constructs/',
        'dawnport'        => $otdir . '/data-global/monster/dawnport/',
        'demons'          => $otdir . '/data-global/monster/demons/',
        'deities'         => $otdir . '/data-global/monster/deities/',
        'dragons'         => $otdir . '/data-global/monster/dragons/',
        'elementals'      => $otdir . '/data-global/monster/elementals/',
        'extra_dimensional' => $otdir . '/data-global/monster/extra_dimensional/',
        'fey'             => $otdir . '/data-global/monster/fey/',
        'giants'          => $otdir . '/data-global/monster/giants/',
        'humanoids'       => $otdir . '/data-global/monster/humanoids/',
        'humans'          => $otdir . '/data-global/monster/humans/',
        'lycanthropes'    => $otdir . '/data-global/monster/lycanthropes/',
        'magicals'        => $otdir . '/data-global/monster/magicals/',
        'mammals'         => $otdir . '/data-global/monster/mammals/',
        'plants'          => $otdir . '/data-global/monster/plants/',
        'quest creatures' => $otdir . '/data-global/monster/quests/',
        'reptiles'        => $otdir . '/data-global/monster/reptiles/',
        'slimes'          => $otdir . '/data-global/monster/slimes/',
        'inkborn'         => $otdir . '/data-global/monster/inkborn/',
        'undeads'         => $otdir . '/data-global/monster/undeads/',
        'vermins'         => $otdir . '/data-global/monster/vermins/',
    );

    if (!isset($_GET['category'])) {
        echo '<h1>Loot Page</h1>';
        echo '<h2>Please, select a category</h2>';

        $catCount = 0;
        foreach($monsterDirs as $catName => $dir) {
            if (!is_dir($dir)) continue;

            if (in_array($catName, $recursiveCategories)) {
                $files = getMonsterFilesRecursive($dir);
            } else {
                $files = glob($dir . '*.lua') ?: [];
            }

            $fileCount = count($files);
            echo '<a id="' . ++$catCount . '" style="text-decoration: none; font: bold 14px verdana; color: orange;" href="javascript:void(0);" onclick="toggleCategory(this)">[+] ' . htmlspecialchars(ucfirst(str_replace('_', ' ', $catName))) . ' (' . $fileCount . ' monsters)</a><br>';

            echo '<div style="display: none; margin-left: 20px;" id="cat' . $catCount . '">';
            if ($fileCount > 0) {
                $totalPages = ceil($fileCount / $monstersPerPage);
                for ($i = 1; $i <= $totalPages; $i++) {
                    echo '<a href="?category=' . urlencode($catName) . '&page=' . $i . '">[' . $i . ']</a> ';
                }
                echo ' | ' . $fileCount . ' monsters total<br>';
            } else {
                echo 'No monsters found in this category<br>';
            }
            echo '</div>';
        }

        include 'layout/overall/footer.php';
        exit;
    }

    $category = $_GET['category'];

    if (!isset($monsterDirs[$category])) {
        die('<b>Invalid category selected!</b>');
    }

    $categoryDir = $monsterDirs[$category];

    echo '<a href="' . htmlspecialchars($_SERVER['PHP_SELF']) . '">← Back to Categories</a><br><br>';
    echo '<h2>Category: ' . htmlspecialchars(ucfirst(str_replace('_', ' ', $category))) . '</h2>';

    $useCache = false;
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
        $cachedData = json_decode(file_get_contents($cacheFile), true);
        if ($cachedData && isset($cachedData['categories'][$category]) && isset($cachedData['timestamp'])) {
            $monsterFiles = $cachedData['categories'][$category];
            $useCache = true;
            echo '<b>Using cached data (updated: ' . date('Y-m-d H:i:s', $cachedData['timestamp']) . ')</b><br>';
        }
    }

    if (!$useCache) {
        if (in_array($category, $recursiveCategories)) {
            $monsterFiles = getMonsterFilesRecursive($categoryDir);
        } else {
            $monsterFiles = [];
            if (is_dir($categoryDir)) {
                $monsterFiles = glob($categoryDir . '*.lua') ?: [];
            }
        }

        $cacheData = [];
        if (file_exists($cacheFile)) {
            $cacheData = json_decode(file_get_contents($cacheFile), true) ?: [];
        }
        $cacheData['categories'][$category] = $monsterFiles;
        $cacheData['timestamp'] = time();
        file_put_contents($cacheFile, json_encode($cacheData));
        echo '<b>Data cached for next time (cache will be valid for 1 hour)</b><br>';
    }

    if (!$monsterFiles) {
        echo '<b>No monster files found in this directory</b><br>';
        include 'layout/overall/footer.php';
        exit;
    } else {
        echo '<b>Found ' . count($monsterFiles) . ' monster files in this category</b><br>';
    }

    $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $totalPages = ceil(count($monsterFiles) / $monstersPerPage);
    $startIndex = ($currentPage - 1) * $monstersPerPage;
    $monsterFilesPage = array_slice($monsterFiles, $startIndex, $monstersPerPage);

    echo '<div style="margin: 10px 0;">';
    echo '<b>Page: </b>';
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == $currentPage) {
            echo '<span style="font-weight: bold; margin: 0 5px;">[' . $i . ']</span> ';
        } else {
            echo '<a href="?category=' . urlencode($category) . '&page=' . $i . '">[' . $i . ']</a> ';
        }
    }
    echo ' | Showing ' . count($monsterFilesPage) . ' monsters (' . ($startIndex + 1) . '-' . min($startIndex + $monstersPerPage, count($monsterFiles)) . ' of ' . count($monsterFiles) . ')';
    echo '</div>';

    $i = 0;
    $monstersWithLoot = 0;
    $monstersWithoutLoot = 0;

    foreach($monsterFilesPage as $monsterFile) {
        $monsterName = basename($monsterFile, '.lua');
        $lootData = parseMonsterLua($monsterFile);

        if($lootData && isset($lootData['name'])) {
            if(isset($lootData['loot']) && !empty($lootData['loot'])) {
                $monstersWithLoot++;
                echo '
                    <a id="' . ++$i . '" style="text-decoration: none; font: bold 14px verdana; color: orange;" href="javascript:void(0);" onclick="toggleVisibility(this)">[+] ' . htmlspecialchars($lootData['name']) . '</a>
                    <br><div style="display: none;" id="d' . $i . '"><br>';
                addLootFromLua($lootData['loot']);
                echo '<br></div>';
            } else {
                $monstersWithoutLoot++;
                echo '<span style="font: bold 14px verdana; color: red;">[x] ' . htmlspecialchars($lootData['name']) . ' (No loot found)</span><br>';
            }
        } else {
            echo '<span style="color: white;">Failed to load monster <b>' . htmlspecialchars($monsterName) . '</b> <i>(' . basename($monsterFile) . ')</i></span><br>';
        }
    }

    echo '<br><b>Summary: ' . $monstersWithLoot . ' monsters with loot, ' . $monstersWithoutLoot . ' monsters without loot</b>';

    if ($totalPages > 1) {
        echo '<div style="margin: 10px 0;">';
        echo '<b>Page: </b>';
        for ($i = 1; $i <= $totalPages; $i++) {
            if ($i == $currentPage) {
                echo '<span style="font-weight: bold; margin: 0 5px;">[' . $i . ']</span> ';
            } else {
                echo '<a href="?category=' . urlencode($category) . '&page=' . $i . '">[' . $i . ']</a> ';
            }
        }
        echo '</div>';
    }

function getMonsterFilesRecursive($dir) {
    $result = [];
    if (!is_dir($dir)) return $result;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() !== 'lua') continue;
        $handle = fopen($file->getPathname(), 'r');
        if (!$handle) continue;
        $header = fread($handle, 2048);
        fclose($handle);

        if (
            strpos($header, 'Game.createMonsterType') !== false ||
            strpos($header, 'monster.name') !== false
        ) {
            $result[] = $file->getPathname();
        }
    }

    sort($result);
    return $result;
}

function parseMonsterLua($file) {
    if(!file_exists($file)) {
        return false;
    }

    $content = file_get_contents($file);
    $data = array();

    if(preg_match('/Game\.createMonsterType\("([^"]+)"\)/', $content, $matches)) {
        $data['name'] = $matches[1];
    }
    elseif(preg_match('/monster\.name\s*=\s*"([^"]+)"/', $content, $matches)) {
        $data['name'] = $matches[1];
    }
    elseif(preg_match('/monster\.description\s*=\s*"([^"]+)"/', $content, $matches)) {
        $description = $matches[1];
        $description = preg_replace('/^(a|an|the)\s+/i', '', $description);
        $data['name'] = ucfirst($description);
    }
    else {
        $data['name'] = basename($file, '.lua');
    }

    $data['loot'] = array();

    if (preg_match('/monster\.loot\s*=\s*\{([\s\S]*?)\n\}/', $content, $lootMatch)) {
        $lootSection = $lootMatch[1];

        preg_match_all('/\{[\s\S]*?\}/', $lootSection, $lootItems);

        foreach($lootItems[0] as $lootItem) {
            $item = parseLootItem($lootItem);
            if ($item) {
                $data['loot'][] = $item;
            }
        }
    }

    return $data;
}

function parseLootItem($lootItem) {
    $item = array();

    $lootItem = trim($lootItem);

    if (preg_match('/id\s*=\s*(\d+)/', $lootItem, $idMatch)) {
        $item['id'] = (int)$idMatch[1];
    }
    elseif (preg_match('/name\s*=\s*"([^"]+)"/', $lootItem, $nameMatch)) {
        $itemName = $nameMatch[1];
        $itemId = findItemIdByName($itemName);
        if ($itemId !== null) {
            $item['id'] = $itemId;
        } else {
            return null;
        }
    } else {
        return null;
    }

    if (preg_match('/chance\s*=\s*(\d+)/', $lootItem, $chanceMatch)) {
        $item['chance'] = (int)$chanceMatch[1];
    } elseif (preg_match('/chance1\s*=\s*(\d+)/', $lootItem, $chanceMatch)) {
        $item['chance'] = (int)$chanceMatch[1];
    } else {
        return null;
    }

    if (preg_match('/maxCount\s*=\s*(\d+)/', $lootItem, $countMatch)) {
        $item['countmax'] = (int)$countMatch[1];
    } else {
        $item['countmax'] = 1;
    }

    if (preg_match('/minCount\s*=\s*(\d+)/', $lootItem, $countMatch)) {
        $item['countmin'] = (int)$countMatch[1];
    } else {
        $item['countmin'] = 1;
    }

    return $item;
}

function findItemIdByName($itemName) {
    global $itemList;

    if (empty($itemList)) {
        return null;
    }

    $itemNameLower = strtolower(trim($itemName));

    foreach($itemList as $id => $name) {
        if (strtolower(trim($name)) === $itemNameLower) {
            return $id;
        }
    }

    foreach($itemList as $id => $name) {
        $cleanName = preg_replace('/\s+/', ' ', strtolower(trim($name)));
        $cleanSearch = preg_replace('/\s+/', ' ', $itemNameLower);
        if (strpos($cleanName, $cleanSearch) !== false) {
            return $id;
        }
    }

    echo "<!-- Item not found: $itemName -->\n";

    return null;
}

function addLootFromLua($loot, $level = 1) {
    foreach($loot as $item) {
        if (isset($item['id']) && isset($item['chance'])) {
            $count = isset($item['countmax']) ? $item['countmax'] : 1;
            printLoot($level, $item['id'], $count, $item['chance']);
        }
    }
}

function printLoot($level, $itemid, $count, $chance) {
    global $itemList, $rarity;

    $chance /= 1000;

    foreach($rarity as $lootRarity => $percent){
        if($chance >= $percent) {
            $itemName = isset($itemList[(int)$itemid]) ? $itemList[(int)$itemid] : 'Unknown Item (ID: ' . $itemid . ')';
            echo str_repeat("... ", $level) . '<u>' . ($count ? $count : 1) . '</u> <span style="color: #7878FF; font-weight: bold;">' . htmlspecialchars($itemName) . '</span> - <span style="color: #C45; font-weight: bold;">' . $lootRarity . '</span> (<span style="color: #FF9A9A;">' . number_format($chance, 2) . '%</span>)<br>';
            break;
        }
    }
}

include 'layout/overall/footer.php';
?>