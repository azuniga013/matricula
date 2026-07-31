<?php

namespace App\Database\Schema\Grammars;

use Illuminate\Database\Schema\Grammars\PostgresGrammar;

class PostgresSinTransaccionesGrammar extends PostgresGrammar
{
    /**
     * El proveedor PostgreSQL de produccion aborta las transacciones que agrupan DDL.
     * Solo se utiliza desde comandos de consola para ejecutar migraciones.
     *
     * @var bool
     */
    protected $transactions = false;
}
