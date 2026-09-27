<?php

namespace App\Models;

use CodeIgniter\Model;
use RuntimeException;

/**
 * Basis voor alle verhuizing-data (handoff.md §6). Elke lees-, tel-, update- en
 * delete-actie via het model filtert automatisch op de actieve verhuizing; een insert
 * krijgt die verhuizing_id vanzelf. Zonder actieve verhuizing: exception (fail closed).
 *
 * Wie echt over verhuizingen heen moet kijken (sticker-token opzoeken), doet dat
 * expliciet met db_connect() — nooit via deze models.
 */
abstract class ScopedModel extends Model
{
    protected $beforeInsert = ['setVerhuizing'];

    protected function currentVerhuizingId(): int
    {
        helper('access');
        $id = access()->verhuizingId();
        if (! $id) {
            throw new RuntimeException('Geen actieve verhuizing voor ' . static::class);
        }

        return $id;
    }

    private function applyScope(): void
    {
        $this->builder()->where($this->table . '.verhuizing_id', $this->currentVerhuizingId());
    }

    protected function setVerhuizing(array $data): array
    {
        $data['data']['verhuizing_id'] = $this->currentVerhuizingId();

        return $data;
    }

    public function find($id = null)
    {
        $this->applyScope();

        return parent::find($id);
    }

    public function findAll(?int $limit = null, int $offset = 0)
    {
        $this->applyScope();

        return parent::findAll($limit, $offset);
    }

    public function first()
    {
        $this->applyScope();

        return parent::first();
    }

    public function update($id = null, $row = null): bool
    {
        $this->applyScope();

        return parent::update($id, $row);
    }

    public function delete($id = null, bool $purge = false)
    {
        $this->applyScope();

        return parent::delete($id, $purge);
    }

    public function countAllResults(bool $reset = true, bool $test = false)
    {
        $this->applyScope();

        return parent::countAllResults($reset, $test);
    }

    /** Builder::countAll() negeert where-clausules — hier dus altijd gescoped tellen. */
    public function countAll(): int
    {
        return (int) $this->countAllResults();
    }
}
