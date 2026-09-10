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
        $jobId = uniqid('job_' . $cola . '_', true);
        $jobData = [
            'id' => $jobId,
            'queue' => $cola,
            'payload' => $payload,
            'status' => 'PENDING',
            'attempts' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $filePath = $this->queueDir . '/' . $jobId . '.json';
        file_put_contents($filePath, json_encode($jobData, JSON_PRETTY_PRINT));

        return $jobId;
    }

    /**
     * Obtiene el siguiente trabajo pendiente de la cola
     */
    public function obtenerSiguiente(string $cola): ?array
    {
        $files = glob($this->queueDir . '/job_' . $cola . '_*.json');
        if (empty($files)) {
            return null;
        }

        // Ordenar por fecha de creación (FIFO)
        sort($files);

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $data = json_decode($content, true);
            if ($data && ($data['status'] ?? '') === 'PENDING') {
                return $data;
            }
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
            file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
        }
    }
}
