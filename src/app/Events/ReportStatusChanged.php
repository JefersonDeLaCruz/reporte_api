<?php

namespace App\Events;

use App\Models\Report;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Report $report,
        public string $previousStatus
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('reports'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'report.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->report->id,
            'status' => $this->report->status,
            'previous_status' => $this->previousStatus,
            'latitude' => (float) $this->report->latitude,
            'longitude' => (float) $this->report->longitude,
            'votes_confirm' => $this->report->votes_confirm,
            'votes_resolve' => $this->report->votes_resolve,
            'updated_at' => $this->report->updated_at->toIso8601String(),
        ];
    }
}
