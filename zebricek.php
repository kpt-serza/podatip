<?php
// zebricek.php

if (!function_exists('spoctiBody')) {
    function spoctiBody($tipA, $tipB, $realA, $realB) {
        if ($realA === null || $realB === null) return 0;
        if ($tipA == $realA && $tipB == $realB) return 5;
        $tipVitez = ($tipA > $tipB) ? 1 : (($tipA < $tipB) ? 2 : 0);
        $realVitez = ($realA > $realB) ? 1 : (($realA < $realB) ? 2 : 0);
        if ($tipVitez == $realVitez) {
            if ($tipVitez == 0) return 2;
            if ($tipA == $realA || $tipB == $realB) return 3;
            return 1;
        }
        return 0;
    }
}

$zebricek = [];

try {
    // 1. KROK: Načteme ID, EMAIL (pro párování) a JMENO (pro zobrazení)
    // Předpokládám, že sloupec se jménem se jmenuje 'jmeno'
    $stmtUsers = $pdo->query("SELECT id, username, jmeno FROM uzivatele");
    while ($u = $stmtUsers->fetch()) {
        // Pokud jméno není vyplněno, použijeme jako nouzovku email (username)
        $zobrazovaneJmeno = !empty($u['jmeno']) ? $u['jmeno'] : $u['username'];
        
        $zebricek[$zobrazovaneJmeno] = [
            'body' => 0,
            'id_uzivatele' => $u['id'],
            'email' => $u['username'] // Uložíme si email pro kontrolu "Moje body"
        ];
    }

    // 2. KROK: Načteme tipy a přičteme body
    $query = "SELECT u.jmeno, u.username, t.tip_a, t.tip_b, z.skore_a, z.skore_b 
              FROM tipy t 
              JOIN zapasy z ON t.id_zapasu = z.id 
              JOIN uzivatele u ON t.id_uzivatele = u.id 
              WHERE z.skore_a IS NOT NULL";

    $stmtTips = $pdo->query($query);

    while ($r = $stmtTips->fetch()) {
        $zobrazovaneJmeno = !empty($r['jmeno']) ? $r['jmeno'] : $r['username'];
        
        if (isset($zebricek[$zobrazovaneJmeno])) {
            $body = spoctiBody($r['tip_a'], $r['tip_b'], $r['skore_a'], $r['skore_b']);
            $zebricek[$zobrazovaneJmeno]['body'] += $body;
        }
    }

    // 3. KROK: Seřazení podle bodů
    uasort($zebricek, function($a, $b) {
        return $b['body'] <=> $a['body'];
    });

} catch (PDOException $e) {
    die("Chyba v zebricek.php: " . $e->getMessage());
}
?>