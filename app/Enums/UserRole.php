<?php

namespace App\Enums;

/**
 * Funções de usuário do Publikai (Sprint 0.1).
 *
 * Escopo intencionalmente mínimo: apenas dois papéis internos.
 * Autorização mais granular será expandida em sprints futuras via Gates/Policies.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Operator = 'operator';
}
