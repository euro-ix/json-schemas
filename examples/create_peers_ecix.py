#!/usr/bin/env python

import json
import urllib

url = "https://www.ecix.net/memberlist_BER.json"
response = urllib.urlopen(url)
ixp_data = json.loads(response.read())

for member in ixp_data['member_list']:
  for connection in member['connection_list']:
    for vlan in connection['vlan_list']:
      if 'ipv4' in vlan:
        print '-----'
        print 'neighbor '+vlan['ipv4']['address']+' remote-as '+ str(member['asnum'])
        print 'neighbor '+vlan['ipv4']['address']+' description PEER::'+member['name']

