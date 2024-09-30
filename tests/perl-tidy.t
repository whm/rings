#!/usr/bin/perl -w
#
# tests/perl-tidy.t

use Test::More qw( no_plan );

##############################################################################
# Subroutines
##############################################################################

sub tidy_test {
    my ($s) = @_;
    
    my $t = "${s}.tdy";
    my @cmd = ('perltidy');
    push @cmd, '-bbao';  # put line breaks before any operator
    push @cmd, '-nbbc';  # don't force blank lines before comments
    push @cmd, '-ce';    # cuddle braces around else
    push @cmd, '-l=79';  # flag long lines
    push @cmd, '-pt=2';  # don't add extra whitespace around parentheses
    push @cmd, '-sbt=2'; # ...or square brackets
    push @cmd, '-sfs';   # no space before semicolon in for
    push @cmd, '-boc';   # break on old commas which perserves list formattting
    push @cmd, $s;
    system(@cmd);

    @cmd = ('diff', '-u', $s, $t);
    my $stat = system(@cmd);
    ok($stat == 0, "Tidy $s");
    unlink $t;

    return;
}

##############################################################################
# Main Routine
##############################################################################

# List of perl source files
tidy_test('../modules/Rings/Common.pm');

my @perl_list = ();
my $perl_dir = '../usr/bin';
opendir(my $dh, $perl_dir) || die ("ERROR: $perl_dir missing");
while (readdir $dh) {
    $this_file = $perl_dir . '/' . $_;
    open(my $fh, '<', $this_file) || die ("ERROR: problem reading $this_file");
    while (<$fh>) {
        my $inline =$_;
        if ($inline =~ /\/usr\/bin\/perl/xms) {
            push @perl_list, $this_file;
        }
        last;
    }
    close $fh;
}

for my $s (@perl_list) {
    my $out;
    my $cmd = "perl -I ../modules/ $s";
    $out = `$cmd --help 2>&1`;
    if (!$out) {
        fail("No output from $cmd\n");
    } elsif (!ok($out =~ /^Usage/, "Help Switch ($s)")) {
        print $out
    }

    tidy_test($s);
}
