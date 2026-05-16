<?php
/**
 * Page de succès pour donation
 */
session_start();
require_once 'config/ticketing_database.php';
require_once 'includes/TicketManager.php';
require_once 'includes/DonationManager.php';

$donationNumber = $_GET['donation'] ?? '';
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
    <title>Don Réussi - Merci!</title>
    <link rel="icon" type="image/x-icon" href="img/ico01.png">
    <link rel="stylesheet" href="assets/vendor/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link href="css/styles.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .success-container {
            max-width: 600px;
            width: 100%;
        }
        .success-icon {
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
        .success-icon i {
            font-size: 4rem;
            color: #10b981;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="pay--billets--card">
            <div class="pay--billets--card-body pay--billets--text-center">
                <div class="success-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h1 class="h2 pay--billets--mb-3">Merci pour votre générosité !</h1>
                <p class="pay--billets--mb-4">Votre don a été effectué avec succès</p>

                <div class="pay--billets--detail-row">
                    <span class="pay--billets--detail-label">Numéro de don</span>
                    <span class="pay--billets--detail-value"><?= htmlspecialchars($donation['donation_number']) ?></span>
                </div>
                <div class="pay--billets--detail-row">
                    <span class="pay--billets--detail-label">Montant</span>
                    <span class="pay--billets--detail-value"><?= formatCurrency($donation['amount'], $donation['currency']) ?></span>
                </div>
                <div class="pay--billets--detail-row">
                    <span class="pay--billets--detail-label">Artiste</span>
                    <span class="pay--billets--detail-value"><?= htmlspecialchars($donation['artist_name']) ?></span>
                </div>

                <?php
                    // On préfère paid_at, sinon on se rabat sur created_at, sinon on affiche un tiret
                    $dateSource = $donation['paid_at'] ?? $donation['created_at'] ?? null;

                    if (!empty($dateSource)) {
                        $dateFormatted = date('d/m/Y H:i', strtotime($dateSource));
                    } else {
                        $dateFormatted = '-';
                    }
                ?>

                <div class="pay--billets--detail-row">
                    <span class="pay--billets--detail-label">Date</span>
                    <span class="pay--billets--detail-value">
                        <?= htmlspecialchars($dateFormatted) ?>
                    </span>
                </div>

                <div class="pay--billets--alert pay--billets--alert-success show pay--billets--mt-4">
                    <i class="bi bi-envelope-check-fill"></i>
                    <span>Une confirmation a été envoyée à <?= htmlspecialchars($donation['donor_phone'] ?? 'votre numéro') ?></span>
                </div>

                <a href="donation" class="pay--billets--btn pay--billets--btn-primary pay--billets--mt-4">
                    Faire un autre don
                </a>
            </div>
        </div>
    </div>
</body>
</html>
