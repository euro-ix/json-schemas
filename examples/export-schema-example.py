#!/usr/bin/env python

'''
Example script to implement the JSON Export Schema as defined by:
   https://github.com/euro-ix/json-schemas

 Everyone's database schema will be different so this is just an example
 which:

 - Uses IXP Manager's schema as an example
 - Is written as a single PHP script with minimum requirements
 - Is hopefully easy to follow and adapt

 The output is compatible with v0.6 of the schema

'''

import pymysql.cursors
import json
import datetime


def connect_db():
    return pymysql.connect(host='localhost',
                           user='username',
                           password='password',
                           db='ixp',
                           charset='utf8mb4',
                           cursorclass=pymysql.cursors.DictCursor)

def select_db(sql, args):
    connection = connect_db()
    with connection.cursor() as cursor:
        cursor.execute(sql, args)
        return cursor.fetchall()

def vlan_info():
    '''
    Function to get VLAN information.
    This shows a database query but you could just as easily hardcode an array here
    if you're a small IXP
    '''
    list_vlans = []

    for vlan in select_db("SELECT v.id AS id, v.name AS name FROM vlan AS v WHERE v.private=0", ((), )):
        for proto in [4, 6]:
            vlan["ipv" + str(proto)] = select_db("SELECT ni.network AS prefix, ni.masklen AS masklen FROM networkinfo AS ni WHERE ni.vlanid = %s AND ni.protocol = %s", (vlan["id"], proto))[0]

        list_vlans.append(vlan)
    return list_vlans

def switch_info():
    list_switch = select_db("SELECT s.id AS id, s.name AS name FROM switch AS s WHERE s.switchtype = 1 AND s.active = 1", ((), ))
    for switch in list_switch:
        switch["colo"] = "Datacentre Name"
        switch["city"] = "City"
        switch["country"] = "CC"
        switch["pdb_facility_id"] = 0
    return list_switch

def member_info():
    list_member = select_db("""SELECT c.id            AS id,
                c.autsys        AS asnum,
                c.name          AS name,
                c.corpwww       AS url,
                c.peeringemail  AS contact_email,
                c.nocphone      AS contact_phone,
                c.nochours      AS contact_hours,
                c.peeringpolicy AS peering_policy,
                c.nocwww        AS peering_policy_url,
                c.datejoin      AS member_since
                
            FROM
                cust AS c
            WHERE
                c.datejoin <= CURRENT_DATE()
                    AND ( c.dateleave IS NULL OR c.dateleave = '0000-00-00' OR c.dateleave >= CURRENT_DATE() )
                AND c.status = 1
                AND c.type != 2""", ((), ))
    for member in list_member:
        member["contact_email"] = [member["contact_email"]]
        member["contact_phone"] = [member["contact_phone"]]
        member["connection_list"] = []

        # get the customers ports
        for vi in select_db("SELECT vi.id AS vid FROM cust AS c LEFT JOIN virtualinterface  AS vi ON c.id = vi.custid WHERE c.id = %s", (member[id], )):
            connection = {}
            connection["ixp_id"] = 1
            connection["state"] = "active"
            connection['if_list'] = []

            # get physical port details
            for pi in select_db("""SELECT s.id AS switch_id, pi.speed AS if_speed
                                   FROM virtualinterface AS vi
                                   LEFT JOIN physicalinterface AS pi ON vi.id = pi.virtualinterfaceid
                                   LEFT JOIN switchport        AS sp ON pi.switchportid = sp.id
                                   LEFT JOIN switch            AS s  ON s.id = sp.switchid
                                   WHERE vi.id = %s AND pi.status = 1""", (vi['vid'], )):
                connection['if_list'].append({"switch_id": pi['switch_id'], "if_speed": pi["if_speed"]})

            # do we have a mac?
            list_mac = []
            for ma in select_db("SELECT ma.mac AS mac FROM macaddress AS ma LEFT JOIN virtualinterface AS vi ON vi.id = ma.virtualinterfaceid WHERE vi.id = %s", (vi['vid'], )):
                list_mac.append(ma["ma"][0-1] + ":" + ma["ma"][2-3] + ":" + ma["ma"][4-5] + ":" + ma["ma"][6-7])

            # get vlan interface details
            for vli in select_db("""SELECT v.id                    AS vlan_id,
                ip4.address             AS ipv4address,
                ip6.address             AS ipv6address,
                vli.ipv4enabled         AS v4enabled,
                vli.ipv6enabled         AS v6enabled,
                vli.maxbgpprefix        AS maxprefix,
                c.peeringmacro          AS v4macro,
                c.peeringmacrov6        AS v6macro,
                vli.rsclient            AS rsclient
                FROM virtualinterface   AS vi
                LEFT JOIN cust              AS c   ON vi.custid = c.id
                LEFT JOIN vlaninterface     AS vli ON vli.virtualinterfaceid = vi.id
                LEFT JOIN vlan              AS v   ON vli.vlanid = v.id
                LEFT JOIN ipv4address       AS ip4 ON vli.ipv4addressid = ip4.id
                LEFT JOIN ipv6address       AS ip6 ON vli.ipv6addressid = ip6.id
                WHERE vi.id = %s """, (vi['vid'], )):

                vlan_list = {}
                vlan_list["vlan_id"] = vli['vlan_id']
                if vli['v4enabled']:
                    vlan_list['ipv4'] = {"address": vli['ipv4address'], "routeserver": vli['rsclient']}
                    vlan_list['ipv4']['max_prefix'] = vli['maxprefix']
                    vlan_list['ipv4']['as_macro'] = vli['v4macro']
                    if len(list_mac) > 0:
                        vlan_list['ipv4']['mac_addresses'] = list_mac

                if vli['v6enabled']:
                    vlan_list['ipv6'] = {"address": vli['ipv6address'], "routeserver": vli['rsclient']}
                    vlan_list['ipv6']['max_prefix'] = vli['maxprefix']
                    if vli['v6macro']:
                        vlan_list['ipv6']['as_macro'] = vli['v6macro']
                    else:
                        vlan_list['ipv6']['as_macro'] = vli['v4macro']
                    if len(list_mac) > 0:
                        vlan_list['ipv6']['mac_addresses'] = list_mac

                connection["vlan_list"] = vlan_list


            member["connection_list"].append(connection)
    return list_member


def make_dict_ixp_info():
    '''
    The schema is made up of different sections so we build those up as an
    array piece by piece. Edit the functions we call here for your own
    data / schemas
    '''
    my_ixp = {}
    my_ixp["version"] = "0.6"
    nowdate = datetime.datetime.now()
    my_ixp["timestamp"] = nowdate.isoformat('T')
    my_ixp["ixp_list"] = []
    my_ixp["ixp_list"].append({
        "shortname": "EIX",
        "name": "Example Internet Exchange",
        "country": "CC",
        "url": "http://www.example.com",

        # IX-F ID is the numeric index of the IXP in the IX Federation database.
        # This is defined as the id of the IXP in the online IX-F database which
        # can be found at the following endpoint: http://db.ix-f.net/api/ixp.
        # Note that this is a persistent index keyed on an external database source.
        "ixf_id": 0,

        # ixp_id - a numeric IXP identifier. Where possible, this index number
        # should be consistent across exports. However as the index is primarily
        # intended to be used as an internal index within the schema, it is not
        # required to be consistent across exports.
        "ixp_id": 1,

        "vlan": vlan_info(),
        "switch": switch_info(),
        "member_list": member_info()
        })



def main():
    print json.dumps(make_dict_ixp_info)

if __name__ == '__main__':
    main()
