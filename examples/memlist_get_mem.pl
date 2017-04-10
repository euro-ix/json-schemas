#!/usr/bin/perl

use strict;
use warnings;
use feature qw(:5.10);

use JSON;
use FindBin;

if (@ARGV < 2) {
    die "Usage: $FindBin::Script json-file mem-index ...\n";
}

my $json_fname = shift @ARGV;

open my $fh, '<', $json_fname
    or die "$FindBin::Script: cannot read $json_fname: $!\n";

my $json_text = do {
    local($/) = undef;
    <$fh>;
};

$fh->close;
my $json = JSON->new->allow_nonref;
my $ixp_data = $json->decode($json_text);

my $members = $ixp_data->{'member_list'};

for my $index (@ARGV) {
    my $mem = $members->[$index];
        
    if (!$mem) {
        say "// $index - out of bounds\n";
    }

    my $json_out = $json->pretty->encode($mem);
    say "// $index\n$json_out";
}

__END__

=head1 NAME

memlist_get_mem.pl - extract information about a member from an IX-F JSON export

=head1 SYNOPSIS

    curl JSON-URL > members.json

    memlist_get_mem.pl members.json index1 index2 ...

=head1 DESCRIPTION

When using the L<IX-F JSON validator|http://ml.ix-f.net/test>, the
validator may sometimes spit out errors regarding a member in the list,
indicated only by its index in the list. To help identify the entry,
this tool can be used.

=head1 EXAMPLE

=over

=item 1.

The L<IX-F JSON validator|http://ml.ix-f.net/test> reports an error:

    member_list[761].url: Invalid URL format

=item 2.

Download a copy of the member list to F<members.json>

=item 3.

Use this script to extract information about the member:

    memlist_get_mem.pl members.json 761 | grep url

Output will be something like:

    "url" : "http:// www.foobar.com",
      #             ^
      #             |
      #     misplaced space

=back

=head1 SEE ALSO

=over

=item *

L<Euro-IX JSON on Github|https://github.com/euro-ix/json-schemas/wiki>

=item *

L<IX-F Member List Directory|http://ml.ix-f.net/>

=item *

L<IX-F JSON validator|http://ml.ix-f.net/test>

=back

=head1 AUTHOR

Steven Bakker, AMS-IX - 2017.
