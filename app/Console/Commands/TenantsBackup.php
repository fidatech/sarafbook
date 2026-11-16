<?php

namespace App\Console\Commands;

use App\Models\Central\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class TenantsBackup extends Command
{
    protected $signature = 'tenants:backup {--tenant=* : Short names; default every Sarafi} {--keep=30 : Days of backups to keep}';

    protected $description = 'Dump each Sarafi database to storage/backups/<slug>/ (needs mysqldump and gzip on the server)';

    public function handle(): int
    {
        $failed = 0;
        $connection = config('database.connections.tenant');
        $query = Tenant::query()->orderBy('id');
        if ($this->option('tenant')) {
            $query->whereIn('slug', $this->option('tenant'));
        }

        foreach ($query->get() as $tenant) {
            $dir = storage_path('backups/'.$tenant->slug);
            @mkdir($dir, 0700, true);
            $file = $dir.'/'.$tenant->database.'-'.now()->format('Y-m-d-His').'.sql.gz';

            // the password travels in the environment, never on the command line
            $result = Process::timeout(3600)
                ->env(['MYSQL_PWD' => (string) $connection['password']])
                ->run(sprintf(
                    'mysqldump --single-transaction --quick --routines --no-tablespaces -h %s -P %s -u %s %s | gzip > %s',
                    escapeshellarg((string) $connection['host']), escapeshellarg((string) $connection['port']),
                    escapeshellarg((string) $connection['username']), escapeshellarg($tenant->database), escapeshellarg($file)
                ));

            if (! $result->successful() || ! is_file($file) || filesize($file) < 100) {
                $failed++;
                @unlink($file);
                $this->error($tenant->slug.': '.trim($result->errorOutput() ?: 'backup failed'));

                continue;
            }
            @chmod($file, 0600);
            $this->line('<info>'.$tenant->slug.'</info>  '.basename($file).'  '.round(filesize($file) / 1024).' KB');

            $cutoff = now()->subDays((int) $this->option('keep'))->getTimestamp();
            foreach (glob($dir.'/*.sql.gz') ?: [] as $old) {
                if (filemtime($old) < $cutoff) {
                    @unlink($old);
                }
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
