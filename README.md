IX-F Member Export JSON Schema
================================

The IX-F Member Export is an agreed and standardized JSON schema which allows IXPs to make their member lists available for consumption by tools such as PeeringDB, networks with automated peering managers, prospective members and the many other tools appearing in the peering eco-system.

The key element of the IX-F Member Export is that it makes the individual IXP the canonical trusted source for data about their own IXP. Data that is guaranteed to be correct and up to date.


## Location

It is suggested to locate the REST call under a well defined URI on the IXP's website, or use standard HTTP redirects (3xx). We recommend the location: `http://www.example.com/participants.json

## Documentation

More detailed documentation is available on the [github wiki page](https://github.com/euro-ix/json-schemas/wiki).

## Directory / Implementations

In April 2017, we launched a directory for all implemented IX-F member exports and in April 2018 this was moved to the IXPDB website and you can find the list of IXPs exporting their member lists by selecting the API option in the search: https://ixpdb.euro-ix.net/en/ixpdb/providers/

### Stale / Potentially Broken Implementations 

**POTENTIALLY STALE/BROKEN - THE ABOVE REFERENCED DIRECTORY WILL BE USED FROM HERE ON IN**

The following have/had implementations but have not yet added themselves to the directory above.

NAPAfrica, FL-IX, United IX, [SFMIX](http://sfmix.org/participants.json), [RIX](http://rix.is/participants.json), , FranceIX ([PARIS](https://www.franceix.net/api/members/list/json?location=PAR), [MARSEILLE](https://www.franceix.net/api/members/list/json?location=MRS), [BOTH](https://www.franceix.net/api/members/list/json)).

## Contact

Please send feedback to:

* Barry O'Donovan <barry.odonovan@inex.ie>
* Elisa Jasinska <elisa@bigwaveit.org>
* Nick Hilliard <nick@inex.ie>
* Bijal Sanghani <bijal@euro-ix.net>
