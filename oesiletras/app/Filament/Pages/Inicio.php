<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard;

/**
 * Lo primero que se ve al entrar al panel: cómo va el sitio público.
 *
 * Sustituye al Dashboard de fábrica, que solo traía la tarjeta de la cuenta y
 * la de Filament. Los widgets salen de `app/Filament/Widgets`.
 */
class Inicio extends Dashboard
{
    protected static ?string $title = 'Inicio';

    protected ?string $subheading = 'Visitas al sitio público. Se cuentan sin cookies ni direcciones IP, y sin robots.';
}
