<?php
namespace App\DataServices;

interface RepoInterface
{
    /**
     * Get all
     * @return mixed
     */
    public function getAll();

    /**
     * Get one
     * @param $id
     * @return mixed
     */
    public function find($id);

    public function findWhere(array $where, $columns = ['*']);

    /**
     * Create
     * @param array $attributes
     * @return mixed
     */
    public function create(array $attributes);

    public function firstOrNew(array $columns);

    /**
     * Update
     * @param $id
     * @param array $attributes
     * @return mixed
     */
    public function update($id, array $attributes);

    public function updateWhere(array $where, array $attributes);

    /**
     * Delete
     * @param $id
     * @return mixed
     */
    public function delete($id);
}
