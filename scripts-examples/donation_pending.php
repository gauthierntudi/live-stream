<?php
/**
 * Page d'attente pour donation
 */
session_start();
require_once 'config/ticketing_database.php';
require_once 'includes/TicketManager.php';
require_once 'includes/DonationManager.php';

$donationNumber = $_GET['donation'] ?? '';
$instructions = $_GET['instructions'] ?? '';
$donationManager = new DonationManager();

if (!$donationNumber) {
    header('Location: donation.php');
    exit;
}

$result = $donationManager->getDonation($donationNumber);

if (!$result['success']) {
    header('Location: donation.php');
    exit;
}

$donation = $result['donation'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement en attente</title>
    <link rel="icon" type="image/x-icon" href="img/ico01.png">
    <link rel="stylesheet" href="assets/vendor/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .pending-container {
            max-width: 600px;
            width: 100%;
        }
        .pending-icon {
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
        .pending-icon i {
            font-size: 4rem;
            color: #f59e0b;
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
    </style>
</head>
<body>
    <div class="pending-container">
        <div class="pay--billets--card">
            <div class="pay--billets--card-body pay--billets--text-center">
                <div class="pending-icon">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <h1 class="h2 pay--billets--mb-3">Paiement en attente</h1>
                <p class="pay--billets--mb-4">Veuillez valider le paiement sur votre téléphone mobile</p>

                <div class="pay--billets--detail-row">
                    <span class="pay--billets--detail-label">Numéro de don</span>
                    <span class="pay--billets--detail-value"><?= htmlspecialchars($donation['donation_number']) ?></span>
                </div>
                <div class="pay--billets--detail-row">
                    <span class="pay--billets--detail-label">Montant</span>
                    <span class="pay--billets--detail-value"><?= formatCurrency($donation['amount'], $donation['currency']) ?></span>
                </div>

                <?php if ($instructions): ?>
                <div class="pay--billets--alert pay--billets--alert-warning show pay--billets--mt-4">
                    <i class="bi bi-info-circle-fill"></i>
                    <span><?= htmlspecialchars(urldecode($instructions)) ?></span>
                </div>
                <?php else: ?>
                <div class="pay--billets--alert pay--billets--alert-warning show pay--billets--mt-4">
                    <i class="bi bi-phone-fill"></i>
                    <span>Vérifiez votre téléphone pour finaliser le paiement</span>
                </div>
                <?php endif; ?>

                <div class="pay--billets--mt-4">
                    <p><strong>Instructions:</strong></p>
                    <ul style="text-align: left; display: inline-block;">
                        <li>Vérifiez votre téléphone mobile</li>
                        <li>Entrez votre code PIN pour valider</li>
                        <li>Vous recevrez une confirmation par SMS/Email</li>
                    </ul>
                </div>

                <div class="pay--billets--d-flex" style="gap: 1rem; justify-content: center; margin-top: 2rem;">
                    <a href="donation.php" class="pay--billets--btn pay--billets--btn-secondary">
                        Retour
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
