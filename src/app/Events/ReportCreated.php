<?php

namespace App\Events;

use App\Models\Report;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReportCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Report $report)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('reports'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'report.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->report->id,
            'latitude' => (float) $this->report->latitude,
            'longitude' => (float) $this->report->longitude,
            'status' => $this->report->status,
            'description' => $this->report->description,
            'category_id' => $this->report->category_id,
            'user_id' => $this->report->user_id,
            'votes_confirm' => $this->report->votes_confirm,
            'votes_resolve' => $this->report->votes_resolve,
            'photo_path' => $this->report->photo_path,
            'created_at' => $this->report->created_at->toIso8601String(),
        ];
    }
}
