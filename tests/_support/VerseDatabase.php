<?php

namespace Tests\Support;

/**
 * Gooit alle tabellen in de test-database weg en laadt sql/schema.sql opnieuw in.
 * Voor de lektests: elke test begint met een lege, actuele database.
 */
trait VerseDatabase
{
    protected function laadSchema(): void
    {
        $db = db_connect();
        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($db->listTables() as $table) {
            $db->query('DROP TABLE `' . $table . '`');
        }
        $db->query('SET FOREIGN_KEY_CHECKS = 1');
        $db->resetDataCache();

        $sql = file_get_contents(ROOTPATH . 'sql/schema.sql');
        foreach (array_filter(array_map('trim', explode(';', preg_replace('/^--.*$/m', '', $sql)))) as $statement) {
            $db->query($statement);
        }
    }
}
