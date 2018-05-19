<?php

namespace Repository;

use Doctrine\DBAL\Connection;

class ListsRepository {

	protected $db;

	public function __construct(Connection $db) {
		$this->db = $db;
	}

	public function findAll() {
		$queryBuilder = $this->queryAll();

		return $queryBuilder->execute()->fetchAll();
	}

	public function findOneById($id) {
		$queryBuilder = $this->queryAll();
		$queryBuilder->where('l.id = :id')
			->setParameter(':id', $id, \PDO::PARAM_INT);
		$result = $queryBuilder->execute()->fetch();

		return  !$result ? [] : $result;
	}

	public function save($list) {
		if(isset($list['id']) && ctype_digit((string) $list['id'])) {
			$id = $list['id'];
			unset($list['id']);

			return $this->db->update('lists', $list, ['id' => $id]);
		} else {
			return $this->db->insert('lists', $list);
		}
	}

	public function delete($list) {
		return $this->db->delete('lists', ['id' => $list['id']]);
	}

	protected function queryAll() {
		$queryBuilder = $this->db->createQueryBuilder();

		return $queryBuilder->select('l.id', 'l.name', 'l.maxCost')
			->from('lists', 'l');
	}

}