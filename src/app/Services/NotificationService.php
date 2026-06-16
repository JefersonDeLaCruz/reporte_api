<?php

namespace App\Services;

use App\Models\Report;
use App\Models\User;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class NotificationService
{
    private $messaging;

    public function __construct()
    {
        try {
            $credentialsPath = base_path(env('FIREBASE_CREDENTIALS_PATH'));
            $factory = (new Factory)->withServiceAccount($credentialsPath);
            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            \Log::error('Firebase initialization failed: ' . $e->getMessage());
            $this->messaging = null;
        }
    }

    /**
     * Envía notificación a usuarios con FCM token cuando se crea un reporte.
     * Notifica a todos los usuarios registrados en el área (pueden filtrar por ubicación en cliente).
     */
    public function notifyNearbyUsers(Report $report): void
    {
        if (!$this->messaging) {
            return;
        }

        $users = User::whereNotNull('fcm_token')
            ->where('id', '!=', $report->user_id)
            ->get();

        foreach ($users as $user) {
            try {
                $message = CloudMessage::new()
                    ->withToken($user->fcm_token)
                    ->withNotification(Notification::create(
                        title: $report->category->name,
                        body: substr($report->description, 0, 100)
                    ))
                    ->withData([
                        'report_id' => (string) $report->id,
                        'latitude' => (string) $report->latitude,
                        'longitude' => (string) $report->longitude,
                        'status' => $report->status,
                        'type' => 'new_report',
                    ]);

                $this->messaging->send($message);
            } catch (\Exception $e) {
                \Log::error("Failed to send notification to user {$user->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Envía notificación de cambio de estado a usuarios que votaron.
     */
    public function notifyVoters(Report $report, string $previousStatus): void
    {
        if (!$this->messaging) {
            return;
        }

        $voterIds = $report->votes()->pluck('user_id')->unique();

        $users = User::whereIn('id', $voterIds)
            ->whereNotNull('fcm_token')
            ->get();

        foreach ($users as $user) {
            try {
                $message = CloudMessage::new()
                    ->withToken($user->fcm_token)
                    ->withNotification(Notification::create(
                        title: 'Estado del reporte cambió',
                        body: "{$report->category->name}: {$previousStatus} → {$report->status}"
                    ))
                    ->withData([
                        'report_id' => (string) $report->id,
                        'new_status' => $report->status,
                        'previous_status' => $previousStatus,
                    ]);

                $this->messaging->send($message);
            } catch (\Exception $e) {
                \Log::error("Failed to send status notification to user {$user->id}: " . $e->getMessage());
            }
        }
    }
}
