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
    'usr/bin/remctl-ring-control',
    'usr/bin/remctl-ring-queue-status',
    'usr/bin/remctl-ring-load'
);

for my $s (@script_list) {
    my $out;
    my $cmd = "../$s help";
    $out = `$cmd 2>&1`;
    if (!ok($out =~ /^Usage/, "Help Switch ($s)")) {
        print $out
    }
}
