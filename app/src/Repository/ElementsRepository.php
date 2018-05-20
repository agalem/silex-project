<?php

namespace Repository;


use Doctrine\DBAL\Connection;

class ElementsRepository {

	protected $db;

	public function __construct( Connection $db ) {
		$this->db = $db;
	}

	public function findAll() {
		$queryBuilder = $this->queryAll();

		return $queryBuilder->execute()->fetchAll();
	}


	public function findOneById( $id ) {
		$queryBuilder = $this->queryAll();
		$queryBuilder->where( 'e.id = :id' )
		             ->setParameter( ':id', $id, \PDO::PARAM_INT );
		$result = $queryBuilder->execute()->fetch();

		return !$result ? [] : $result;
	}

	public function findOneByName($name) {
		$queryBuilder = $this->queryAll();

		$queryBuilder->where('e.name = :name')
		             ->setParameter(':name', $name, \PDO::PARAM_STR);
		$result = $queryBuilder->execute()->fetch();

		return !$result ? [] : $result;
	}

	public function findById($ids) {
		$queryBuilder = $this->queryAll();
		$queryBuilder->where('e.id IN (:ids)')
		             ->setParameter(':ids', $ids, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);

		return $queryBuilder->execute()->fetchAll();
	}

	public function save($element)
	{
		/*if (isset($element['id']) && ctype_digit((string) $element['id'])) {
			// update record
			$id = $element['id'];
			unset($element['id']);

			return $this->db->update('elements', $element, ['id' => $id]);
		} else {
			// add new record
			$this->db->insert('elements', $element);
			$element['id'] = $this->db->lastInsertId();

			return $element;
		}*/
		$this->db->beginTransaction();

	}

	protected function addLinkedElements($listId, $elementsIds) {
		if(!is_array($elementsIds)) {
			$elementsIds = [$elementsIds];
		}
		foreach ($elementsIds as $elementId) {
			$this->db->insert(
				'elements_lists',
				[
					'element_id' => $elementId,
					'list_id' => $listId,
				]
			);
		}
	}

	protected function queryAll() {
		$queryBuilder = $this->db->createQueryBuilder();

		return $queryBuilder->select('e.id', 'e.name', 'e.value', 'e.quantity', 'e.isBought')
		                    ->from('elements', 'e');
	}
}