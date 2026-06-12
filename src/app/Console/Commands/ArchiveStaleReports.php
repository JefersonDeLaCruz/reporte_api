<?php

namespace App\Console\Commands;

use App\Models\Report;
use Illuminate\Console\Command;

class ArchiveStaleReports extends Command
{
    protected $signature = 'reports:archive-stale';

    protected $description = 'Archiva reportes resueltos hace más de 2h (RF-18) y reportes pendientes/verificados sin interacción en 24h (RF-13)';

    public function handle(): int
    {
        $archived = Report::archiveStaleReports();

        $this->info(count($archived).' reporte(s) archivado(s).');

        return self::SUCCESS;
    }
}
