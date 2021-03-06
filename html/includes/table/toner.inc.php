<?php
/*
 * LibreNMS
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.  Please see LICENSE.txt at the top level of
 * the source code distribution for details.
 *
 * @package    LibreNMS
 * @subpackage webui
 * @link       http://librenms.org
 * @copyright  2017 LibreNMS
 * @author     LibreNMS Contributors
*/

$graph_type = 'toner_usage';

$sql = 'SELECT * FROM `toner` AS S, `devices` AS D WHERE S.device_id = D.device_id ORDER BY D.hostname, S.toner_descr';

$count_sql = "SELECT COUNT(`toner_id`) FROM `toner`";
$param[] = $_SESSION['user_id'];

$count     = dbFetchCell($count_sql, $param);
if (empty($count)) {
    $count = 0;
}

if (isset($current)) {
    $limit_low  = (($current * $rowCount) - ($rowCount));
    $limit_high = $rowCount;
}

if ($rowCount != -1) {
    $sql .= " LIMIT $limit_low,$limit_high";
}

foreach (dbFetchRows($sql, $param) as $toner) {
    if (device_permitted($toner['device_id'])) {
        $total = $toner['toner_capacity'];
        $perc  = $toner['toner_current'];

        $graph_array['type']        = $graph_type;
        $graph_array['id']          = $toner['toner_id'];
        $graph_array['from']        = $config['time']['day'];
        $graph_array['to']          = $config['time']['now'];
        $graph_array['height']      = '20';
        $graph_array['width']       = '80';
        $graph_array_zoom           = $graph_array;
        $graph_array_zoom['height'] = '150';
        $graph_array_zoom['width']  = '400';
        $link       = 'graphs/id='.$graph_array['id'].'/type='.$graph_array['type'].'/from='.$graph_array['from'].'/to='.$graph_array['to'].'/';
        $mini_graph = overlib_link($link, generate_lazy_graph_tag($graph_array), generate_graph_tag($graph_array_zoom), null);
        $bar_link   = print_percentage_bar(400, 20, $perc, "$perc%", 'ffffff', $background['left'], $free, 'ffffff', $background['right']);
        $background = get_percentage_colours(100 - $perc);

        $response[] = array(
            'hostname' => generate_device_link($toner),
            'toner_descr' => $toner['toner_descr'],
            'graph' => $mini_graph,
            'toner_used' => $bar_link,
            'toner_perc' => $perc.'%',
        );

        if ($vars['view'] == 'graphs') {
            $graph_array['height'] = '100';
            $graph_array['width']  = '216';
            $graph_array['to']     = $config['time']['now'];
            $graph_array['id']     = $toner['toner_id'];
            $graph_array['type']   = $graph_type;
            $return_data           = true;
            include 'includes/print-graphrow.inc.php';
            unset($return_data);
            $response[] = array(
                'hostname'      => $graph_data[0],
                'mempool_descr' => $graph_data[1],
                'graph'         => $graph_data[2],
                'mempool_used'  => $graph_data[3],
                'mempool_perc'  => '',
            );
        }
    }
}

$output = array(
    'current'  => $current,
    'rowCount' => $rowCount,
    'rows'     => $response,
    'total'    => $count,
);
echo _json_encode($output);
