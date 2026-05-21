<?php 
// 1. Musí být spuštěna session pro přístup k $_SESSION
session_start(); 

include 'db.php'; 
include 'zebricek.php'; 

// --- LOGIKA PRO STATISTIKY ---
$pocetUzivatelu = count($zebricek);
$bank = $pocetUzivatelu * 50; 
$stmtOnline = $pdo->query("SELECT COUNT(*) FROM uzivatele WHERE posledni_aktivita > NOW() - INTERVAL 5 MINUTE");
$online = $stmtOnline->fetchColumn();

// Získání jména a bodů přihlášeného uživatele
$aktualniJmeno = "Uživatel";
$mojeBody = 0;
if (isset($_SESSION['user_id'])) {
    // Prioritně hledáme v žebříčku, abychom měli aktuální body i jméno
    foreach ($zebricek as $jmenoKlic => $data) {
        if (isset($data['id_uzivatele']) && $data['id_uzivatele'] == $_SESSION['user_id']) {
            $mojeBody = $data['body'];
            $aktualniJmeno = $jmenoKlic;
            break;
        }
    }
    // Pokud není v žebříčku (např. nový hráč), vytáhneme jméno z DB
    if ($aktualniJmeno == "Uživatel") {
        $stMe = $pdo->prepare("SELECT COALESCE(jmeno, username) FROM uzivatele WHERE id = ?");
        $stMe->execute([$_SESSION['user_id']]);
        $resMe = $stMe->fetchColumn();
        if ($resMe) $aktualniJmeno = $resMe;
    }
}

$page = $_GET['page'] ?? 'prehled_zapasu';

if (!function_exists('spoctiBody')) {
    function spoctiBody($tipA, $tipB, $skoreA, $skoreB) {
        if ($skoreA === null || $skoreB === null) return 0;
        if ($tipA == $skoreA && $tipB == $skoreB) return 3;
        if (($tipA - $tipB) == ($skoreA - $skoreB)) return 2;
        if (($tipA > $tipB && $skoreA > $skoreB) || ($tipA < $tipB && $skoreA < $skoreB)) return 1;
        return 0;
    }
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>PODA TIP 2026</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; font-family: 'Open Sans', sans-serif; -webkit-tap-highlight-color: transparent; overflow-x: hidden; }
        .bg-poda-blue { background-color: #2d307d; }
        .text-poda-blue { color: #2d307d; }
        .btn-poda { background: linear-gradient(90deg, #2d307d 0%, #4a00e0 100%); transition: all 0.3s cubic-bezier(.25,.8,.25,1); background-size: 200% auto;box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .btn-poda2 { background: linear-gradient(90deg, #dc2626 0%, #ef4444 100%); transition: all 0.3s cubic-bezier(.25,.8,.25,1); background-size: 200% auto;box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .btn-poda:hover { background-position: right center; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.2), 0 0 15px rgba(74, 0, 224, 0.4); filter: brightness(1.1); }
        .btn-poda:active { transform: translateY(1px); box-shadow: 0 2px 5px rgba(0,0,0,0.2); filter: brightness(0.9); }
        .btn-poda2:hover { background-position: right center; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.2), 0 0 15px rgba(74, 0, 224, 0.4); filter: brightness(1.1); }
        .btn-poda2:active { transform: translateY(1px); box-shadow: 0 2px 5px rgba(0,0,0,0.2); filter: brightness(0.9); }
        .poda-card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .table-container { overflow-x: auto; -webkit-overflow-scrolling: touch; width: 100%; }
        @keyframes pulse {0% { transform: scale(0.95); opacity: 1; } 50% { transform: scale(1.2); opacity: 0.7; } 100% { transform: scale(0.95); opacity: 1; }}
        .dot-pulse {width: 10px; height: 10px; background-color: #22c55e; border-radius: 50%; display: inline-block; animation: pulse 2s infinite ease-in-out;}
        dialog::backdrop { background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(4px); }
        input[type="number"]::-webkit-inner-spin-button, input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    </style>
</head>
<body class="pb-24 md:pb-0">

<?php if(!isset($_SESSION['user_id'])): ?>
    <!-- LOGIN SCREEN -->
    <div class="min-h-screen flex items-center justify-center p-4 bg-gray-100">
        <div class="max-w-md w-full poda-card p-8 text-center">
            <img src="logo.svg" alt="PODA Logo" class="h-12 mx-auto mb-6">
            <h1 class="text-2xl font-extrabold text-poda-blue uppercase mb-8 tracking-tight">PODA TIP 2026</h1>
            
            <?php if (isset($_GET['error']) && $_GET['error'] === 'login_failed'): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4 text-sm font-bold">⚠️ Neplatné jméno nebo heslo.</div>
            <?php endif; ?>

            <form action="db.php" method="POST" class="space-y-4 text-left">
                <input type="hidden" name="action" value="login">
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase ml-1 mb-1">Přezdívka</label>
                    <input name="username" type="text" class="w-full border p-3 rounded-lg outline-none focus:ring-2 focus:ring-poda-blue" required>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase ml-1 mb-1">Heslo</label>
                    <input name="password" type="password" class="w-full border p-3 rounded-lg outline-none focus:ring-2 focus:ring-poda-blue" required>
                </div>
                <button type="submit" class="w-full btn-poda text-white font-bold py-4 rounded-lg shadow-lg uppercase mt-4">Vstoupit</button>
                <label class="flex items-center gap-2">
    <input type="checkbox" name="remember"> Pamatovat si mě
</label>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- DESKTOP NAVBAR -->
    <header class="hidden md:flex bg-white border-b sticky top-0 z-50 h-20 shadow-sm">
        <div class="max-w-7xl mx-auto px-6 w-full flex justify-between items-center">
            <div class="flex items-center gap-8">
                <a href="index.php"><img src="logo.svg" alt="PODA" class="h-10"></a>
                <div class="flex gap-6 border-l pl-8 border-gray-100 text-18px] font-bold text-gray-400 uppercase italic">
                    <span class="text-gray-600">Bank: <span class="text-poda-blue"><?php echo number_format($bank, 0, ',', ' '); ?> Kč</span></span>
                    <span class="flex items-center gap-2"><span class="dot-pulse"></span> Online: <span class="text-green-600"><?php echo $online; ?></span></span>
                </div>
            </div>
            <nav class="flex items-center space-x-6 text-18px] font-bold text-gray-500">
                <a href="index.php?page=moje_vysledky" class="hover:text-poda-blue transition <?php echo $page=='moje_vysledky' ? 'text-poda-blue border-poda-blue' : ''; ?>">Moje Výsledky</a>
                <a href="index.php?page=prehled_zapasu" class="hover:text-poda-blue transition <?php echo $page=='prehled_zapasu' ? 'text-poda-blue border-poda-blue' : ''; ?>">Zápasy</a>
                <a href="index.php?page=vysledky_hracu" class="hover:text-poda-blue transition <?php echo ($page=='vysledky_hracu'||$page=='detail_hrace') ? 'text-poda-blue border-poda-blue' : ''; ?>">Žebříček</a>
                <a href="index.php?page=vysledky_tymu" class="hover:text-poda-blue transition <?php echo $page == 'vysledky_tymu' ? 'text-poda-blue border-poda-blue' : ''; ?>">Týmy</a>
                <a href="/test/pravidla26.pdf" target="_blank" class="hover:text-poda-blue">Pravidla</a>
                <div class="h-6 w-[1px] bg-gray-200 mx-2"></div>
                <div class="relative group cursor-pointer">
                    <span class="text-poda-blue font-black"><?php echo htmlspecialchars($aktualniJmeno); ?> ▼</span>
                    <div class="absolute right-0 top-full pt-4 hidden group-hover:block w-48">
                        <div class="bg-white border rounded-lg shadow-xl py-2">
                            <button onclick="document.getElementById('changePasswordModal').showModal()" class="w-full text-left px-4 py-2 hover:bg-gray-50">🔒Změnit heslo</button>
                            <a href="logout.php" class="block px-4 py-2 text-red-500 hover:bg-red-50">🚪Odhlásit se</a>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <!-- MOBILE NAVBAR (BOTTOM) -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-white border-t flex justify-around items-center py-3 z-50 shadow-[0_-4px_10px_rgba(0,0,0,0.05)]">
        <a href="index.php?page=moje_vysledky" class="flex flex-col items-center <?php echo $page=='moje_vysledky' ? 'text-poda-blue' : 'text-gray-400'; ?>">
            <span class="text-[18px]">👤</span><span class="text-[9px] font-bold uppercase">Přehled</span>
        </a>
        <a href="index.php?page=prehled_zapasu" class="flex flex-col items-center <?php echo $page=='prehled_zapasu' ? 'text-poda-blue' : 'text-gray-400'; ?>">
            <span class="text-[18px]">🏒</span><span class="text-[9px] font-bold uppercase">Zápasy</span>
        </a>
        <a href="index.php?page=vysledky_hracu" class="flex flex-col items-center <?php echo ($page=='vysledky_hracu'||$page=='detail_hrace') ? 'text-poda-blue' : 'text-gray-400'; ?>">
            <span class="text-[18px]">🏆</span><span class="text-[9px] font-bold uppercase">Pořadí</span>
        </a>
        <a href="index.php?page=vysledky_tymu" class="flex flex-col items-center <?php echo $page=='vysledky_tymu' ? 'text-poda-blue' : 'text-gray-400'; ?>">
            <span class="text-[18px]">👥</span><span class="text-[9px] font-bold uppercase">Týmy</span>
        </a>
        <button onclick="document.getElementById('mobileMenu').showModal()" class="flex flex-col items-center text-gray-400">
            <span class="text-[18px]">⚙️</span><span class="text-[9px] font-bold uppercase">Více</span>
        </button>
    </nav>

    <!-- NOTIFIKACE -->
    <div class="max-w-7xl mx-auto px-4 mt-4">
        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm mb-4 flex justify-between items-center">
                <span class="text-sm font-bold">✅ Operace byla úspěšná.</span>
                <button onclick="this.parentElement.remove()" class="font-black">&times;</button>
            </div>
        <?php endif; ?>
        <!-- Chybové zprávy -->
        <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm mb-4 flex justify-between items-center">
            <span class="text-sm font-bold">
                ⚠️ <?php 
                    // Převod kódů chyb na lidsky čitelný text
                    echo match($_GET['error']) {
                        'neplatne_udaje_tymu' => 'Neplatné údaje! Tým musí mít název a 1 až 3 hráče.',
                        'chyba_tvorby_tymu' => 'Došlo k technické chybě při zápisu do databáze.',
                        'hesla_se_neshoduji' => 'Nové heslo a potvrzení se neshodují.',
                        'spatne_stare_heslo' => 'Stávající heslo není správné.',
                        default => 'Něco se nepovedlo. Zkuste to prosím znovu.'
                    };
                ?>
            </span>
            <button onclick="this.parentElement.remove()" class="font-black">&times;</button>
        </div>
        <?php endif; ?>
    </div>

    <!-- MOBILE MORE MENU MODAL -->
    <dialog id="mobileMenu" class="rounded-t-2xl mt-auto mb-0 w-full p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-black text-poda-blue uppercase">Nastavení a Info</h3>
            <form method="dialog"><button class="text-2xl">&times;</button></form>
        </div>
        <div class="space-y-4">
            <div class="bg-gray-50 p-4 rounded-xl">
                <p class="text-xs text-gray-400 uppercase font-bold">Přihlášen jako</p>
                <p class="font-black text-lg"><?php echo htmlspecialchars($aktualniJmeno); ?></p>
            </div>
            <a href="https://podatip.cz/pravidla25.pdf" target="_blank" class="block w-full text-center py-4 bg-gray-100 rounded-xl font-bold">Pravidla (PDF)</a>
            <button onclick="document.getElementById('changePasswordModal').showModal()" class="w-full py-4 bg-gray-100 rounded-xl font-bold">Změnit heslo</button>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                 <button onclick="document.getElementById('adminMatchModal').showModal()" class="w-full py-4 bg-red-100 text-red-800 rounded-xl font-bold">Nový zápas (Admin)</button>
            <?php endif; ?>
            <a href="logout.php" class="block w-full text-center py-4 bg-red-50 text-red-600 rounded-xl font-bold">Odhlásit se</a>
        </div>
    </dialog>

    <main class="max-w-7xl mx-auto p-4 md:p-8">
        
        <?php if ($page == 'prehled_zapasu'): ?>
            <div class="flex justify-between items-center mb-6 px-1">
                <h2 class="text-xl md:text-2xl font-black text-poda-blue uppercase">Zápasy k tipování</h2>
            </div>
            
            <div class="space-y-3">
                <?php
                $st = $pdo->prepare("SELECT z.*, t.id as tip_id, t.tip_a, t.tip_b FROM zapasy z LEFT JOIN tipy t ON z.id = t.id_zapasu AND t.id_uzivatele = ? WHERE z.skore_a IS NULL ORDER BY z.datum ASC");
                $st->execute([$_SESSION['user_id']]);
                while ($m = $st->fetch()):
                ?>
                <div class="poda-card overflow-hidden shadow-sm border border-gray-100">
                    <div class="flex flex-col md:flex-row md:items-center p-3 md:p-4 gap-3 md:gap-6">
                        
                        <!-- Čas a Datum -->
                        <div class="flex flex-row md:flex-col items-center md:items-start gap-2 md:gap-0 min-w-[70px] border-b md:border-b-0 md:border-r border-gray-100 pb-2 md:pb-0 md:pr-4">
                            <span class="text-[14px] font-bold text-gray-400 uppercase"><?php echo date('d.m.', strtotime($m['datum'])); ?></span>
                            <span class="text-base font-black text-poda-blue leading-none"><?php echo date('H:i', strtotime($m['datum'])); ?></span>
                        </div>

                        <!-- Týmy a Místo -->
                        <div class="flex-1">
                            <h4 class="text-[14px] md:text-base font-black text-gray-800 uppercase leading-tight">
                                <?php echo htmlspecialchars($m['tym_a']); ?> - <?php echo htmlspecialchars($m['tym_b']); ?>
                            </h4>
                            <span class="text-[11px] text-gray-400 font-bold uppercase italic tracking-wider"><?php echo htmlspecialchars($m['misto']); ?></span>
                        </div>

                        <!-- zarovnání -->
                        <div class="flex flex-col items-end md:flex-row md:items-center gap-3 bg-gray-50 md:bg-transparent p-3 md:p-0 rounded-xl md:rounded-none">
    
    <!-- ADMIN VKLÁDÁNÍ VÝSLEDKŮ -->
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <form action="db.php" method="POST" class="flex items-center gap-2">
            <input type="hidden" name="action" value="update_score">
            <input type="hidden" name="m_id" value="<?php echo $m['id']; ?>">
            <div class="flex gap-1">
                <input name="s_a" type="number" class="w-10 h-10 border rounded-lg text-center text-sm font-bold bg-white" required>
                <input name="s_b" type="number" class="w-10 h-10 border rounded-lg text-center text-sm font-bold bg-white" required>
            </div>
            <button class="btn-poda2 text-white w-28 h-10 rounded-lg text-[10px] font-black uppercase active:scale-95 transition shadow-md">Skore</button>
        </form>
    <?php endif; ?>

    <!-- TIPOVÁNÍ -->
    <div class="flex justify-end w-full md:w-auto">
        <?php if (!$m['tip_id']): ?>
            <form action="db.php" method="POST" class="flex items-center gap-2">
                <input type="hidden" name="action" value="tip">
                <input type="hidden" name="match_id" value="<?php echo $m['id']; ?>">
                <div class="flex gap-1">
                    <input name="tip_a" type="number" class="w-10 h-10 border-2 border-gray-200 rounded-lg text-center font-black text-poda-blue focus:border-poda-blue outline-none bg-white" required>
                    <input name="tip_b" type="number" class="w-10 h-10 border-2 border-gray-200 rounded-lg text-center font-black text-poda-blue focus:border-poda-blue outline-none bg-white" required>
                </div>
                <button class="btn-poda text-white w-28 h-10 rounded-lg font-black text-[10px] uppercase shadow-md active:scale-95 transition ">Tipovat</button>
            </form>
        <?php else: ?>
            <div class="flex items-center justify-center gap-2 bg-white md:bg-transparent px-3 h-10 rounded-lg border border-green-100 md:border-transparent min-w-[160px]">
                <span class="text-green-500 font-black">✓ Máš natipováno</span>
                <span class="text-base font-black text-poda-blue"><?php echo $m['tip_a']; ?> : <?php echo $m['tip_b']; ?></span>
            </div>
        <?php endif; ?>
    </div>
</div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

        <?php elseif ($page == 'moje_vysledky'): ?>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 mb-6 px-1">
                <div class="pb-1">
                    <h2 class="text-4xl font-black text-poda-blue uppercase leading-none">Výsledky</h2>
                    <p class="text-[13px] font-bold text-gray-400 uppercase tracking-wide mt-1">Aktuální stav tvého tipování</p>
                </div>
                <div class="flex justify-start md:justify-end w-full md:w-auto">
                    <div class="bg-white shadow-sm rounded-lg px-5 py-3 flex items-center justify-between md:justify-end border-r-4 border-poda-blue min-w-[140px] w-full md:w-auto">
                        <div class="pr-4 border-r border-gray-100">
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider md:hidden">Bank</p>
                            <p class="text-lg font-black text-poda-blue leading-tight md:hidden"><?php echo number_format($bank, 0, ',', ' '); ?> Kč</p>
                        </div>
                        <div class="flex flex-col items-end ml-4">
                            <p class="text-[14px] font-bold text-gray-400 uppercase tracking-wider">Moje Body</p>
                            <p class="text-3xl font-black text-poda-blue leading-tight"><?php echo $mojeBody; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="poda-card overflow-hidden">
                <div class="table-container">
                    <table class="w-full text-left">
                        <thead class="bg-gray-800 text-white text-[18px] uppercase tracking-widest">
                            <tr>
                                <th class="p-3">Zápas</th>
                                <th class="p-3 text-center">Tip</th>
                                <th class="p-3 text-center">Skore</th>
                                <th class="p-3 text-center">Zisk</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->prepare("SELECT z.*, t.tip_a, t.tip_b FROM tipy t JOIN zapasy z ON t.id_zapasu = z.id WHERE t.id_uzivatele = ? ORDER BY z.datum ASC");
                            $stmt->execute([$_SESSION['user_id']]);
                            while ($v = $stmt->fetch()):
                                $b = spoctiBody($v['tip_a'], $v['tip_b'], $v['skore_a'], $v['skore_b']);
                                $bodyColor = ($b == 3) ? 'text-green-500' : (($b > 0) ? 'text-blue-500' : 'text-gray-300');
                            ?>
                            <tr class="border-b">
                                <td class="p-3">
                                    <div class="font-bold text-gray-700 uppercase text-[16px]"><?php echo htmlspecialchars($v['tym_a']); ?> - <?php echo htmlspecialchars($v['tym_b']); ?></div>
                                    <div class="text-[13px] text-gray-400 font-bold"><?php echo date('d.m. H:i', strtotime($v['datum'])); ?></div>
                                </td>
                                <td class="p-3 text-center font-bold text-gray-400 text-2xl"><?php echo "{$v['tip_a']}:{$v['tip_b']}"; ?></span></td>
                                <td class="p-3 text-center"><span class="bg-green-50 text-poda-black px-2 py-1 rounded font-black text-2xl"><?php echo ($v['skore_a'] !== null) ? "{$v['skore_a']}:{$v['skore_b']}" : "-"; ?></td>
                                <td class="p-3 text-center font-black text-2xl <?php echo $bodyColor; ?>"><?php echo ($v['skore_a'] !== null) ? $b : ''; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($page == 'vysledky_hracu'): ?>
            <h2 class="text-2xl font-black text-poda-blue mb-6 uppercase px-1">Žebříček hráčů</h2>
            <div class="poda-card overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-[15px] font-bold text-gray-400 uppercase tracking-widest border-b">
                        <tr><th class="p-3 w-12">#</th><th class="p-4">Hráč</th><th class="p-4 text-right">Body</th></tr>
                    </thead>
                    <tbody>
                        <?php $pos = 1; foreach ($zebricek as $name => $data): 
                            $isMe = ($data['id_uzivatele'] == $_SESSION['user_id']);
                        ?>
                        <tr class="border-b hover:bg-blue-50/50 transition <?php echo $isMe ? 'bg-blue-200 shadow-inner' : ''; ?>">
                            <td class="p-3 text-gray-400 font-black text-xl"><?php echo $pos++; ?>.</td>
                            <td class="p-3 font-bold text-gray-800 text-xl"><?php echo htmlspecialchars($name); ?></td>
                            <td class="p-3 text-right">
                                <a href="index.php?page=detail_hrace&user_id=<?php echo $data['id_uzivatele']; ?>" class="text-black font-black text-xl"><?php echo $data['body']; ?> b.
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php elseif ($page == 'detail_hrace'): 
            $tid = $_GET['user_id'] ?? 0;
            $stName = $pdo->prepare("SELECT jmeno, username FROM uzivatele WHERE id = ?");
            $stName->execute([$tid]);
            $uRow = $stName->fetch();
            $zobrazeneJmeno = !empty($uRow['jmeno']) ? $uRow['jmeno'] : ($uRow['username'] ?? "Neznámý");
        ?>
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-black text-poda-blue uppercase italic">Tipy: <?php echo htmlspecialchars($zobrazeneJmeno); ?></h2>
                <a href="index.php?page=vysledky_hracu" class="text-xl font-bold text-gray-400 uppercase border-b">← Zpět</a>
            </div>
            <div class="poda-card overflow-hidden">
                <div class="table-container">
                    <table class="w-full text-left">
                        <thead class="bg-gray-800 text-white text-[15px] uppercase">
                            <tr><th class="p-4">Zápas</th><th class="p-4 text-center">Tip</th><th class="p-4 text-center">Skore</th><th class="p-4 text-center">Body</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $stTipy = $pdo->prepare("SELECT z.*, t.tip_a, t.tip_b FROM tipy t JOIN zapasy z ON t.id_zapasu = z.id WHERE t.id_uzivatele = ? ORDER BY z.datum ASC");
                            $stTipy->execute([$tid]);
                            while ($v = $stTipy->fetch()):
                                $b = spoctiBody($v['tip_a'], $v['tip_b'], $v['skore_a'], $v['skore_b']);
                                $zapasZacal = strtotime($v['datum']) <= time();
                                $bodyColor = ($b == 3) ? 'text-green-500' : (($b > 0) ? 'text-blue-500' : 'text-gray-300');
                            ?>
                            <tr class="border-b">
                                <td class="p-4">
                                    <div class="font-bold text-[15px] text-gray-700 uppercase italic"><?php echo htmlspecialchars($v['tym_a']); ?> - <?php echo htmlspecialchars($v['tym_b']); ?></div>
                                    <div class="text-[11px] text-gray-400 font-bold uppercase"><?php echo date('d.m. H:i', strtotime($v['datum'])); ?></div>
                                </td>
                                <td class="p-4 text-center">
                                    <?php if($zapasZacal || (isset($_SESSION['role']) && $_SESSION['role']=='admin') || $tid == $_SESSION['user_id']): ?>
                                        <span class="font-black text-xl text-poda-blue"><?php echo "{$v['tip_a']}:{$v['tip_b']}"; ?></span>
                                    <?php else: ?>
                                        <span class="text-gray-300 text-[15px] italic">🔒 skryto</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center font-bold text-gray-400 text-xl"><?php echo ($v['skore_a']!==null) ? "{$v['skore_a']}:{$v['skore_b']}" : "-"; ?></td>
                                <td class="p-4 text-center font-black text-xl <?php echo $bodyColor; ?>"><?php echo ($v['skore_a']!==null) ? $b : ''; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($page == 'vysledky_tymu'): 
            $tymy_data = [];
            $stmt = $pdo->query("SELECT id, nazev FROM tymy");
            while ($t = $stmt->fetch()) {
                $tymy_data[$t['id']] = ['nazev' => $t['nazev'], 'body' => 0, 'clenove' => []];
            }
            foreach ($zebricek as $username => $u) {
                $st = $pdo->prepare("SELECT id_tymu FROM uzivatele WHERE id = ?");
                $st->execute([$u['id_uzivatele']]);
                $t_id = $st->fetchColumn();
                if ($t_id && isset($tymy_data[$t_id])) {
                    $tymy_data[$t_id]['body'] += $u['body'];
                    $tymy_data[$t_id]['clenove'][] = ['jmeno' => $username, 'body' => $u['body']];
                }
            }
            uasort($tymy_data, fn($a, $b) => $b['body'] <=> $a['body']);
        ?>      
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-black text-poda-blue uppercase">Soutěž týmů</h2>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                    <button onclick="document.getElementById('addTeamModal').showModal()" class="bg-green-500 text-white px-4 py-2 rounded font-bold text-xs uppercase shadow-md">Vytvořit tým</button>
                <?php endif; ?>
            </div>
            <div class="poda-card overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-800 text-white text-[10px] uppercase">
                        <tr><th class="p-4">Pořadí</th><th class="p-4">Název týmu</th><th class="p-4 text-right">Body</th></tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($tymy_data as $tid => $t): ?>
                        <tr class="border-b hover:bg-gray-50 transition cursor-pointer" onclick="document.getElementById('modal-<?php echo $tid; ?>').showModal()">
                            <td class="p-4 font-bold text-gray-400">#<?php echo $i++; ?></td>
                            <td class="p-4 font-black text-poda-blue"><?php echo htmlspecialchars($t['nazev']); ?></td>
                            <td class="p-4 text-right"><span class="bg-poda-blue text-white px-3 py-1 rounded-full font-black text-xs"><?php echo $t['body']; ?> b.</span></td>
                        </tr>
                        <dialog id="modal-<?php echo $tid; ?>" class="rounded-xl p-0 backdrop:bg-black/50 w-full max-w-md shadow-2xl">
                            <div class="p-6">
                                <div class="flex justify-between items-center mb-4 border-b pb-2">
                                    <h3 class="font-black text-poda-blue uppercase"><?php echo htmlspecialchars($t['nazev']); ?></h3>
                                    <form method="dialog"><button class="text-gray-400 text-xl">&times;</button></form>
                                </div>
                                <div class="space-y-2">
                                    <?php foreach ($t['clenove'] as $c): ?>
                                        <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg">
                                            <span class="font-bold text-gray-700"><?php echo htmlspecialchars($c['jmeno']); ?></span>
                                            <span class="font-black text-poda-blue"><?php echo $c['body']; ?> b.</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </dialog>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>

    <!-- MODALS -->
    <dialog id="changePasswordModal" class="rounded-lg p-0 w-full max-w-sm shadow-2xl overflow-hidden">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-black text-poda-blue uppercase">Změna hesla</h3>
                <form method="dialog"><button class="text-gray-400 hover:text-red-500 font-bold text-xl">&times;</button></form>
            </div>
            <form action="db.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="change_password">
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Stávající heslo</label>
                    <input name="current_password" type="password" class="w-full border p-2 rounded outline-none focus:ring-2 focus:ring-poda-blue" required>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Nové heslo</label>
                    <input name="new_password" id="new_pw" type="password" class="w-full border p-2 rounded outline-none focus:ring-2 focus:ring-poda-blue" required minlength="6">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Potvrzení nového hesla</label>
                    <input name="confirm_password" id="confirm_pw" type="password" class="w-full border p-2 rounded outline-none focus:ring-2 focus:ring-poda-blue" required minlength="6">
                </div>
                <div id="pw_error" class="hidden text-red-500 text-[10px] font-bold uppercase">Hesla se neshodují!</div>
                <button type="submit" onclick="return validatePassword()" class="w-full btn-poda text-white font-bold py-2 rounded uppercase text-sm shadow-md">Uložit nové heslo</button>
            </form>
        </div>
    </dialog>

    <script>
    function validatePassword() {
        const newPw = document.getElementById('new_pw').value;
        const confirmPw = document.getElementById('confirm_pw').value;
        const errorMsg = document.getElementById('pw_error');

        if (newPw !== confirmPw) {
            errorMsg.classList.remove('hidden');
            return false;
        }
        errorMsg.classList.add('hidden');
        return true;
    }
    </script>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <dialog id="adminMatchModal" class="rounded-xl p-0 w-full max-w-sm shadow-2xl">
        <div class="p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-black text-red-600 uppercase">Nový zápas</h3>
                <form method="dialog"><button class="text-2xl">&times;</button></form>
            </div>
            <form action="db.php" method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_match">
                <input name="tym_a" type="text" placeholder="Domácí" class="w-full border p-3 rounded-lg" required>
                <input name="tym_b" type="text" placeholder="Hosté" class="w-full border p-3 rounded-lg" required>
                <input name="datum" type="datetime-local" class="w-full border p-3 rounded-lg" required>
                <input name="misto" type="text" placeholder="Stadion" class="w-full border p-3 rounded-lg">
                <button type="submit" class="w-full bg-red-600 text-white font-bold py-3 rounded-lg uppercase">Vytvořit</button>
            </form>
        </div>
    </dialog>

    <dialog id="addTeamModal" class="rounded-xl p-6 backdrop:bg-black/50 w-full max-w-md shadow-2xl">
        <h3 class="font-black text-poda-blue uppercase mb-4">Vytvořit tým</h3>
        <form action="db.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="create_team">
            <input name="nazev" type="text" placeholder="Název týmu" class="w-full border p-3 rounded-lg outline-none" required>
            <div class="max-h-48 overflow-y-auto border p-2 rounded bg-gray-50">
                <?php
                $stmt = $pdo->query("SELECT id, username FROM uzivatele WHERE id_tymu IS NULL");
                while($u = $stmt->fetch()): ?>
                    <label class="flex items-center gap-2 text-sm p-2 hover:bg-white cursor-pointer border-b last:border-0">
                        <input type="checkbox" name="hraci[]" value="<?php echo $u['id']; ?>"> <?php echo htmlspecialchars($u['username']); ?>
                    </label>
                <?php endwhile; ?>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="flex-1 bg-poda-blue text-white py-3 rounded-lg font-bold uppercase text-xs">Vytvořit</button>
                <button type="button" onclick="this.closest('dialog').close()" class="flex-1 bg-gray-200 py-3 rounded-lg font-bold uppercase text-xs">Zavřít</button>
            </div>
        </form>
    </dialog>
    <?php endif; ?>

<?php endif; ?>
</body>
</html>