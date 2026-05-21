<?php
// --- 1. SESSION MANAGEMENT ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- 2. PŘIPOJENÍ K DATABÁZI ---
$host = 'localhost';
$dbname = 'phpmyadmin';
$user = 'admin'; 
$pass = 'mansa';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Chyba připojení: " . $e->getMessage());
}

// --- 3. AUTOMATICKÉ PŘIHLÁŠENÍ (COOKIE) ---
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    $stmt = $pdo->prepare("SELECT * FROM uzivatele WHERE remember_token = ? AND remember_token IS NOT NULL");
    $stmt->execute([$_COOKIE['remember_me']]);
    $u = $stmt->fetch();
    
    if ($u) {
        // Přihlášení uživatele ze session
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['role'] = $u['role'];
        $_SESSION['email'] = $u['username'];
        $_SESSION['prezdivka'] = $u['prezdivka'];

        // BEZPEČNOSTNÍ BONUS: Rotace tokenu (vytvoří se nový pro příště)
        $newToken = bin2hex(random_bytes(32));
        $pdo->prepare("UPDATE uzivatele SET remember_token = ? WHERE id = ?")->execute([$newToken, $u['id']]);
        setcookie('remember_me', $newToken, time() + (86400 * 30), "/", "", false, true); // httponly = true
    } else {
        // Pokud je cookie neplatná (např. ručně smazaný token v DB), smažeme ji z prohlížeče
        setcookie('remember_me', '', time() - 3600, "/");
    }
}

// --- 4. ZPRACOVÁNÍ FORMULÁŘŮ (POST METODA) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // --- PŘIHLÁŠENÍ ---
    if ($action == 'login') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT * FROM uzivatele WHERE prezdivka = ? OR username = ?");
        $stmt->execute([$username, $username]);
        $u = $stmt->fetch();

        if ($u && password_verify($password, $u['heslo'])) {
            $_SESSION['user_id'] = $u['id'];
            $_SESSION['role'] = $u['role'];
            $_SESSION['email'] = $u['username'];
            $_SESSION['prezdivka'] = $u['prezdivka'];

            if (isset($_POST['remember'])) {
                $token = bin2hex(random_bytes(32));
                $pdo->prepare("UPDATE uzivatele SET remember_token = ? WHERE id = ?")->execute([$token, $u['id']]);
                setcookie('remember_me', $token, time() + (86400 * 30), "/");
            }
            
            $pdo->prepare("UPDATE uzivatele SET posledni_aktivita = NOW() WHERE id = ?")->execute([$u['id']]);
            header("Location: index.php");
            exit;
        } else {
            header("Location: index.php?error=login_failed");
            exit;
        }
    }

    // --- ZMĚNA HESLA ---
    if ($action === 'change_password' && isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        $currentPass = $_POST['current_password'];
        $newPass = $_POST['new_password'];
        $confirmPass = $_POST['confirm_password'] ?? ''; // Přidáno pro jistotu

        if ($newPass !== $confirmPass) {
            header("Location: index.php?error=hesla_se_neshoduji");
            exit;
        }

        $stmt = $pdo->prepare("SELECT heslo FROM uzivatele WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($user && password_verify($currentPass, $user['heslo'])) {
            $newHash = password_hash($newPass, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE uzivatele SET heslo = ? WHERE id = ?");
            $update->execute([$newHash, $userId]);
            header("Location: index.php?success=heslo_zmeneno");
        } else {
            header("Location: index.php?error=spatne_stare_heslo");
        }
        exit;
    }

    // --- VYTVOŘENÍ TÝMU (Admin) ---
    if ($action == 'create_team' && isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
        $nazev = $_POST['nazev'];
        $hraci = $_POST['hraci'] ?? [];

        if (!empty($nazev) && count($hraci) > 0 && count($hraci) <= 3) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("INSERT INTO tymy (nazev) VALUES (?)");
                $stmt->execute([$nazev]);
                $team_id = $pdo->lastInsertId();

                $updateStmt = $pdo->prepare("UPDATE uzivatele SET id_tymu = ? WHERE id = ?");
                foreach ($hraci as $user_id) {
                    $updateStmt->execute([$team_id, $user_id]);
                }
                $pdo->commit();
                header("Location: index.php?page=vysledky_tymu&success=tym_vytvoren");
            } catch (Exception $e) { 
                $pdo->rollBack(); 
                header("Location: index.php?error=chyba_tvorby_tymu");
            }
        } else {
            header("Location: index.php?error=neplatne_udaje_tymu");
        }
        exit;
    }

    // --- ULOŽENÍ TIPU ---
    if ($action == 'tip' && isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("INSERT INTO tipy (id_uzivatele, id_zapasu, tip_a, tip_b) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE tip_a = VALUES(tip_a), tip_b = VALUES(tip_b)");
            $stmt->execute([$_SESSION['user_id'], $_POST['match_id'], $_POST['tip_a'], $_POST['tip_b']]);
            header("Location: index.php?page=prehled_zapasu&success=tip_ulozen");
        } catch (Exception $e) {
            header("Location: index.php?page=prehled_zapasu&error=chyba_tipu");
        }
        exit;
    }

    // --- UPDATE SKÓRE (Admin) ---
    if ($action == 'update_score' && isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
        $stmt = $pdo->prepare("UPDATE zapasy SET skore_a = ?, skore_b = ? WHERE id = ?");
        $stmt->execute([$_POST['s_a'], $_POST['s_b'], $_POST['m_id']]);
        header("Location: index.php?page=prehled_zapasu&success=skore_aktualizovano");
        exit;
    }

    // --- PŘIDÁNÍ ZÁPASU (Admin) - TEĎ JE TO SPRÁVNĚ UVNITŘ IF POST ---
    if ($action == 'add_match' && isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
        $tym_a = trim($_POST['tym_a']);
        $tym_b = trim($_POST['tym_b']);
        $datum = $_POST['datum'];
        $misto = trim($_POST['misto']);

        if (!empty($tym_a) && !empty($tym_b) && !empty($datum)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO zapasy (tym_a, tym_b, datum, misto) VALUES (?, ?, ?, ?)");
                $stmt->execute([$tym_a, $tym_b, $datum, $misto]);
                header("Location: index.php?page=prehled_zapasu&success=zapas_pridan");
            } catch (Exception $e) {
                header("Location: index.php?page=prehled_zapasu&error=chyba_pridani_zapasu");
            }
        } else {
            header("Location: index.php?page=prehled_zapasu&error=nevyplnene_udaje");
        }
        exit;
    }
}

// AKTUALIZACE AKTIVITY PRO PŘIHLÁŠENÉ (při každém načtení stránky přes GET i POST)
if (isset($_SESSION['user_id'])) {
    $pdo->prepare("UPDATE uzivatele SET posledni_aktivita = NOW() WHERE id = ?")->execute([$_SESSION['user_id']]);
}