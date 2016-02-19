geotimeControllers.controller('TerritoryIdentificationController', ['$scope',
	function($scope) {
		$scope.locatedTerritories = [];
		$scope.hoveredTerritoryId = null;
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
					selectedTerritory = svgMap.xpath($scope.selectedTerritory.xpath);
				}
				else {
					$scope.selectedTerritory = {};
					selectedTerritory = hoveredTerritory;
				}
				selectedTerritory
					.animateTerritoryPathOff()
					.animateTerritoryPathOn('in', 500);
			}
		};

		$scope.hideNewTerritoryForm = function() {
			$scope.selectedTerritory = null;
			selectedTerritory.animateTerritoryPathOff();
			clearHoveredAndSelectedTerritories();
			$scope.initTerritorySelectionAndAutocomplete();
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
		};

		$scope.toggleTerritoryLabelHighlight = function(territoryId, toggle) {
			$scope.hoveredTerritoryId = toggle ? territoryId : null;
		};

		$scope.addTerritory = function() {
			var territoryToEdit = {
				element: selectedTerritory,
				startDate: $scope.selectedTerritory.territoryPeriodStart,
				endDate: $scope.selectedTerritory.territoryPeriodEnd,
				referencedTerritory: {
					id: territoryName.datum().territoryId,
					name: $scope.selectedTerritory.referencedTerritory.name
				}
			};

			var isUpdate = false;
			angular.forEach($scope.locatedTerritories, function(locatedTerritory) {
				if (locatedTerritory.referencedTerritory.id === territoryToEdit.referencedTerritory.id) {
					locatedTerritory.element = territoryToEdit.element;
					locatedTerritory.startDate = territoryToEdit.startDate;
					locatedTerritory.endDate = territoryToEdit.endDate;
					locatedTerritory.referencedTerritory = territoryToEdit.referencedTerritory;
					isUpdate = true;
				}
			});
			if (!isUpdate) {
				locatedTerritories.push(territoryToEdit);
			}
			$scope.showLocatedTerritories();
			$scope.hideNewTerritoryForm();
		};

		$scope.getMapInfo = function() {
			return $scope.$parent.$parent.mapInfo;
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