#!/usr/bin/perl -w
#
# tests/dependencies.t
#
#  Test that all the Perl modules we require are available.
#

use Test::More qw( no_plan );

use_ok('AppConfig');
use_ok('Carp');
use_ok('DBI');
use_ok('File::Copy');
use_ok('Getopt::Long');
use_ok('IPC::Run');
use_ok('Pod::Usage');
