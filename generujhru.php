<?php
include 'db.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2 style='font-family: sans-serif; color: #2d307d;'>Spouštím testovací scénář (10+1 hráčů) s českými jmény...</h2>";

try {
    $password_hashed = password_hash('heslo123', PASSWORD_DEFAULT);
    $all_test_user_ids = [];

    // Pole pro generování náhodných českých jmen
    $krestni = ['Jan', 'Petr', 'Jakub', 'Tomáš', 'Martin', 'Lukáš', 'Filip', 'Ondřej', 'Matěj', 'Václav', 'Michal', 'Jiří', 'Marek', 'David', 'Adam', 'Pavel', 'Vojtěch', 'Jaroslav', 'Dominik', 'Štěpán'];
    $prijmeni = ['Novák', 'Svoboda', 'Novotný', 'Dvořák', 'Černý', 'Procházka', 'Kučera', 'Veselý', 'Horák', 'Němec', 'Marek', 'Pospíšil', 'Pokorný', 'Hájek', 'Král', 'Jelínek', 'Růžička', 'Beneš', 'Fiala', 'Sedláček'];

    // 1. VYTVOŘENÍ 10 BĚŽNÝCH UŽIVATELŮ
    for ($i = 1; $i <= 20; $i++) {
        // Náhodný výběr jména
        $nahodne_jmeno = $krestni[array_rand($krestni)];
        $nahodne_prijmeni = $prijmeni[array_rand($prijmeni)];
        $display_name = $nahodne_jmeno . " " . $nahodne_prijmeni;
        
        // Unikátní username (přezdívka)
        $username = strtolower(str_replace(' ', '', $nahodne_prijmeni)) . $i . "_" . bin2hex(random_bytes(2));
        $email = $username . "@poda.cz";

        $stmt = $pdo->prepare("INSERT INTO uzivatele (prezdivka, jmeno, username, heslo, role) VALUES (?, ?, ?, ?, 'user')");
        if ($stmt->execute([$username, $display_name, $email, $password_hashed])) {
            $all_test_user_ids[] = $pdo->lastInsertId();
            echo "Vytvořen uživatel: <strong>$display_name</strong> ($username)<br>";
        }
    }

    // 2. KONTROLA/PŘÍPRAVA ADMINA (Erik Hanulík)
    $stmtAdmin = $pdo->prepare("SELECT id FROM uzivatele WHERE jmeno = 'Erik Hanulík' OR prezdivka = 'admin' LIMIT 1");
    $stmtAdmin->execute();
    $admin = $stmtAdmin->fetch();

    if ($admin) {
        $adminId = $admin['id'];
        echo "<br>Admin <strong>Erik Hanulík</strong> nalezen (ID: $adminId).<br>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO uzivatele (prezdivka, jmeno, username, heslo, role) VALUES (?, ?, ?, ?, 'admin')");
        $stmt->execute(['admin', 'Erik Hanulík', 'hanulik@poda.cz', $password_hashed]);
        $adminId = $pdo->lastInsertId();
        echo "<br>Admin <strong>Erik Hanulík</strong> vytvořen nově.<br>";
    }
    $all_test_user_ids[] = $adminId;

    // 3. GENEROVÁNÍ RANDOM TIPŮ
    $stmtZapasy = $pdo->query("SELECT id FROM zapasy");
    $zapasy = $stmtZapasy->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($zapasy)) {
        $sqlTip = "INSERT INTO tipy (id_uzivatele, id_zapasu, tip_a, tip_b) 
                   VALUES (?, ?, ?, ?) 
                   ON DUPLICATE KEY UPDATE tip_a = VALUES(tip_a), tip_b = VALUES(tip_b)";
        $stmtInsertTip = $pdo->prepare($sqlTip);
        
        $total_tips = 0;
        foreach ($all_test_user_ids as $uid) {
            foreach ($zapasy as $zapasId) {
                if ($stmtInsertTip->execute([$uid, $zapasId, rand(0, 4), rand(0, 4)])) {
                    $total_tips++;
                }
            }
        }
        echo "<div style='margin-top:20px; padding:15px; background:#eefbf2; border:1px solid #22c55e; border-radius:8px;'>";
        echo "<strong>Úspěch!</strong> Celkem vytvořeno/aktualizováno $total_tips náhodných tipů pro " . count($all_test_user_ids) . " hráčů.";
        echo "</div>";
    }

    echo "<br><a href='index.php?page=vysledky_hracu' style='background:#2d307d; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; font-family:sans-serif;'>Zobrazit žebříček</a>";

} catch (PDOException $e) {
    echo "<div style='color:red; padding:10px; border:1px solid red;'>Chyba: " . $e->getMessage() . "</div>";
}

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