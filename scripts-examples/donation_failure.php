<?php
/**
 * Page d'échec pour donation
 */
session_start();
require_once 'config/ticketing_database.php';

$donationNumber = $_GET['donation'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Échec du don</title>
    <link rel="icon" type="image/x-icon" href="img/ico01.png">
    <link rel="stylesheet" href="assets/vendor/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .failure-container {
            max-width: 600px;
            width: 100%;
        }
        .failure-icon {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
        }
        .failure-icon i {
            font-size: 4rem;
            color: #ef4444;
        }
    </style>
</head>
<body>
    <div class="failure-container">
        <div class="pay--billets--card">
            <div class="pay--billets--card-body pay--billets--text-center">
                <div class="failure-icon">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <h1 class="h2 pay--billets--mb-3">Échec du paiement</h1>
                <p class="pay--billets--mb-4">Le paiement de votre don n'a pas pu être traité</p>

                <?php if ($donationNumber): ?>
                <div class="pay--billets--alert pay--billets--alert-error show pay--billets--mb-4">
                    <span>Référence: <?= htmlspecialchars($donationNumber) ?></span>
                </div>
                <?php endif; ?>

                <p>Raisons possibles:</p>
                <ul style="text-align: left; display: inline-block;">
                    <li>Fonds insuffisants</li>
                    <li>Problème de connexion</li>
                    <li>Transaction annulée</li>
                    <li>Informations de paiement invalides</li>
                </ul>

                <div class="pay--billets--d-flex" style="gap: 1rem; justify-content: center; margin-top: 2rem;">
                    <a href="donation.php" class="pay--billets--btn pay--billets--btn-primary">
                        Réessayer
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
