<?php
/**
 * Webhook pour les callbacks de paiement des donations
 */
require_once 'config/ticketing_database.php';
require_once 'includes/TicketManager.php';
require_once 'includes/DonationManager.php';

// Logger toutes les requêtes entrantes
logError("=== DONATION WEBHOOK APPELÉ ===");
logError("Method: " . $_SERVER['REQUEST_METHOD']);
logError("GET: " . json_encode($_GET));
logError("POST: " . json_encode($_POST));
logError("Headers: " . json_encode(getallheaders()));

// Initialiser le gestionnaire de donations
$donationManager = new DonationManager();

// Gérer les requêtes GET (redirections MaxiCash)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $donationNumber = $_GET['purchase'] ?? $_GET['donation'] ?? null;
    $status = $_GET['status'] ?? $_GET['ResponseStatus'] ?? null;

    logError("GET Request - Donation: $donationNumber, Status: $status");

    if ($donationNumber && $status) {
        // Extraire le numéro de don de la référence (format: DON-20250131-1234_timestamp)
        $parts = explode('_', $donationNumber);
        $cleanDonationNumber = $parts[0];

        switch (strtolower($status)) {
            case 'success':
            case 'paid':
                logError("Redirection vers donation-reussie: $cleanDonationNumber");
                header("Location: donation-reussie?donation=" . urlencode($cleanDonationNumber));
                exit;

            case 'failed':
            case 'error':
                logError("Redirection vers donation-echouee: $cleanDonationNumber");
                header("Location: donation-echouee?donation=" . urlencode($cleanDonationNumber));
                exit;

            case 'pending':
            case 'cancelled':
                logError("Redirection vers donation_pending: $cleanDonationNumber");
                header("Location: donation_pending.php?donation=" . urlencode($cleanDonationNumber));
                exit;
        }
    }

    // Si pas de paramètres valides, rediriger vers la page de donation
    header("Location: donation.php");
    exit;
}

// Gérer les requêtes POST (callbacks MaxiCash)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer le corps de la requête
    $rawBody = file_get_contents('php://input');
    logError("POST Raw Body: " . $rawBody);

    // Essayer de décoder le JSON
    $jsonData = json_decode($rawBody, true);
    if ($jsonData) {
        logError("POST JSON Data: " . json_encode($jsonData));
    }

    // Données de callback MaxiCash
    $reference = $_POST['Reference'] ?? $jsonData['Reference'] ?? null;
    $transactionId = $_POST['TransactionID'] ?? $jsonData['TransactionID'] ?? null;
    $status = $_POST['ResponseStatus'] ?? $jsonData['ResponseStatus'] ?? null;
    $amount = $_POST['Amount'] ?? $jsonData['Amount'] ?? null;

    logError("Parsed - Ref: $reference, TxID: $transactionId, Status: $status, Amount: $amount");

    if ($reference && $status) {
        // Extraire le numéro de don de la référence (format: DON-20250131-1234_timestamp)
        $parts = explode('_', $reference);
        $donationNumber = $parts[0];

        logError("Processing donation: $donationNumber");

        // Récupérer la donation
        $result = $donationManager->getDonation($donationNumber);

        if ($result['success']) {
            $donation = $result['donation'];

            switch (strtolower($status)) {
                case 'success':
                case 'paid':
                    // Marquer la donation comme payée
                    if ($donation['payment_status'] !== 'paid') {
                        logError("Finalisation de la donation: $donationNumber");
                        $donationManager->finalizeDonation($donation['id'], $transactionId);
                    }

                    // Répondre à MaxiCash
                    http_response_code(200);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Don traité avec succès'
                    ]);
                    exit;

                case 'pending':
                    // Mettre à jour le statut
                    $db = getDB();
                    $stmt = $db->prepare("
                        UPDATE donations
                        SET payment_status = 'pending', transaction_id = ?
                        WHERE donation_number = ?
                    ");
                    $stmt->execute([$transactionId, $donationNumber]);

                    logError("Don en attente: $donationNumber");

                    http_response_code(200);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Don en attente'
                    ]);
                    exit;

                case 'failed':
                case 'error':
                case 'cancelled':
                    // Mettre à jour le statut
                    $db = getDB();
                    $stmt = $db->prepare("
                        UPDATE donations
                        SET payment_status = 'failed', transaction_id = ?
                        WHERE donation_number = ?
                    ");
                    $stmt->execute([$transactionId, $donationNumber]);

                    logError("Don échoué: $donationNumber");

                    http_response_code(200);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Don échoué'
                    ]);
                    exit;
            }
        } else {
            logError("Don non trouvé: $donationNumber");
        }
    }

    // Réponse par défaut
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Webhook reçu'
    ]);
    exit;
}

// Méthode non supportée
http_response_code(405);
echo json_encode([
    'success' => false,
    'error' => 'Méthode non supportée'
]);
exit;
?>
