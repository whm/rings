#!/usr/bin/perl -w
#
# tests/ring-db2files.t

use Test::More qw( no_plan );

my $out;
my $s = '../usr/bin/ring-db2files';
my $cmd = "perl -I ../modules/ $s";

$out = `$cmd --help 2>&1`;
if (!ok($out =~ /^Usage/, 'Help Switch')) {
    `$cmd --help`;
}

$out = `$cmd help 2>&1`;
if (!ok($out =~ /^Usage/, 'Help')) {
    `$cmd --help`;
}

my $t = "${s}.tdy";

my @cmd = ('perltidy');
push @cmd, '-bbao';  # put line breaks before any operator
push @cmd, '-nbbc';  # don't force blank lines before comments
push @cmd, '-ce';    # cuddle braces around else
push @cmd, '-l=79';  # usually use 78, but don't want 79-long lines reformatted
push @cmd, '-pt=2';  # don't add extra whitespace around parentheses
push @cmd, '-sbt=2'; # ...or square brackets
push @cmd, '-sfs';   # no space before semicolon in for
push @cmd, $s;
system(@cmd);

@cmd = ('diff', '-u', $s, $t);
system(@cmd) == 0 
    or die "$s is UNTIDY\n";

ok ('success', 'Tidy');
unlink $t;


