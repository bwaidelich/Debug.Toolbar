<?php
namespace Debug\Toolbar\Debugger;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Debug.Toolbar".         *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 */
class SqlDebugger {

	/**
	 * TODO: Document this Method!
	 */
	public function preToolbarRendering() {
		$times = \Debug\Toolbar\Service\DataStorage::get('SqlLogger:Times');
		if (!is_array($times)) {
			$times = array();
		}

		$queries = \Debug\Toolbar\Service\DataStorage::get('SqlLogger:Queries');
		$origins = \Debug\Toolbar\Service\DataStorage::get('SqlLogger:Origins');
		$params = \Debug\Toolbar\Service\DataStorage::get('SqlLogger:Params');
		$types = \Debug\Toolbar\Service\DataStorage::get('SqlLogger:Types');
		$merged = array();
		if (is_array($queries)) {
			foreach ($queries as $key => $value) {
				$origin = $origins[$key];
				if (isset($origin['line'])) {
					$origin = (((('Called from: ' . $origin['class']) . $origin['type']) . $origin['function']) . ' on line ') . $origin['line'];
				} else {
					$origin = '';
				}
				$merged[$key] = array(
					'query' => $value,
					'time' => isset($times[$key]) ? number_format($times[$key] * 1000, 2) : '-',
					'origin' => $origin,
					'params' => $params[$key],
					'types' => $types[$key]
				);
			}
		}
		$merged = $this->formatQueries($merged);

		\Debug\Toolbar\Service\Collector::getModule('Sql')
			->getToolbar()
			->addIcon('hdd')
			->addBadge(count($queries))
			->getPopup()
			->addPartial('Sql/Statistic', array(
			'time' => array_sum($times),
			'queries' => $merged,
			'queriesCount' => count($queries)
		))
			->getPanel()
			->addPartial('Sql/Queries', array(
			'time' => array_sum($times),
			'queries' => $merged,
			'queriesCount' => count($queries)
		));
	}

	/**
	 * TODO: Document this Method!
	 */
	public function formatQueries($merged) {
		if (empty($merged)) {
			return $merged;
		}
		foreach ($merged as $key => $value) {
			$params = $value['params'];
			$paramIndex = 0;
			$query = preg_replace_callback('/\?/', function() use ($params, &$paramIndex) {
				$paramValue = $params[$paramIndex];
				$paramIndex ++;
				return '"' . $paramValue . '"';
			}, $value['query']);
			$query = \SqlFormatter::format($query);
			$merged[$key]['query'] = $query;
		}
		return $merged;
	}

}

?>