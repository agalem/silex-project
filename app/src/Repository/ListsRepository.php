<?php

namespace Repository;

use Doctrine\DBAL\Connection;

class ListsRepository {

	protected $db;

	public function __construct(Connection $db) {
		$this->db = $db;
		$this->elementsRepository = new ElementsRepository($db);
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

		if ($result) {
			$result['elements'] = $this->findLinkedElements($result['id']);
		}

		return $result;
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


	public function updateModiefiedDate($listId) {
		$currentDateTime = new \DateTime();
		$list['modifiedAt'] = $currentDateTime->format('Y-m-d H:i:s');
		$this->db->update('lists', $list, ['id' => $listId]);
	}

	public function delete($list) {
		return $this->db->delete('lists', ['id' => $list['id']]);
	}


	public function findLinkedElements($listId)
	{
		$elementsIds = $this->findLinkedElementsIds($listId);

		return is_array($elementsIds)
			? $this->elementsRepository->findById($elementsIds)
			: [];
	}

	protected function findLinkedElementsIds($listId) {
		$queryBuilder = $this->db->createQueryBuilder()
			->select('el.element_id')
			->from('elements_lists', 'el')
			->where('el.list_id = :listId')
			->setParameter(':listId', $listId, \PDO::PARAM_INT);
		$result = $queryBuilder->execute()->fetchAll();

		return isset($result) ? array_column($result, 'element_id') : [];
	}

	protected function removeLinkedElements($listId) {
		return $this->db->delete('elements_lists', ['list_id' => $listId]);
	}

	protected function queryAll() {
		$queryBuilder = $this->db->createQueryBuilder();

		return $queryBuilder->select('l.id', 'l.name', 'l.maxCost')
			->from('lists', 'l');
	}

}