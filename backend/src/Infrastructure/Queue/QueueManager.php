<?php

declare(strict_types=1);

namespace App\Infrastructure\Queue;

class QueueManager
{
    private string $queueDir;

    public function __construct(?string $queueDir = null)
    {
        $this->queueDir = $queueDir ?? dirname(__DIR__, 3) . '/storage/queue';
        if (!is_dir($this->queueDir)) {
            @mkdir($this->queueDir, 0777, true);
        }
    }

    /**
     * Encola una solicitud de matrícula para procesamiento asíncrono
     */
    public function encolar(string $cola, array $payload): string
    {
        if (!preg_match('/^[a-z0-9_]+$/D', $cola)) throw new \InvalidArgumentException('Cola inválida.');
        $jobId = 'job_' . $cola . '_' . bin2hex(random_bytes(16));
        $jobData = [
            'id' => $jobId,
            'queue' => $cola,
            'payload' => $payload,
            'status' => 'PENDING',
            'attempts' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if (!preg_match('/^job_[a-z0-9_]+$/D', $jobId)) throw new \InvalidArgumentException('Trabajo inválido.');
        $filePath = $this->queueDir . '/' . $jobId . '.json';
        if (file_put_contents($filePath, json_encode($jobData, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), LOCK_EX) === false) throw new \RuntimeException('No se pudo guardar el trabajo.');

        return $jobId;
    }

    /**
     * Obtiene el siguiente trabajo pendiente de la cola
     */
    public function obtenerSiguiente(string $cola): ?array
    {
        if (!preg_match('/^[a-z0-9_]+$/D', $cola)) throw new \InvalidArgumentException('Cola inválida.');
        $files = glob($this->queueDir . '/job_' . $cola . '_*.json');
        if (empty($files)) {
            return null;
        }

        // Ordenar por fecha de creación (FIFO)
        usort($files, fn($a,$b)=>filemtime($a)<=>filemtime($b));

        foreach ($files as $file) {
            $handle=fopen($file,'r+');
            if(!$handle) continue;
            try {
                if(!flock($handle,LOCK_EX)) continue;
                $data=json_decode(stream_get_contents($handle),true);
                if($data && ($data['status']??'')==='PENDING') {
                    $data['status']='PROCESSING'; $data['attempts']++;
                    rewind($handle); ftruncate($handle,0);
                    fwrite($handle,json_encode($data,JSON_THROW_ON_ERROR)); fflush($handle);
                    return $data;
                }
            } finally { flock($handle,LOCK_UN); fclose($handle); }
        }

        return null;
    }

    /**
     * Marca un trabajo como completado o fallido
     */
    public function actualizarEstado(string $jobId, string $status, ?string $error = null): void
    {
        $filePath = $this->queueDir . '/' . $jobId . '.json';
        if (file_exists($filePath)) {
            $data = json_decode(file_get_contents($filePath), true);
            $data['status'] = $status;
            if ($error) {
                $data['error'] = $error;
            }
            $data['processed_at'] = date('Y-m-d H:i:s');
            file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), LOCK_EX);
        }
    }
}
