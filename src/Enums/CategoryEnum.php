<?php

namespace Ultra\UltraConfigManager\Enums;

enum CategoryEnum: string
{
    case System = 'system';
    case Application = 'application';
    case Security = 'security';
    case Performance = 'performance';
    case None = ''; // Per rappresentare l'assenza di categoria (opzionale)
}