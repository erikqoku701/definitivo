<link rel="stylesheet" href="style.css">

<?php
$filename = "partite.csv";
$partite = [];
$classifica = [];

// Leggi CSV
if (($handle = fopen($filename, "r")) !== FALSE) {
    $header = fgetcsv($handle, 0, ","); // Salta intestazione
    while (($data = fgetcsv($handle, 0, ",")) !== FALSE) {
        $casa = trim($data[3]);
        $ospite = trim($data[4]);
        $golCasa = (int)$data[5];
        $golOspite = (int)$data[6];

        $partite[] = [
                'casa' => $casa,
                'ospite' => $ospite,
                'golcasa' => $golCasa,
                'golospite' => $golOspite
        ];

        // Inizializza squadra
        foreach([$casa, $ospite] as $squadra) {
            if(!isset($classifica[$squadra])) {
                $classifica[$squadra] = [
                        'nome' => $squadra,
                        'pg' => 0,
                        'v' => 0,
                        'p' => 0,
                        's' => 0,
                        'gf' => 0,
                        'gs' => 0,
                        'dr' => 0,
                        'pt' => 0,
                        'clean_sheet' => 0
                ];
            }
        }

        // Aggiorna statistiche
        $classifica[$casa]['pg']++;
        $classifica[$casa]['gf'] += $golCasa;
        $classifica[$casa]['gs'] += $golOspite;

        $classifica[$ospite]['pg']++;
        $classifica[$ospite]['gf'] += $golOspite;
        $classifica[$ospite]['gs'] += $golCasa;

        $classifica[$casa]['dr'] = $classifica[$casa]['gf'] - $classifica[$casa]['gs'];
        $classifica[$ospite]['dr'] = $classifica[$ospite]['gf'] - $classifica[$ospite]['gs'];

        if($golCasa > $golOspite){
            $classifica[$casa]['v']++; $classifica[$casa]['pt'] +=3;
            $classifica[$ospite]['s']++;
        } elseif($golCasa < $golOspite){
            $classifica[$ospite]['v']++; $classifica[$ospite]['pt'] +=3;
            $classifica[$casa]['s']++;
        } else {
            $classifica[$casa]['p']++; $classifica[$ospite]['p']++;
            $classifica[$casa]['pt']++; $classifica[$ospite]['pt']++;
        }

        if($golOspite == 0) $classifica[$casa]['clean_sheet']++;
        if($golCasa == 0) $classifica[$ospite]['clean_sheet']++;
    }
    fclose($handle);
}

// Statistiche generali
$totPartite = count($partite);
$totGol = 0; $vittorieCasa = 0; $vittorieTrasferta = 0; $pareggi = 0;
$over25=0;$under25=0;$gg=0;$zeroZero=0; $risultati=[];

foreach($partite as $p){
    $totGol += $p['golcasa'] + $p['golospite'];
    if($p['golcasa']>$p['golospite']) $vittorieCasa++;
    elseif($p['golcasa']<$p['golospite']) $vittorieTrasferta++;
    else $pareggi++;

    if($p['golcasa']+$p['golospite']>2.5) $over25++; else $under25++;
    if($p['golcasa']>0 && $p['golospite']>0) $gg++;
    if($p['golcasa']==0 && $p['golospite']==0) $zeroZero++;

    $score = $p['golcasa'].'-'.$p['golospite'];
    if(!isset($risultati[$score])) $risultati[$score]=0;
    $risultati[$score]++;
}

$percCasa = round($vittorieCasa/$totPartite*100,1);
$percTrasf = round($vittorieTrasferta/$totPartite*100,1);
$percPareggi = round($pareggi/$totPartite*100,1);
$percOver = round($over25/$totPartite*100,1);
$percUnder = round($under25/$totPartite*100,1);
$percGG = round($gg/$totPartite*100,1);

arsort($risultati);
usort($classifica,function($a,$b){ return $b['pt']-$a['pt']; });
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Previsioni Campionato</title>
</head>
<body>

<div class="container section">
    <h1>Previsioni e Statistiche Campionato</h1>
    <p><a class="btn ghost" href="classifica.php">← Torna alla Classifica</a></p>
</div>

<div class="container section">
    <h2>Statistiche generali</h2>
    <ul class="stats-list">
        <li>Partite giocate: <?= $totPartite ?></li>
        <li>Totale gol: <?= $totGol ?></li>
        <li>Media gol per partita: <?= round($totGol/$totPartite,2) ?></li>
        <li>Vittorie casa: <?= $percCasa ?>% | Pareggi: <?= $percPareggi ?>% | Vittorie trasferta: <?= $percTrasf ?>%</li>
        <li>Over 2.5: <?= $percOver ?>% | Under 2.5: <?= $percUnder ?>%</li>
        <li>GG (entrambe segnano): <?= $percGG ?>%</li>
        <li>Partite finite 0-0: <?= $zeroZero ?></li>
    </ul>
</div>

<div class="container section">
    <h2>Risultati più frequenti</h2>
    <div class="table-wrap">
        <table class="table-league">
            <thead>
            <tr><th>Risultato</th><th>Occorrenze</th><th>Percentuale</th></tr>
            </thead>
            <tbody>
            <?php foreach($risultati as $res=>$cnt): ?>
                <tr>
                    <td><?= $res ?></td>
                    <td><?= $cnt ?></td>
                    <td><?= round($cnt/$totPartite*100,1) ?>%</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="container section">
    <h2>Squadre con più Clean Sheet</h2>
    <div class="table-wrap">
        <table class="table-league">
            <thead><tr><th>Squadra</th><th>Clean Sheet</th></tr></thead>
            <tbody>
            <?php foreach($classifica as $stat): ?>
                <tr>
                    <td><?= $stat['nome'] ?></td>
                    <td><?= $stat['clean_sheet'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="container section">
    <h2>Classifica Punti</h2>
    <div class="table-wrap">
        <table class="table-league">
            <thead>
            <tr>
                <th>Pos</th><th>Squadra</th><th>PG</th><th>V</th><th>P</th><th>S</th>
                <th>GF</th><th>GS</th><th>DR</th><th>PT</th>
            </tr>
            </thead>
            <tbody>
            <?php $pos=1; foreach($classifica as $stat): ?>
                <tr>
                    <td><?= $pos++ ?></td>
                    <td><?= $stat['nome'] ?></td>
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

<div class="container section">
    <h2>Classifica Attacco (più gol segnati)</h2>
    <?php $attacco = $classifica; usort($attacco,function($a,$b){ return $b['gf']-$a['gf']; }); ?>
    <div class="table-wrap">
        <table class="table-league">
            <thead><tr><th>Squadra</th><th>GF</th></tr></thead>
            <tbody>
            <?php foreach($attacco as $stat): ?>
                <tr><td><?= $stat['nome'] ?></td><td><?= $stat['gf'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="container section">
    <h2>Classifica Difesa (meno gol subiti)</h2>
    <?php $difesa = $classifica; usort($difesa,function($a,$b){ return $a['gs']-$b['gs']; }); ?>
    <div class="table-wrap">
        <table class="table-league">
            <thead><tr><th>Squadra</th><th>GS</th></tr></thead>
            <tbody>
            <?php foreach($difesa as $stat): ?>
                <tr><td><?= $stat['nome'] ?></td><td><?= $stat['gs'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
