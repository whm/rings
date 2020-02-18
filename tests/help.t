#!/usr/bin/perl -w
#
# tests/help.t

use Test::More qw( no_plan );

##############################################################################
# Main Routine
##############################################################################

# List of perl source files
@script_list = ('usr/sbin/remctl-ring-control',
                'usr/sbin/remctl-ring-queue-status',
                'usr/sbin/remctl-ring-load',
);

for my $s (@script_list) {
    my $out;
    my $cmd = "../$s help";
    $out = `$cmd 2>&1`;
    if (!ok($out =~ /^Usage/, "Help Switch ($s)")) {
        print $out
    }
}
