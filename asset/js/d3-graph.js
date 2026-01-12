$(document).ready(function() {

    /**
     * Force-directed graph using D3 v7.9.
     *
     * @link https://observablehq.com/@d3/force-directed-graph
     */
    function myD3() {
        var graph = d3GraphData;

        // Check for empty graph.
        if (!graph.nodes || !graph.nodes.length) {
            $('#d3-graph').removeClass('bg-loading').text('No data available.');
            return;
        }

        // Config with defaults.
        var config = Object.assign({
            height: 800,
            forceCharge: -100,
            forceLinkDistance: 100,
            baseCirclePow: 0.6,
            baseCircleMin: 5,
            fontSizeTop: 35,
            fontSizeMin: '.1px',
            fontSizeMax: '16px'
        }, d3GraphConfig || {});

        var container = d3.select('#d3-graph');
        var width = container.node().clientWidth;
        var height = config.height;

        var svg = container
            .append('svg')
            .attr('width', width)
            .attr('height', height)
            .append('g');

        // Force simulation.
        var simulation = d3.forceSimulation(graph.nodes)
            .force('link', d3.forceLink(graph.links).distance(config.forceLinkDistance))
            .force('charge', d3.forceManyBody().strength(config.forceCharge))
            .force('center', d3.forceCenter(width / 2, height / 2))
            .force('collision', d3.forceCollide().radius(function(d) {
                return Math.pow(d.total, config.baseCirclePow) + config.baseCircleMin + 2;
            }));

        // Links.
        var link = svg.selectAll('.link')
            .data(graph.links)
            .enter()
            .append('line')
            .attr('class', 'link')
            .style('stroke-width', function(d) { return Math.sqrt(d.value); });

        // Nodes.
        var node = svg.selectAll('.node')
            .data(graph.nodes)
            .enter()
            .append('g')
            .attr('class', function(d) { return 'node ' + d.type; });

        // Circle for each node.
        node.append('circle')
            .attr('class', 'cluster')
            .attr('r', function(d) { return Math.pow(d.total, config.baseCirclePow) + config.baseCircleMin; });

        // Helper to determine font size.
        var getFontSize = function(d) {
            var isLargeType = ['item-set', 'resource-class', 'resource-template', 'value'].indexOf(d.type) >= 0;
            return (isLargeType || d.total > config.fontSizeTop) ? config.fontSizeMax : config.fontSizeMin;
        };

        // Text labels with links for resources.
        node.append('a')
            .attr('href', function(d) { return d.id ? d3GraphbaseUrl + '/' + d.type + '/' + d.id : null; })
            .append('text')
            .attr('dy', '.35em')
            .text(function(d) { return d.title; })
            .style('text-anchor', 'middle')
            .style('font-size', getFontSize);

        // Drag behavior.
        node.call(d3.drag()
            .on('start', function(event, d) {
                if (!event.active) simulation.alphaTarget(0.3).restart();
                d.fx = d.x;
                d.fy = d.y;
            })
            .on('drag', function(event, d) {
                d.fx = event.x;
                d.fy = event.y;
            })
            .on('end', function(event, d) {
                if (!event.active) simulation.alphaTarget(0);
                d.fx = null;
                d.fy = null;
            }));

        // Update positions on tick.
        simulation.on('tick', function() {
            link
                .attr('x1', function(d) { return d.source.x; })
                .attr('y1', function(d) { return d.source.y; })
                .attr('x2', function(d) { return d.target.x; })
                .attr('y2', function(d) { return d.target.y; });
            node.attr('transform', function(d) { return 'translate(' + d.x + ',' + d.y + ')'; });
        });

        container.classed('bg-loading', false);
    }

    myD3();

});
