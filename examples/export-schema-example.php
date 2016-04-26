<?php

// Example script to implement the JSON Export Schema as defined by:
//   https://github.com/euro-ix/json-schemas
//
// Everyone's database schema will be different so this is just an example
// which:
//
// - Uses IXP Manager's schema as an example
// - Is written as a single PHP script with minimum requirements
// - Is hopefully easy to follow and adapt
//
// The output is compatible with v0.6 of the schema
//
// Revision History:
//
// 20160426 - Barry O'Donovan <barry.odonovan (at) inex.ie>


// We use PDO so we can support many databases rather than just MySQL.
// See http://php.net/manual/en/pdo.connections.php
$db = new PDO('mysql:host=localhost;dbname=ixp;charset=utf8mb4', 'root', 'password');

// The schema is made up of different sections so we build those up as an
// array piece by piece. Edit the functions we call here for your own
// data / schemas

// normalise times to UTC for exports
date_default_timezone_set('UTC');

echo json_encode( array(

        'version'      => '0.6',
        'timestamp'    => date( 'Y-m-d', time() ) . 'T' . date( 'H:i:s', time() ) . 'Z',
        
        'ixp_list'     => array( array(
                        
            'shortname' => 'EIX',
            'name'      => 'Example Internet Exchange',
            'country'   => 'CC',
            'url'       => 'https://www.example.com/',
            
            // IX-F ID is the numeric index of the IXP in the IX Federation database.
            // This is defined as the id of the IXP in the online IX-F database which
            // can be found at the following endpoint: http://db.ix-f.net/api/ixp.
            // Note that this is a persistent index keyed on an external database source.
            'ixf_id'    => 0,
            
            // ixp_id - a numeric IXP identifier. Where possible, this index number
            // should be consistent across exports. However as the index is primarily
            // intended to be used as an internal index within the schema, it is not
            // required to be consistent across exports.
            'ixp_id'    => 1,
            
            'vlan'        => getVLANs($db),
            'switch'      => getSwitches($db),
            'member_list' => getMembers($db),
        ) )
    ),
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
) . "\n";



// Function to get VLAN information.
// This shows a database query but you could just as easily hardcode an array here
// if you're a small IXP.
function getVLANs($db) {
    $vlanquery = $db->query(
        "SELECT v.id AS id, v.name AS name
            FROM vlan AS v
            WHERE v.private=0"
    );
    
    // prepared query for VLAN information
    $vlaninfo = $db->prepare(
        "SELECT ni.network AS prefix, ni.masklen AS masklen
            FROM networkinfo AS ni
            WHERE ni.vlanid = ? AND ni.protocol = ?"
    );

    $vlans = array();

    foreach( $vlanquery->fetchAll(PDO::FETCH_ASSOC) as $v ) {
        $vlan['id']   = $v['id'];
        $vlan['name'] = $v['name'];

        foreach( [4,6] as $p ) {
            $vlaninfo->bindValue(1,$v['id']);
            $vlaninfo->bindValue(2,$p);
            $vlaninfo->execute();
            if( $query = $vlaninfo->fetchAll(PDO::FETCH_ASSOC) ) {
                $vlan['ipv'.$p] = array(
                    'prefix'   => $query[0]['prefix'],
                    'masklen'  => $query[0]['masklen']
                );
            }
        }
        
        $vlans[] = $vlan;
    }

    return $vlans;
}

// Function to get switch information.
// This shows a database query but you could just as easily hardcode an array here
// if you're a small IXP.
function getSwitches($db) {
    $switchquery = $db->query(
        "SELECT s.id AS id, s.name AS name
            FROM switch AS s
            WHERE s.switchtype = 1 AND s.active = 1"
    );
    
    $switches = array();

    foreach( $switchquery->fetchAll(PDO::FETCH_ASSOC) as $s ) {
        $switches[] = array(
            'id'              => $s['id'],
            'name'            => $s['name'],
            'colo'            => "Datacentre Name",
            'city'            => "City",
            'country'         => "CC",
            
            // PeeringDB facility ID
            // https://www.peeringdb.com/api/fac
            'pdb_facility_id' => 0
        );
    }
    
    return $switches;
}

// Function to get member information.
function getMembers($db) {
    // Database query to find all appropriate customers for export.
    // Our query looks for connected customers who have not left.
    $members = $db->query(
        "SELECT c.id            AS id,
                c.autsys        AS asnum,
                c.name          AS name,
                c.corpwww       AS url,
                c.peeringemail  AS contact_email,
                c.nocphone      AS contact_phone,
                c.nochours      AS contact_hours,
                c.peeringpolicy AS peering_policy,
                c.nocwww        AS peering_policy_url,
                c.datejoin      AS member_since,
                c.type          AS type
                
            FROM
                cust AS c
            WHERE
                c.datejoin <= CURRENT_DATE()
                    AND ( c.dateleave IS NULL OR c.dateleave = '0000-00-00' OR c.dateleave >= CURRENT_DATE() )
                AND c.status = 1
                AND c.type != 2
        "
    );

    // Prepared query for finding all virtual interfaces for a customer.
    // A virtual interface in IXP Manager represents one or more physical
    // interfaces (where >1 means a LAG port) and one or more VLAN interfaces.
    $virtualInterfaceQuery = $db->prepare(
        "SELECT vi.id         AS vid
            FROM
                cust AS c
                LEFT JOIN virtualinterface  AS vi ON c.id = vi.custid
            WHERE
                c.id = ?"
    );

    // Prepared query to find the physical interface(s) in a virtual interface.
    $physicalInterfaceQuery = $db->prepare(
        "SELECT s.id          AS switch_id,
                pi.speed      AS if_speed
            FROM
                virtualinterface       AS vi
                LEFT JOIN physicalinterface AS pi ON vi.id = pi.virtualinterfaceid
                LEFT JOIN switchport        AS sp ON pi.switchportid = sp.id
                LEFT JOIN switch            AS s  ON s.id = sp.switchid
            WHERE
                vi.id = ?
                AND pi.status = 1"
    );

    // Prepared query to find the VLAN interface(s) details - i.e. the IP address, etc.
    $vlanInterfaceQuery = $db->prepare(
        "SELECT v.id                    AS vlan_id,
                ip4.address             AS ipv4address,
                ip6.address             AS ipv6address,
                vli.ipv4enabled         AS v4enabled,
                vli.ipv6enabled         AS v6enabled,
                vli.maxbgpprefix        AS maxprefix,
                c.peeringmacro          AS v4macro,
                c.peeringmacrov6        AS v6macro,
                vli.rsclient            AS rsclient
            FROM
                virtualinterface            AS vi
                LEFT JOIN cust              AS c   ON vi.custid = c.id
                LEFT JOIN vlaninterface     AS vli ON vli.virtualinterfaceid = vi.id
                LEFT JOIN vlan              AS v   ON vli.vlanid = v.id
                LEFT JOIN ipv4address       AS ip4 ON vli.ipv4addressid = ip4.id
                LEFT JOIN ipv6address       AS ip6 ON vli.ipv6addressid = ip6.id
            WHERE
                vi.id = ?"
    );

    // Prepared query to find a MAC associated with a port
    $macAddressQuery = $db->prepare(
    "SELECT ma.mac        AS mac
        FROM
            macaddress                 AS ma
            LEFT JOIN virtualinterface AS vi ON vi.id = ma.virtualinterfaceid
        WHERE vi.id = ?"
    );
    
    // we'll build up all the member data and store it here:
    $memberdata = array();

    // Now, iterate over all the members and build up the data for export:
    foreach( $members->fetchAll(PDO::FETCH_ASSOC) as $m ) {
        $data = array(
            'id'                   => $m['id'],
            'asnum'                => $m['asnum'],
            'name'                 => $m['name'],
            'url'                  => $m['url'],
            'contact_email'        => array( $m['contact_email'] ),
            'contact_phone'        => array( $m['contact_phone'] ),
            'contact_hours'        => $m['contact_hours'],
            'peering_policy'       => $m['peering_policy'],
            'peering_policy_url'   => $m['peering_policy_url'],
            'member_since'         => date( 'Y-m-d\T00:00:00\Z', strtotime( $m['member_since'] ) ),
            'type'                 => $m['type'] == 1 ? 'full' : ( $m['type'] == 4 ? 'probono' : 'other' ),
            'connection_list'      => array()
        );
        
        // get the customers ports
        $virtualInterfaceQuery->bindValue(1,$data['id']);
        $virtualInterfaceQuery->execute();
        foreach($virtualInterfaceQuery->fetchAll(PDO::FETCH_ASSOC) as $vi) {

            $connection = array(
                "ixp_id"      => 1,
                "state"       => 'active',     // our query below selects only active ports
            );
            
            // get physical port details
            $physicalInterfaceQuery->bindValue(1,$vi['vid']);
            $physicalInterfaceQuery->execute();
            
            foreach($physicalInterfaceQuery->fetchAll(PDO::FETCH_ASSOC) as $pi) {
                $connection['if_list'][] = array(
                    'switch_id'    => $pi['switch_id'],
                    'if_speed'     => $pi['if_speed'],
                );
            }

            // do we have a mac?
            $macAddressQuery->bindValue(1,$vi['vid']);
            $macAddressQuery->execute();
            $mac = array();
            foreach( $macAddressQuery->fetchAll(PDO::FETCH_ASSOC) as $ma ) {
                $amac = strtolower($ma['mac']);
                $mac[] = substr($amac,0,2).':'.substr($amac,2,2).':'.substr($amac,4,2).':'.substr($amac,6,2).':'.substr($amac,8,2).':'.substr($amac,10,2);
            }
            
            // get vlan interface details
            $vlanInterfaceQuery->bindValue(1,$vi['vid']);
            $vlanInterfaceQuery->execute();
            foreach($vlanInterfaceQuery->fetchAll(PDO::FETCH_ASSOC) as $vli) {
                $vlan_list = array(
                    'vlan_id'      => $vli['vlan_id'],
                );
                
                
                if( $vli['v4enabled'] ) {
                    $vlan_list['ipv4'] = array(
                        'address'     => $vli['ipv4address'],
                        'routeserver' => $vli['rsclient'] ? true : false
                    );
                    if($vli['maxprefix']) { $vlan_list['ipv4']['max_prefix'] = $vli['maxprefix']; }
                    if($vli['v4macro']) { $vlan_list['ipv4']['as_macro'] = $vli['v4macro']; }
                    if(count($mac)) { $vlan_list['ipv4']['mac_addresses'] = $mac; }
                }
                
                if( $vli['v6enabled'] ) {
                    $vlan_list['ipv6'] = array(
                        'address'     => $vli['ipv6address'],
                        'routeserver' => $vli['rsclient'] ? true : false
                    );
                    if($vli['maxprefix']) { $vlan_list['ipv6']['max_prefix'] = $vli['maxprefix']; }
                    if($vli['v6macro']) {
                        $vlan_list['ipv6']['as_macro'] = $vli['v6macro'];
                    } else if($vli['v4macro']) {
                        $vlan_list['ipv6']['as_macro'] = $vli['v4macro'];
                    }
                    if(count($mac)) { $vlan_list['ipv4']['mac_addresses'] = $mac; }
                }
                
                $connection['vlan_list'][] = $vlan_list;
            }
            $data['connection_list'][] = $connection;
        }
        
        //we don't need to export the member id - this was just used for queries above:
        unset($data['id']);
        
        $memberdata[] = $data;
    }
    
    return $memberdata;
}
