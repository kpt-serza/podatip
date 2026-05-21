<?php
include 'db.php';

echo "<h2 style='font-family: sans-serif; color: #d97706;'>Simulace administrátora: Zapisování výsledků</h2>";

try {
    // Načteme zápasy - používáme správné názvy sloupců skore_a, skore_b
    $stmt = $pdo->query("SELECT id, tym_a, tym_b FROM zapasy");
    $zapasy = $stmt->fetchAll();

    if (empty($zapasy)) {
        die("Žádné zápasy k aktualizaci nenalezeny.");
    }

    // OPRAVA: Měníme vysledek_a/b na skore_a/b podle struktury DB[cite: 1]
    $sqlResult = "UPDATE zapasy SET skore_a = ?, skore_b = ? WHERE id = ?";
    $stmtUpdate = $pdo->prepare($sqlResult);

    echo "<div id='admin-progress' style='font-family: monospace; background: #fffbeb; padding: 15px; border: 1px solid #f59e0b; border-radius: 8px;'>";
    
    foreach ($zapasy as $zapas) {
        $skoreA = rand(0, 5);
        $skoreB = rand(0, 5);
        
        if ($stmtUpdate->execute([$skoreA, $skoreB, $zapas['id']])) {
            echo "⚽ Zápas ID {$zapas['id']} ({$zapas['tym_a']} vs {$zapas['tym_b']}): Nastaven výsledek <b>$skoreA:$skoreB</b><br>";
            
            // Okamžité vypsání na obrazovku
            if (ob_get_level() > 0) ob_flush();
            flush(); 
            usleep(150000); // Mírné zpřelení pro vizuální efekt
        }
    }

    echo "</div>";
    echo "<p>✅ Všechny výsledky byly zapsány do sloupců skore_a a skore_b.</p>";
    echo "<br><a href='index.php?page=vysledky_hracu' style='background:#d97706; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; font-family:sans-serif;'>Zkontrolovat body v žebříčku</a>";

} catch (PDOException $e) {
    // Detailní výpis chyby, pokud by se něco pokazilo[cite: 1]
    echo "<div style='color:red; font-weight:bold;'>Chyba SQL: " . $e->getMessage() . "</div>";
}
?>