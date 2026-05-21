<?php
// reg.php
include 'db.php';
$message = "";
$new_id = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $prezdivka = trim($_POST['prezdivka']); 
    $cele_jmeno = trim($_POST['cele_jmeno']); 
    $email_kontakt = trim($_POST['email_kontakt']);
    $pass = $_POST['password'];
    $pass_confirm = $_POST['password_confirm'];

    // 1. Kontrola unikátnosti přezdívky
    $check = $pdo->prepare("SELECT id FROM uzivatele WHERE prezdivka = ?");
    $check->execute([$prezdivka]);

    if ($pass !== $pass_confirm) {
        $message = "Hesla se neshodují!";
    } elseif (strlen($prezdivka) < 3) {
        $message = "Přezdívka musí mít aspoň 3 znaky.";
    } elseif ($check->fetch()) {
        $message = "Tato přezdívka je již obsazená!";
    } else {
    try {
        $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);

        // Změna: Místo 'email' píšeme 'username', protože tak se jmenuje sloupec v tvé DB
        $stmt = $pdo->prepare("INSERT INTO uzivatele (prezdivka, jmeno, username, heslo, role) VALUES (?, ?, ?, ?, 'user')");
        
        if ($stmt->execute([$prezdivka, $cele_jmeno, $email_kontakt, $hashed_pass])) {
            $new_id = $prezdivka; 
            $message = "Registrace proběhla úspěšně!";
        }
    } catch (PDOException $e) {
        $message = "Chyba databáze: " . $e->getMessage();
    }
}
}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title>Registrace | PODA TIP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #f0f2f5; font-family: sans-serif; }
        .btn-poda { background: linear-gradient(90deg, #2d307d 0%, #4a00e0 100%); transition: all 0.3s cubic-bezier(.25,.8,.25,1); background-size: 200% auto;box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .btn-poda:hover { background-position: right center; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.2), 0 0 15px rgba(74, 0, 224, 0.4); filter: brightness(1.1); }
        .btn-poda:active { transform: translateY(1px); box-shadow: 0 2px 5px rgba(0,0,0,0.2); filter: brightness(0.9); }
        .poda-card { background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="poda-card p-8 w-full max-w-md text-center">
        <img src="logo.svg" class="h-10 mx-auto mb-6">
        
        <?php if ($new_id): ?>
            <div class="bg-green-50 border border-green-200 p-6 rounded-lg">
                <h2 class="text-green-700 font-bold text-xl mb-2">Úspěch!</h2>
                <p class="text-gray-600 mb-4">Můžete se přihlásit jako: <strong><?php echo htmlspecialchars($new_id); ?></strong></p>
                <a href="index.php" class="block w-full btn-poda text-white font-bold py-3 rounded uppercase text-center">Přejít k přihlášení</a>
            </div>
        <?php else: ?>
            <h1 class="text-xl font-bold mb-6 uppercase tracking-tight">Registrace hráče</h1>
            <?php if($message) echo "<p class='text-red-500 mb-4 font-bold'>$message</p>"; ?>
            
            <form method="POST" class="space-y-4 text-left">
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Přezdívka (pro přihlášení)</label>
                    <input name="prezdivka" type="text" placeholder="např. jenda99" class="w-full border p-3 rounded-md focus:ring-2 focus:ring-purple-500 outline-none" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Celé Jméno a Příjmení</label>
                    <input name="cele_jmeno" type="text" placeholder="Jan Novák" class="w-full border p-3 rounded-md focus:ring-2 focus:ring-purple-500 outline-none" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Pracovní E-mail</label>
                    <input name="email_kontakt" type="email" placeholder="novak@poda.cz" class="w-full border p-3 rounded-md focus:ring-2 focus:ring-purple-500 outline-none" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Heslo</label>
                    <input name="password" type="password" placeholder="••••••" class="w-full border p-3 rounded-md focus:ring-2 focus:ring-purple-500 outline-none" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Heslo znovu</label>
                    <input name="password_confirm" type="password" placeholder="••••••" class="w-full border p-3 rounded-md focus:ring-2 focus:ring-purple-500 outline-none" required>
                </div>
                <button type="submit" class="w-full btn-poda text-white font-bold py-3 rounded-md uppercase shadow-lg">Zaregistrovat se</button>
            </form>
            <p class="mt-6 text-sm text-gray-500">Už máte účet? <a href="index.php" class="text-purple-600 font-bold">Přihlaste se</a></p>
        <?php endif; ?>
    </div>
</body>
</html>