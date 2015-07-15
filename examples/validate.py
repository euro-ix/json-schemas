#!/usr/bin/env python

import json
import jsonschema
import sys
import urllib

if len(sys.argv) != 2:
    raise Exception('usage: validate.py <url>')

url = sys.argv[1]
schema_url = "https://raw.githubusercontent.com/euro-ix/json-schemas/master/ixp-member-list.schema.json"

schema = json.loads(urllib.urlopen(schema_url).read())
ixp_data = json.loads(urllib.urlopen(url).read())

jsonschema.validate(schema, ixp_data)

