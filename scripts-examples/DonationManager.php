<?php
require_once __DIR__ . '/../config/ticketing_database.php';
require_once __DIR__ . '/TicketManager.php';
require_once __DIR__ . '/MaxiCashAPI.php';
require_once __DIR__ . '/DonationNotificationService.php';

/**
 * Gestionnaire de donations
 */
class DonationManager {
    private $db;

    public function __construct() {
        $this->db = getDB();
        $this->initializeDonationTables();
    }

    /**
     * Initialise les tables de donation
     */
    private function initializeDonationTables() {
        try {
            // Table des donations
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS donations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    donation_number VARCHAR(50) NOT NULL UNIQUE,
                    artist_name VARCHAR(255) NOT NULL DEFAULT 'BENJI4',
                    donor_name VARCHAR(255) NOT NULL,
                    donor_email VARCHAR(255) NOT NULL,
                    donor_phone VARCHAR(20) NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    currency VARCHAR(3) DEFAULT 'USD',
                    payment_status ENUM('pending', 'pending_validation', 'paid', 'failed', 'cancelled') DEFAULT 'pending',
                    payment_method VARCHAR(50) NULL,
                    transaction_id VARCHAR(100) NULL,
                    payment_reference VARCHAR(100) NULL,
                    paid_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_donation_number (donation_number),
                    INDEX idx_donor_email (donor_email),
                    INDEX idx_donor_phone (donor_phone),
                    INDEX idx_payment_status (payment_status),
                    INDEX idx_transaction_id (transaction_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

        } catch (PDOException $e) {
            logError("Erreur initialisation tables donations: " . $e->getMessage());
        }
    }

    /**
     * Crée une donation
     */
    public function createDonation($donationData) {
        try {
            $this->db->beginTransaction();

            // Générer numéro de donation
            $donationNumber = $this->generateDonationNumber();

            // Créer la donation
            $stmt = $this->db->prepare("
                INSERT INTO donations (
                    donation_number, artist_name, donor_name, donor_email, donor_phone,
                    amount, currency, payment_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");

            $stmt->execute([
                $donationNumber,
                $donationData['artist_name'] ?? 'BENJI4',
                $donationData['donor_name'],
                $donationData['donor_email'],
                TicketManager::formatPhoneForSearch($donationData['donor_phone']),
                $donationData['amount'],
                $donationData['currency'] ?? 'USD'
            ]);

            $donationId = $this->db->lastInsertId();

            $this->db->commit();

            return [
                'success' => true,
                'donation_id' => $donationId,
                'donation_number' => $donationNumber,
                'amount' => $donationData['amount'],
                'currency' => $donationData['currency'] ?? 'USD'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            logError("Erreur création donation: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erreur lors de la création de la donation: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Traite le paiement d'une donation
     */
    public function processDonationPayment($donationNumber, $paymentData) {
        try {
            // Récupérer la donation
            $donationResult = $this->getDonation($donationNumber);

            if (!$donationResult['success']) {
                return ['success' => false, 'error' => 'Donation non trouvée'];
            }

            $donation = $donationResult['donation'];

            if ($donation['payment_status'] === 'paid') {
                return ['success' => false, 'error' => 'Cette donation est déjà payée'];
            }

            // Valider le numéro de téléphone pour MaxiCash (doit être congolais)
            if (isset($paymentData['phone'])) {
                $formattedPhone = TicketManager::formatPhoneForSearch($paymentData['phone']);

                // MaxiCash ne supporte que les numéros congolais
                if (!preg_match('/^\+243[0-9]{9}$/', $formattedPhone)) {
                    // Essayer de convertir vers un format congolais si possible
                    $cleanDigits = preg_replace('/[^\d]/', '', $paymentData['phone']);

                    if (strlen($cleanDigits) === 9) {
                        $paymentData['phone'] = '+243' . $cleanDigits;
                    } elseif (strlen($cleanDigits) === 10 && substr($cleanDigits, 0, 1) === '0') {
                        $paymentData['phone'] = '+243' . substr($cleanDigits, 1);
                    } elseif (strlen($cleanDigits) === 12 && substr($cleanDigits, 0, 3) === '243') {
                        $paymentData['phone'] = '+' . $cleanDigits;
                    } else {
                        return [
                            'success' => false,
                            'error' => 'Ce numéro de téléphone n\'est pas supporté pour le paiement. Veuillez utiliser un numéro congolais.'
                        ];
                    }
                } else {
                    $paymentData['phone'] = $formattedPhone;
                }
            }

            // Préparer les données de paiement
            $paymentData['amount'] = $donation['amount'];
            $paymentData['currency'] = $donation['currency'];
            $paymentData['reference'] = $donation['donation_number'] . '_' . time();

            // URLs de retour avec le numéro de donation
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);

            $paymentData['success_url'] = $baseUrl . '/donation_webhook.php';
            $paymentData['failure_url'] = $baseUrl . '/donation_webhook.php';
            $paymentData['cancel_url'] = $baseUrl . '/donation_webhook.php';
            $paymentData['notify_url'] = $baseUrl . '/donation_webhook.php';

            // Initialiser MaxiCash
            $maxiCash = new MaxiCashAPI(
                MAXICASH_MERCHANT_ID,
                MAXICASH_MERCHANT_PASSWORD,
                MAXICASH_API_MODE
            );

            // Valider les données
            $validationErrors = $maxiCash->validatePaymentData($paymentData);
            if (!empty($validationErrors)) {
                return [
                    'success' => false,
                    'error' => 'Données invalides: ' . implode(', ', $validationErrors)
                ];
            }

            // Traiter le paiement
            if ($paymentData['method'] === 'card') {
                $result = $maxiCash->payCreditCard($paymentData);
            } else {
                $result = $maxiCash->payNowSync($paymentData);
            }

            // Mettre à jour la donation avec le résultat
            $this->updateDonationPayment($donation['id'], $paymentData, $result);

            if ($result['success']) {
                if (isset($result['pending']) && $result['pending']) {
                    // Marquer la donation comme "en attente de paiement"
                    $stmt = $this->db->prepare("
                        UPDATE donations
                        SET payment_status = 'pending_validation'
                        WHERE id = ?
                    ");
                    $stmt->execute([$donation['id']]);

                    return [
                        'success' => true,
                        'pending' => true,
                        'awaiting_mobile_validation' => $result['awaiting_mobile_validation'] ?? false,
                        'redirect_url' => $result['redirect_url'] ?? null,
                        'transaction_id' => $result['transaction_id'] ?? null,
                        'reference' => $result['reference'] ?? null,
                        'message' => $result['message'] ?? 'Paiement en attente',
                        'instructions' => $result['instructions'] ?? null,
                        'donation_number' => $donation['donation_number']
                    ];
                } else {
                    // Paiement réussi - finaliser
                    $this->finalizeDonation($donation['id'], $result['transaction_id']);

                    return [
                        'success' => true,
                        'transaction_id' => $result['transaction_id'],
                        'message' => 'Don effectué avec succès'
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Erreur de paiement'
                ];
            }

        } catch (Exception $e) {
            logError("Erreur traitement paiement donation: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erreur système: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Finalise la donation après paiement réussi
     */
    public function finalizeDonation($donationId, $transactionId) {
        try {
            $this->db->beginTransaction();

            // Récupérer les détails de la donation
            $stmt = $this->db->prepare("
                SELECT * FROM donations WHERE id = ?
            ");
            $stmt->execute([$donationId]);
            $donation = $stmt->fetch();

            if (!$donation) {
                throw new Exception("Donation non trouvée");
            }

            // Marquer comme payée
            $stmt = $this->db->prepare("
                UPDATE donations
                SET payment_status = 'paid', transaction_id = ?, paid_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$transactionId, $donationId]);

            // Envoyer notification SMS + Email de succès
            $this->sendDonationNotification($donationId);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            logError("Erreur finalisation donation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère une donation
     */
    public function getDonation($donationNumber) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM donations
                WHERE donation_number = ?
            ");
            $stmt->execute([$donationNumber]);
            $donation = $stmt->fetch();

            if (!$donation) {
                return ['success' => false, 'error' => 'Donation non trouvée'];
            }

            return [
                'success' => true,
                'donation' => $donation
            ];

        } catch (Exception $e) {
            logError("Erreur récupération donation: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erreur lors de la récupération'
            ];
        }
    }

    /**
     * Envoie une notification SMS + Email après don réussi
     */
    private function sendDonationNotification($donationId) {
        try {
            // Récupérer les détails de la donation
            $stmt = $this->db->prepare("
                SELECT * FROM donations WHERE id = ?
            ");
            $stmt->execute([$donationId]);
            $donation = $stmt->fetch();

            if (!$donation) {
                logError("Donation non trouvée pour notification: ID " . $donationId);
                return;
            }

            logError("=== ENVOI NOTIFICATION DONATION ===");
            logError("Donation: " . $donation['donation_number']);
            logError("Donateur: " . $donation['donor_name']);
            logError("Téléphone: " . $donation['donor_phone']);
            logError("Montant: " . formatCurrency($donation['amount'], $donation['currency']));

            // Préparer les données pour la notification (SMS/WhatsApp uniquement)
            $donationData = [
                'donor_name' => $donation['donor_name'],
                'amount' => $donation['amount'],
                'currency' => $donation['currency'],
                'donation_number' => $donation['donation_number'],
                'artist_name' => $donation['artist_name']
            ];

            try {
                $donationNotificationService = new DonationNotificationService();

                logError("Tentative d'envoi via DonationNotificationService...");

                // Envoyer la notification (SMS ou WhatsApp uniquement)
                $result = $donationNotificationService->sendDonationNotification(
                    $donation['donor_phone'],
                    $donationData
                );

                logError("Résultat notification: " . json_encode($result));

                if ($result['success']) {
                    logError("✅ Notification envoyée pour donation: " . $donation['donation_number'] . " via " . $result['provider']);
                } else {
                    logError("❌ Échec notification donation: " . ($result['error'] ?? 'Erreur inconnue'));
                    logError("Détails: " . json_encode($result['details'] ?? []));
                }
            } catch (Exception $notifException) {
                logError("❌ Exception lors de l'envoi de notification: " . $notifException->getMessage());
                logError("Stack trace: " . $notifException->getTraceAsString());
            }

        } catch (Exception $e) {
            logError("❌ Erreur notification donation: " . $e->getMessage());
            logError("Stack trace: " . $e->getTraceAsString());
        }
    }

    // Méthodes privées
    private function generateDonationNumber() {
        $prefix = 'DON-';
        $timestamp = date('Ymd');

        do {
            $random = sprintf('%04d', mt_rand(1, 9999));
            $donationNumber = $prefix . $timestamp . '-' . $random;

            $stmt = $this->db->prepare("SELECT id FROM donations WHERE donation_number = ?");
            $stmt->execute([$donationNumber]);
            $exists = $stmt->fetch();
        } while ($exists);

        return $donationNumber;
    }

    private function updateDonationPayment($donationId, $paymentData, $result) {
        $stmt = $this->db->prepare("
            UPDATE donations
            SET payment_method = ?, payment_reference = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $paymentData['method'],
            $paymentData['reference'],
            $donationId
        ]);
    }

    /**
     * Recherche des donations par numéro de téléphone
     */
    public function searchDonationsByPhone($phoneNumber) {
        try {
            // Formater le numéro principal
            $formattedPhone = TicketManager::formatPhoneForSearch($phoneNumber);

            // Générer les variantes de recherche
            $phoneVariants = [$phoneNumber, $formattedPhone];

            if (strpos($formattedPhone, '+243') === 0) {
                $phoneVariants[] = preg_replace('/^\+243/', '0', $formattedPhone);
                $phoneVariants[] = preg_replace('/^\+243/', '', $formattedPhone);
            } elseif (strpos($phoneNumber, '+') === 0) {
                $phoneVariants[] = $phoneNumber;
            } else {
                if (strlen($phoneNumber) === 10 && substr($phoneNumber, 0, 1) === '0') {
                    $phoneVariants[] = '+243' . substr($phoneNumber, 1);
                } elseif (strlen($phoneNumber) === 9) {
                    $phoneVariants[] = '+243' . $phoneNumber;
                }
            }

            $phoneVariants = array_unique($phoneVariants);

            $placeholders = str_repeat('?,', count($phoneVariants) - 1) . '?';

            $stmt = $this->db->prepare("
                SELECT * FROM donations
                WHERE donor_phone IN ($placeholders) AND payment_status = 'paid'
                ORDER BY created_at DESC
            ");

            $stmt->execute($phoneVariants);
            $donations = $stmt->fetchAll();

            return [
                'success' => true,
                'donations' => $donations
            ];

        } catch (Exception $e) {
            logError("Erreur recherche donations par téléphone: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erreur lors de la recherche des donations'
            ];
        }
    }
}
?>
