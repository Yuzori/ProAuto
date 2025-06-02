<?php
// modifier.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

function genererEmailHTML($data, $id, $type = 'reservation') {
    $titre = ($type === 'modification') 
        ? "Confirmation de Modification de votre Réservation"
        : "Confirmation de réservation de table";
    
    return "
    <html>
    <head>
        <title>{$titre}</title>
        <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
        <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap' rel='stylesheet'>
        <style>
            body {
                font-family: 'Poppins', sans-serif;
                background-color: #161616;
                color: #0052e9;
                padding: 20px;
                margin: 0;
            }
            .container {
                background-color: #161616;
                padding: 30px;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                max-width: 600px;
                margin: auto;
                text-align: center;
            }
            h2 {
                color: #0052e9;
                margin-bottom: 20px;
                font-size: 28px;
            }
            p {
                margin: 10px 0;
                font-size: 16px;
            }
            .highlight {
                color: #0052e9;
                font-weight: bold;
            }
            .footer {
                margin-top: 20px;
                font-size: 14px;
                color: #0052e9;
                text-align: center;
                border-top: 1px solid #0052e9;
                padding-top: 15px;
            }
            .btn {
                background-color: #0052e9;
                color: #161616;
                padding: 10px 20px;
                text-decoration: none;
                border-radius: 5px;
                margin: 10px;
                display: inline-block;
                border: none;
                transition: 0.5s ease;
            }
            .btn:hover {
                background-color: #0a3fa3;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <img src='http://s818987091.onlinehome.fr/promotors/images/Group%201.png' alt='Logo' style='width: 200px; margin-bottom: 20px;'>
            <h2>{$titre}</h2>
            <p style='color: #0052e9;'><span class='highlight'>Nom :</span> {$data['nom']}</p>
            <p style='color: #0052e9;'><span class='highlight'>Téléphone :</span> {$data['telephone']}</p>
            <p style='color: #0052e9;'><span class='highlight'>Email :</span> {$data['email']}</p>
            <p style='color: #0052e9;'><span class='highlight'>Date :</span> {$data['date']}</p>
            <p style='color: #0052e9;'><span class='highlight'>Horaire :</span> {$data['horaire']}</p>
            " . 
            (!empty($data['message']) ? "<p style='color: #0052e9;'><span class='highlight'>Message :</span> {$data['message']}</p>" : "") 
            . "
            <p style='color: #0052e9;'>Votre réservation a été " . ($type === 'modification' ? "modifiée" : "confirmée") . " avec succès.</p>
            <p style='color: #0052e9;'>Numéro de réservation : <span class='highlight'>$id</span></p>
            
            <a href='https://maps.app.goo.gl/q8qpGe2BeFzkqqJP8' class='btn' style='color: #161616;'>Voir l'itinéraire</a>
            <a href='http://s818987091.onlinehome.fr/promotors/modifier.php?id={$id}' class='btn' style='color: #161616;'>Modifier ma réservation</a>
            <a href='http://s818987091.onlinehome.fr/promotors/annuler.php?id={$id}' class='btn' style='color: #161616;'>Annuler ma réservation</a>
            
            <div class='footer'>
                Nous vous attendons chez Pro Auto !<br>
                Adresse : <strong>Av. de Morges 90, 1004 Lausanne, Suisse</strong>
            </div>
        </div>
    </body>
    </html>";
}

function getReservation($id) {
    $reservations = file('reservations.txt', FILE_IGNORE_NEW_LINES);
    foreach ($reservations as $reservation) {
        $data = explode('|', $reservation);
        if ($data[0] === $id) {
            return [
                'id' => $data[0],
                'status' => $data[1],
                'nom' => $data[2],
                'telephone' => $data[3],
                'email' => $data[4],
                'date' => $data[5],
                'total_guest' => $data[5],
                'horaire' => $data[6],
                'message' => $data[8] ?? ''
            ];
        }
    }
    return null;
}

function updateReservation($id, $newData) {
    $reservations = file('reservations.txt', FILE_IGNORE_NEW_LINES);
    $updated = false;
    $newContent = '';

    foreach ($reservations as $reservation) {
        $data = explode('|', $reservation);
        if ($data[0] === $id) {
            $newLine = implode('|', [
                $id,
                'modifiée',
                $newData['nom'],
                $newData['telephone'],
                $newData['email'],
                $newData['date'],
                $newData['horaire'],
                $newData['total_guest'],
                $newData['message'] ?? '',
                date('Y-m-d H:i:s')
            ]);
            $newContent .= $newLine . "\n";
            $updated = true;
        } else {
            $newContent .= $reservation . "\n";
        }
    }

    if ($updated) {
        file_put_contents('reservations.txt', $newContent);
        return true;
    }
    return false;
}

$reservation = null;
$message = '';

if (isset($_GET['id'])) {
    $reservation = getReservation($_GET['id']);
    if (!$reservation) {
        die('Réservation non trouvée');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newData = [
        'nom' => $_POST['nom'],
        'telephone' => $_POST['telephone'],
        'email' => $_POST['email'],
        'date' => $_POST['date'],
        'horaire' => $_POST['horaire'],
        'total_guest' => $_POST['total_guest'],
        'message' => $_POST['message'] ?? ''
    ];

    if (updateReservation($_GET['id'], $newData)) {
        // Envoyer l'email de confirmation
        $to = $newData['email'];
        $subject = "Modification de votre réservation";
        $message_html = genererEmailHTML($newData, $_GET['id'], 'modification');
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Pro-Auto <sh-818987092@eu.hosting-webspace.io>" . "\r\n";
        
        mail($to, $subject, $message_html, $headers);
        $message = '<div style="color: #0052e9;">Votre réservation a été modifiée avec succès.</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier votre réservation - Pro Auto</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #161616;
            color: #0052e9;
            margin: 0;
            padding: 10px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .background-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, #161616, #0052e9);
            background-size: 400% 400%;
            animation: gradientAnimation 15s ease infinite;
            z-index: -1;
        }

        @keyframes gradientAnimation {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .container {
            background: #161616;
            border-radius: 20px;
            padding: 40px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 8px 32px #0052e9;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }

        #logo {
            max-width: 200px;
            height: auto;
        }

        h1 {
            font-size: 24px;
            color: #0052e9;
            text-align: center;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #0052e9;
            font-size: 16px;
        }

        input, select {
            width: 92%;
            margin: 10px 0;
            padding: 15px;
            border: 1px solid #0052e9;
            border-radius: 10px;
            font-size: 16px;
            background: #000;
            color: #0052e9;
            transition: all 0.3s ease;
        }

        input:focus, select:focus {
            outline: none;
            border-color: #0052e9;
            box-shadow: 0 0 5px rgba(255, 172, 237, 0.5);
        }

        button {
            background-color: #0052e9;
            color: #161616;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            width: 100%;
            transition: background-color 0.3s, transform 0.2s;
            margin-top: 20px;
            font-family: 'Poppins', sans-serif;
        }

        button:hover {
            background-color: #de99cf;
            transform: translateY(-2px);
        }

        .message {
            margin: 20px 0;
            padding: 15px;
            border-radius: 4px;
            text-align: center;
            color: #0052e9;
        }

        @media screen and (max-width: 480px) {
            .container {
                padding: 20px;
            }

            input, select, button {
                font-size: 14px;
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="background-animation"></div>
    <div class="container">
        <div class="logo-container">
            <img src="http://s818987091.onlinehome.fr/kafika/pages/assets/img/logobloomy.png" alt="Logo" id="logo">
        </div>
        
        <h1>Modifier votre réservation</h1>
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Nom</label>
                <input type="text" name="nom" required value="<?php echo htmlspecialchars($reservation['nom']); ?>" placeholder="Votre nom">
            </div>

            <div class="form-group">
                <label>Téléphone</label>
                <input type="tel" name="telephone" required value="<?php echo htmlspecialchars($reservation['telephone']); ?>" placeholder="Votre numéro de téléphone">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required value="<?php echo htmlspecialchars($reservation['email']); ?>" placeholder="Votre adresse email">
            </div>

            <div class="form-group">
                <label>Date</label>
                <input type="date" name="date" required value="<?php echo htmlspecialchars($reservation['date']); ?>">
            </div>

            <div class="form-group">
                <label>Horaire</label>
                <input type="time" name="horaire" required value="<?php echo htmlspecialchars($reservation['horaire']); ?>">
            </div>

            <div class="form-group">
                <label>Décrivez votre problème</label>
                <input type="text" name="message" value="<?php echo htmlspecialchars($reservation['total_guest'] ?? ''); ?>" placeholder="Votre message">
            </div>

            <button type="submit">Modifier la réservation</button>
        </form>
    </div>
</body>
</html>