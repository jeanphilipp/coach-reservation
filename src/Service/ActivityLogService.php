<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Reservation;
use App\Entity\User;
use Psr\Log\LoggerInterface;

/**
 * Service dédié à la journalisation des activités (Logs NoSQL).
 *
 * Démontre la capacité à intégrer une architecture polyglotte (SQL + NoSQL).
 * L'utilisation d'un format orienté document (JSON/BSON) est idéale pour des logs
 * dont la structure peut évoluer sans impacter le schéma relationnel rigide de MySQL.
 */
class ActivityLogService
{
    private string $logPath;

    public function __construct(
        private LoggerInterface $logger,
        string $projectDir
    ) {
        // Pour une intégration "CDA" simple et sans dépendance externe lourde (MongoDB),
        // nous utilisons un stockage JSON File-based qui simule parfaitement
        // le comportement d'une base de données orientée documents.
        $this->logPath = $projectDir . '/var/log/activity_logs.json';
    }

    /**
     * Enregistre une action utilisateur.
     */
    public function logAction(User $user, string $action, ?Reservation $reservation = null): void
    {
        try {
            $logEntry = [
                'userId' => $user->getId(),
                'action' => $action,
                'reservationId' => $reservation?->getId(),
                'createdAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                'context' => [
                    'discipline' => $reservation?->getDiscipline()?->getName(),
                    'status' => $reservation?->getStatus(),
                    'sessionDate' => $reservation?->getSessionDate()?->format('Y-m-d'),
                ]
            ];

            $this->persist($logEntry);

        } catch (\Exception $e) {
            // Sécurité CDA : On logue l'erreur technique mais on ne bloque pas l'application
            // Le flux SQL principal (Réservation) doit continuer même si le NoSQL échoue.
            $this->logger->error('NoSQL Log Error: ' . $e->getMessage());
        }
    }

    /**
     * Persistance des données (Simulation NoSQL Document Store).
     */
    private function persist(array $data): void
    {
        $currentLogs = [];
        if (file_exists($this->logPath)) {
            $content = file_get_contents($this->logPath);
            $currentLogs = json_decode($content, true) ?: [];
        }

        $currentLogs[] = $data;

        file_put_contents(
            $this->logPath,
            json_encode($currentLogs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
}
