geotimeControllers.controller('TerritoryIdentificationController', ['$scope',
	function($scope) {
		$scope.locatedTerritories = [];
		$scope.hoveredTerritoryId = null;

		$scope.loadLocatedTerritories = function(mapDatum) {
			if (mapDatum.territories) {
				$scope.locatedTerritories = mapDatum.territories.filter(function (d) {
					return d.referencedTerritory;
				});
			}
		};

		$scope.editTerritory = function(territory) {
			if (territory.xpath) {
				selectedTerritory = svgMap.xpath(d.xpath);
			}
		};

		$scope.removeTerritory = function(territoryIndex) {
			$scope.locatedTerritories.splice(territoryIndex, 1);
			$scope.showLocatedTerritories();
		};

		$scope.showLocatedTerritories = function() {
			angular.forEach(locatedTerritoriesElements, function(locatedTerritory) {
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
			d3.select('#locatedTerritories')
				.append('li')
				.attr('id', 'addTerritorySection')
				.classed('list-group-item', true)
				.loadTemplate({
					name: 'addLocatedTerritory',
					callback: function() {
						territoryId = d3.select('#territoryId');
						territoryName = d3.select('#territoryName');
						territoryName.node().focus();

						autocomplete(d3.select('#territoryName').node())
							.dataField("name")
							.width(960)
							.height(500)
							.render();

						enableTerritorySelection();
					}
				});
		};

		$scope.toggleTerritoryLabelHighlight = function(territoryId, toggle) {
			$scope.hoveredTerritoryId = toggle ? territoryId : null;
		};

		$scope.loadLocatedTerritories($scope.$parent.$parent.mapInfo);
		$scope.initTerritorySelectionAndAutocomplete();
	}]
);