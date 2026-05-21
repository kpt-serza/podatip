<?php
include 'db.php';

try {
    // 1. Smazání všech tipů (musí být první kvůli cizím klíčům)
    $pdo->exec("DELETE FROM tipy");

    // 2. Smazání všech zápasů a resetování ID (AUTO_INCREMENT)
    $pdo->exec("DELETE FROM zapasy");
    $pdo->exec("ALTER TABLE zapasy AUTO_INCREMENT = 1");

    // 3. Smazání všech uživatelů kromě admina
    // Předpokládáme, že admin má roli 'admin' nebo ID 1
    $pdo->exec("DELETE FROM uzivatele WHERE role != 'admin' AND username != 'admin'");
    
    // 4. Smazání všech týmů (pokud existují)
    $pdo->exec("DELETE FROM tymy");
    $pdo->exec("ALTER TABLE tymy AUTO_INCREMENT = 1");

    // 5. Vložení nových zápasů - SKUPINA A (Curych)
    $sqlA = "INSERT INTO zapasy (datum, tym_a, tym_b, misto) VALUES 
    ('2026-05-15 16:15:00', 'Finsko', 'Německo', 'Curych'),
    ('2026-05-15 20:15:00', 'Švýcarsko', 'USA', 'Curych'),
    ('2026-05-16 12:15:00', 'Rakousko', 'Velká Británie', 'Curych'),
    ('2026-05-16 16:15:00', 'Finsko', 'Maďarsko', 'Curych'),
    ('2026-05-16 20:15:00', 'Švýcarsko', 'Lotyšsko', 'Curych'),
    ('2026-05-17 12:15:00', 'USA', 'Velká Británie', 'Curych'),
    ('2026-05-17 16:15:00', 'Rakousko', 'Maďarsko', 'Curych'),
    ('2026-05-17 20:15:00', 'Německo', 'Lotyšsko', 'Curych'),
    ('2026-05-18 16:15:00', 'Finsko', 'USA', 'Curych'),
    ('2026-05-18 20:15:00', 'Švýcarsko', 'Německo', 'Curych'),
    ('2026-05-19 16:15:00', 'Lotyšsko', 'Rakousko', 'Curych'),
    ('2026-05-19 20:15:00', 'Maďarsko', 'Velká Británie', 'Curych'),
    ('2026-05-20 16:15:00', 'Švýcarsko', 'Rakousko', 'Curych'),
    ('2026-05-20 20:15:00', 'USA', 'Německo', 'Curych'),
    ('2026-05-21 16:15:00', 'Finsko', 'Lotyšsko', 'Curych'),
    ('2026-05-21 20:15:00', 'Švýcarsko', 'Velká Británie', 'Curych'),
    ('2026-05-22 16:15:00', 'Maďarsko', 'Německo', 'Curych'),
    ('2026-05-22 20:15:00', 'Finsko', 'Velká Británie', 'Curych'),
    ('2026-05-23 12:15:00', 'USA', 'Lotyšsko', 'Curych'),
    ('2026-05-23 16:15:00', 'Švýcarsko', 'Maďarsko', 'Curych'),
    ('2026-05-23 20:15:00', 'Německo', 'Rakousko', 'Curych'),
    ('2026-05-24 16:15:00', 'Lotyšsko', 'Velká Británie', 'Curych'),
    ('2026-05-24 20:15:00', 'Finsko', 'Rakousko', 'Curych'),
    ('2026-05-25 16:15:00', 'USA', 'Maďarsko', 'Curych'),
    ('2026-05-25 20:15:00', 'Německo', 'Velká Británie', 'Curych'),
    ('2026-05-26 12:15:00', 'Maďarsko', 'Lotyšsko', 'Curych'),
    ('2026-05-26 16:15:00', 'USA', 'Rakousko', 'Curych'),
    ('2026-05-26 20:15:00', 'Švýcarsko', 'Finsko', 'Curych')";
    $pdo->exec($sqlA);

    // 6. Vložení nových zápasů - SKUPINA B (Fribourg)
    $sqlB = "INSERT INTO zapasy (datum, tym_a, tym_b, misto) VALUES 
    ('2026-05-15 16:15:00', 'Švédsko', 'Kanada', 'Fribourg'),
    ('2026-05-15 20:15:00', 'Dánsko', 'ČESKO', 'Fribourg'),
    ('2026-05-16 12:15:00', 'Slovensko', 'Norsko', 'Fribourg'),
    ('2026-05-16 16:15:00', 'Kanada', 'Itálie', 'Fribourg'),
    ('2026-05-16 20:15:00', 'Slovinsko', 'ČESKO', 'Fribourg'),
    ('2026-05-17 12:15:00', 'Itálie', 'Slovensko', 'Fribourg'),
    ('2026-05-17 16:15:00', 'Švédsko', 'Dánsko', 'Fribourg'),
    ('2026-05-17 20:15:00', 'Norsko', 'Slovinsko', 'Fribourg'),
    ('2026-05-18 16:15:00', 'Kanada', 'Dánsko', 'Fribourg'),
    ('2026-05-18 20:15:00', 'ČESKO', 'Švédsko', 'Fribourg'),
    ('2026-05-19 16:15:00', 'Itálie', 'Norsko', 'Fribourg'),
    ('2026-05-19 20:15:00', 'Slovinsko', 'Slovensko', 'Fribourg'),
    ('2026-05-20 16:15:00', 'ČESKO', 'Itálie', 'Fribourg'),
    ('2026-05-20 20:15:00', 'Švédsko', 'Slovinsko', 'Fribourg'),
    ('2026-05-21 16:15:00', 'Norsko', 'Kanada', 'Fribourg'),
    ('2026-05-21 20:15:00', 'Dánsko', 'Slovensko', 'Fribourg'),
    ('2026-05-22 16:15:00', 'Kanada', 'Slovinsko', 'Fribourg'),
    ('2026-05-22 20:15:00', 'Itálie', 'Švédsko', 'Fribourg'),
    ('2026-05-23 12:15:00', 'Dánsko', 'Slovinsko', 'Fribourg'),
    ('2026-05-23 16:15:00', 'Slovensko', 'ČESKO', 'Fribourg'),
    ('2026-05-23 20:15:00', 'Švédsko', 'Norsko', 'Fribourg'),
    ('2026-05-24 16:15:00', 'Dánsko', 'Itálie', 'Fribourg'),
    ('2026-05-24 20:15:00', 'Kanada', 'Slovensko', 'Fribourg'),
    ('2026-05-25 16:15:00', 'ČESKO', 'Norsko', 'Fribourg'),
    ('2026-05-25 20:15:00', 'Slovinsko', 'Itálie', 'Fribourg'),
    ('2026-05-26 12:15:00', 'Norsko', 'Dánsko', 'Fribourg'),
    ('2026-05-26 16:15:00', 'Slovensko', 'Švédsko', 'Fribourg'),
    ('2026-05-26 20:15:00', 'ČESKO', 'Kanada', 'Fribourg')";
    $pdo->exec($sqlB);

    echo "<h1>Systém vyčištěn!</h1>";
    echo "<ul>
            <li>Všechny tipy byly smazány.</li>
            <li>Staré zápasy byly odstraněny a nahráno 56 nových zápasů pro MS 2026.</li>
            <li>Všichni testovací uživatelé (kromě admina) byli smazáni.</li>
            <li>Týmy byly rozpuštěny.</li>
          </ul>";
    echo "<a href='index.php'>Přejít na hlavní stránku</a>";

} catch (PDOException $e) {
    die("Chyba při čištění databáze: " . $e->getMessage());
}
?>