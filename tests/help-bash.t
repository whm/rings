#!/usr/bin/perl -w
#
# tests/bash-help.t

use Test::More qw( no_plan );

##############################################################################
# Main Routine
##############################################################################

# List of bash scripts
@script_list = (
    'usr/bin/cz-ring-token',
    'usr/bin/ring-control',
    'usr/bin/ring-status',
);

for my $s (@script_list) {
    my $out;
    my $cmd = "../$s help";
    $out = `$cmd 2>&1`;
    if (!ok($out =~ /^Usage/, "bash Help Switch ($s)")) {
        print $out
    }
}
