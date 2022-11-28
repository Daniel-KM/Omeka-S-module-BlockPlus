$(document).ready(function() {

$(function() {

    function myD3() {

        /**
         * Adapted from
         * @link https://www.brasilianaiconografica.art.br/explore/autores
         * @link https://observablehq.com/@d3/force-directed-graph-canvas?collection=@d3/d3-force
         */

        // TODO Compute these variables from the number of nodes.
        d3GraphConfig.height = d3GraphConfig.height ? d3GraphConfig.height : 800;
        d3GraphConfig.forceCharge = d3GraphConfig.forceCharge ? d3GraphConfig.forceCharge : -100;
        d3GraphConfig.forceLinkDistance = d3GraphConfig.forceLinkDistance ? d3GraphConfig.forceLinkDistance : 100;
        d3GraphConfig.baseCirclePow = d3GraphConfig.baseCirclePow ? d3GraphConfig.baseCirclePow : 0.6;
        d3GraphConfig.baseCircleMin = d3GraphConfig.baseCircleMin ? d3GraphConfig.baseCircleMin : 5;
        d3GraphConfig.fontSizeTop = d3GraphConfig.fontSizeTop ? d3GraphConfig.fontSizeTop : 35;
        d3GraphConfig.fontSizeMin = d3GraphConfig.fontSizeMin ? d3GraphConfig.fontSizeMin : '.1px';
        d3GraphConfig.fontSizeMax = d3GraphConfig.fontSizeMax ? d3GraphConfig.fontSizeMax : '16px';

        var width = $('#d3-graph').width();
        var height = d3GraphConfig.height;

        var svg = d3.select('#d3-graph')
            .append('svg')
            .attr('width', width)
            .attr('height', height)
            /*
            .call(d3.behavior.zoom().on('zoom', function () {
                svg.attr('transform', 'translate(' + d3.event.translate + ')' + ' scale(' + d3.event.scale + ')');
            }))
            */
            .append('g');

        var force = d3.layout.force()
            .charge(d3GraphConfig.forceCharge)
            .linkDistance(d3GraphConfig.forceLinkDistance)
            .size([width, height]);

        var graph = d3GraphData;
        //d3.json(d3GraphData, function(error, graph) {

            // if (error) throw error;

            force
                .nodes(graph.nodes)
                .links(graph.links)
                .start();

            var link = svg.selectAll('.link')
                .data(graph.links)
                .enter()
                .append('line')
                .attr('class', 'link')
                .style('stroke-width', function(d) { return Math.sqrt(d.value); });

            var node = svg.selectAll('.node')
                .data(graph.nodes)
                .enter()
                .append('g')
                .attr('class', function(d) { return 'node ' + d.type; })
                .call(force.drag);
            var nodeResource = node
                .filter(function(d) { return d.id > 0; });
            var nodeValue = node
                .filter(function(d) { return !d.id; });

            node.append('circle')
                .attr('class', 'node cluster')
                .attr('r', function(d) { return Math.pow(d.total, d3GraphConfig.baseCirclePow) + d3GraphConfig.baseCircleMin; });

            nodeResource.append('svg:a')
                .attr('xlink:href', function(d) { return d3GraphbaseUrl + '/' + d.type + '/' + d.id; })
                .append('text')
                .attr('dy', '.35em')
                .text(function(d) { return d.title; })
                .style('text-anchor', 'middle')
                .style('font-size', function(d) {
                    return ['item-set', 'resource-class', 'resource-template', 'value'].indexOf(d.type) >= 0 || (d.total > d3GraphConfig.fontSizeTop)
                        ? d3GraphConfig.fontSizeMax
                        : d3GraphConfig.fontSizeMin;
                });

            nodeValue.append('svg:a')
                .append('text')
                .attr('dy', '.35em')
                .text(function(d) { return d.title; })
                .style('text-anchor', 'middle')
                .style('font-size', function(d) {
                    return ['item-set', 'resource-class', 'resource-template', 'value'].indexOf(d.type) >= 0 || (d.total > d3GraphConfig.fontSizeTop)
                        ? d3GraphConfig.fontSizeMax
                        : d3GraphConfig.fontSizeMin;
                });

            force.on('tick', function() {
                link
                    .attr('x1', function(d) { return d.source.x; })
                    .attr('y1', function(d) { return d.source.y; })
                    .attr('x2', function(d) { return d.target.x; })
                    .attr('y2', function(d) { return d.target.y; });
                node.attr('transform', function(d) { return 'translate(' + d.x + ',' + d.y + ')'; });
                // node.attr('cx', function(d) { return d.x; }).attr('cy', function(d) { return d.y; });
            });

        // });

        $('#d3-graph').removeClass('bg-loading');

    }

    myD3();

});

});
