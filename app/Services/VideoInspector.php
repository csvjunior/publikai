<?php

namespace App\Services;

/**
 * Inspeção de vídeo (Sprint 5.6.0). Só metadata — nunca edição/composição.
 * Produção usa FFprobe; testes injetam fake explícito.
 */
interface VideoInspector
{
    /**
     * Retorna null quando o arquivo não é um vídeo válido.
     */
    public function inspect(string $path): ?VideoMetadata;
}
