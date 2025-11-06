<link rel="stylesheet" href="style.css">
<?php
$filename = "partite.csv";
$partite = [];
$classifica = [];

// Filtri GET
$selectedDiv = $_GET['div'] ?? '';
$selectedGiornata = $_GET['giornata'] ?? '';
$filter = $_GET['squadra'] ?? '';

// Strutture dati
$campionati = [];
$matchCountPerDiv = [];
$partiteByDivGiornata = [];

// --- Lettura CSV ---
if (($handle = fopen($filename, "r")) !== FALSE) {
    $header = fgetcsv($handle, 0, ","); // intestazione

    while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
        $div = isset($data[0]) ? trim($data[0]) : '';
        $casa = trim($data[3]);
        $ospite = trim($data[4]);
        $golCasa = (int)$data[5];
        $golOspite = (int)$data[6];

        // Salva campionati
        if ($div !== '') $campionati[$div] = true;

        // Calcola giornata a blocchi da 10
        if (!isset($matchCountPerDiv[$div])) $matchCountPerDiv[$div] = 0;
        $matchCountPerDiv[$div]++;
        $giornata = (int)ceil($matchCountPerDiv[$div] / 10);

        // Salva partite raggruppate
        $partiteByDivGiornata[$div][$giornata][] = [
                'casa' => $casa,
                'ospite' => $ospite,
                'golcasa' => $golCasa,
                'golospite' => $golOspite
        ];

        // Costruisci classifica (sempre su TUTTE le partite)
        foreach([$casa, $ospite] as $squadra) {
            if(!isset($classifica[$squadra])) {
                $classifica[$squadra] = [
                        'pg' => 0, 'v' => 0, 'p' => 0, 's' => 0,
                        'gf' => 0, 'gs' => 0, 'dr' => 0, 'pt' => 0
                ];
            }
        }
        $classifica[$casa]['pg']++; $classifica[$ospite]['pg']++;
        $classifica[$casa]['gf'] += $golCasa; $classifica[$casa]['gs'] += $golOspite;
        $classifica[$ospite]['gf'] += $golOspite; $classifica[$ospite]['gs'] += $golCasa;
        $classifica[$casa]['dr'] = $classifica[$casa]['gf'] - $classifica[$casa]['gs'];
        $classifica[$ospite]['dr'] = $classifica[$ospite]['gf'] - $classifica[$ospite]['gs'];

        if($golCasa > $golOspite) {
            $classifica[$casa]['v']++; $classifica[$ospite]['s']++;
            $classifica[$casa]['pt'] += 3;
        } elseif($golCasa < $golOspite) {
            $classifica[$ospite]['v']++; $classifica[$casa]['s']++;
            $classifica[$ospite]['pt'] += 3;
        } else {
            $classifica[$casa]['p']++; $classifica[$ospite]['p']++;
            $classifica[$casa]['pt']++; $classifica[$ospite]['pt']++;
        }

        // Salva tutte le partite (come nel tuo originale)
        $partite[] = [
                'div' => $div, 'giornata' => $giornata,
                'casa' => $casa, 'ospite' => $ospite,
                'golcasa' => $golCasa, 'golospite' => $golOspite
        ];
    }
    fclose($handle);
}

// --- Ordina classifica ---
uasort($classifica, function($a, $b) {
    if($b['pt'] === $a['pt']) {
        if($b['dr'] === $a['dr']) return $b['gf'] - $a['gf'];
        return $b['dr'] - $a['dr'];
    }
    return $b['pt'] - $a['pt'];
});

// --- Liste per i select ---
$campionati = array_keys($campionati);
sort($campionati);
$squadre = array_keys($classifica);
sort($squadre);

// Default: se non selezioni un campionato, usa il primo disponibile
if ($selectedDiv === '' && !empty($campionati)) {
    $selectedDiv = $campionati[0];
}

$giornateDisponibili = [];
if ($selectedDiv && isset($partiteByDivGiornata[$selectedDiv])) {
    $giornateDisponibili = array_keys($partiteByDivGiornata[$selectedDiv]);
    sort($giornateDisponibili, SORT_NUMERIC);
}
?>

<!-- ================== CLASSIFICA (fissa, non filtrata) ================== -->
<div class="container section">
    <h2>Classifica</h2>
    <p><a class="btn ghost" href="previsioni.php">Vai a Previsioni</a></p>
    <div class="table-wrap">
        <table class="table-league" border="1">
            <thead>
            <tr>
                <th>Pos</th><th>Squadra</th><th>PG</th><th>V</th><th>P</th><th>S</th>
                <th>GF</th><th>GS</th><th>DR</th><th>PT</th>
            </tr>
            </thead>
            <tbody>
            <?php $pos = 1; foreach($classifica as $nome => $stat): ?>
                <tr>
                    <td><?= $pos++ ?></td>
                    <td><?= htmlspecialchars($nome) ?></td>
                    <td><?= $stat['pg'] ?></td>
                    <td><?= $stat['v'] ?></td>
                    <td><?= $stat['p'] ?></td>
                    <td><?= $stat['s'] ?></td>
                    <td><?= $stat['gf'] ?></td>
                    <td><?= $stat['gs'] ?></td>
                    <td><?= $stat['dr'] ?></td>
                    <td><?= $stat['pt'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ================== FILTRO (dopo la classifica) ================== -->
<div class="container section" id="filtro">
    <h2>Filtra giornate</h2>
    <!-- 🔸 IMPORTANTE: action con ancora #risultati per non tornare in cima -->
    <form method="get" class="toolbar" action="<?= htmlspecialchars(basename(__FILE__)) ?>#risultati">
        <div class="stack" style="flex:1">
            <label>Campionato</label>
            <select name="div">
                <?php foreach($campionati as $d): ?>
                    <option value="<?= htmlspecialchars($d) ?>" <?= $selectedDiv===$d ? 'selected' : '' ?>><?= htmlspecialchars($d) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="stack" style="flex:1">
            <label>Giornata</label>
            <select name="giornata">
                <option value="">Tutte</option>
                <?php foreach($giornateDisponibili as $g): ?>
                    <option value="<?= $g ?>" <?= $selectedGiornata==$g ? 'selected' : '' ?>><?= $g ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="stack" style="flex:1">
            <label>Squadra</label>
            <select name="squadra">
                <option value="">Tutte</option>
                <?php foreach($squadre as $sq): ?>
                    <option value="<?= htmlspecialchars($sq) ?>" <?= $filter===$sq ? 'selected' : '' ?>><?= htmlspecialchars($sq) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="stack">
            <label>&nbsp;</label>
            <button class="btn" type="submit">Applica</button>
        </div>
        <div class="stack">
            <label>&nbsp;</label>
            <a class="btn ghost" href="<?= htmlspecialchars(basename(__FILE__)) ?>#risultati">Reset</a>
        </div>
    </form>
</div>

<!-- ================== PARTITE PER GIORNATA ================== -->
<!-- 🔸 Anchor di atterraggio post-submit -->
<div id="risultati"></div>

<div class="container section">
    <h2>Partite per Giornate</h2>
    <?php
    if ($selectedDiv === '' || !isset($partiteByDivGiornata[$selectedDiv])) {
        echo '<div class="notice">Seleziona un campionato.</div>';
    } else {
        $giornate = $partiteByDivGiornata[$selectedDiv];

        foreach ($giornate as $num => $lista) {
            if ($selectedGiornata !== '' && (int)$selectedGiornata !== $num) continue;

            // Filtra per squadra se selezionata
            $matches = array_filter($lista, function($m) use ($filter) {
                if ($filter === '') return true;
                return stripos($m['casa'], $filter) !== false || stripos($m['ospite'], $filter) !== false;
            });

            if (empty($matches)) continue;

            echo "<h3>Giornata {$num}</h3>";
            echo "<div class='table-wrap'><table class='table-league' border='1'>";
            echo "<tr><th>Casa</th><th>Gol Casa</th><th>Ospite</th><th>Gol Ospite</th></tr>";
            foreach($matches as $p) {
                echo "<tr>";
                echo "<td>".htmlspecialchars($p['casa'])."</td>";
                echo "<td>{$p['golcasa']}</td>";
                echo "<td>".htmlspecialchars($p['ospite'])."</td>";
                echo "<td>{$p['golospite']}</td>";
                echo "</tr>";
            }
            echo "</table></div>";
        }
    }
    ?>
</div>
