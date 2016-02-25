geotimeControllers.controller('TerritoryIdentificationController', ['$scope', '$filter',
	function($scope,$filter) {
		$scope.locatedTerritories = [];

		$scope.hoveredTerritory = null;
		$scope.selectedTerritory = null;

		$scope.loadLocatedTerritories = function(mapDatum) {
			if (mapDatum.territories) {
				$scope.locatedTerritories = mapDatum.territories.filter(function (d) {
					return d.referencedTerritory;
				});
			}
		};

		$scope.editTerritory = function(territory) {
			if (territory) {
				if (territory.id) {
					$scope.selectedTerritory = territory;
				}
			}
			else {
				$scope.selectedTerritory = $scope.hoveredTerritory;
				$scope.clearHoveredTerritory();
			}
			svgMap.xpath($scope.selectedTerritory.xpath)
				.animateTerritoryPathOff()
				.animateTerritoryPathOn('in', 500);
		};

		$scope.clearHoveredTerritory = function() {
			svgMap.xpath($scope.hoveredTerritory.xpath).animateTerritoryPathOff();
			$scope.hoveredTerritory = null;
		};

		$scope.clearSelectedTerritory = function() {
			svgMap.xpath($scope.selectedTerritory.xpath).animateTerritoryPathOff();
			$scope.selectedTerritory = null;
		};

		$scope.hideNewTerritoryForm = function() {
			$scope.clearSelectedTerritory();
		};

		$scope.removeTerritory = function(territoryIndex) {
			$scope.locatedTerritories.splice(territoryIndex, 1);
			$scope.showLocatedTerritories();
		};

		$scope.showLocatedTerritories = function() {
			angular.forEach($scope.locatedTerritories, function(locatedTerritory) {
				if (locatedTerritory.xpath) {
					var territoryElement = svgMap.xpath(locatedTerritory.xpath);
					if (territoryElement.empty()) {
						console.warn('Could not locate territory with XPath '+ locatedTerritory.xpath);
					}
					else {
						territoryElement
							.classed('already-identified', true)
							.datum(locatedTerritory);
					}
				}
			});
		};

		$scope.initTerritorySelectionAndAutocomplete = function() {
			territoryId = d3.select('#territoryId');
			territoryName = d3.select('#territoryName');
			territoryName.node().focus();

			autocomplete(d3.select('#territoryName').node())
				.dataField("name")
				.width(960)
				.height(500)
				.render();

			enableTerritorySelection();
		};

		$scope.disableTerritorySelection = function () {
			disableTerritorySelection();
			$scope.clearSelectedTerritory();
		};

		$scope.toggleTerritoryHighlight = function(element, toggle) {
			if (!$scope.selectedTerritory) {
				$scope.hoveredTerritory = element.datum() || { };
				$scope.hoveredTerritory.xpath = element.xpath();

				if (toggle) {
					svgMap.xpath($scope.hoveredTerritory.xpath).animateTerritoryPathOn('in', 1000);
				}
				else {
					svgMap.xpath($scope.hoveredTerritory.xpath).animateTerritoryPathOff();
					$scope.hoveredTerritory = null;
				}
			}
		};

		$scope.getTerritoryLabel = function(territory) {
			return territory
				   && ((territory.referencedTerritory && territory.referencedTerritory.name)
				     || territory.xpath
				);
		};

		$scope.getCurrentTerritoryLabel = function() {
			return $scope.getTerritoryLabel($scope.selectedTerritory)
				|| $scope.getTerritoryLabel($scope.hoveredTerritory)
				|| 'None';
		};

		$scope.addTerritory = function() {
			if (! $filter('filter')($scope.locatedTerritories, $scope.selectedTerritory, true).length) {
				$scope.locatedTerritories.push($scope.selectedTerritory);
			}
			$scope.showLocatedTerritories();
			$scope.hideNewTerritoryForm();
		};

		$scope.getMapInfo = function() {
			return $scope.$parent.$parent.mapInfo;
		};

		$scope.validate = function() {
			if ($scope.locatedTerritories.length) {
				validateTerritories(
					$scope.getMapInfo().id,
					$scope.locatedTerritories.map(function(locatedTerritory) {
						delete locatedTerritory.polygon;
						delete locatedTerritory.initialFill;
						return locatedTerritory;
					})
				);
			}
			else {
				alert('No territory has been identified on the map');
			}
		};

		$scope.loadLocatedTerritories($scope.getMapInfo());
		$scope.showLocatedTerritories();
		hideBackgroundMapIfNotCalibrated($scope.getMapInfo());
		showMapsSuperimposed($scope.getMapInfo());
		$scope.initTerritorySelectionAndAutocomplete();

		$scope.$on('$destroy', function() {
			$scope.disableTerritorySelection();
		})
	}]
);