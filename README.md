IX-F Member Export JSON Schema
================================

The IX-F Member Export is an agreed and standardized JSON schema which allows IXPs to make their member lists available for consumption by tools such as PeeringDB, networks with automated peering managers, prospective members and the many other tools appearing in the peering eco-system.

The key element of the IX-F Member Export is that it makes the individual IXP the canonical trusted source for data about their own IXP. Data that is guaranteed to be correct and up to date.


## Location

It is suggested to locate the REST call under a well defined URI on the IXP's website, or use standard HTTP redirects (3xx). We recommend the location: `http://www.example.com/participants.json`.

## Documentation

More detailed documentation is available on the [github wiki page](https://github.com/euro-ix/json-schemas/wiki).

## Directory

In April 2017, we launched a directory for all implemented IX-F member exports. You can find it at: http://ml.ix-f.net/.

## Implementations

**POTENTIALLY STALE - THE ABOVE REFERENCED DIRECTORY WILL BE USED FROM HERE ON IN **


 1. [INEX](https://www.inex.ie/ixp/apiv1/member-list/list )
 1. ECIX ([BER](https://www.ecix.net/memberlist_BER.json), [DUS](https://www.ecix.net/memberlist_DUS.json), [FRA](https://www.ecix.net/memberlist_FRA.json), [HAM](https://www.ecix.net/memberlist_HAM.json), [MUC](https://www.ecix.net/memberlist_MUC.json))
 1. BCIX
 1. NAPAfrica
 1. [AMS-IX](https://my.ams-ix.net/api/v1/members.json)
 1. FL-IX
 1. [LONAP](https://portal.lonap.net/apiv1/member-list/list)
 1. United IX
 1. [SIX](https://www.seattleix.net/autogen/participants.json)
 1. SwissIX
 1. [GR-IX](https://www.gr-ix.gr/participants.json)
 1. [TREX](http://www.trex.fi/memberlist.json)
 1. [SFMIX](http://sfmix.org/participants.json)
 1. [RIX](http://rix.is/participants.json)
 1. [Telx](https://tie.telx.com/stats/members.json)
 1. [CATNIX](http://www.catnix.net/participants.json)
 1. [Megaport](https://lg.megaport.com/megaport.json)
 1. [NIX.CZ](http://www.nix.cz/networks.json)
 1. [NIX.SK](http://www.nix.sk/networks.json)
 1. [Gigapix](http://square.gigapix.pt/participants.json)
 1. FranceIX ([PARIS](https://www.franceix.net/api/members/list/json?location=PAR), [MARSEILLE](https://www.franceix.net/api/members/list/json?location=MRS), [BOTH](https://www.franceix.net/api/members/list/json))
 1. [VIX](https://www.vix.at/participants.json)
 1. [MIX Milan](http://www.mix-it.net/participants.json)
 1. [LINX](https://www.linx.net/members.json)

## Contact

Please send feedback to:

* Barry O'Donovan <barry.odonovan@inex.ie>
* Elisa Jasinska <elisa@bigwaveit.org>
* Nick Hilliard <nick@inex.ie>
