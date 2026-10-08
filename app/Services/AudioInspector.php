<?php

namespace App\Services;

/**
 * Inspeção de áudio (Sprint 5.6.2). Só metadata — nunca edição.
 * Produção usa FFprobe; testes injetam fake explícito.
 */
interface AudioInspector
{
    /**
     * Retorna null quando o arquivo não é um áudio válido.
     */
    public function inspect(string $path): ?AudioMetadata;
}
