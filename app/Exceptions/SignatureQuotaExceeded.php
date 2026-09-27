<?php

namespace App\Exceptions;

use DomainException;

/**
 * A tenant tried to sell a signature it has no quota for: no prepaid units
 * of the product left, or the unit price exceeds its available credit (or
 * the account is inactive / missing).
 */
class SignatureQuotaExceeded extends DomainException
{
    public static function noAccount(): self
    {
        return new self('Tu empresa aún no está habilitada para vender firmas electrónicas. Contacta al administrador de la plataforma.');
    }

    public static function inactiveAccount(): self
    {
        return new self('La venta de firmas está suspendida para tu empresa. Contacta al administrador de la plataforma.');
    }

    public static function noUnits(string $productName): self
    {
        return new self("No tienes firmas disponibles de «{$productName}». Adquiere un nuevo paquete para continuar vendiendo.");
    }

    public static function creditExhausted(string $available, string $price): self
    {
        return new self("Tu cupo de crédito disponible (\${$available}) no cubre el valor de la firma (\${$price}). Realiza un abono para continuar vendiendo.");
    }
}
