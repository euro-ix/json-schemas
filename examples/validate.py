#!/usr/bin/env python

import sys
import argparse
import requests
import jsonschema
import re
import json


def parse_args(args):
    schema_url = ('https://raw.githubusercontent.com'
                  '/euro-ix/json-schemas/master/'
                  'ixp-member-list.schema.json')

    parser = argparse.ArgumentParser(
        description='Validate json output against schema.'
    )
    parser.add_argument(
        '-u',
        '--username',
        default=None,
        help='HTTP Basic Auth User'
    )
    parser.add_argument(
        '-p',
        '--password',
        default=None,
        help='HTTP Basic Auth Password'
    )
    parser.add_argument(
        '-s',
        '--schema',
        default=schema_url,
        help='URL of json schema to validate against'
    )
    parser.add_argument('url', help='URL of json to validate')
    return parser.parse_args(args)


def main(argv=sys.argv[1:]):
    args = parse_args(argv)

    schema_url = args.schema
    response = requests.get(schema_url)
    schema = response.json()

    # Special case for "file://" URL (not handled by "requests")
    match = re.search('^file://(.*)$', args.url)
    if match:
        ixp_data = json.loads(open(match.group(1), 'r').read())
    else:
        if args.username and args.password:
            response = requests.get(
                args.url,
                auth=(
                    args.username,
                    args.password
                )
            )
        else:
            response = requests.get(args.url)

        ixp_data = response.json()

    jsonschema.validate(ixp_data, schema)


if __name__ == '__main__':
    main()
